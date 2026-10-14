<?php
// handlers/import_items.php
// Import items CSV / XLSX - Mode: Tambah Stok jika duplikat
// Pastikan includes/db.php memiliki get_db_connection() dan column_exists()

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/xlsx_reader.php'; // file ini boleh ada; jika tidak untuk XLSX akan error
require_login();
if (!has_role(['admin_utama'])) {
    header('Location: ../index.php');
    exit;
}

$conn = get_db_connection();

// --- konfigurasi ---
$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedExt = ['csv', 'xlsx'];
// ---------------------

// Validasi file upload
if (empty($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Tidak ada file yang diunggah.'));
    exit;
}

$origName = $_FILES['csv_file']['name'];
$tmpPath  = $_FILES['csv_file']['tmp_name'];
$size     = (int)($_FILES['csv_file']['size'] ?? 0);
$ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt, true)) {
    header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Format tidak didukung. Gunakan CSV atau XLSX.'));
    exit;
}
if ($size <= 0 || $size > $maxFileSize) {
    header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Ukuran file tidak valid (maks 5MB).'));
    exit;
}

// Simpan salinan file untuk audit
$saveName = 'import_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($origName));
$savedPath = $uploadDir . '/' . $saveName;
if (!move_uploaded_file($tmpPath, $savedPath)) {
    header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Gagal menyimpan file upload.'));
    exit;
}

// Baca file -> $rows (array of arrays)
$rows = [];

if ($ext === 'csv') {
    // Baca CSV. Coba koma dulu; jika satu kolom saja, reparse dengan ';'
    $handle = fopen($savedPath, 'r');
    if ($handle === false) {
        header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Gagal membuka CSV.'));
        exit;
    }
    while (($data = fgetcsv($handle, 0, ',')) !== false) {
        $rows[] = $data;
    }
    fclose($handle);
    if (count($rows) > 0 && is_array($rows[0]) && count($rows[0]) === 1) {
        // kemungkinan delimiter ; (atau csv dibuat dengan ; sebagai pemisah)
        $rows = [];
        $handle = fopen($savedPath, 'r');
        if ($handle !== false) {
            while (($data = fgetcsv($handle, 0, ';')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
    }
} else { // xlsx
    if (!class_exists('ZipArchive')) {
        header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Ekstensi ZipArchive tidak aktif. Aktifkan di php.ini.'));
        exit;
    }
    $rows = xlsx_read_rows($savedPath);
}

// Validasi rows
if (empty($rows) || !is_array($rows) || count($rows) < 1) {
    header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('File kosong atau tidak dapat dibaca.'));
    exit;
}

// Normalize header (baris pertama)
$header = $rows[0];
foreach ($header as $i => $h) {
    // hapus BOM, trim, lowercase
    $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
    $header[$i] = strtolower(trim((string)$h));
}

// Map header to fields
$map = [];
foreach ($header as $i => $col) {
    $col = strtolower(trim((string)$col));
    if (in_array($col, ['name','nama','nama barang'], true)) $map['name'] = $i;
    if (in_array($col, ['sku','kode','kode barang'], true)) $map['sku'] = $i;
    if (in_array($col, ['stock','stok','qty','jumlah'], true)) $map['stock'] = $i;
    if (in_array($col, ['category','kategori'], true)) $map['category'] = $i;
    if (in_array($col, ['price','harga','harga satuan'], true)) $map['price'] = $i;
}

// minimal harus ada kolom name & stock (sku opsional)
if (!isset($map['name']) || !isset($map['stock'])) {
    header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode('Header tidak valid. Minimal harus ada kolom nama & stock.'));
    exit;
}

// Pastikan tabel categories ada (Postgres compatible)
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(200) UNIQUE NOT NULL
        );
    ");
} catch (PDOException $e) {
    // lanjut saja (mungkin privilege), tapi catat
}

// Pastikan kolom category_id ada di items jika akan dipakai
$useCategory = isset($map['category']);
if ($useCategory) {
    if (!column_exists($conn, 'items', 'category_id')) {
        // tambahkan kolom (jika items ada)
        try {
            $conn->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS category_id INTEGER NULL;");
        } catch (PDOException $e) {
            // abaikan error; akan lanjut tanpa category
            $useCategory = false;
        }
    }
    // coba tambahkan FK (jika mungkin)
    // note: menambahkan FK bisa gagal bila tidak punya privilege; non-fatal
    try {
        // create fk only if both tables exist and column exists
        $stmt = $conn->prepare("SELECT to_regclass('public.items') IS NOT NULL as has_items, to_regclass('public.categories') IS NOT NULL as has_categories;");
        $stmt->execute();
        $ok = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($ok) && $ok['has_items'] && $ok['has_categories']) {
            // best-effort, ignore errors
            $conn->exec("ALTER TABLE items ADD CONSTRAINT IF NOT EXISTS fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id);");
        }
    } catch (PDOException $e) { /* ignore */ }
}

// Pastikan kolom price ada di items
$usePrice = isset($map['price']);
if ($usePrice) {
    if (!column_exists($conn, 'items', 'price')) {
        try {
            $conn->exec("ALTER TABLE items ADD COLUMN IF NOT EXISTS price DECIMAL(15,2) NULL;");
        } catch (PDOException $e) {
            // abaikan error; akan lanjut tanpa price
            $usePrice = false;
        }
    }
}

// Start transaction
$conn->beginTransaction();

$inserted = 0;
$updated = 0;
$skipped = 0;
$errors = 0;

try {
    // Prepare statements for category
    $stmtSelectCat = $conn->prepare("SELECT id FROM categories WHERE name = :name LIMIT 1");
    $stmtInsertCat = $conn->prepare("INSERT INTO categories(name) VALUES(:name) RETURNING id");

    // Prepare item-select queries
    if (isset($map['sku'])) {
        $stmtSelectBySku = $conn->prepare("SELECT id, stock FROM items WHERE sku = :sku LIMIT 1");
    }
    $stmtSelectByName = $conn->prepare("SELECT id, stock FROM items WHERE name = :name LIMIT 1");

    // Insert / update statements dengan price
    if ($usePrice) {
        $stmtInsertItem = $conn->prepare("INSERT INTO items(name, sku, stock, category_id, price) VALUES (:name, :sku, :stock, :cat, :price) RETURNING id");
        $stmtInsertItemNoCat = $conn->prepare("INSERT INTO items(name, sku, stock, price) VALUES (:name, :sku, :stock, :price) RETURNING id");
        $stmtUpdateWithCat = $conn->prepare("UPDATE items SET name = :name, sku = :sku, stock = stock + :stock, category_id = :cat, price = :price WHERE id = :id");
        $stmtUpdateNoCat = $conn->prepare("UPDATE items SET name = :name, sku = :sku, stock = stock + :stock, price = :price WHERE id = :id");
    } else {
        $stmtInsertItem = $conn->prepare("INSERT INTO items(name, sku, stock, category_id) VALUES (:name, :sku, :stock, :cat) RETURNING id");
        $stmtInsertItemNoCat = $conn->prepare("INSERT INTO items(name, sku, stock) VALUES (:name, :sku, :stock) RETURNING id");
        $stmtUpdateWithCat = $conn->prepare("UPDATE items SET name = :name, sku = :sku, stock = stock + :stock, category_id = :cat WHERE id = :id");
        $stmtUpdateNoCat = $conn->prepare("UPDATE items SET name = :name, sku = :sku, stock = stock + :stock WHERE id = :id");
    }

    // Iterate rows (skip header)
    for ($r = 1; $r < count($rows); $r++) {
        $row = $rows[$r];
        // tolerate rows with missing columns
        $name = isset($row[$map['name']]) ? trim((string)$row[$map['name']]) : '';
        $sku  = isset($map['sku']) && isset($row[$map['sku']]) ? trim((string)$row[$map['sku']]) : '';
        $stockRaw = isset($row[$map['stock']]) ? trim((string)$row[$map['stock']]) : '';
        // Normalize numeric stock: remove non digits, allow negative? we clamp to >=0
        $stock = (int) preg_replace('/[^\d\-]/', '', ($stockRaw === '' ? '0' : $stockRaw));
        if ($stock < 0) $stock = 0;

        $categoryName = ($useCategory && isset($row[$map['category']])) ? trim((string)$row[$map['category']]) : '';
        
        // Process price
        $price = null;
        if ($usePrice && isset($row[$map['price']])) {
            $priceRaw = trim((string)$row[$map['price']]);
            // Remove currency symbols, commas, and convert to decimal
            $priceRaw = preg_replace('/[^\d.,]/', '', $priceRaw);
            // Replace comma with dot for decimal
            $priceRaw = str_replace(',', '.', $priceRaw);
            if ($priceRaw !== '') {
                $price = (float) $priceRaw;
                // Ensure price is non-negative
                if ($price < 0) $price = 0;
            }
        }

        // skip empty rows
        if ($name === '' && $sku === '') {
            $skipped++;
            continue;
        }
        if ($name === '') {
            // require name if sku empty; but prefer to skip ambiguous rows
            $skipped++;
            continue;
        }

        // Resolve category id
        $catId = null;
        if ($useCategory && $categoryName !== '') {
            // try select
            $stmtSelectCat->execute([':name' => $categoryName]);
            $res = $stmtSelectCat->fetch(PDO::FETCH_ASSOC);
            if ($res && isset($res['id'])) {
                $catId = (int)$res['id'];
            } else {
                // insert category
                try {
                    $stmtInsertCat->execute([':name' => $categoryName]);
                    $catIdRow = $stmtInsertCat->fetch(PDO::FETCH_ASSOC);
                    // For PostgreSQL RETURNING id returns [ 'id' => value ]
                    if ($catIdRow && isset($catIdRow['id'])) {
                        $catId = (int)$catIdRow['id'];
                    } else {
                        // fallback: select again
                        $stmtSelectCat->execute([':name' => $categoryName]);
                        $res2 = $stmtSelectCat->fetch(PDO::FETCH_ASSOC);
                        if ($res2 && isset($res2['id'])) $catId = (int)$res2['id'];
                    }
                } catch (PDOException $e) {
                    // maybe unique race; try select again
                    $stmtSelectCat->execute([':name' => $categoryName]);
                    $res2 = $stmtSelectCat->fetch(PDO::FETCH_ASSOC);
                    if ($res2 && isset($res2['id'])) $catId = (int)$res2['id'];
                }
            }
        }

        // Check existing item (prefer sku)
        $existing = null;
        if ($sku !== '' && isset($stmtSelectBySku)) {
            $stmtSelectBySku->execute([':sku' => $sku]);
            $ex = $stmtSelectBySku->fetch(PDO::FETCH_ASSOC);
            if ($ex && isset($ex['id'])) $existing = (int)$ex['id'];
        }
        if ($existing === null) {
            $stmtSelectByName->execute([':name' => $name]);
            $ex = $stmtSelectByName->fetch(PDO::FETCH_ASSOC);
            if ($ex && isset($ex['id'])) $existing = (int)$ex['id'];
        }

        if ($existing === null) {
            // Insert new item
            try {
                if ($useCategory && $catId !== null) {
                    if ($usePrice) {
                        $stmtInsertItem->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':cat' => $catId,
                            ':price' => $price
                        ]);
                    } else {
                        $stmtInsertItem->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':cat' => $catId
                        ]);
                    }
                } else {
                    if ($usePrice) {
                        $stmtInsertItemNoCat->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':price' => $price
                        ]);
                    } else {
                        $stmtInsertItemNoCat->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock
                        ]);
                    }
                }
                $inserted++;
            } catch (PDOException $e) {
                // log / count error and continue
                $errors++;
                continue;
            }
        } else {
            // Update: tambah stok (stock = stock + new) dan update harga jika ada
            try {
                if ($useCategory && $catId !== null) {
                    if ($usePrice) {
                        $stmtUpdateWithCat->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':cat' => $catId,
                            ':price' => $price,
                            ':id' => $existing
                        ]);
                    } else {
                        $stmtUpdateWithCat->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':cat' => $catId,
                            ':id' => $existing
                        ]);
                    }
                } else {
                    if ($usePrice) {
                        $stmtUpdateNoCat->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':price' => $price,
                            ':id' => $existing
                        ]);
                    } else {
                        $stmtUpdateNoCat->execute([
                            ':name' => $name,
                            ':sku' => $sku === '' ? null : $sku,
                            ':stock' => $stock,
                            ':id' => $existing
                        ]);
                    }
                }
                $updated++;
            } catch (PDOException $e) {
                $errors++;
                continue;
            }
        }
    } // end loop rows

    $conn->commit();
} catch (Exception $ex) {
    $conn->rollBack();
    $errors++;
}

// Redirect summary
$msg = sprintf('Impor selesai: tambah %d, update %d, skip %d, error %d', $inserted, $updated, $skipped, $errors);
header('Location: ../dashboard_admin.php?view=master&msg=' . urlencode($msg));
exit;
