<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect ke halaman yang sesuai berdasarkan role - PERBAIKI PATH
    if ($user['role_name'] === 'admin_utama') {
        header('Location: ../dashboard_admin.php?view=pengambilan');
    } else {
        header('Location: ../dashboard_user.php?view=pengambilan');
    }
    exit;
}

$conn = get_db_connection();

try {
    // Validasi input
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['qty'];
    $purpose = trim($_POST['purpose']);
    $department = trim($_POST['department']);
    $pickup_date = $_POST['pickup_date'];
    $notes = trim($_POST['notes'] ?? '');

    if ($item_id <= 0 || $quantity <= 0 || empty($purpose) || empty($department) || empty($pickup_date)) {
        throw new Exception("Data yang diisi tidak lengkap atau tidak valid");
    }

    // Cek stok tersedia
    $stmt = $conn->prepare("SELECT name, stock FROM items WHERE id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        throw new Exception("Barang tidak ditemukan");
    }

    if ($item['stock'] < $quantity) {
        throw new Exception("Stok tidak mencukupi. Stok tersedia: " . $item['stock']);
    }

    // Handle file upload
    $proof_file = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/pickup_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
        $filename = 'pickup_' . time() . '_' . $user['id'] . '.' . $file_extension;
        $file_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['proof']['tmp_name'], $file_path)) {
            $proof_file = 'uploads/pickup_proofs/' . $filename;
        }
    }

    // Simpan ke database dengan status pending
    $stmt = $conn->prepare("
        INSERT INTO item_takings 
        (user_id, item_id, quantity, purpose, department, taking_date, notes, proof_file, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
    ");
    
    $stmt->execute([
        $user['id'],
        $item_id,
        $quantity,
        $purpose,
        $department,
        $pickup_date,
        $notes,
        $proof_file
    ]);

    // Redirect berdasarkan role user - PERBAIKI PATH
    if ($user['role_name'] === 'admin_utama') {
        header('Location: ../dashboard_admin.php?view=pengambilan&msg=Pengajuan+pengambilan+barang+berhasil+dikirim.+Menunggu+persetujuan+admin.');
    } else {
        header('Location: ../dashboard_user.php?view=pengambilan&msg=Pengajuan+pengambilan+barang+berhasil+dikirim.+Menunggu+persetujuan+admin.');
    }
    
} catch (Exception $e) {
    // Redirect dengan error message berdasarkan role - PERBAIKI PATH
    if ($user['role_name'] === 'admin_utama') {
        header('Location: ../dashboard_admin.php?view=pengambilan&msg=' . urlencode($e->getMessage()));
    } else {
        header('Location: ../dashboard_user.php?view=pengambilan&msg=' . urlencode($e->getMessage()));
    }
}
exit;
?>
