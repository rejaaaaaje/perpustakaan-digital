<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id']) || !isset($_SESSION['reset_otp'])) {
    redirect('auth/forgot.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp']);
    
    // Cek apakah kode cocok dan belum kedaluwarsa (5 menit = 300 detik)
    if ($otp_input == $_SESSION['reset_otp'] && (time() - $_SESSION['reset_time'] <= 300)) {
        // OTP Valid, izinkan ganti password
        $_SESSION['otp_verified'] = true;
        redirect('auth/reset.php');
    } else {
        $error = 'Kode OTP salah atau sudah kedaluwarsa.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- SAMA SEPERTI HEADER FORGOT.PHP -->
    <title>Verifikasi OTP</title>
</head>
<body>
    <!-- TAMPILAN CARD SAMA SEPERTI FORGOT.PHP -->
    
    <div class="head">
        <h1>Masukkan Kode OTP</h1>
        <p>Kami telah mengirimkan 6 digit kode ke email <b><?= htmlspecialchars($_SESSION['reset_email']) ?></b></p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="field">
            <label for="otp">Kode Verifikasi (6 Angka)</label>
            <div class="field-input">
                <input type="text" name="otp" class="input" placeholder="Contoh: 123456" 
                       style="text-align:center; font-size: 24px; letter-spacing: 10px;" maxlength="6" required autofocus>
            </div>
        </div>
        <button type="submit" class="btn-submit">Verifikasi Kode</button>
    </form>
</body>
</html>