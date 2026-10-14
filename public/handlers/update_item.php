<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
$u = current_user();
if (!$u || $u['role_name'] !== 'admin_utama') { header('Location: ../'); exit; }

$id    = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name  = isset($_POST['name']) ? trim($_POST['name']) : '';
$sku   = isset($_POST['sku']) ? trim($_POST['sku']) : '';
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' 
               ? (int)$_POST['category_id'] : null;

if ($id <= 0 || $name === '') {
  header('Location: ../dashboard_admin.php?view=master&msg=Data%20barang%20tidak%20valid');
  exit;
}

$conn = get_db_connection();

// Update baris data
if ($category_id === null) {
  $stmt = $conn->prepare("
    UPDATE items 
    SET name=?, sku=?, stock=?, price=?, category_id=NULL 
    WHERE id=?
  ");
  $ok = $stmt->execute([$name, $sku, $stock, $price, $id]);
} else {
  $stmt = $conn->prepare("
    UPDATE items 
    SET name=?, sku=?, stock=?, price=?, category_id=? 
    WHERE id=?
  ");
  $ok = $stmt->execute([$name, $sku, $stock, $price, $category_id, $id]);
}

if ($ok) {
  header('Location: ../dashboard_admin.php?view=master&msg=Barang%20diperbarui');
} else {
  header('Location: ../dashboard_admin.php?view=master&msg=Gagal%20memperbarui%20barang');
}
exit;
