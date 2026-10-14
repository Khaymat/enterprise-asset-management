<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
$u = current_user();
if (!$u || $u['role_name'] !== 'admin_utama') { header('Location: ../'); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { header('Location: ../dashboard_admin.php?view=master&msg=ID%20kategori%20tidak%20valid'); exit; }

$conn = get_db_connection();
// Set item.category_id menjadi NULL sebelum hapus kategori (agar aman terhadap FK)
$stmt = $conn->prepare("UPDATE items SET category_id=NULL WHERE category_id = ?");
@ $stmt->execute([$id]);

$stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
$stmt->execute([$id]);
if ($stmt->execute()) {
  header('Location: ../dashboard_admin.php?view=master&msg=Kategori%20dihapus');
} else {
  header('Location: ../dashboard_admin.php?view=master&msg=Gagal%20menghapus%20kategori');
}
exit;
?>
