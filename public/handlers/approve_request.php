<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!has_role(['admin_utama'])) { header('Location: ../index.php'); exit; }

$conn = get_db_connection();
$req_id = intval($_POST['req_id'] ?? 0);

// Ambil request dan stok
$stmt = $conn->prepare("SELECT r.id, r.item_id, r.qty, i.stock FROM requests r JOIN items i ON i.id=r.item_id WHERE r.id = ?");
$stmt->execute([$req_id]);
$row = $stmt->fetch();
if ($row) {
    $stmt = $conn->prepare("UPDATE requests SET status='approved' WHERE id = ?");
    $stmt->execute([$req_id]);
    if ((int)$row['stock'] >= (int)$row['qty']) {
        $stmt = $conn->prepare("UPDATE items SET stock=stock-? WHERE id = ?");
        $stmt->execute([$row['qty'], $row['item_id']]);
        $stmt = $conn->prepare("INSERT INTO stock_movements (item_id, type, qty, source, ref_id) VALUES (?, 'out', ?, 'request', ?)");
        $stmt->execute([$row['item_id'], $row['qty'], $req_id]);
    }
}
header('Location: ../dashboard_admin.php?view=pengajuan&msg=request_approved');
?>
