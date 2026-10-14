<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
if (!$user) { header('Location: ./'); exit; }
// Hanya atasan yang boleh akses
if ($user['role_name'] !== 'atasan') {
  switch ($user['role_name']) {
    case 'admin_utama': header('Location: dashboard_admin.php'); break;
    case 'admin_stok': header('Location: dashboard_stock.php'); break;
    default: header('Location: dashboard_user.php'); break;
  }
  exit;
}

$conn = get_db_connection(); // PDO
$view = isset($_GET['view']) ? $_GET['view'] : 'pemasukan';

// Notifikasi sederhana untuk atasan: jumlah pengajuan dan reimburse pending
$pendingReqCount = 0; $pendingReimbCount = 0; $notifTotal = 0;
try {
    $stmt = $conn->query("SELECT COUNT(*) AS c FROM requests WHERE status = 'pending'");
    $pendingReqCount = (int) $stmt->fetchColumn();
    $stmt = $conn->query("SELECT COUNT(*) AS c FROM reimbursements WHERE status = 'pending'");
    $pendingReimbCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    // log if needed
}
$notifTotal = $pendingReqCount + $pendingReimbCount;

// Filter periode
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';

// Helper: build date WHERE clause safely
function build_date_where(array &$params, string $field, string $from, string $to): string {
    $where = '';
    if ($from && $to) {
        $where = " AND $field BETWEEN :from_date AND :to_date";
        $params[':from_date'] = $from . ' 00:00:00';
        $params[':to_date'] = $to . ' 23:59:59';
    } elseif ($from) {
        $where = " AND $field >= :from_date";
        $params[':from_date'] = $from . ' 00:00:00';
    } elseif ($to) {
        $where = " AND $field <= :to_date";
        $params[':to_date'] = $to . ' 23:59:59';
    }
    return $where;
}

// Export CSV jika diminta (gunakan prepared statements + streaming)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  $type = ($view === 'pengeluaran') ? 'out' : 'in';
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . ($view==='pengeluaran'?'pengeluaran':'pemasukan') . '_stok.csv"');
  $out = fopen('php://output', 'w');
  fputcsv($out, ['Tanggal','Barang','Qty','Sumber']);

  $params = [':type' => $type];
  $dateWhere = build_date_where($params, 'sm.created_at', $from, $to);
  $sql = "SELECT sm.created_at, i.name AS iname, sm.qty, sm.source FROM stock_movements sm JOIN items i ON i.id = sm.item_id WHERE sm.type = :type" . $dateWhere . " ORDER BY sm.id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
      fputcsv($out, [($r['created_at'] ?? ''), $r['iname'], (int)$r['qty'], ($r['source'] ?? '-')]);
  }
  fclose($out);
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard Atasan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="assets/styles.css" rel="stylesheet" />
  <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
</head>
<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  
  <nav class="navbar navbar-expand-lg app-navbar">
    <div class="container-fluid">
      <button class="navbar-toggler-btn me-2" id="sidebarToggle">
        <i class="fa-solid fa-bars"></i>
      </button>
      
      <div class="ms-auto d-flex align-items-center gap-3">
        <div class="notif-wrap">
          <a href="#" class="notif-bell text-decoration-none" data-notif-trigger>
            <i class="fa-solid fa-bell"></i>
            <?php if ($notifTotal>0){ ?><span class="badge rounded-pill bg-danger" style="font-size: 0.6rem; position: absolute; top: 0; right: 0;"><?php echo $notifTotal; ?></span><?php } ?>
          </a>
          <div class="notif-panel shadow-lg">
            <div class="notif-header">Notifikasi</div>
            <div class="notif-body">
              <div class="notif-item"><span>Pengajuan Pending</span><span class="badge bg-warning text-dark"><?php echo $pendingReqCount; ?></span></div>
              <div class="notif-item"><span>Reimburse Pending</span><span class="badge bg-warning text-dark"><?php echo $pendingReimbCount; ?></span></div>
              <div class="mt-3 d-grid gap-2">
                <a href="dashboard_boss.php?view=riwayat_pengajuan" class="btn btn-sm btn-primary">Lihat Aktivitas</a>
              </div>
            </div>
          </div>
        </div>
        <div class="dropdown">
          <a class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
              <i class="fa-solid fa-user-tie"></i>
            </div>
            <span class="d-none d-sm-inline fw-medium text-secondary"><?php echo htmlspecialchars($user['name']); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="handlers/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <div class="layout-with-sidebar">
    <aside class="app-sidebar" id="appSidebar">
      <div class="sidebar-header">
        <div class="brand">
          <img src="img/logo.png" alt="Logo">
          <span>PT NHM</span>
        </div>
      </div>
      <div class="sidebar-nav">
        <a href="dashboard_boss.php?view=pemasukan" class="nav-link <?php echo $view==='pemasukan'?'active':''; ?>"><i class="fa-solid fa-arrow-down"></i> Pemasukan</a>
        <a href="dashboard_boss.php?view=pengeluaran" class="nav-link <?php echo $view==='pengeluaran'?'active':''; ?>"><i class="fa-solid fa-arrow-up"></i> Pengeluaran</a>
        <a href="dashboard_boss.php?view=riwayat_pembelian" class="nav-link <?php echo $view==='riwayat_pembelian'?'active':''; ?>"><i class="fa-solid fa-receipt"></i> Pembelian</a>
        <a href="dashboard_boss.php?view=riwayat_pengajuan" class="nav-link <?php echo $view==='riwayat_pengajuan'?'active':''; ?>"><i class="fa-solid fa-list"></i> Pengajuan</a>
      </div>
      <div class="p-3 mt-auto border-top border-light border-opacity-10">
        <small class="text-white text-opacity-50">Logged in as Management</small>
      </div>
    </aside>

    <main class="main-content p-4">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-12">
            <h1 class="h3 fw-bold mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i> Dashboard Atasan</h1>
            <p class="text-muted">Analisis data ketersediaan dan mutasi aset.</p>
          </div>
        </div>

        <?php if ($view==='pemasukan'): ?>
        <section class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3"><i class="fa-solid fa-arrow-down"></i> Dashboard Pemasukan Barang</h2>
            <form class="row g-2 align-items-end mb-3" method="get" action="dashboard_boss.php">
              <input type="hidden" name="view" value="pemasukan">
              <div class="col-sm-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from); ?>">
              </div>
              <div class="col-sm-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to); ?>">
              </div>
              <div class="col-sm-3">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Terapkan</button>
                <a class="btn btn-outline-secondary" href="dashboard_boss.php?view=pemasukan&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=csv"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
              </div>
            </form>
            <?php
              $params = [':type' => 'in'];
              $dateWhere = build_date_where($params, 'sm.created_at', $from, $to);
              $sqlTop = "SELECT i.name AS name, SUM(sm.qty) AS total FROM stock_movements sm JOIN items i ON i.id = sm.item_id WHERE sm.type = :type" . $dateWhere . " GROUP BY sm.item_id ORDER BY total DESC LIMIT 10";
              try {
                $stmt = $conn->prepare($sqlTop);
                $stmt->execute($params);
                $insTopRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $labels = []; $data = [];
                foreach ($insTopRows as $r) { $labels[] = $r['name']; $data[] = (int)$r['total']; }
              } catch (PDOException $e) { $labels = []; $data = []; }
            ?>
            <canvas id="inChart" height="140"></canvas>
            <h3 class="h6 mt-4">Pemasukan Terbaru</h3>
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead><tr><th>Tanggal</th><th>Barang</th><th>Qty</th><th>Sumber</th></tr></thead>
                <tbody>
                <?php
                  $params = [':type' => 'in'];
                  $dateWhere = build_date_where($params, 'sm.created_at', $from, $to);
                  $sqlRecent = "SELECT sm.created_at, i.name AS iname, sm.qty, sm.source FROM stock_movements sm JOIN items i ON i.id = sm.item_id WHERE sm.type = :type" . $dateWhere . " ORDER BY sm.id DESC LIMIT 20";
                  try {
                      $stmt = $conn->prepare($sqlRecent);
                      $stmt->execute($params);
                      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['iname']); ?></td>
                    <td><?php echo (int)$row['qty']; ?></td>
                    <td><?php echo htmlspecialchars($row['source'] ?? '-'); ?></td>
                  </tr>
                <?php }
                  } catch (PDOException $e) { /* ignore */ }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($view==='pengeluaran'): ?>
        <section class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3"><i class="fa-solid fa-arrow-up"></i> Dashboard Pengeluaran Barang</h2>
            <form class="row g-2 align-items-end mb-3" method="get" action="dashboard_boss.php">
              <input type="hidden" name="view" value="pengeluaran">
              <div class="col-sm-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars($from); ?>">
              </div>
              <div class="col-sm-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars($to); ?>">
              </div>
              <div class="col-sm-3">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Terapkan</button>
                <a class="btn btn-outline-secondary" href="dashboard_boss.php?view=pengeluaran&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=csv"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
              </div>
            </form>
            <?php
              $params = [':type' => 'out'];
              $dateWhere = build_date_where($params, 'sm.created_at', $from, $to);
              $sqlTop = "SELECT i.name AS name, SUM(sm.qty) AS total FROM stock_movements sm JOIN items i ON i.id = sm.item_id WHERE sm.type = :type" . $dateWhere . " GROUP BY sm.item_id ORDER BY total DESC LIMIT 10";
              try {
                $stmt = $conn->prepare($sqlTop);
                $stmt->execute($params);
                $outTopRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $labelsOut = []; $dataOut = [];
                foreach ($outTopRows as $r) { $labelsOut[] = $r['name']; $dataOut[] = (int)$r['total']; }
              } catch (PDOException $e) { $labelsOut = []; $dataOut = []; }
            ?>
            <canvas id="outChart" height="140"></canvas>
            <h3 class="h6 mt-4">Pengeluaran Terbaru</h3>
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead><tr><th>Tanggal</th><th>Barang</th><th>Qty</th><th>Sumber</th></tr></thead>
                <tbody>
                <?php
                  $params = [':type' => 'out'];
                  $dateWhere = build_date_where($params, 'sm.created_at', $from, $to);
                  $sqlRecent = "SELECT sm.created_at, i.name AS iname, sm.qty, sm.source FROM stock_movements sm JOIN items i ON i.id = sm.item_id WHERE sm.type = :type" . $dateWhere . " ORDER BY sm.id DESC LIMIT 20";
                  try {
                      $stmt = $conn->prepare($sqlRecent);
                      $stmt->execute($params);
                      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['created_at'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['iname']); ?></td>
                    <td><?php echo (int)$row['qty']; ?></td>
                    <td><?php echo htmlspecialchars($row['source'] ?? '-'); ?></td>
                  </tr>
                <?php }
                  } catch (PDOException $e) { /* ignore */ }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($view==='riwayat_pembelian'): ?>
        <section class="card shadow-sm mt-3">
          <div class="card-body">
            <h2 class="h5 mb-3"><i class="fa-solid fa-receipt"></i> Riwayat Pembelian Barang</h2>
            <p class="text-muted small mb-2">Menampilkan 30 transaksi pembelian terakhir.</p>
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead>
                  <tr>
                    <th>ID Pembelian</th>
                    <th>Tanggal</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Supplier</th>
                    <th>Jumlah Dibeli</th>
                    <th>Harga Satuan</th>
                    <th>Total Harga</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th>Catatan</th>
                    <th>Nota</th>
                  </tr>
                </thead>
                <tbody>
                <?php
                  try {
                    $sql = "SELECT p.purchase_code, p.purchase_date, i.sku, i.name AS item_name, s.name AS supplier_name, p.qty, p.unit_price, p.price_total, p.payment_method, p.status, p.note_text, p.note_file FROM purchases p JOIN items i ON i.id = p.item_id JOIN suppliers s ON s.id = p.supplier_id ORDER BY p.id DESC LIMIT 30";
                    $stmt = $conn->query($sql);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['purchase_code'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['purchase_date'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['sku'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><?php echo (int)$row['qty']; ?></td>
                    <td>Rp <?php echo number_format((float)($row['unit_price'] ?? 0), 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format((float)($row['price_total'] ?? 0), 0, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($row['payment_method'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['status'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($row['note_text'] ?? '-'); ?></td>
                    <td>
                      <?php if (!empty($row['note_file'])): ?>
                        <a href="../<?php echo htmlspecialchars($row['note_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file"></i> Lihat</a>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php }
                  } catch (PDOException $e) { /* ignore */ }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($view==='riwayat_pengajuan'): ?>
        <section class="card shadow-sm mt-3">
          <div class="card-body">
            <h2 class="h5 mb-3"><i class="fa-solid fa-list"></i> Riwayat Pengajuan Barang</h2>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead><tr><th>Tanggal</th><th>User</th><th>Barang</th><th>Qty</th><th>Departemen</th><th>Status</th><th>Bukti</th></tr></thead>
                <tbody>
                <?php
                  try {
                    $sql = "SELECT r.created_at, r.request_date, r.department, r.photo_file, u.name AS uname, i.name AS iname, r.qty, r.status FROM requests r JOIN users u ON u.id = r.user_id JOIN items i ON i.id = r.item_id ORDER BY r.id DESC";
                    $stmt = $conn->query($sql);
                    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['request_date'] ?: ($r['created_at'] ?? '')); ?></td>
                    <td><?php echo htmlspecialchars($r['uname']); ?></td>
                    <td><?php echo htmlspecialchars($r['iname']); ?></td>
                    <td><?php echo (int)$r['qty']; ?></td>
                    <td><?php echo htmlspecialchars($r['department'] ?? ''); ?></td>
                    <td>
                      <?php if ($r['status']==='approved'): ?>
                        <span class="badge bg-success"><i class="fa-solid fa-check"></i> Disetujui</span>
                      <?php elseif ($r['status']==='rejected'): ?>
                        <span class="badge bg-secondary"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half"></i> Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!empty($r['photo_file'])): ?>
                        <a href="../<?php echo htmlspecialchars($r['photo_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-image"></i> Lihat</a>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php }
                  } catch (PDOException $e) { /* ignore */ }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>
        <?php endif; ?>
      </main>
    </div>
  </div>
  <footer class="text-center text-muted small py-4">© <?php echo date('Y'); ?> PT NHM · Asset Management System</footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="assets/main.js"></script>
  <?php if ($view==='pemasukan'): ?>
  <script>
    const inCtx = document.getElementById('inChart');
    new Chart(inCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{ label: 'Top Pemasukan per Barang', data: <?php echo json_encode($data); ?> }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
  </script>
  <?php endif; ?>
  <?php if ($view==='pengeluaran'): ?>
  <script>
    const outCtx = document.getElementById('outChart');
    new Chart(outCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($labelsOut, JSON_UNESCAPED_UNICODE); ?>,
        datasets: [{ label: 'Top Pengeluaran per Barang', data: <?php echo json_encode($dataOut); ?> }]
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
  </script>
  <?php endif; ?>
</body>
</html>
