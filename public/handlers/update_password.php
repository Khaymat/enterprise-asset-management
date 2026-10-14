<?php
require_once __DIR__.'/includes/db.php';
$conn = get_db_connection();

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("SELECT * FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$data = $stmt->fetch();

if (!$data) {
    header("Location: reset_password.php?msg=invalid");
    exit;
}

if (strtotime($data['expires_at']) < time()) {
    header("Location: reset_password.php?msg=expired");
    exit;
}

$email = $data['email'];

// Update password
$newPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$newPassword, $email]);

// Hapus token
$conn->prepare("DELETE FROM password_resets WHERE token = ?")->execute([$token]);

header("Location: reset_password.php?msg=success");
exit;
