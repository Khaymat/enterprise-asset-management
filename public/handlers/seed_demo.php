<?php
require_once __DIR__ . '/../../includes/db.php';

$conn = get_db_connection();
$conn->beginTransaction();

try {
    $conn->exec("INSERT INTO roles (id, name) VALUES (1,'admin_utama'),(2,'admin_stok'),(3,'atasan'),(4,'user') ON CONFLICT (id) DO NOTHING");

    $conn->exec("INSERT INTO company_settings (id, name, logo_path) VALUES (1, 'PT Contoh Sejahtera', NULL) ON CONFLICT (id) DO NOTHING");

    $users = [
        ['Admin Utama','admin@demo.com', 1],
        ['Admin Stok','stok@demo.com', 2],
        ['Atasan','atasan@demo.com', 3],
        ['User','user@demo.com', 4],
    ];
    $pwd = password_hash('password', PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (id, name,email,password_hash,role_id) VALUES (DEFAULT,?,?,?,?) ON CONFLICT (email) DO NOTHING");
    foreach ($users as $u) {
        $stmt->execute([$u[0], $u[1], $pwd, $u[2]]);
    }

    $stmt2 = $conn->prepare("UPDATE users SET password_hash=? WHERE email IN ('admin@demo.com','stok@demo.com','atasan@demo.com','user@demo.com')");
    $stmt2->execute([$pwd]);

    $stmt = $conn->prepare("UPDATE users SET role_id=? WHERE email=?");
    $stmt->execute([1, 'admin@demo.com']);
    $stmt->execute([2, 'stok@demo.com']);
    $stmt->execute([3, 'atasan@demo.com']);
    $stmt->execute([4, 'user@demo.com']);

    $conn->exec("INSERT INTO items (id,name,sku,stock) VALUES (1,'Laptop','IT-001',5),(2,'Mouse','IT-002',20),(3,'Keyboard','IT-003',10) ON CONFLICT (id) DO NOTHING");

    $conn->exec("INSERT INTO suppliers (id,name,contact) VALUES (1,'PT Sumber Teknologi','0812-3456-7890') ON CONFLICT (id) DO NOTHING");

    $conn->commit();
    $msg = 'Seeding demo berhasil. Akun: admin@demo.com / password, dst.';
} catch (Throwable $e) {
    $conn->rollBack();
    $msg = 'Seeding gagal: ' . $e->getMessage();
}

header('Content-Type: text/plain; charset=utf-8');
echo $msg;
?>
