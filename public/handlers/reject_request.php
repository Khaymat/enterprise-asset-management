<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!has_role(['admin_utama'])) { header('Location: ../index.php'); exit; }

$conn = get_db_connection();
$req_id = intval($_POST['req_id'] ?? 0);
$stmt = $conn->prepare("UPDATE requests SET status='rejected' WHERE id = ?");
$stmt->execute([$req_id]);
header('Location: ../dashboard_admin.php?view=pengajuan&msg=request_rejected');
?>
