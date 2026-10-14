<?php
session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$conn = get_db_connection();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_SESSION['reset_email'];

    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $stmt->execute([$pass, $email]);

    unset($_SESSION['reset_email']);
    $msg = "Password berhasil diubah. Silakan login kembali.";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <?php include __DIR__ . '/../includes/pwa_meta.php'; ?>
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height:100vh;">

<div class="card shadow p-4" style="width: 380px;">
    <h4 class="mb-3 text-center">Reset Password</h4>

    <?php if ($msg): ?>
        <div class="alert alert-success py-2"><?= $msg ?></div>
        <div class="text-center">
            <a href="login.php" class="btn btn-primary w-100 mt-2">Login</a>
        </div>
    <?php else: ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" class="form-control" name="password" required>
        </div>

        <button class="btn btn-success w-100">Simpan Password</button>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">Kembali ke Login</a>
        </div>
    </form>

    <?php endif; ?>
</div>

</body>
</html>
