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
    // Mulai transaction
    $conn->beginTransaction();

    // Dapatkan user ID yang sedang login
    $current_user = current_user();
    if (!$current_user) {
        throw new Exception("User tidak ditemukan");
    }
    $current_user_id = $current_user['id'];

    // Ambil data pengambilan barang
    $stmt = $conn->prepare("
        SELECT 
            tk.id, 
            tk.item_id, 
            tk.quantity as qty, 
            i.stock,
            tk.user_id,
            u.name as user_name,
            tk.purpose,
            i.name as item_name
        FROM item_takings tk 
        JOIN items i ON i.id = tk.item_id 
        JOIN users u ON u.id = tk.user_id
        WHERE tk.id = ? AND tk.status = 'pending'
    ");
    $stmt->execute([$taking_id]);
    $taking = $stmt->fetch();

    if (!$taking) {
        throw new Exception("Data pengambilan tidak ditemukan atau sudah diproses");
    }

    // Cek stok cukup
    if ((int)$taking['stock'] < (int)$taking['qty']) {
        throw new Exception("Stok tidak mencukupi. Stok tersedia: " . $taking['stock'] . ", yang diminta: " . $taking['qty']);
    }

    // Update status pengambilan menjadi approved
    $stmt = $conn->prepare("
        UPDATE item_takings 
        SET status = 'approved', 
            approved_by = ?,
            approved_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$current_user_id, $taking_id]);
    
    // Update stok barang
    $stmt = $conn->prepare("UPDATE items SET stock = stock - ? WHERE id = ?");
    $stmt->execute([$taking['qty'], $taking['item_id']]);
    
    // Catat di stock_movements
    $stmt = $conn->prepare("
        INSERT INTO stock_movements 
        (item_id, type, qty, source, ref_id, created_by) 
        VALUES (?, 'out', ?, 'pengambilan_barang', ?, ?)
    ");
    $stmt->execute([
        $taking['item_id'], 
        $taking['qty'], 
        $taking_id,
        $current_user_id
    ]);

    // Commit transaction
    $conn->commit();
    
    $msg = "Pengambilan+barang+berhasil+disetujui";
    
} catch (Exception $e) {
    // Rollback transaction jika ada error
    $conn->rollBack();
    $msg = urlencode($e->getMessage());
}

header('Location: ../dashboard_admin.php?view=pengambilan&msg=' . $msg);
exit;
?>
