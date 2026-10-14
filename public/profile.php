<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();
$user = current_user();
if (!$user) { header('Location: ./'); exit; }
$conn = get_db_connection();

$notifTotal = 0;
$role = $user['role_name'];
// Notification logic remains same...
if ($role === 'admin_utama') {
  $pendingReqCount = 0; $pendingReimbCount = 0;
  if ($res=$conn->query("SELECT COUNT(*) c FROM requests WHERE status='pending'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $pendingReqCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM reimbursements WHERE status='pending'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $pendingReimbCount=(int)$row['c']; }
  $notifTotal = $pendingReqCount + $pendingReimbCount;
} elseif ($role === 'user') {
  $uid = (int)$user['id'];
  $reqPendingCount=0; $reqApprovedCount=0; $reqRejectedCount=0; $reimPendingCount=0; $reimApprovedCount=0; $reimRejectedCount=0;
  if ($res=$conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid AND status='pending'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $reqPendingCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid AND status='approved'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $reqApprovedCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM requests WHERE user_id=$uid AND status='rejected'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $reqRejectedCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM reimbursements WHERE user_id=$uid AND status='pending'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $reimPendingCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM reimbursements WHERE user_id=$uid AND status='approved'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $reimApprovedCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM reimbursements WHERE user_id=$uid AND status='rejected'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $reimRejectedCount=(int)$row['c']; }
  $notifTotal = $reqPendingCount + $reimPendingCount + $reqApprovedCount + $reqRejectedCount + $reimApprovedCount + $reimRejectedCount;
} elseif ($role === 'atasan') {
  $pendingReqCount = 0; $pendingReimbCount = 0;
  if ($res=$conn->query("SELECT COUNT(*) c FROM requests WHERE status='pending'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $pendingReqCount=(int)$row['c']; }
  if ($res=$conn->query("SELECT COUNT(*) c FROM reimbursements WHERE status='pending'")) { $row=$res->fetch(PDO::FETCH_ASSOC); $pendingReimbCount=(int)$row['c']; }
  $notifTotal = $pendingReqCount + $pendingReimbCount;
} elseif ($role === 'admin_stok') {
  $lowStockCount = 0;
  if ($res=$conn->query("SELECT COUNT(*) c FROM items WHERE stock<=0")) { $row=$res->fetch(PDO::FETCH_ASSOC); $lowStockCount=(int)$row['c']; }
  $notifTotal = $lowStockCount;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Profil · PT NHM</title>
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
        <?php if ($role==='admin_utama'): ?>
          <a href="dashboard_admin.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
          <a href="dashboard_admin.php?view=pengajuan" class="nav-link"><i class="fa-solid fa-inbox"></i> Pengajuan</a>
          <a href="dashboard_admin.php?view=supplier" class="nav-link"><i class="fa-solid fa-address-card"></i> Supplier</a>
          <a href="dashboard_admin.php?view=pembelian" class="nav-link"><i class="fa-solid fa-cart-shopping"></i> Pembelian</a>
        <?php elseif ($role==='user'): ?>
          <a href="dashboard_user.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
          <a href="dashboard_user.php?view=ajukan" class="nav-link"><i class="fa-solid fa-boxes-stacked"></i> Ajukan</a>
        <?php elseif ($role==='atasan'): ?>
          <a href="dashboard_boss.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
          <a href="dashboard_boss.php?view=pemasukan" class="nav-link"><i class="fa-solid fa-chart-line"></i> Analisis</a>
        <?php else: ?>
          <a href="dashboard_stock.php" class="nav-link"><i class="fa-solid fa-gauge"></i> Dashboard</a>
          <a href="dashboard_stock.php?view=stok" class="nav-link"><i class="fa-solid fa-warehouse"></i> Stok</a>
        <?php endif; ?>
      </div>
      <div class="p-3 mt-auto border-top border-light border-opacity-10 text-center">
        <a href="index.php" class="btn btn-sm btn-outline-light w-100">Kembali ke Beranda</a>
      </div>
    </aside>

    <main class="main-content p-4">
      <div class="container-fluid">
        <div class="row mb-4">
          <div class="col-12">
            <h1 class="h3 fw-bold mb-0"><i class="fa-solid fa-user-pen text-primary me-2"></i> Pengaturan Profil</h1>
            <p class="text-muted">Kelola informasi akun dan keamanan Anda.</p>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-8">
            <section class="card shadow-sm mb-4">
              <div class="card-body p-4">
                <h2 class="h5 mb-4 fw-bold">Informasi Akun</h2>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label text-muted small text-uppercase fw-bold">Nama Lengkap</label>
                    <p class="form-control-plaintext fw-medium border-bottom"><?php echo htmlspecialchars($user['name']); ?></p>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-muted small text-uppercase fw-bold">Hak Akses</label>
                    <p class="form-control-plaintext fw-medium border-bottom"><span class="badge bg-primary"><?php echo strtoupper(htmlspecialchars($user['role_name'])); ?></span></p>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label text-muted small text-uppercase fw-bold">Email</label>
                    <p class="form-control-plaintext fw-medium border-bottom"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
                  </div>
                </div>
              </div>
            </section>

            <section class="card shadow-sm">
              <div class="card-body p-4">
                <h2 class="h5 mb-4 fw-bold">Keamanan</h2>
                <?php if (!empty($_SESSION['success'])) { echo '<div class="alert alert-success border-0 shadow-sm mb-4">'.$_SESSION['success'].'</div>'; unset($_SESSION['success']); } ?>
                <?php if (!empty($_SESSION['error'])) { echo '<div class="alert alert-danger border-0 shadow-sm mb-4">'.$_SESSION['error'].'</div>'; unset($_SESSION['error']); } ?>
                
                <form method="post" action="handlers/change_password.php" autocomplete="off">
                  <div class="mb-4">
                    <label class="form-label fw-medium">Password Saat Ini</label>
                    <div class="input-group">
                      <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                      <input type="password" class="form-control border-start-0" name="current_password" required placeholder="Masukkan password lama">
                      <button class="btn btn-outline-secondary" type="button" data-toggle-password><i class="fa-regular fa-eye"></i></button>
                    </div>
                  </div>
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Password Baru</label>
                      <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-key text-muted"></i></span>
                        <input type="password" class="form-control border-start-0" name="new_password" minlength="6" required placeholder="Minimal 6 karakter">
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password><i class="fa-regular fa-eye"></i></button>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label fw-medium">Konfirmasi Password Baru</label>
                      <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-shield-check text-muted"></i></span>
                        <input type="password" class="form-control border-start-0" name="confirm_password" minlength="6" required placeholder="Ulangi password baru">
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password><i class="fa-regular fa-eye"></i></button>
                      </div>
                    </div>
                  </div>
                  <button class="btn btn-primary px-4 py-2" type="submit"><i class="fa-solid fa-save me-2"></i> Update Password</button>
                </form>
              </div>
            </section>
          </div>
          
          <div class="col-lg-4">
            <div class="card bg-primary text-white border-0 shadow-sm overflow-hidden mb-4">
              <div class="card-body p-4 position-relative">
                <i class="fa-solid fa-shield-halved position-absolute opacity-10" style="font-size: 8rem; right: -20px; bottom: -20px;"></i>
                <h3 class="h6 fw-bold mb-3">Keamanan Akun</h3>
                <p class="small text-white-50 mb-4">Pastikan Anda menggunakan password yang kuat dan unik untuk melindungi akun Anda dari akses yang tidak sah.</p>
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-lg bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-user-shield"></i>
                  </div>
                  <div>
                    <div class="small opacity-75">Status Akun</div>
                    <div class="fw-bold">Aktif & Terverifikasi</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <footer class="text-center text-muted small py-4">© <?php echo date('Y'); ?> PT NHM · Asset Management System</footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/main.js"></script>
</body>
</html>
