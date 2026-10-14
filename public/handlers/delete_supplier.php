<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
$user = current_user();
if (!$user || $user['role_name'] !== 'admin_utama') {
  header('Location: ../index.php');
  exit;
}

$conn = get_db_connection();
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
  header('Location: ../dashboard_admin.php?view=supplier&msg=ID_supplier_tidak_valid');
  exit;
}

try {
  $stmt = $conn->prepare("DELETE FROM suppliers WHERE id=? LIMIT 1");
  $stmt->execute([$id]);
  $affected = $stmt->rowCount();
  if ($affected > 0) {
    header('Location: ../dashboard_admin.php?view=supplier&msg=Supplier_berhasil_dihapus');
  } else {
    header('Location: ../dashboard_admin.php?view=supplier&msg=Supplier_tidak_ditemukan');
  }
} catch (Throwable $e) {
  // Kemungkinan gagal karena foreign key (dipakai di purchases)
  header('Location: ../dashboard_admin.php?view=supplier&msg=Gagal_menghapus_supplier:_terkait_transaksi');
}
exit;
?>
