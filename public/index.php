<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

$conn = get_db_connection();
$company = fetch_company($conn);
$user = current_user();

// Jika sudah login, langsung arahkan ke dashboard sesuai peran
if ($user) {
  switch ($user['role_name']) {
    case 'admin_utama': header('Location: dashboard_admin.php'); break;
    case 'admin_stok': header('Location: dashboard_stock.php'); break;
    case 'atasan': header('Location: dashboard_boss.php'); break;
    default: header('Location: dashboard_user.php'); break;
  }
  exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/purchase/create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/handlers/create_purchase.php';
    exit;
}


// Fallback logo & nama perusahaan
$logo_rel = 'img/logo.png';
$logo_exists = file_exists(__DIR__ . '/img/logo.png');
$company_name = 'PT NHM';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk · <?php echo htmlspecialchars($company_name); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
  <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
</head>
<body class="auth-page">
  <div class="container py-5">
    <div class="auth-card mx-auto shadow-lg">
      <div class="auth-left">
        <div class="brand-block">
          <?php if (!empty($company['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars($company['logo_path']); ?>" alt="Logo" class="logo"/>
          <?php elseif ($logo_exists): ?>
            <img src="<?php echo $logo_rel; ?>" alt="Logo" class="logo"/>
          <?php endif; ?>
          <div class="brand-text">
            <div class="small text-uppercase">Sistem Manajemen Aset</div>
            <h1 class="brand-title"><?php echo htmlspecialchars($company_name); ?></h1>
          </div>
        </div>
      </div>
      <div class="auth-right">
        <div class="p-4 p-md-5">
          <h2 class="h5 mb-3">Masuk ke Akun</h2>
          <?php if (!empty($_SESSION['error'])) { echo '<div class="alert alert-danger py-2">' . $_SESSION['error'] . '</div>'; unset($_SESSION['error']); } ?>
          <form method="post" action="handlers/login.php" class="auth-form">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" placeholder="admin@demo.com" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Kata Sandi</label>
              <div class="input-group">
                <input type="password" class="form-control" name="password" placeholder="password" required>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password>
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" value="1" id="rememberDummy" disabled>
              <div class="text-end mt-1">
        <a href="forgot_password.php" class="text-decoration-none">Lupa Password?</a>
    </div>
            
            </div>
            <button class="btn btn-primary w-100" type="submit">Masuk Sekarang</button>
          </form>
          <p class="small mt-3">Belum punya akun? <a href="register.php">Daftar</a></p>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/main.js"></script>
</body>
</html>
