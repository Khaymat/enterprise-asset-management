<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../profile.php');
  exit;
}

$current = trim($_POST['current_password'] ?? '');
$new = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

if ($new === '' || strlen($new) < 6) {
  $_SESSION['error'] = 'Password baru minimal 6 karakter.';
  header('Location: ../profile.php');
  exit;
}
if ($new !== $confirm) {
  $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
  header('Location: ../profile.php');
  exit;
}

$conn = get_db_connection();
$stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($current, $row['password_hash'])) {
  $_SESSION['error'] = 'Password saat ini salah.';
  header('Location: ../profile.php');
  exit;
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$upd = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$ok = $upd->execute([$newHash, $user['id']]);

if ($ok) {
  $_SESSION['success'] = 'Password berhasil diperbarui.';
} else {
  $_SESSION['error'] = 'Gagal memperbarui password. Coba lagi.';
}

header('Location: ../profile.php');
exit;
?>
