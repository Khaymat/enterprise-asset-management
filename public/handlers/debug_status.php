<?php
require_once __DIR__ . '/../../includes/db.php';

$conn = get_db_connection();
$out = [];

// Cek jumlah data
$stmt = $conn->query("SELECT COUNT(*) AS c FROM roles");
$r = $stmt ? $stmt->fetch() : null;
$stmt = $conn->query("SELECT COUNT(*) AS c FROM users");
$u = $stmt ? $stmt->fetch() : null;
$stmt = $conn->query("SELECT COUNT(*) AS c FROM items");
$i = $stmt ? $stmt->fetch() : null;
$out['counts'] = [
    'roles' => $r ? (int)$r['c'] : 0,
    'users' => $u ? (int)$u['c'] : 0,
    'items' => $i ? (int)$i['c'] : 0,
];

// Verifikasi akun demo
$demoEmails = ['admin@demo.com','stok@demo.com','atasan@demo.com','user@demo.com'];
$out['demo'] = [];
foreach ($demoEmails as $email) {
    $stmt = $conn->prepare("SELECT email, role_id, password_hash FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if ($row) {
        $out['demo'][] = [
            'email' => $row['email'],
            'role_id' => (int)$row['role_id'],
            'password_ok' => password_verify('password', $row['password_hash'])
        ];
    } else {
        $out['demo'][] = [
            'email' => $email,
            'missing' => true
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
