<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
if (!$user || $user['role_name'] !== 'admin_utama') { 
    header('Location: ./index.php'); 
    exit; 
}

$conn = get_db_connection();
$month = trim($_GET['month'] ?? '');
$scope = trim($_GET['scope'] ?? 'all');

if (!preg_match('/^\d{4}-\d{2}$/', $month)) { 
    $month = date('Y-m'); 
}
$dt = DateTime::createFromFormat('Y-m-d', $month . '-01');
$start = $dt->format('Y-m-01 00:00:00');
$end = $dt->format('Y-m-t 23:59:59');

// Perusahaan
$compName = 'PT NHM';
try {
    $qComp = $conn->query("SELECT name FROM company_settings WHERE id=1");
    if ($qComp) {
        $compData = $qComp->fetch(PDO::FETCH_ASSOC);
        if ($compData) { 
            $compName = $compData['name']; 
        }
    }
} catch (PDOException $e) {
    // Tetap gunakan default name jika error
}

// Data stok - DIPERBAIKI
try {
    // Stok Masuk
    $inQuery = "
        SELECT i.id, i.name AS item, SUM(sm.qty) AS total 
        FROM stock_movements sm 
        JOIN items i ON i.id = sm.item_id 
        WHERE sm.type = 'in' 
        AND sm.created_at BETWEEN :start AND :end 
        GROUP BY i.id, i.name 
        ORDER BY total DESC
    ";
    $stmt = $conn->prepare($inQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $inItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stok Keluar
    $outQuery = "
        SELECT i.id, i.name AS item, SUM(sm.qty) AS total 
        FROM stock_movements sm 
        JOIN items i ON i.id = sm.item_id 
        WHERE sm.type = 'out' 
        AND sm.created_at BETWEEN :start AND :end 
        GROUP BY i.id, i.name 
        ORDER BY total DESC
    ";
    $stmt = $conn->prepare($outQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $outItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total Stok Masuk
    $inSumQuery = "SELECT SUM(qty) as s FROM stock_movements WHERE type='in' AND created_at BETWEEN :start AND :end";
    $stmt = $conn->prepare($inSumQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $inSumData = $stmt->fetch(PDO::FETCH_ASSOC);
    $inSum = (int)($inSumData['s'] ?? 0);

    // Total Stok Keluar
    $outSumQuery = "SELECT SUM(qty) as s FROM stock_movements WHERE type='out' AND created_at BETWEEN :start AND :end";
    $stmt = $conn->prepare($outSumQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $outSumData = $stmt->fetch(PDO::FETCH_ASSOC);
    $outSum = (int)($outSumData['s'] ?? 0);

} catch (PDOException $e) {
    $inItems = [];
    $outItems = [];
    $inSum = 0;
    $outSum = 0;
}

// Data pembelian - DIPERBAIKI
try {
    $purchaseQuery = "
        SELECT id, COALESCE(purchase_date, created_at) AS tgl, price_total 
        FROM purchases 
        WHERE COALESCE(purchase_date, created_at) BETWEEN :start AND :end 
        ORDER BY id DESC
    ";
    $stmt = $conn->prepare($purchaseQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $purchaseSumQuery = "SELECT SUM(price_total) as s FROM purchases WHERE COALESCE(purchase_date, created_at) BETWEEN :start AND :end";
    $stmt = $conn->prepare($purchaseSumQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $purchaseSumData = $stmt->fetch(PDO::FETCH_ASSOC);
    $purchaseSum = (float)($purchaseSumData['s'] ?? 0);

} catch (PDOException $e) {
    $purchases = [];
    $purchaseSum = 0;
}

// Data reimburse - DIPERBAIKI
try {
    $reimQuery = "
        SELECT id, created_at AS tgl, description, amount 
        FROM reimbursements 
        WHERE created_at BETWEEN :start AND :end 
        ORDER BY id DESC
    ";
    $stmt = $conn->prepare($reimQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $reims = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reimSumQuery = "SELECT SUM(amount) as s FROM reimbursements WHERE created_at BETWEEN :start AND :end";
    $stmt = $conn->prepare($reimSumQuery);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $reimSumData = $stmt->fetch(PDO::FETCH_ASSOC);
    $reimSum = (float)($reimSumData['s'] ?? 0);

} catch (PDOException $e) {
    $reims = [];
    $reimSum = 0;
}

$grandOut = $purchaseSum + $reimSum;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Laporan Bulanan - <?php echo htmlspecialchars($month); ?></title>
  <style>
    :root { --brand: #0f6efd; --text: #222; --muted: #666; --border: #dcdcdc; }
    body { font-family: Arial, sans-serif; color: var(--text); }
    .container { width: 980px; margin: 0 auto; }
    .header { display: flex; align-items: center; gap: 12px; margin: 12px 0 8px; }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand img { height: 42px; width: auto; }
    .brand h1 { margin: 0; font-size: 22px; color: var(--brand); letter-spacing: .2px; }
    .meta { color: var(--muted); margin-bottom: 18px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    th, td { border: 1px solid var(--border); padding: 8px 10px; font-size: 12px; }
    th { background: #f7f9fc; color: #333; }
    .section-title { margin: 18px 0 8px; font-weight: bold; color: #333; border-left: 3px solid var(--brand); padding-left: 8px; }
    .summary { margin-top: 8px; font-size: 13px; color: #333; }
    .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 12px 0 18px; }
    .card { border: 1px solid var(--border); border-radius: 8px; padding: 10px 12px; background: #fff; }
    .card .label { font-size: 12px; color: var(--muted); }
    .card .value { font-size: 16px; font-weight: bold; color: #000; }
    @media print { .no-print { display: none; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="brand">
        <?php $logoPath = __DIR__ . '/img/logo.png'; if (file_exists($logoPath)) { ?>
          <img src="img/logo.png" alt="Logo" />
        <?php } ?>
        <h1><?php echo htmlspecialchars($compName); ?></h1>
      </div>
      <div class="meta">Laporan Bulanan · Periode: <?php echo htmlspecialchars($month); ?></div>
    </div>

    <?php // Ringkasan kartu jika scope all
    if ($scope==='all') { ?>
      <div class="grid">
        <div class="card"><div class="label">Total Pemasukan Stok</div><div class="value"><?php echo (int)$inSum; ?> unit</div></div>
        <div class="card"><div class="label">Total Pengeluaran Stok</div><div class="value"><?php echo (int)$outSum; ?> unit</div></div>
        <div class="card"><div class="label">Grand Total Pengeluaran</div><div class="value">Rp <?php echo number_format($grandOut, 0, ',', '.'); ?></div></div>
      </div>
    <?php } ?>

    <?php if ($scope==='all' || $scope==='stock'): ?>
      <div class="section-title">Mutasi Stok (Pemasukan)</div>
      <table>
        <thead><tr><th>Barang</th><th>Total Qty</th></tr></thead>
        <tbody>
          <?php if (count($inItems) > 0) { 
            foreach($inItems as $row){ ?>
            <tr><td><?php echo htmlspecialchars($row['item'] ?? ''); ?></td><td><?php echo (int)($row['total'] ?? 0); ?></td></tr>
          <?php } } else { ?><tr><td colspan="2">Tidak ada pemasukan stok bulan ini.</td></tr><?php } ?>
        </tbody>
      </table>
      <div class="summary">Total pemasukan stok: <?php echo (int)$inSum; ?> unit</div>

      <div class="section-title">Mutasi Stok (Pengeluaran)</div>
      <table>
        <thead><tr><th>Barang</th><th>Total Qty</th></tr></thead>
        <tbody>
          <?php if (count($outItems) > 0) { 
            foreach($outItems as $row){ ?>
            <tr><td><?php echo htmlspecialchars($row['item'] ?? ''); ?></td><td><?php echo (int)($row['total'] ?? 0); ?></td></tr>
          <?php } } else { ?><tr><td colspan="2">Tidak ada pengeluaran stok bulan ini.</td></tr><?php } ?>
        </tbody>
      </table>
      <div class="summary">Total pengeluaran stok: <?php echo (int)$outSum; ?> unit</div>
    <?php endif; ?>

    <?php if ($scope==='all' || $scope==='purchase'): ?>
      <div class="section-title">Pembelian</div>
      <table>
        <thead><tr><th>ID</th><th>Tanggal</th><th>Total Harga</th></tr></thead>
        <tbody>
          <?php if (count($purchases) > 0) { 
            foreach($purchases as $p){ ?>
            <tr>
                <td><?php echo (int)($p['id'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars($p['tgl'] ?? ''); ?></td>
                <td>Rp <?php echo number_format((float)($p['price_total'] ?? 0), 0, ',', '.'); ?></td>
            </tr>
          <?php } } else { ?><tr><td colspan="3">Tidak ada pembelian bulan ini.</td></tr><?php } ?>
        </tbody>
      </table>
      <div class="summary">Total pembelian bulan ini: Rp <?php echo number_format($purchaseSum, 0, ',', '.'); ?></div>
    <?php endif; ?>

    <?php if ($scope==='all' || $scope==='reimburse'): ?>
      <div class="section-title">Reimbursement</div>
      <table>
        <thead><tr><th>ID</th><th>Tanggal</th><th>Deskripsi</th><th>Total</th></tr></thead>
        <tbody>
          <?php if (count($reims) > 0) { 
            foreach($reims as $r){ ?>
            <tr>
                <td><?php echo (int)($r['id'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars($r['tgl'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                <td>Rp <?php echo number_format((float)($r['amount'] ?? 0), 0, ',', '.'); ?></td>
            </tr>
          <?php } } else { ?><tr><td colspan="4">Tidak ada reimbursement bulan ini.</td></tr><?php } ?>
        </tbody>
      </table>
      <div class="summary">Total reimbursement bulan ini: Rp <?php echo number_format($reimSum, 0, ',', '.'); ?></div>
    <?php endif; ?>

    <?php if ($scope==='all'): ?>
      <div class="section-title">Ringkasan Pengeluaran</div>
      <table>
        <thead><tr><th>Komponen</th><th>Total</th></tr></thead>
        <tbody>
          <tr><td>Pembelian</td><td>Rp <?php echo number_format($purchaseSum, 0, ',', '.'); ?></td></tr>
          <tr><td>Reimbursement</td><td>Rp <?php echo number_format($reimSum, 0, ',', '.'); ?></td></tr>
          <tr><td><strong>Grand Total Pengeluaran</strong></td><td><strong>Rp <?php echo number_format($grandOut, 0, ',', '.'); ?></strong></td></tr>
        </tbody>
      </table>
    <?php endif; ?>

    <div class="no-print" style="margin-top:16px; display:flex; gap:8px;">
      <!-- Tombol opsional bisa ditambahkan di sini -->
    </div>
  </div>
  <script>
    // Otomatis buka dialog print
    window.addEventListener('load', function(){ 
        setTimeout(function(){ 
            window.print(); 
        }, 300); 
    });
  </script>
</body>
</html>
