<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
if (!has_role(['admin_utama','admin_stok'])) { header('Location: ../index.php'); exit; }

$conn = get_db_connection();
$movement_id = intval($_GET['movement_id'] ?? 0);
if ($movement_id <= 0) { http_response_code(400); echo 'Bad request'; exit; }

$conn->exec("CREATE TABLE IF NOT EXISTS stock_movements_proofs (
  id SERIAL PRIMARY KEY,
  movement_id INTEGER NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_movement_proof_view FOREIGN KEY (movement_id) REFERENCES stock_movements(id)
)");

$stmt = $conn->prepare("SELECT file_path FROM stock_movements_proofs WHERE movement_id = ? ORDER BY id DESC LIMIT 1");
if (!$stmt) { http_response_code(500); echo 'Server error'; exit; }
$stmt->execute([$movement_id]);
$row = $stmt->fetch();
$file_path = $row ? $row['file_path'] : null;
$found = $row !== false;

if (!$found || empty($file_path)) { http_response_code(404); echo 'Bukti tidak ditemukan'; exit; }

$abs = dirname(__DIR__) . '/' . $file_path;
if (!is_file($abs)) { http_response_code(404); echo 'File tidak tersedia'; exit; }

$ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
$types = [
  'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'pdf' => 'application/pdf'
];
$ctype = $types[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $ctype);
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: inline; filename="' . basename($abs) . '"');
readfile($abs);
exit;
?>
