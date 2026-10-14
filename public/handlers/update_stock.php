<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php'; // PASTIKAN ini di-include sebelum get_db_connection()
require_login();
if (!has_role(['admin_utama','admin_stok'])) {
    header('Location: ../index.php');
    exit;
}

$conn = get_db_connection(); // sekarang function tersedia

// ambil input
$item_id = intval($_POST['item_id'] ?? 0);
$item_code = trim($_POST['item_code'] ?? '');
$item_name = trim($_POST['item_name'] ?? '');
$stock_in = intval($_POST['stock_in'] ?? 0);
$received_date = trim($_POST['received_date'] ?? ''); // ekspektasi dd/mm/yyyy atau kosong
$note_text = trim($_POST['note_text'] ?? '');

// helper untuk konversi dd/mm/yyyy -> Y-m-d H:i:s
function convert_to_datetime($d) {
    if (!$d) return null;
    // terima juga format yyyy-mm-dd atau yyyy-mm-dd H:i:s
    if (preg_match('/^\d{4}-\d{2}-\d{2}(?:\s+\d{2}:\d{2}:\d{2})?$/', $d)) {
        // sudah dalam format yang bisa diterima (untuk safety tambahkan waktu jika perlu)
        if (strlen($d) === 10) {
            return $d . ' 00:00:00';
        }
        return $d;
    }
    $p = preg_split('/[\/\-\.]/', $d);
    if (count($p) === 3) {
        // asumsikan dd/mm/yyyy
        $day = str_pad($p[0], 2, '0', STR_PAD_LEFT);
        $mon = str_pad($p[1], 2, '0', STR_PAD_LEFT);
        $yr  = $p[2];
        // jika tahun 2-digit -> tidak ditangani di sini
        return sprintf('%04d-%02d-%02d %s', $yr, $mon, $day, date('H:i:s'));
    }
    return null;
}

// deteksi item_id berdasarkan kode/nama jika belum ada
$use_in_form = ($stock_in > 0) || ($received_date !== '') || ($note_text !== '') || ($item_code !== '') || ($item_name !== '');

if (!$item_id) {
    $codeNorm = strtoupper(trim($item_code));
    $nameNorm = trim($item_name);

    if ($codeNorm !== '') {
        $stmt = $conn->prepare("SELECT id FROM items WHERE UPPER(sku) = ? LIMIT 1");
        if ($stmt) {
            $stmt->execute([$codeNorm]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $item_id = intval($row['id']); }
        }
    }
    if (!$item_id && $codeNorm !== '') {
        $like = "%$codeNorm%";
        $stmt = $conn->prepare("SELECT id FROM items WHERE UPPER(sku) LIKE ? LIMIT 2");
        if ($stmt) {
            $stmt->execute([$like]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) === 1) {
                $item_id = intval($rows[0]['id']);
            }
        }
    }
    if (!$item_id && $nameNorm !== '') {
        $stmt = $conn->prepare("SELECT id FROM items WHERE name = ? LIMIT 1");
        if ($stmt) {
            $stmt->execute([$nameNorm]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) { $item_id = intval($row['id']); }
        }
    }
    if (!$item_id && $nameNorm !== '') {
        $likeName = "%" . $nameNorm . "%";
        $stmt = $conn->prepare("SELECT id FROM items WHERE name LIKE ? LIMIT 2");
        if ($stmt) {
            $stmt->execute([$likeName]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) === 1) {
                $item_id = intval($rows[0]['id']);
            }
        }
    }
}

// fallback untuk delta (tetap kompatibel)
$delta = intval($_POST['delta'] ?? 0);
if ($use_in_form) {
    $type = 'in';
    $qty = max(1, abs($stock_in));
    $delta = $qty;
} else {
    $type = $delta >= 0 ? 'in' : 'out';
    $qty = abs($delta);
}

if ($item_id <= 0 || $qty <= 0) {
    $_SESSION['error'] = 'Barang tidak ditemukan/unik. Mohon isi kode atau nama lengkap.';
    $u = current_user();
    $redirect = ($u && (($u['role_name'] ?? '') === 'admin_stok')) ? '../dashboard_stock.php?view=adjust' : '../dashboard_admin.php?view=adjust';
    header('Location: ' . $redirect);
    exit;
}

// handling upload file (sama seperti sebelumnya)
$uploaded_path = null;
if (!empty($_FILES['proof_file']) && isset($_FILES['proof_file']['error']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
    $allowed_ext = ['jpg','jpeg','png','pdf'];
    $orig_name = $_FILES['proof_file']['name'];
    $tmp = $_FILES['proof_file']['tmp_name'];
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (in_array($ext, $allowed_ext, true)) {
        $safe_name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($orig_name));
        $unique = date('Ymd_His') . '_' . mt_rand(1000,9999) . '_' . $safe_name;
        $upload_dir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $dest = $upload_dir . $unique;
        if (@move_uploaded_file($tmp, $dest)) {
            $uploaded_path = 'uploads/' . $unique;
        }
    }
}

// pastikan kolom note_text ada (fungsi column_exists ada di includes/db.php)
if (!column_exists($conn, 'stock_movements', 'note_text')) {
    $conn->exec("ALTER TABLE stock_movements ADD COLUMN note_text VARCHAR(255) NULL");
}

// mulai transaksi
try {
    $conn->beginTransaction();

    // 1) update stok
    $stmt = $conn->prepare("UPDATE items SET stock = stock + :qty WHERE id = :id");
    $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
    $stmt->bindValue(':id', $item_id, PDO::PARAM_INT);
    $stmt->execute();

    // 2) siapkan created_at yang valid
    $converted = convert_to_datetime($received_date);
    // jika kosong, gunakan current timestamp DB atau PHP
    $movement_datetime = $converted ?: date('Y-m-d H:i:s');

    // 3) insert ke stock_movements (selalu lakukan insert, jangan cabang yang membuat tidak ter-insert)
    $stmt = $conn->prepare("
        INSERT INTO stock_movements (item_id, type, qty, source, created_at, note_text)
        VALUES (:item_id, :type, :qty, :source, :created_at, :note_text)
    ");
    $stmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);
    $stmt->bindValue(':type', $type);
    $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
    $stmt->bindValue(':source', 'manual');
    $stmt->bindValue(':created_at', $movement_datetime);
    $stmt->bindValue(':note_text', $note_text !== '' ? $note_text : null);
    $stmt->execute();

    // 4) ambil id movement
    // Jika menggunakan Postgres dan sequence bernama 'stock_movements_id_seq', lastInsertId('stock_movements_id_seq') bisa dipanggil.
    // Di sini kita coba tanpa argumen; jika perlu, ganti sesuai DB kamu.
    $movement_id = (int) $conn->lastInsertId('stock_movements_id_seq');

    // 5) simpan proof jika ada
    if ($uploaded_path && $movement_id > 0) {
        // buat tabel proofs jika belum ada (safe)
        $conn->exec("CREATE TABLE IF NOT EXISTS stock_movements_proofs (
            id SERIAL PRIMARY KEY,
            movement_id INTEGER NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $stmt = $conn->prepare("INSERT INTO stock_movements_proofs (movement_id, file_path) VALUES (:movement_id, :file_path)");
        $stmt->bindValue(':movement_id', $movement_id, PDO::PARAM_INT);
        $stmt->bindValue(':file_path', $uploaded_path);
        $stmt->execute();
    }

    $conn->commit();

} catch (Exception $e) {
    // rollback dan log error untuk debug
    try { $conn->rollBack(); } catch (Exception $_) {}
    error_log('Error update stok: ' . $e->getMessage());
    // (opsional) simpan pesan error di session untuk ditampilkan di UI
    $_SESSION['error'] = 'Gagal menyimpan update stok: ' . $e->getMessage();
    $u = current_user();
    $redirect = ($u && (($u['role_name'] ?? '') === 'admin_stok')) ? '../dashboard_stock.php?view=adjust' : '../dashboard_admin.php?view=adjust';
    header('Location: ' . $redirect);
    exit;
}

// redirect sukses
$u = current_user();
if ($u && ($u['role_name'] ?? '') === 'admin_stok') {
    $msg = $uploaded_path ? 'stock_updated_bukti_tersimpan' : 'stock_updated';
    header('Location: ../dashboard_stock.php?view=adjust&msg=' . $msg);
} else {
    $msg = $uploaded_path ? 'stock_updated_bukti_tersimpan' : 'stock_updated';
    header('Location: ../dashboard_admin.php?view=adjust&msg=' . $msg);
}
exit;
