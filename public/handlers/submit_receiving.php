<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!has_role(['admin_stok'])) { header('Location: ../index.php'); exit; }

$conn = get_db_connection();

$conn->exec("CREATE TABLE IF NOT EXISTS receivings (
  id SERIAL PRIMARY KEY,
  receiver_id INTEGER NOT NULL,
  receiver_name VARCHAR(120) NOT NULL,
  item_id INTEGER NOT NULL,
  supplier_id INTEGER NOT NULL,
  qty INTEGER NOT NULL,
  unit_price DECIMAL(12,2) DEFAULT 0,
  total_price DECIMAL(12,2) DEFAULT 0,
  payment_method VARCHAR(30),
  status VARCHAR(20),
  note_text VARCHAR(255),
  receiving_date DATE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (receiver_id) REFERENCES users(id),
  FOREIGN KEY (item_id) REFERENCES items(id),
  FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
)");

$receiver_id = current_user()['id'];
$receiver_name = trim($_POST['receiver_name'] ?? '');
$item_id = intval($_POST['item_id'] ?? 0);
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$qty = max(1, intval($_POST['qty'] ?? 0));
$unit_price = floatval($_POST['unit_price'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$status = trim($_POST['status'] ?? 'received');
$note_text = trim($_POST['note_text'] ?? '');
$receiving_date = trim($_POST['receiving_date'] ?? '');
if ($receiving_date === '') { $receiving_date = date('Y-m-d'); }

if ($receiver_name === '' || !$item_id || !$supplier_id || !$qty) {
  $_SESSION['error'] = 'Lengkapi Nama Penerima, Barang, Supplier, dan Jumlah.';
  header('Location: ../dashboard_stock.php?view=receiving');
  exit;
}

$total = round($qty * $unit_price, 2);

$stmt = $conn->prepare("INSERT INTO receivings (receiver_id, receiver_name, item_id, supplier_id, qty, unit_price, total_price, payment_method, status, note_text, receiving_date) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([$receiver_id, $receiver_name, $item_id, $supplier_id, $qty, $unit_price, $total, $payment_method, $status, $note_text, $receiving_date]);
$recId = $conn->lastInsertId('receivings_id_seq');

$stmt = $conn->prepare("UPDATE items SET stock = stock + ? WHERE id = ?");
$stmt->execute([$qty, $item_id]);
$stmt = $conn->prepare("INSERT INTO stock_movements (item_id, type, qty, source, ref_id) VALUES (?, 'in', ?, 'receiving', ?)");
$stmt->execute([$item_id, $qty, $recId]);

$_SESSION['success'] = 'Penerimaan barang berhasil dicatat.';
header('Location: ../dashboard_stock.php?view=receiving');
exit;
?>
