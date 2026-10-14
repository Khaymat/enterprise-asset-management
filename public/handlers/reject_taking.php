<?php
require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!has_role(['admin_utama'])) { 
    header('Location: ../index.php'); 
    exit; 
}

$conn = get_db_connection();
$taking_id = intval($_POST['taking_id'] ?? 0);

try {
    // Dapatkan user ID yang sedang login
    $current_user = current_user();
    if (!$current_user) {
        throw new Exception("User tidak ditemukan");
    }
    $current_user_id = $current_user['id'];

    // Update status pengambilan menjadi rejected
    $stmt = $conn->prepare("
        UPDATE item_takings 
        SET status = 'rejected', 
            approved_by = ?,
            approved_at = CURRENT_TIMESTAMP 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->execute([$current_user_id, $taking_id]);

    if ($stmt->rowCount() > 0) {
        $msg = "Pengambilan+barang+berhasil+ditolak";
    } else {
        $msg = "Gagal+menolak+pengambilan+barang+atau+sudah+diproses";
    }
    
} catch (Exception $e) {
    $msg = urlencode($e->getMessage());
}

header('Location: ../dashboard_admin.php?view=pengambilan&msg=' . $msg);
exit;
?>
