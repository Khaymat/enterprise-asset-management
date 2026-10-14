<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../register.php');
  exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm = trim($_POST['confirm'] ?? '');
$roleReq = trim($_POST['role'] ?? 'user');

// Validasi sederhana
if ($name === '' || $email === '' || $password === '' || $confirm === '') {
  $_SESSION['error'] = 'Semua field wajib diisi.';
  header('Location: ../register.php');
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $_SESSION['error'] = 'Format email tidak valid.';
  header('Location: ../register.php');
  exit;
}
if (strlen($password) < 6) {
  $_SESSION['error'] = 'Password minimal 6 karakter.';
  header('Location: ../register.php');
  exit;
}
if ($password !== $confirm) {
  $_SESSION['error'] = 'Konfirmasi password tidak cocok.';
  header('Location: ../register.php');
  exit;
}

$conn = get_db_connection();

// Cek email unik
$stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
if ($stmt->fetch()) {
  $_SESSION['error'] = 'Email sudah terdaftar.';
  header('Location: ../register.php');
  exit;
}

// Tentukan peran yang diminta (hanya dari daftar yang diizinkan)
$allowed = ['user','atasan','admin_stok','admin_utama'];
if (!in_array($roleReq, $allowed, true)) { $roleReq = 'user'; }

// Batasi Admin Utama: hanya boleh jika belum ada admin_utama
if ($roleReq === 'admin_utama') {
  $stmt = $conn->prepare("SELECT COUNT(*) c FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='admin_utama'");
  $stmt->execute();
  $r = $stmt->fetch();
  $hasAdmin = $r ? (int)$r['c'] : 0;
  if ($hasAdmin > 0) {
    $_SESSION['error'] = 'Admin Utama sudah ada. Gunakan halaman Admin untuk menambah akun lain.';
    header('Location: ../register.php');
    exit;
  }
}

// Ambil role id sesuai roleReq (buat jika belum ada)
$stmt = $conn->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
$stmt->execute([$roleReq]);
$row = $stmt->fetch();
$roleId = $row ? (int)$row['id'] : null;
if (!$roleId) {
  $stmt = $conn->prepare("INSERT INTO roles(name) VALUES(?)");
  $stmt->execute([$roleReq]);
  $roleId = (int)$conn->lastInsertId('roles_id_seq');
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ins = $conn->prepare('INSERT INTO users(name, email, password_hash, role_id) VALUES(?,?,?,?)');
$ok = $ins->execute([$name, $email, $hash, $roleId]);

if ($ok) {
  $_SESSION['success'] = 'Akun berhasil dibuat sebagai '.$roleReq.'. Silakan masuk.';
  header('Location: ../index.php');
  exit;
}

$_SESSION['error'] = 'Terjadi kesalahan saat membuat akun.';
header('Location: ../register.php');
exit;
?>
