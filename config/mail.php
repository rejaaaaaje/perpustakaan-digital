<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Panggil file PHPMailer secara manual (pastikan folder PHPMailer ada di root project)
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

function kirimEmailOTP($tujuan_email, $tujuan_nama, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Pengaturan Server SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // ==========================================
        // EMAIL KAMU SUDAH DIMASUKKAN DI SINI
        // ==========================================
        $mail->Username   = 'reza.adityarahman01102@gmail.com'; 
        
        // ⚠️ GANTI DENGAN 16 KARAKTER APP PASSWORD GMAIL KAMU ⚠️
        $mail->Password   = 'rdqt ntjz mnok cewu'; 
        // ==========================================
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Pengirim & Penerima
        $mail->setFrom('reza.adityarahman01102@gmail.com', 'Perpustakaan Digital');
        $mail->addAddress($tujuan_email, $tujuan_nama);

        // Konten Email
        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Reset Password';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Halo, " . htmlspecialchars($tujuan_nama) . "</h2>
                <p>Kami menerima permintaan untuk mereset kata sandi akun Anda di Perpustakaan Digital SMK As-Syafi'iyah.</p>
                <p>Berikut adalah kode verifikasi (OTP) Anda:</p>
                <h1 style='color: #0f5132; font-size: 36px; letter-spacing: 5px;'>" . $otp . "</h1>
                <p>Kode ini hanya berlaku selama 5 menit. Jika Anda tidak merasa meminta reset password, abaikan email ini.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>