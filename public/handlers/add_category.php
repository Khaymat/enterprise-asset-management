<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
$u = current_user();
if (!$u || $u['role_name'] !== 'admin_utama') { header('Location: ../'); exit; }

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
if ($name === '') { header('Location: ../dashboard_admin.php?view=master&msg=Nama%20kategori%20wajib'); exit; }

$conn = get_db_connection();
$conn->exec("CREATE TABLE IF NOT EXISTS categories (id SERIAL PRIMARY KEY, name VARCHAR(100) UNIQUE NOT NULL)");

$stmt = $conn->prepare("INSERT INTO categories(name) VALUES(?)");
$stmt->execute([$name]);
if ($stmt->execute()) {
  header('Location: ../dashboard_admin.php?view=master&msg=Kategori%20ditambahkan');
} else {
  header('Location: ../dashboard_admin.php?view=master&msg=Gagal%20menambah%20kategori');
}
exit;
?>
