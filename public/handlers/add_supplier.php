<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!has_role(['admin_utama'])) { header('Location: ../index.php'); exit; }

$conn = get_db_connection();
require_once __DIR__ . '/../../includes/db.php';
$needCols = [
  'email' => "ALTER TABLE suppliers ADD COLUMN email VARCHAR(120) NULL",
  'pic_name' => "ALTER TABLE suppliers ADD COLUMN pic_name VARCHAR(120) NULL",
  'pic_title' => "ALTER TABLE suppliers ADD COLUMN pic_title VARCHAR(120) NULL",
  'supply_type' => "ALTER TABLE suppliers ADD COLUMN supply_type VARCHAR(120) NULL",
  'phone' => "ALTER TABLE suppliers ADD COLUMN phone VARCHAR(40) NULL",
  'payment_method' => "ALTER TABLE suppliers ADD COLUMN payment_method VARCHAR(30) NULL",
  'status' => "ALTER TABLE suppliers ADD COLUMN status VARCHAR(20) NULL",
  'address' => "ALTER TABLE suppliers ADD COLUMN address TEXT NULL"
];
foreach ($needCols as $col => $sql) {
  if (!column_exists($conn, 'suppliers', $col)) {
    $conn->exec($sql);
  }
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pic_name = trim($_POST['pic_name'] ?? '');
$supply_type = trim($_POST['supply_type'] ?? '');
$pic_title = trim($_POST['pic_title'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$status = trim($_POST['status'] ?? '');
$address = trim($_POST['address'] ?? '');

$contact = $phone;

if ($name === '') {
  $_SESSION['error'] = 'Nama supplier wajib diisi.';
  header('Location: ../dashboard_admin.php?view=supplier');
  exit;
}

$stmt = $conn->prepare("INSERT INTO suppliers (name, email, pic_name, pic_title, supply_type, phone, payment_method, status, address, contact) VALUES (?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([$name, $email, $pic_name, $pic_title, $supply_type, $phone, $payment_method, $status, $address, $contact]);

$_SESSION['success'] = 'Supplier berhasil ditambahkan.';
header('Location: ../dashboard_admin.php?view=supplier&msg=supplier_added');
?>
