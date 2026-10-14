<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_login();

$u = current_user();
if (!$u || $u['role_name'] !== 'admin_utama') {
    header('Location: ../');
    exit;
}

$name = trim($_POST['name'] ?? '');
$sku  = trim($_POST['sku'] ?? '');
$stock = (int)($_POST['stock'] ?? 0);
$price = (float)($_POST['price'] ?? 0);
$category_id = ($_POST['category_id'] !== '') ? (int)$_POST['category_id'] : null;

if ($name === '') {
    header("Location: ../dashboard_admin.php?msg=Nama barang wajib diisi!");
    exit;
}

$conn = get_db_connection();

try {
    $stmt = $conn->prepare("
        INSERT INTO items (name, sku, stock, price, category_id)
        VALUES (:name, :sku, :stock, :price, :category_id)
    ");

    $stmt->execute([
        ':name' => $name,
        ':sku'  => $sku ?: null,
        ':stock' => $stock,
        ':price' => $price,
        ':category_id' => $category_id
    ]);

    header("Location: ../dashboard_admin.php?msg=Barang berhasil ditambahkan");
    exit;

} catch (PDOException $e) {

    // 23505 → Unique Violation (contoh: SKU duplicate)
    if ($e->getCode() == "23505") {
        header("Location: ../dashboard_admin.php?msg=SKU sudah digunakan! Gunakan SKU lain.");
        exit;
    }

    header("Location: ../dashboard_admin.php?msg=Gagal menambah barang");
    exit;
}
