<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// KONEKSI DATABASE
$conn = get_db_connection();

// LOAD PHPMAILER MANUAL
require_once __DIR__ . '/../includes/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../includes/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);

    // Cek user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: forgot_password.php?msg=not_found");
    exit;
}

    $user = $stmt->fetch();
    $token = bin2hex(random_bytes(32));

    // Simpan token reset
    $conn->prepare("
        UPDATE users 
        SET reset_token=?, reset_expire = NOW() + INTERVAL '1 hour' 
        WHERE id=?
    ")->execute([$token, $user['id']]);

    // Kirim email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'emailkamu@gmail.com'; 
        $mail->Password = 'app password gmail'; 
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('emailkamu@gmail.com', 'Sistem Aset PT NHM');
        $mail->addAddress($email);

        $resetLink = "http://localhost/assetmanagement/public/reset_password.php?token=" . $token;

        $mail->isHTML(true);
        $mail->Subject = "Reset Password Anda";
        $mail->Body = "
            Klik link berikut untuk mengatur ulang password Anda:<br><br>
            <a href='$resetLink'>$resetLink</a><br><br>
            Link berlaku selama 1 jam.
        ";

        $mail->send();

        header("Location: forgot_password.php?msg=sent");
        exit;

    } catch (Exception $e) {
        echo "Email gagal dikirim: {$mail->ErrorInfo}";
    }
}
