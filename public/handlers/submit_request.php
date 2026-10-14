<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();

if (!has_role(['user'])) {
    header('Location: ../index.php');
    exit;
}

$conn = get_db_connection();

// === AMBIL INPUT ===
$item_id = intval($_POST['item_id'] ?? 0);
$qty = intval($_POST['qty'] ?? 0);
$note = trim($_POST['note'] ?? '');
$user_id = current_user()['id'];
$department = trim($_POST['department'] ?? '');
$req_date = trim($_POST['request_date'] ?? '');
$status = 'pending'; // force pending

// Jika tanggal kosong
if ($req_date === '') {
    $req_date = date('Y-m-d');
}

// === FUNGSI CEK KOLOM (POSTGRESQL) ===
function column_exists_pg(PDO $conn, string $table, string $column): bool {
    $stmt = $conn->prepare("
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = ? AND column_name = ?
    ");
    $stmt->execute([$table, $column]);
    return ($stmt->fetchColumn() !== false);
}

// === ALTER TABLE jika kolom belum ada ===
$alterCols = [
    'department'   => "ALTER TABLE requests ADD COLUMN department VARCHAR(120)",
    'request_date' => "ALTER TABLE requests ADD COLUMN request_date DATE",
    'photo_file'   => "ALTER TABLE requests ADD COLUMN photo_file VARCHAR(255)"
];

foreach ($alterCols as $col => $sql) {
    if (!column_exists_pg($conn, 'requests', $col)) {
        try { $conn->exec($sql); } catch(Exception $e) {}
    }
}

// === UPLOAD FILE ===
$photo_path = null;
if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {

    $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
    $orig = $_FILES['photo']['name'];
    $tmp = $_FILES['photo']['tmp_name'];
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed_ext, true)) {
        $safe = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($orig));
        $unique = 'req_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '_' . $safe;
        $dest = UPLOAD_DIR . '/' . $unique;

        if (move_uploaded_file($tmp, $dest)) {
            $photo_path = 'uploads/' . $unique;
        }
    }
}

// === INSERT REQUEST ===
$stmt = $conn->prepare("
    INSERT INTO requests (user_id, item_id, qty, note, status, request_date, department, photo_file)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $user_id,
    $item_id,
    $qty,
    $note,
    $status,
    $req_date,
    $department,
    $photo_path
]);

header('Location: ../dashboard_user.php?view=ajukan&msg=pengajuan_terkirim');
exit;
