<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();
if (!has_role(['admin_utama'])) { header('Location: ../index.php'); exit; }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: ../dashboard_admin.php?view=reimbursement&msg=bad_request'); exit; }

$conn = get_db_connection();
$stmt = $conn->prepare("UPDATE reimbursements SET status='approved' WHERE id=?");
if ($stmt) {
  $stmt->execute([$id]);
}
header('Location: ../dashboard_admin.php?view=reimbursement&msg=reimbursement_approved');
exit;
?>
