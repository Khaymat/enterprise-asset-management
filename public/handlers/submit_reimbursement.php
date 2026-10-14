<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/config.php';

require_login();

if (!has_role(['user'])) {
    header('Location: ../index.php');
    exit;
}

$conn = get_db_connection();

$item_desc = trim($_POST['description'] ?? '');
$qty = intval($_POST['qty'] ?? 0);
$unit_price = floatval($_POST['unit_price'] ?? 0);
$notes = trim($_POST['notes'] ?? '');
$date = trim($_POST['reimburse_date'] ?? '');
$department = trim($_POST['department'] ?? '');
$user_id = current_user()['id'];

// Validasi sederhana: department wajib (form Anda sebelumnya menandai required)
if ($department === '') {
    header('Location: ../dashboard_user.php?view=reimburse&msg=' . urlencode('Departemen harus diisi.'));
    exit;
}

// Gabungkan deskripsi + catatan agar tetap tersimpan tanpa ubah skema
$desc = $item_desc;
if ($notes !== '') {
    $desc .= ' — Catatan: ' . $notes;
}

// Hitung total
$amount = ($qty > 0 && $unit_price > 0)
    ? ($qty * $unit_price)
    : floatval($_POST['amount'] ?? 0);

// === Upload file (lebih aman) ===
$receipt_path = null;
if (!empty($_FILES['receipt']['name'])) {

    $allowed_ext = ['jpg','jpeg','png','pdf'];
    $orig = $_FILES['receipt']['name'];
    $tmp = $_FILES['receipt']['tmp_name'];
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed_ext, true)) {
        $safe = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($orig));
        $unique = 'receipt_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '_' . $safe;
        $dest = UPLOAD_DIR . '/' . $unique;
        
        if (move_uploaded_file($tmp, $dest)) {
            // simpan path relatif agar consistent dengan aplikasi Anda
            $receipt_path = 'uploads/' . $unique;
        }
    }
}

// Jika tanggal kosong → pakai tanggal sekarang
if ($date === '') {
    $date = date('Y-m-d');
}
$created_at = $date . ' 00:00:00';

// === INSERT ke database ===
// Pastikan tabel reimbursements punya kolom `department` (lihat SQL di bawah)
$stmt = $conn->prepare("
    INSERT INTO reimbursements
    (user_id, description, amount, receipt_file, department, status, created_at)
    VALUES (?, ?, ?, ?, ?, 'pending', ?)
");

try {
    $stmt->execute([
        $user_id,
        $desc,
        $amount,
        $receipt_path,
        $department,
        $created_at
    ]);

    header('Location: ../dashboard_user.php?view=reimburse&msg=' . urlencode('reimbursement_terkirim'));
    exit;
} catch (PDOException $e) {
    // Anda bisa mencatat error ke log file jika perlu
    header('Location: ../dashboard_user.php?view=reimburse&msg=' . urlencode('Gagal menyimpan pengajuan.'));
    exit;
}
