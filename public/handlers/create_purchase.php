<?php
// handlers/create_purchase.php
// Pastikan file ini berada di luar webroot dan dipanggil melalui wrapper di public/

require_once __DIR__ . '/../../includes/auth.php';
require_login();
if (!has_role(['admin_utama'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php'; // harus menyediakan get_db_connection(), column_exists(), dsb
$conn = get_db_connection();

// Pastikan UPLOAD_DIR terdefinisi (sesuaikan kalau Anda mendefinisikannya di includes/config.php)
if (!defined('UPLOAD_DIR')) {
    // Default: simpan ke public/uploads
    define('UPLOAD_DIR', __DIR__ . '/../uploads');
}
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}

// Pastikan tabel purchases memiliki kolom tambahan yang kita pakai
$ensureCols = [
  'purchase_code' => 'VARCHAR(60)',
  'purchase_date' => 'DATE',
  'unit_price' => 'DECIMAL(12,2)',
  'payment_method' => 'VARCHAR(30)',
  'status' => 'VARCHAR(20)',
  'note_text' => 'VARCHAR(255)',
  'item_name' => 'VARCHAR(255)'
];
foreach ($ensureCols as $col => $type) {
  if (!column_exists($conn, 'purchases', $col)) {
    $conn->exec("ALTER TABLE purchases ADD COLUMN $col $type NULL");
  }
}

// ambil input (aman)
$admin_id = current_user()['id'] ?? null;
$item_id = isset($_POST['item_id']) && $_POST['item_id'] !== '' ? intval($_POST['item_id']) : 0;
$item_name = trim($_POST['item_name'] ?? '');
$supplier_id = isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '' ? intval($_POST['supplier_id']) : 0;
$supplier_name = trim($_POST['supplier_name'] ?? '');
$qty = max(1, intval($_POST['qty'] ?? 0));
$unit_price = floatval($_POST['unit_price'] ?? 0.0);
$purchase_code = trim($_POST['purchase_code'] ?? '');
$purchase_date = trim($_POST['purchase_date'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? '');
$status = trim($_POST['status'] ?? '');
$note_text = trim($_POST['note_text'] ?? '');

if ($purchase_code === '') {
    $purchase_code = 'PO-' . date('Ymd') . '-' . rand(1000,9999);
}
if ($purchase_date === '') {
    $purchase_date = date('Y-m-d');
}
$total = round($unit_price * $qty, 2);

// handle file upload (opsional)
$file_path = null;
if (!empty($_FILES['note_file']['name'])) {
    $ext = pathinfo($_FILES['note_file']['name'], PATHINFO_EXTENSION);
    $filename = 'purchase_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $dest = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (move_uploaded_file($_FILES['note_file']['tmp_name'], $dest)) {
        // simpan path relatif ke public agar bisa diakses
        $file_path = 'uploads/' . $filename;
    }
}

// Start transaction
try {
    $conn->beginTransaction();

    // ---------- supplier handling ----------
    // If supplier_id provided, check existence; otherwise try supplier_name; if not found, create new.
    if ($supplier_id > 0) {
        $chk = $conn->prepare("SELECT id FROM suppliers WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $supplier_id]);
        if (!$chk->fetch()) {
            // not found, reset supplier_id to 0 to fallback to name
            $supplier_id = 0;
        }
    }

    if ($supplier_id <= 0) {
        if ($supplier_name !== '') {
            $chk = $conn->prepare("SELECT id FROM suppliers WHERE name = :name LIMIT 1");
            $chk->execute([':name' => $supplier_name]);
            if ($row = $chk->fetch(PDO::FETCH_ASSOC)) {
                $supplier_id = (int)$row['id'];
            } else {
                // create supplier and get id (Postgres RETURNING)
                $ins = $conn->prepare("INSERT INTO suppliers (name) VALUES (:name) RETURNING id");
                $ins->execute([':name' => $supplier_name]);
                $supplier_id = (int)$ins->fetchColumn();
            }
        } else {
            throw new Exception('Supplier tidak valid. Pilih supplier dari daftar atau isi nama supplier.');
        }
    }

    // ---------- item handling ----------
    if ($item_id > 0) {
        $chk = $conn->prepare("SELECT id FROM items WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $item_id]);
        if (!$chk->fetch()) {
            $item_id = 0;
        }
    }

    if ($item_id <= 0) {
        if ($item_name !== '') {
            $chk = $conn->prepare("SELECT id FROM items WHERE name = :name LIMIT 1");
            $chk->execute([':name' => $item_name]);
            if ($row = $chk->fetch(PDO::FETCH_ASSOC)) {
                $item_id = (int)$row['id'];
            } else {
                // create item with stock 0 (sesuaikan kolom items Anda jika berbeda)
                $ins = $conn->prepare("INSERT INTO items (name, stock) VALUES (:name, 0) RETURNING id");
                $ins->execute([':name' => $item_name]);
                $item_id = (int)$ins->fetchColumn();
            }
        } else {
            throw new Exception('Item tidak valid. Pilih item atau isi nama item.');
        }
    }

    // Pastikan id valid sebelum lanjut
    if ($supplier_id <= 0) {
        throw new Exception('Supplier tidak valid setelah proses lookup/creation.');
    }
    if ($item_id <= 0) {
        throw new Exception('Item tidak valid setelah proses lookup/creation.');
    }

    // ---------- insert purchase (gunakan RETURNING id agar aman di Postgres) ----------
    $insPurchase = $conn->prepare("
      INSERT INTO purchases
        (admin_id, item_id, supplier_id, qty, price_total, note_file, purchase_code, purchase_date, unit_price, payment_method, status, note_text, item_name)
      VALUES
        (:admin_id, :item_id, :supplier_id, :qty, :price_total, :note_file, :purchase_code, :purchase_date, :unit_price, :payment_method, :status, :note_text, :item_name)
      RETURNING id
    ");
    $insPurchase->execute([
        ':admin_id' => $admin_id,
        ':item_id' => $item_id,
        ':supplier_id' => $supplier_id,
        ':qty' => $qty,
        ':price_total' => $total,
        ':note_file' => $file_path,
        ':purchase_code' => $purchase_code,
        ':purchase_date' => $purchase_date,
        ':unit_price' => $unit_price,
        ':payment_method' => $payment_method,
        ':status' => $status,
        ':note_text' => $note_text,
        ':item_name' => $item_name
    ]);
    $purchase_id = (int)$insPurchase->fetchColumn();

    if (!$purchase_id) {
        throw new Exception('Gagal membuat record pembelian.');
    }

    // ---------- update items.stock dan insert stock_movements (HANYA jika item_id valid) ----------
    if ($item_id > 0 && $qty > 0) {
        // Update stock
        $upd = $conn->prepare("UPDATE items SET stock = COALESCE(stock,0) + :qty WHERE id = :id");
        $upd->execute([':qty' => $qty, ':id' => $item_id]);

        // Insert into stock_movements with explicit columns
        $insMove = $conn->prepare("
            INSERT INTO stock_movements (item_id, type, qty, source, ref_id, created_at)
            VALUES (:item_id, 'in', :qty, 'purchase', :ref_id, now())
        ");
        $insMove->execute([
            ':item_id' => $item_id,
            ':qty' => $qty,
            ':ref_id' => $purchase_id
        ]);
    } else {
        // Jika ada situasi tidak boleh update stok, pertimbangkan throw. Di sini kita throw untuk konsistensi.
        throw new Exception('Tidak dapat memperbarui stok: item_id atau qty tidak valid.');
    }

    // commit jika semua sukses
    $conn->commit();

    // redirect sukses (sesuaikan path)
    header('Location: ../dashboard_admin.php?view=pembelian&msg=purchase_done');
    exit;

} catch (Exception $e) {
    // rollback jika in transaction
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Tampilkan pesan error untuk debugging / development.
    // Untuk production, sebaiknya log dan redirect ke halaman error user-friendly.
    http_response_code(500);
    echo 'Gagal menyimpan pembelian: ' . htmlspecialchars($e->getMessage());
    exit;
}
