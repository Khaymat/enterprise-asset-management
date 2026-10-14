<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_login();
$user = current_user();
if (!$user) { header('Location: ./'); exit; }
// Jaga agar hanya admin_utama yang bisa akses halaman ini
if ($user['role_name'] !== 'admin_utama') {
  switch ($user['role_name']) {
    case 'admin_stok': header('Location: dashboard_stock.php'); break;
    case 'atasan': header('Location: dashboard_boss.php'); break;
    default: header('Location: dashboard_user.php'); break;
  }
  exit;
}
$conn = get_db_connection();
$view = isset($_GET['view']) ? $_GET['view'] : 'pengajuan';
// Notifikasi jumlah pending
$pendingReqCount = 0; $pendingReimbCount = 0; $pendingTakeCount = 0; 
if ($res=$conn->query("SELECT COUNT(*) c FROM requests WHERE status='pending'")) { $row=$res->fetch(); $pendingReqCount=(int)$row['c']; }
if ($res=$conn->query("SELECT COUNT(*) c FROM reimbursements WHERE status='pending'")) { $row=$res->fetch(); $pendingReimbCount=(int)$row['c']; }
if ($res=$conn->query("SELECT COUNT(*) c FROM item_takings WHERE status='pending'")) { $row=$res->fetch(); $pendingTakeCount=(int)$row['c']; }
$notifTotal = $pendingReqCount + $pendingReimbCount + $pendingTakeCount;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard Admin Utama</title>
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
              <div class="notif-item">
                <span>Pengajuan</span>
                <span class="badge bg-warning text-dark"><?php echo $pendingReqCount; ?></span>
              </div>
              <div class="notif-item">
                <span>Reimbursement</span>
                <span class="badge bg-warning text-dark"><?php echo $pendingReimbCount; ?></span>
              </div>
              <div class="notif-item">
                <span>Pengambilan</span>
                <span class="badge bg-warning text-dark"><?php echo $pendingTakeCount; ?></span>
              </div>
              <div class="mt-3 d-grid gap-2">
                <a href="dashboard_admin.php?view=pengajuan" class="btn btn-sm btn-primary">Lihat Semua</a>
              </div>
            </div>
          </div>
        </div>
        <div class="dropdown">
          <a class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
              <i class="fa-solid fa-user"></i>
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
        <a href="dashboard_admin.php?view=pengajuan" class="nav-link <?php echo $view==='pengajuan'?'active':''; ?>"><i class="fa-solid fa-inbox"></i> Pengajuan <?php if($pendingReqCount>0){ ?><span class="badge bg-warning text-dark ms-auto"><?php echo $pendingReqCount; ?></span><?php } ?></a>
        <a href="dashboard_admin.php?view=pengambilan" class="nav-link <?php echo $view==='pengambilan'?'active':''; ?>"><i class="fa-solid fa-box-open"></i> Pengambilan <?php if($pendingTakeCount>0){ ?><span class="badge bg-warning text-dark ms-auto"><?php echo $pendingTakeCount; ?></span><?php } ?></a>
        <a href="dashboard_admin.php?view=supplier" class="nav-link <?php echo $view==='supplier'?'active':''; ?>"><i class="fa-solid fa-address-card"></i> Supplier</a>
        <a href="dashboard_admin.php?view=pembelian" class="nav-link <?php echo $view==='pembelian'?'active':''; ?>"><i class="fa-solid fa-cart-shopping"></i> Pembelian</a>
        <a href="dashboard_admin.php?view=reimbursement" class="nav-link <?php echo $view==='reimbursement'?'active':''; ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Reimburse <?php if($pendingReimbCount>0){ ?><span class="badge bg-warning text-dark ms-auto"><?php echo $pendingReimbCount; ?></span><?php } ?></a>
        <a href="dashboard_admin.php?view=adjust" class="nav-link <?php echo $view==='adjust'?'active':''; ?>"><i class="fa-solid fa-arrows-rotate"></i> Penyesuaian</a>
        <a href="dashboard_admin.php?view=grafik" class="nav-link <?php echo $view==='grafik'?'active':''; ?>"><i class="fa-solid fa-chart-line"></i> Grafik</a>
        <a href="dashboard_admin.php?view=master" class="nav-link <?php echo $view==='master'?'active':''; ?>"><i class="fa-solid fa-database"></i> Master Data</a>
      </div>
      <div class="p-3 mt-auto border-top border-light border-opacity-10">
        <small class="text-white text-opacity-50">Logged in as Admin</small>
      </div>
    </aside>

    <main class="main-content p-4">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-12">
            <h1 class="h3 fw-bold mb-0"><i class="fa-solid fa-gauge-high text-primary me-2"></i> Dashboard Admin</h1>
            <p class="text-muted">Selamat datang kembali, <?php echo htmlspecialchars($user['name']); ?>.</p>
          </div>
        </div>

    <?php if ($view==='pengajuan'): ?>
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-inbox"></i> Pengajuan Barang (Pending)</h2>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>User</th><th>Barang</th><th>Qty</th><th>Departemen</th><th>Tanggal</th><th>Status</th><th>Bukti</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php
            $rows = $conn->query("SELECT r.id, u.name as uname, i.name as iname, r.qty, r.department, r.request_date, r.photo_file, r.status FROM requests r JOIN users u ON u.id=r.user_id JOIN items i ON i.id=r.item_id WHERE r.status='pending' ORDER BY r.id DESC");
            while ($r = $rows->fetch()) {
            ?>
              <tr>
                <td><?php echo htmlspecialchars($r['uname']); ?></td>
                <td><?php echo htmlspecialchars($r['iname']); ?></td>
                <td><?php echo (int)$r['qty']; ?></td>
                <td><?php echo htmlspecialchars($r['department'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['request_date'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['status']); ?></td>
                <td>
                  <?php if (!empty($r['photo_file'])): ?>
                    <a href="../<?php echo htmlspecialchars($r['photo_file']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-image"></i> Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="d-flex gap-2">
                  <form method="post" action="handlers/approve_request.php" class="d-inline">
                    <input type="hidden" name="req_id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-success btn-sm" type="submit"><i class="fa-solid fa-check"></i> Approve</button>
                  </form>
                  <form method="post" action="handlers/reject_request.php" class="d-inline">
                    <input type="hidden" name="req_id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- IMPOR BARANG (dipindah ke bawah Laporan Bulanan) -->
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-file-import"></i> Impor Barang (CSV/XLSX)</h2>
        <div class="alert alert-info small" role="alert">
          Untuk impor massal, siapkan file CSV atau Excel (.xlsx) dengan header: <code>name,sku,stock,category</code>.
          Kolom <code>category</code> opsional; jika diisi dan belum ada, kategori akan dibuat otomatis.
        </div>
        <form method="post" action="handlers/import_items.php" enctype="multipart/form-data" class="mb-3">
          <div class="row g-3 align-items-end">
            <div class="col-md-6">
              <label class="form-label">Pilih File</label>
              <input type="file" name="csv_file" class="form-control" accept=".csv,.xlsx" required>
              <div class="form-text">Maks. 5MB. CSV (UTF-8, koma) atau Excel (.xlsx).</div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button type="submit" class="btn btn-success"><i class="fa-solid fa-file-import"></i> Impor Data</button>
            </div>
          </div>
        </form>
      </div>
    </section>

    <section class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pengajuan Terbaru</h2>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>Tanggal</th><th>User</th><th>Barang</th><th>Qty</th><th>Departemen</th><th>Status</th><th>Bukti</th></tr></thead>
            <tbody>
            <?php
              $hist = $conn->query("SELECT r.created_at, r.request_date, r.department, r.photo_file, u.name as uname, i.name as iname, r.qty, r.status FROM requests r JOIN users u ON u.id=r.user_id JOIN items i ON i.id=r.item_id ORDER BY r.id DESC LIMIT 30");
              if ($hist) { while($h=$hist->fetch()) { ?>
              <tr>
                <td><?php echo htmlspecialchars($h['request_date'] ?: ($h['created_at'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($h['uname']); ?></td>
                <td><?php echo htmlspecialchars($h['iname']); ?></td>
                <td><?php echo (int)$h['qty']; ?></td>
                <td><?php echo htmlspecialchars($h['department'] ?? ''); ?></td>
                <td>
                  <?php if ($h['status']==='approved'): ?>
                    <span class="badge bg-success"><i class="fa-solid fa-check"></i> Disetujui</span>
                  <?php elseif ($h['status']==='rejected'): ?>
                    <span class="badge bg-secondary"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half"></i> Pending</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($h['photo_file'])): ?>
                    <a href="../<?php echo htmlspecialchars($h['photo_file']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-image"></i> Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php } }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <?php endif; ?>

   <?php if ($view==='pengambilan'): ?>
<section class="card shadow-sm mb-4">
  <div class="card-body">
    <h2 class="h5 mb-3"><i class="fa-solid fa-box-open"></i> Pengambilan Barang (Pending Approval)</h2>
    
    <?php
    try {
        // Query sederhana tanpa kolom notes dulu
        $takings = $conn->query("
            SELECT 
                tk.id,
                tk.taking_date,
                u.name as user_name,
                tk.department,
                i.name as item_name,
                i.sku,
                i.stock as current_stock,
                tk.quantity,
                tk.purpose,
                tk.status,
                tk.created_at
            FROM item_takings tk
            JOIN users u ON u.id = tk.user_id
            JOIN items i ON i.id = tk.item_id
            WHERE tk.status = 'pending'
            ORDER BY tk.created_at ASC
        ");
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error memuat data pengambilan: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $takings = false;
    }
    ?>

    <?php if ($takings && $takings->rowCount() > 0): ?>
      <div class="table-responsive">
        <table class="table table-hover table-striped">
          <thead class="table-dark">
            <tr>
              <th>Tanggal</th>
              <th>User & Dept</th>
              <th>Barang</th>
              <th>Qty</th>
              <th>Stok Tersedia</th>
              <th>Tujuan</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($taking = $takings->fetch()): ?>
            <tr>
              <td><?php echo htmlspecialchars($taking['taking_date'] ?? ''); ?></td>
              <td>
                <div><strong><?php echo htmlspecialchars($taking['user_name'] ?? ''); ?></strong></div>
                <small class="text-muted"><?php echo htmlspecialchars($taking['department'] ?? ''); ?></small>
              </td>
              <td>
                <div><?php echo htmlspecialchars($taking['item_name'] ?? ''); ?></div>
                <small class="text-muted">SKU: <?php echo htmlspecialchars($taking['sku'] ?? ''); ?></small>
              </td>
              <td>
                <span class="badge bg-primary fs-6"><?php echo (int)($taking['quantity'] ?? 0); ?></span>
              </td>
              <td>
                <span class="badge <?php echo ($taking['current_stock'] ?? 0) >= ($taking['quantity'] ?? 0) ? 'bg-success' : 'bg-danger'; ?>">
                  <?php echo (int)($taking['current_stock'] ?? 0); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($taking['purpose'] ?? ''); ?></td>
              <td>
               <!-- Form Approve -->
<form method="post" action="handlers/approve_taking.php" class="d-inline mb-1">
    <input type="hidden" name="taking_id" value="<?php echo (int)($taking['id'] ?? 0); ?>">
    <button type="submit" class="btn btn-success btn-sm" 
            onclick="return confirm('Approve pengambilan barang ini?')"
            <?php echo ($taking['current_stock'] ?? 0) < ($taking['quantity'] ?? 0) ? 'disabled' : ''; ?>>
        <i class="fa-solid fa-check"></i> Approve
    </button>
</form>

<!-- Form Reject -->
<form method="post" action="handlers/reject_taking.php" class="d-inline">
    <input type="hidden" name="taking_id" value="<?php echo (int)($taking['id'] ?? 0); ?>">
    <button type="submit" class="btn btn-outline-danger btn-sm" 
            onclick="return confirm('Tolak pengambilan barang ini?')">
        <i class="fa-solid fa-xmark"></i> Reject
    </button>
</form>
                </div>
                
                <?php if (($taking['current_stock'] ?? 0) < ($taking['quantity'] ?? 0)): ?>
                  <small class="text-danger d-block mt-1">
                    <i class="fa-solid fa-exclamation-triangle"></i> Stok tidak cukup
                  </small>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <i class="fa-solid fa-info-circle"></i> Tidak ada pengambilan barang yang menunggu persetujuan.
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Riwayat Pengambilan Barang -->
<section class="card shadow-sm">
  <div class="card-body">
    <h2 class="h6 mb-3"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Pengambilan Barang</h2>
    
    <?php
    try {
        $historyQuery = "
            SELECT 
                tk.id,
                tk.taking_date,
                u.name as user_name,
                tk.department,
                i.name as item_name,
                i.sku,
                tk.quantity,
                tk.purpose,
                tk.status,
                tk.approved_at,
                admin.name as admin_name
            FROM item_takings tk
            JOIN users u ON u.id = tk.user_id
            JOIN items i ON i.id = tk.item_id
            LEFT JOIN users admin ON admin.id = tk.approved_by
            WHERE tk.status != 'pending'
            ORDER BY tk.approved_at DESC
            LIMIT 50
        ";
        $history = $conn->query($historyQuery);
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error memuat riwayat: ' . htmlspecialchars($e->getMessage() ?? '') . '</div>';
        $history = false;
    }
    ?>

    <?php if ($history && $history->rowCount() > 0): ?>
      <div class="table-responsive">
        <table class="table table-sm table-striped">
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>User</th>
              <th>Dept</th>
              <th>Barang</th>
              <th>Qty</th>
              <th>Tujuan</th>
              <th>Status</th>
              <th>Disetujui Oleh</th>
              <th>Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($hist = $history->fetch()): ?>
            <tr>
              <td><?php echo htmlspecialchars($hist['taking_date'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($hist['user_name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($hist['department'] ?? ''); ?></td>
              <td>
                <div><?php echo htmlspecialchars($hist['item_name'] ?? ''); ?></div>
                <small class="text-muted">SKU: <?php echo htmlspecialchars($hist['sku'] ?? ''); ?></small>
              </td>
              <td><?php echo (int)($hist['quantity'] ?? 0); ?></td>
              <td><?php echo htmlspecialchars($hist['purpose'] ?? ''); ?></td>
              <td>
                <?php if (($hist['status'] ?? '') === 'approved'): ?>
                  <span class="badge bg-success">
                    <i class="fa-solid fa-check"></i> Disetujui
                  </span>
                <?php elseif (($hist['status'] ?? '') === 'rejected'): ?>
                  <span class="badge bg-danger">
                    <i class="fa-solid fa-xmark"></i> Ditolak
                  </span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">
                    <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($hist['status'] ?? 'Pending'); ?>
                  </span>
                <?php endif; ?>
              </td>
              <td><?php echo !empty($hist['admin_name']) ? htmlspecialchars($hist['admin_name']) : '<span class="text-muted">-</span>'; ?></td>
              <td><?php echo !empty($hist['approved_at']) ? htmlspecialchars($hist['approved_at']) : '<span class="text-muted">-</span>'; ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <i class="fa-solid fa-info-circle"></i> Belum ada riwayat pengambilan barang.
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

   <?php if ($view==='reimbursement'): ?>
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-file-invoice-dollar"></i> Reimbursement (Pending)</h2>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>ID Reimburse</th>
                <th>Tanggal</th>
                <th>User</th>
                <th>Nama Barang / Keperluan</th>
                <th>Departemen</th>
                <th>Total (Rp)</th>
                <th>Bukti</th>
                <th>Status</th>
                <th>Catatan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $rows = $conn->query("SELECT r.id, r.created_at, u.name as uname, r.description, r.amount, r.receipt_file, r.status, r.department FROM reimbursements r JOIN users u ON u.id=r.user_id WHERE r.status='pending' ORDER BY r.id DESC");
            while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
              $full = $r['description'] ?? '';
              $marker = " — Catatan: ";
              $main = $full; $note = '';
              $pos = strpos($full, $marker);
              if ($pos !== false) { $main = substr($full, 0, $pos); $note = substr($full, $pos + strlen($marker)); }
            ?>
              <tr>
                <td><?php echo htmlspecialchars($r['id']); ?></td>
                <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['uname']); ?></td>
                <td><?php echo htmlspecialchars($main); ?></td>
                <td><?php echo htmlspecialchars($r['department'] ?? '-'); ?></td>
                <td>Rp <?php echo number_format((float)$r['amount'], 0, ',', '.'); ?></td>
                <td>
                  <?php if (!empty($r['receipt_file'])): ?>
                    <a href="../<?php echo htmlspecialchars($r['receipt_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file"></i> Lihat</a>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge bg-warning text-dark">Pending</span></td>
                <td><?php echo $note !== '' ? htmlspecialchars($note) : '<span class="text-muted">-</span>'; ?></td>
                <td class="d-flex gap-2">
                  <form method="post" action="handlers/approve_reimbursement.php" class="d-inline">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-success btn-sm" type="submit"><i class="fa-solid fa-check"></i> Approve</button>
                  </form>
                  <form method="post" action="handlers/reject_reimbursement.php" class="d-inline">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button>
                  </form>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-list"></i> Riwayat Reimbursement</h2>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>ID Reimburse</th>
                <th>Tanggal</th>
                <th>User</th>
                <th>Nama Barang / Keperluan</th>
                <th>Departemen</th>
                <th>Total (Rp)</th>
                <th>Status</th>
                <th>Catatan</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $rows = $conn->query("SELECT r.id, r.created_at, u.name as uname, r.description, r.amount, r.status, r.department FROM reimbursements r JOIN users u ON u.id=r.user_id ORDER BY r.id DESC LIMIT 30");
            while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
              $full = $r['description'] ?? '';
              $marker = " — Catatan: ";
              $main = $full; $note = '';
              $pos = strpos($full, $marker);
              if ($pos !== false) { $main = substr($full, 0, $pos); $note = substr($full, $pos + strlen($marker)); }
            ?>
              <tr>
                <td><?php echo htmlspecialchars($r['id']); ?></td>
                <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['uname']); ?></td>
                <td><?php echo htmlspecialchars($main); ?></td>
                <td><?php echo htmlspecialchars($r['department'] ?? '-'); ?></td>
                <td>Rp <?php echo number_format((float)$r['amount'], 0, ',', '.'); ?></td>
                <td>
                  <?php if ($r['status']==='approved'): ?>
                    <span class="badge bg-success"><i class="fa-solid fa-check"></i> Disetujui</span>
                  <?php elseif ($r['status']==='rejected'): ?>
                    <span class="badge bg-secondary"><i class="fa-solid fa-xmark"></i> Ditolak</span>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half"></i> Pending</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $note !== '' ? htmlspecialchars($note) : '<span class="text-muted">-</span>'; ?></td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
<?php endif; ?>


    <?php if ($view==='grafik'): ?>
<section class="card shadow-sm mb-4">
  <div class="card-body">
    <h2 class="h5 mb-3"><i class="fa-solid fa-chart-line"></i> Grafik Pemasukan & Pengeluaran</h2>
    <?php 
  // filter default
  $filter = $_GET['filter'] ?? 'all';

  // Tentukan kondisi WHERE berdasarkan filter
  if ($filter === 'week') {
      $filter_sql = "AND sm.created_at >= CURRENT_DATE - INTERVAL '7 days'";
  } elseif ($filter === 'month') {
      $filter_sql = "AND DATE_TRUNC('month', sm.created_at) = DATE_TRUNC('month', CURRENT_DATE)";
  } else {
      $filter_sql = ""; // semua data
  }
?>

<form method="GET" class="mb-3">
    <input type="hidden" name="view" value="grafik">

    <label><input type="radio" name="filter" value="all"   <?= $filter==='all'?'checked':'' ?>> Semua</label>
    <label class="ms-3"><input type="radio" name="filter" value="week"  <?= $filter==='week'?'checked':'' ?>> Minggu</label>
    <label class="ms-3"><input type="radio" name="filter" value="month" <?= $filter==='month'?'checked':'' ?>> Bulan</label>

    <button class="btn btn-sm btn-primary ms-3">Terapkan</button>
</form>





    <?php
      // --- AMBIL DATA PER ITEM DARI stock_movements ---
$sql = "
    SELECT 
        i.name AS item_name,
        COALESCE(SUM(CASE WHEN sm.type='in' THEN sm.qty END), 0) AS in_total,
        COALESCE(SUM(CASE WHEN sm.type='out' THEN sm.qty END), 0) AS out_total
    FROM items i
    LEFT JOIN stock_movements sm 
        ON sm.item_id = i.id
        $filter_sql
    GROUP BY i.name
    ORDER BY i.name
";

$stmt = $conn->query($sql);

$itemNames = [];
$inTotals = [];
$outTotals = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $itemNames[] = $row['item_name'];
    $inTotals[] = (int)$row['in_total'];
    $outTotals[] = (int)$row['out_total'];
}
?>




<canvas id="adminFlowChart" height="140"></canvas>

<script>
const ctx = document.getElementById('adminFlowChart').getContext('2d');

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($itemNames); ?>,
        datasets: [
            {
                label: 'Pemasukan',
                data: <?= json_encode($inTotals); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.6)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            },
            {
                label: 'Pengeluaran',
                data: <?= json_encode($outTotals); ?>,
                backgroundColor: 'rgba(220, 53, 69, 0.6)',
                borderColor: 'rgba(220, 53, 69, 1)',
                borderWidth: 1 
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            x: { title: { display: true, text: 'Nama Barang' } },
            y: { beginAtZero: true, title: { display: true, text: 'Jumlah' } }
        }
    }
});
</script>


  </div>
</section>
<?php endif; ?>


    <?php if ($view==='master'): ?>
    <?php
      require_once __DIR__ . '/../includes/db.php';
      $conn->exec("CREATE TABLE IF NOT EXISTS categories (id SERIAL PRIMARY KEY, name VARCHAR(100) UNIQUE NOT NULL)");

      if (!column_exists($conn, 'items', 'category_id')) {
        $conn->exec("ALTER TABLE items ADD COLUMN category_id INTEGER NULL");
      }

      $stmt = $conn->prepare("SELECT constraint_name FROM information_schema.table_constraints WHERE table_name='items' AND constraint_type='FOREIGN KEY' AND constraint_name LIKE '%category%'");
      $stmt->execute();
      $fkExists = $stmt->fetch();
      if (!$fkExists) {
        $conn->exec("ALTER TABLE items ADD CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id)");
      }

      $cats = $conn->query("SELECT id, name FROM categories ORDER BY name");
    ?>

    <!-- LAPORAN BULANAN -->
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-file-pdf"></i> Laporan Bulanan</h2>
        <form method="get" action="print_report.php" class="row g-3 align-items-end">
          <div class="col-sm-4">
            <label class="form-label">Pilih Bulan</label>
            <input type="month" class="form-control" name="month" required>
          </div>
          <div class="col-sm-4">
            <label class="form-label">Jenis Laporan</label>
            <select class="form-select" name="scope">
              <option value="all">Semua (In/Out, Pembelian, Reimburse)</option>
              <option value="stock">Mutasi Stok (Pemasukan/Pengeluaran)</option>
              <option value="purchase">Pembelian</option>
              <option value="reimburse">Reimbursement</option>
            </select>
          </div>
          <div class="col-sm-4 d-flex gap-2 align-items-end">
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-file-pdf"></i> Cetak PDF</button>
            <button type="submit" class="btn btn-success" formaction="export_report.php"><i class="fa-solid fa-file-excel"></i> Export Excel (CSV)</button>
            <input type="hidden" name="return" value="dashboard_admin.php?view=master">
          </div>
        </form>
        <p class="form-text">PDF dicetak via dialog print browser dari halaman ringkas.</p>
      </div>
    </section>


 <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-tags"></i> Kategori</h2>
        <form method="post" action="handlers/add_category.php" class="mb-3">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Nama Kategori</label>
              <input type="text" name="name" class="form-control" placeholder="Contoh: Perangkat IT" required>
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Tambah</button>
            </div>
          </div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>ID</th><th>Nama</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php if ($cats) { while ($c = $cats->fetch()) { ?>
              <tr>
                <td><?php echo (int)$c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['name']); ?></td>
                <td>
                  <form method="post" action="handlers/delete_category.php" class="d-inline" onsubmit="return confirm('Hapus kategori ini?');">
                    <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
                  </form>
                </td>
              </tr>
              <?php } } else { ?>
              <tr><td colspan="3" class="text-muted">Belum ada kategori.</td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    

    <!-- BARANG -->
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-box"></i> Barang</h2>
        <?php if (isset($_GET['msg']) && $_GET['msg'] !== ''): ?>
          <div class="alert alert-info" role="alert">
            <?php echo htmlspecialchars($_GET['msg']); ?>
          </div>
        <?php endif; ?>
        <form method="post" action="handlers/add_item.php" class="mb-3">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Nama Barang</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">SKU</label>
              <input type="text" name="sku" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Stok</label>
              <input type="number" name="stock" class="form-control" min="0" value="0">
            </div>
            <div class="col-md-3">
              <label class="form-label">Harga</label>
              <input type="number" step="0.01" name="price" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Kategori</label>
              <select name="category_id" class="form-select">
                <option value="">- Tanpa Kategori -</option>
                <?php
                  $catOpts = $conn->query("SELECT id, name FROM categories ORDER BY name");
                  if ($catOpts) { while ($opt = $catOpts->fetch()) { ?>
                    <option value="<?php echo (int)$opt['id']; ?>"><?php echo htmlspecialchars($opt['name']); ?></option>
                <?php } }
                ?>
              </select>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Tambah Barang</button>
            </div>
          </div>
        </form>

        <?php $items = $conn->query("SELECT i.id, i.name, i.sku, i.stock, i.price, i.category_id AS cid, c.name AS cname FROM items i LEFT JOIN categories c ON c.id=i.category_id ORDER BY i.id DESC LIMIT 50"); ?>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead><tr><th>ID</th><th>Nama</th><th>SKU</th><th>Kategori</th><th>Stok</th><th>Harga</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php if ($items) { while ($it = $items->fetch()) { ?>
              <tr>
                <td><?php echo (int)$it['id']; ?></td>
                <td><?php echo htmlspecialchars($it['name']); ?></td>
                <td><?php echo htmlspecialchars($it['sku'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($it['cname'] ?? '-'); ?></td>
                <td><?php echo (int)$it['stock']; ?></td>
                <td><?php if (isset($it['price'])) {echo "Rp " . number_format((int)$it['price'], 0, ',', '.');} 
                else {echo "-";}?></td>
                <td class="d-flex gap-2">
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#editItem<?php echo (int)$it['id']; ?>"><i class="fa-solid fa-pen"></i> Edit</button>
                  <form method="post" action="handlers/delete_item.php" onsubmit="return confirm('Hapus barang ini?');">
                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
                  </form>
                </td>
              </tr>
              <tr class="collapse" id="editItem<?php echo (int)$it['id']; ?>">
                <td colspan="6">
                  <form method="post" action="handlers/update_item.php">
                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($it['name']); ?>" required>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($it['sku'] ?? ''); ?>">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" class="form-control" min="0" value="<?php echo (int)$it['stock']; ?>">
                      </div>
                      <div class="col-md-2">
                        <label class="form-label">Harga</label>
                        <input type="number" name="price" class="form-control" min="0" step="100" value="<?php echo (int)$it['price']; ?>"> 
                      </div>
                      <div class="col-md-3">
                        <label class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                          <option value="">- Tanpa Kategori -</option>
                          <?php $catEdit = $conn->query("SELECT id, name FROM categories ORDER BY name"); if ($catEdit) { while ($ce = $catEdit->fetch()) { ?>
                            <option value="<?php echo (int)$ce['id']; ?>" <?php echo ((int)$it['cid'] === (int)$ce['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($ce['name']); ?></option>
                          <?php } } ?>
                        </select>
                      </div>
                      <div class="col-12">
                        <button class="btn btn-success" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                      </div>
                    </div>
                  </form>
                </td>
              </tr>
              <?php } } else { ?>
              <tr><td colspan="6" class="text-muted">Belum ada barang.</td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    
    <?php endif; ?>

     <?php if ($view==='supplier'): ?>
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-address-card"></i> Form Supplier</h2>
        <form method="post" action="handlers/add_supplier.php">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama Supplier</label><input type="text" class="form-control" name="name" required></div>
            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
            <div class="col-md-6"><label class="form-label">PIC / Contact Person</label><input type="text" class="form-control" name="pic_name"></div>
            <div class="col-md-6"><label class="form-label">Jenis Barang Disuplai</label><input type="text" class="form-control" name="supply_type"></div>
            <div class="col-md-6"><label class="form-label">Jabatan PIC</label><input type="text" class="form-control" name="pic_title"></div>
            <div class="col-md-6"><label class="form-label">No. Telepon</label><input type="text" class="form-control" name="phone"></div>
            <div class="col-md-6">
              <label class="form-label">Metode Pembayaran</label>
              <select class="form-select" name="payment_method">
                <option value="">-- Pilih --</option>
                <option>Cash</option>
                <option>Transfer</option>
                <option>Kredit</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status Supplier</label>
              <select class="form-select" name="status">
                <option value="">-- Pilih --</option>
                <option>Aktif</option>
                <option>Nonaktif</option>
                <option>Blacklist</option>
              </select>
            </div>
            <div class="col-12"><label class="form-label">Alamat Perusahaan</label><textarea class="form-control" name="address" rows="3"></textarea></div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan Data</button></div>
          </div>
        </form>
      </div>
    </section>

    <section class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Supplier (Terbaru)</h2>
        <p class="text-muted small mb-2">Menampilkan 30 supplier terakhir yang ditambahkan.</p>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>PIC</th><th>Jabatan</th><th>Telepon</th><th>Metode</th><th>Status</th><th>Alamat</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php
              // Guard kolom untuk tampilan riwayat lengkap
              $needCols2 = [
                'email' => "ALTER TABLE suppliers ADD COLUMN email VARCHAR(120) NULL",
                'pic_name' => "ALTER TABLE suppliers ADD COLUMN pic_name VARCHAR(120) NULL",
                'pic_title' => "ALTER TABLE suppliers ADD COLUMN pic_title VARCHAR(120) NULL",
                'phone' => "ALTER TABLE suppliers ADD COLUMN phone VARCHAR(60) NULL",
                'payment_method' => "ALTER TABLE suppliers ADD COLUMN payment_method VARCHAR(30) NULL",
                'status' => "ALTER TABLE suppliers ADD COLUMN status VARCHAR(30) NULL",
                'address' => "ALTER TABLE suppliers ADD COLUMN address VARCHAR(255) NULL"
              ];
              foreach ($needCols2 as $col => $sql) {
                  if (!column_exists($conn, 'suppliers', $col)) {
                      try {
                          $conn->exec($sql);
                      } catch (PDOException $e) {
                          // Kolom mungkin sudah ada, abaikan error
                      }
                  }
              }

              $rows = $conn->query("SELECT id, name, email, pic_name, pic_title, phone, payment_method, status, address FROM suppliers ORDER BY id DESC LIMIT 30");
              if ($rows) { while($r=$rows->fetch(PDO::FETCH_ASSOC)) { ?>
              <tr>
                <td><?php echo (int)$r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo htmlspecialchars($r['email'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['pic_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['pic_title'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['phone'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['payment_method'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['status'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['address'] ?? ''); ?></td>
                <td class="d-flex gap-2">
                  <form method="post" action="handlers/delete_supplier.php" onsubmit="return confirm('Hapus supplier ini?');">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i> Hapus</button>
                  </form>
                </td>
              </tr>
              <?php } }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <?php endif; ?>


    <?php if ($view==='pembelian'): ?>
    <section class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 mb-3"><i class="fa-solid fa-cart-shopping"></i> Form Pembelian Barang</h2>
        <form action="create_purchase.php" method="POST" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">ID Pembelian</label>
              <input type="text" class="form-control" name="purchase_code" placeholder="PO-20251114-0001">
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Pembelian</label>
              <input type="date" class="form-control" name="purchase_date" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Nama Barang</label>
              <input type="text" class="form-control" name="item_name" required>
              <small class="form-text text-muted">Masukkan nama barang yang sudah ada di database.</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">Supplier</label>
              <select class="form-select" name="supplier_id" required>
              <?php $sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name"); while ($su = $sups->fetch()) { ?>
                <option value="<?php echo (int)$su['id']; ?>"><?php echo htmlspecialchars($su['name']); ?></option>
              <?php } ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Jumlah Dibeli</label>
              <input type="number" class="form-control" name="qty" min="1" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Harga Satuan</label>
              <input type="number" step="0.01" class="form-control" name="unit_price" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Metode Pembayaran</label>
              <select class="form-select" name="payment_method">
                <option value="">Pilih</option>
                <option>Cash</option>
                <option>Transfer</option>
                <option>Kredit</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status Pembelian</label>
              <select class="form-select" name="status">
                <option value="">Pilih</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Catatan</label>
              <textarea class="form-control" name="note_text" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Lampiran Nota (opsional)</label>
              <input type="file" class="form-control" name="note_file" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan</button></div>
          </div>
        </form>
      </div>
    </section>

    <section class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-receipt"></i> Riwayat Pembelian (Terbaru)</h2>
        <p class="text-muted small mb-2">Menampilkan 30 transaksi pembelian terakhir.</p>
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
            $rows = $conn->query("SELECT p.purchase_code, p.purchase_date, i.sku, i.name AS item_name, 
            s.name AS supplier_name, p.qty, p.unit_price, p.price_total, p.payment_method, 
            p.status, p.note_text, p.note_file 
            FROM purchases p 
            left JOIN items i ON i.id=p.item_id 
            left JOIN suppliers s ON s.id=p.supplier_id ORDER BY p.id DESC LIMIT 30");
            if ($rows) { while ($row = $rows->fetch()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($row['purchase_code'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($row['purchase_date'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row['sku'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($row['item_name'] ?? '-'); ?></td>
              <td><?php echo htmlspecialchars($row['supplier_name'] ?? '-'); ?></td>
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
            <?php } }
          ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($view==='adjust'): ?>
    <section class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-box"></i> Update Stok Consumable</h2>
        <form method="post" action="handlers/update_stock.php" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-6">
             <label class="form-label">Nama Barang</label>
             <input type="text" class="form-control" name="item_name" placeholder="Masukkan nama barang" required>
             <div class="form-text">Ketik nama barang sesuai data pada menu master barang.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Stok Masuk (Unit)</label>
              <input type="number" class="form-control" name="stock_in" min="1" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Tanggal Barang Masuk</label>
              <input type="date" class="form-control" name="received_date">
            </div>
            <div class="col-md-6">
              <label class="form-label">Upload Bukti</label>
              <input type="file" class="form-control" name="proof_file" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <div class="col-12">
              <label class="form-label">Catatan</label>
              <textarea class="form-control" name="note_text" rows="2"></textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Simpan</button></div>
          </div>
        </form>
      </div>
    </section>
    <section class="card shadow-sm mt-3">
      <div class="card-body">
        <h2 class="h6 mb-3"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Penyesuaian Stok Terbaru</h2>
        <?php
          // Pastikan tabel bukti ada agar query aman
          $conn->exec("CREATE TABLE IF NOT EXISTS stock_movements_proofs (
            id SERIAL PRIMARY KEY,
            movement_id INTEGER NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_movement_proof_admin FOREIGN KEY (movement_id) REFERENCES stock_movements(id)
          )");

          $stmt = $conn->query("SELECT sm.id, sm.created_at, i.name AS iname, sm.type, sm.qty, sm.source,
            (SELECT COUNT(*) FROM stock_movements_proofs sp WHERE sp.movement_id=sm.id) AS has_proof
            FROM stock_movements sm JOIN items i ON i.id=sm.item_id
            ORDER BY sm.id DESC LIMIT 20");
        ?>
           
        <table class="table table-sm table-striped">
          <thead><tr><th>Tanggal</th><th>Barang</th><th>Jenis</th><th>Qty</th><th>Sumber</th><th>Bukti</th></tr></thead>
          <tbody>
            <?php if ($stmt) { while ($r = $stmt->fetch()) { ?>
            <tr>
              <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['iname']); ?></td>
              <td><?php echo $r['type']==='in' ? 'Masuk' : 'Keluar'; ?></td>
              <td><?php echo (int)$r['qty']; ?></td>
              <td><?php echo htmlspecialchars($r['source'] ?? '-'); ?></td>
              <td>
                <?php if ((int)$r['has_proof']>0) { ?>
                  <a href="handlers/view_stock_proof.php?movement_id=<?php echo (int)$r['id']; ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file"></i> Lihat Bukti</a>
                <?php } else { ?>
                  <span class="text-muted">-</span>
                <?php } ?>
              </td>
            </tr>
            <?php }} ?>

          </tbody>
        </table>
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

</body>
</html>
