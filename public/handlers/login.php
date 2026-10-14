<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

$conn = get_db_connection();
$stmt = $conn->prepare("SELECT u.id, u.name, u.email, u.password_hash, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? LIMIT 1");
$stmt->execute([$email]);
$row = $stmt->fetch();

if ($row) {
    $passwordOk = password_verify($password, $row['password_hash']);


    // Fallback dev: bila akun demo dan password 'password', tapi hash tidak cocok, perbarui hash lalu lanjut
    if (!$passwordOk && defined('DEV_MODE') && DEV_MODE) {
        $demoEmails = ['admin@demo.com','stok@demo.com','atasan@demo.com','user@demo.com'];
        if (in_array($row['email'], $demoEmails, true) && $password === 'password') {
            $newHash = password_hash('password', PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
            $upd->execute([$newHash, $row['id']]);
            // cek ulang
            $passwordOk = password_verify($password, $newHash);
        } 

    }

    if ($passwordOk) {
        $_SESSION['user'] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role_name' => $row['role_name']
        ];
        // Redirect ke dashboard per peran
        switch ($row['role_name']) {
            case 'admin_utama':
                header('Location: ../dashboard_admin.php');
                break;
            case 'admin_stok':
                header('Location: ../dashboard_stock.php');
                break;
            case 'atasan':
                header('Location: ../dashboard_boss.php');
                break;
            default:
                header('Location: ../dashboard_user.php');
        }
        exit;
    }
}

if (!$row) {
    $_SESSION['error'] = 'Email tidak terdaftar di database.';
} else {
    $_SESSION['error'] = 'Kata sandi salah. Silakan coba lagi.';
}
header('Location: ../index.php');
?>
