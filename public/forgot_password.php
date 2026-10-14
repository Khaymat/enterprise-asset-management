<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
$conn = get_db_connection();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        session_start();
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit;
    } else {
        $msg = "Email tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Lupa Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height:100vh;">

<div class="card shadow p-4" style="width: 380px;">
    <h4 class="mb-3 text-center">Lupa Password</h4>

    <?php if ($msg): ?>
        <div class="alert alert-danger py-2"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Masukkan Email Anda</label>
            <input type="email" class="form-control" name="email" required>
        </div>

        <button class="btn btn-primary w-100">Lanjut</button>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">Kembali ke Login</a>
        </div>
    </form>
</div>

</body>
</html>
