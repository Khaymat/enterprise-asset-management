<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
$u = current_user();
if (!$u || $u['role_name'] !== 'admin_utama') { header('Location: ../'); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { header('Location: ../dashboard_admin.php?view=master&msg=ID%20barang%20tidak%20valid'); exit; }

$conn = get_db_connection();
$stmt = $conn->prepare("DELETE FROM items WHERE id=?");
$stmt->execute([$id]);
if ($stmt->execute()) {
  header('Location: ../dashboard_admin.php?view=master&msg=Barang%20dihapus');
} else {
  header('Location: ../dashboard_admin.php?view=master&msg=Gagal%20menghapus%20barang');
}
exit;
?>
