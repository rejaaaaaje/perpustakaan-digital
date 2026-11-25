<?php
require_once '../config/database.php';
require_once '../config/mail.php'; // Panggil fungsi email

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $telp  = trim($_POST['no_telp']);

    if (empty($email) || empty($telp)) {
        $error = 'Email dan nomor telepon wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama FROM users WHERE email=? AND no_telp=? AND status='aktif'");
        $stmt->bind_param('ss', $email, $telp);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // 1. Buat kode OTP 6 digit
            $otp = rand(100000, 999999);
            
            // 2. Simpan OTP dan data user ke Session sementara
            $_SESSION['reset_otp']     = $otp;
            $_SESSION['reset_email']   = $email;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_time']    = time();

            // 3. Kirim Email OTP
            $kirim = kirimEmailOTP($email, $user['nama'], $otp);

            if ($kirim) {
                // Jika berhasil, arahkan ke halaman input OTP
                redirect('auth/verifikasi_otp.php');
            } else {
                $error = 'Gagal mengirim email verifikasi. Periksa konfigurasi email server.';
            }
        } else {
            $error = 'Email dan nomor telepon tidak cocok dengan data kami.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lupa Kata Sandi - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html, body {
    min-height: 100%;
    font-family: 'Segoe UI', system-ui, -apple-system, Tahoma, sans-serif;
    -webkit-font-smoothing: antialiased;
    background: #0a1929;
}
a { text-decoration: none; color: inherit; }

.bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
.bg::before {
    content: "";
    position: absolute; inset: 0;
    background: url('../assets/img/foto.png') center center no-repeat;
    background-size: cover;
    transform: scale(1.05);
    animation: slowZoom 30s ease-in-out infinite alternate;
}
.bg::after {
    content: "";
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(5,40,25,0.88) 0%, rgba(15,81,50,0.78) 50%, rgba(5,40,25,0.92) 100%);
}
@keyframes slowZoom {
    0%   { transform: scale(1.05); }
    100% { transform: scale(1.15); }
}

.top-bar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    padding: 16px 32px;
    display: flex; justify-content: space-between; align-items: center; gap: 16px;
}
.top-brand { display: flex; align-items: center; gap: 12px; color: #fff; transition: opacity 0.25s; }
.top-brand:hover { opacity: 0.85; }
.top-logo {
    width: 40px; height: 40px; border-radius: 50%;
    object-fit: contain; background: #fff; padding: 3px; flex-shrink: 0;
}
.top-brand-text strong {
    display: block; font-size: 13px; font-weight: 700; color: #fff;
    letter-spacing: -0.2px; line-height: 1.2;
}
.top-brand-text small {
    display: block; font-size: 10.5px; color: rgba(255,255,255,0.7);
    letter-spacing: 0.5px; margin-top: 2px;
}
.top-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: #fff; font-size: 13px; font-weight: 600;
    padding: 8px 18px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.25s; white-space: nowrap;
}
.top-link:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.4);
}
.top-link i { font-size: 14px; }

.page {
    position: relative; z-index: 2;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: 90px 24px 40px;
}

.card {
    width: 100%; max-width: 460px;
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 30px 70px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.05);
    padding: 40px 38px 34px;
    position: relative; overflow: hidden;
    animation: cardIn 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(30px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.card::before {
    content: "";
    position: absolute; top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, #0f5132, #ffcc00, #0f5132);
    background-size: 200% 100%;
    animation: gradientMove 4s linear infinite;
}
@keyframes gradientMove {
    0%   { background-position: 0% 0; }
    100% { background-position: 200% 0; }
}

/* ============ BRAND — LOGO DI TENGAH ============ */
.brand-head {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 12px;
    margin-bottom: 30px;
}

.brand-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 22px rgba(15,81,50,0.18),
                0 0 0 4px rgba(15,81,50,0.06);
    flex-shrink: 0;
    overflow: hidden;
    padding: 8px;
    transition: transform 0.3s;
}
.brand-icon:hover {
    transform: scale(1.05) rotate(-3deg);
}
.brand-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.brand-text strong {
    display: block;
    font-size: 16px;
    font-weight: 800;
    color: #0f5132;
    letter-spacing: -0.2px;
    line-height: 1.2;
}
.brand-text small {
    display: block;
    font-size: 10.5px;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: 1.3px;
    margin-top: 4px;
    text-transform: uppercase;
}

.head { margin-bottom: 26px; text-align: center; }
.head h1 {
    font-family: 'Georgia', 'Times New Roman', serif;
    font-size: 26px; font-weight: 800; color: #0f172a;
    letter-spacing: -0.4px; line-height: 1.2; margin-bottom: 8px;
}
.head p { font-size: 13.5px; color: #64748b; line-height: 1.55; }

.alert {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 13px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 500; margin-bottom: 20px;
    animation: shake 0.4s ease-out;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25%      { transform: translateX(-5px); }
    75%      { transform: translateX(5px); }
}
.alert-danger { background: #fef2f2; color: #991b1b; border-left: 3px solid #ef4444; }
.alert i { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

.field { margin-bottom: 16px; }
.field label {
    display: block; font-size: 12.5px; font-weight: 700; color: #334155;
    margin-bottom: 7px; letter-spacing: 0.2px;
}
.field-input { position: relative; }
.field-icon {
    position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
    font-size: 15px; color: #94a3b8; pointer-events: none;
    transition: color 0.2s; z-index: 2;
}
.input {
    width: 100%; padding: 14px 18px 14px 44px;
    border: 1.5px solid #e2e8f0; border-radius: 11px;
    font-size: 14px; font-family: inherit;
    color: #0f172a; background: #f8fafc;
    transition: all 0.2s; outline: none; font-weight: 500;
}
.input::placeholder { color: #94a3b8; font-weight: 400; }
.input:focus {
    border-color: #0f5132; background: #fff;
    box-shadow: 0 0 0 4px rgba(15,81,50,0.08);
}
.field-input:focus-within .field-icon { color: #0f5132; }

.btn-submit {
    width: 100%; padding: 15px 24px;
    background: #0f172a; color: #ffffff;
    border: none; border-radius: 11px;
    font-size: 14px; font-weight: 700; font-family: inherit;
    letter-spacing: 0.4px; cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex; align-items: center; justify-content: center;
    gap: 10px;
    box-shadow: 0 6px 16px rgba(15,23,42,0.15);
    text-transform: uppercase;
}
.btn-submit:hover {
    background: #0f5132; transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(15,81,50,0.3);
}
.btn-submit i { font-size: 16px; transition: transform 0.25s; }
.btn-submit:hover i { transform: translateX(3px); }

.back-line {
    text-align: center; font-size: 13px; color: #64748b;
    margin-top: 22px; padding-top: 20px; border-top: 1px solid #f1f5f9;
}
.back-line a {
    color: #0f5132; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    margin-left: 4px; transition: color 0.2s;
}
.back-line a:hover { color: #ffcc00; }
.back-line a i { font-size: 12px; transition: transform 0.25s; }
.back-line a:hover i { transform: translateX(3px); }

@media (max-width: 640px) {
    .top-bar { padding: 12px 18px; gap: 10px; }
    .top-logo { width: 36px; height: 36px; }
    .top-brand-text strong { font-size: 12px; }
    .top-brand-text small { font-size: 9.5px; }
    .top-link { padding: 7px 14px; font-size: 12.5px; }
    .page { padding: 80px 16px 32px; }
    .card { padding: 32px 24px 28px; border-radius: 16px; }
    .card::before { height: 3px; }
    .brand-head { margin-bottom: 24px; }
    .brand-icon { width: 78px; height: 78px; padding: 6px; }
    .brand-text strong { font-size: 15px; }
    .head { margin-bottom: 22px; }
    .head h1 { font-size: 22px; }
    .head p { font-size: 13px; }
    .field { margin-bottom: 14px; }
    .input { padding: 13px 16px 13px 42px; font-size: 14px; border-radius: 10px; }
    .field-icon { left: 14px; font-size: 14px; }
    .btn-submit { padding: 14px 22px; font-size: 13px; }
}
@media (max-width: 400px) {
    .top-bar { padding: 10px 14px; }
    .top-brand-text small { display: none; }
    .top-link { padding: 6px 12px; font-size: 12px; }
    .page { padding: 70px 12px 28px; }
    .card { padding: 26px 20px 22px; border-radius: 14px; }
    .brand-icon { width: 68px; height: 68px; padding: 5px; }
    .brand-text strong { font-size: 14px; }
    .head h1 { font-size: 20px; }
    .input { padding: 12px 14px 12px 40px; font-size: 13.5px; }
    .btn-submit { padding: 13px 18px; font-size: 12.5px; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
</head>
<body>

<div class="bg"></div>

<div class="top-bar">
    <a href="../index.php" class="top-brand">
        <img src="../assets/img/foto.png" alt="Logo" class="top-logo">
        <div class="top-brand-text">
            <strong>Perpustakaan Digital</strong>
            <small>SMK As-Syafi'iyah Jakarta</small>
        </div>
    </a>
    <a href="login.php" class="top-link">
        <i class="bi bi-box-arrow-in-right"></i> Login
    </a>
</div>

<div class="page">
    <div class="card">

        <div class="brand-head">
            <div class="brand-icon">
                <img src="../assets/img/foto.png" alt="Logo Perpus Digital">
            </div>
            <div class="brand-text">
                <strong>Perpus Digital</strong>
                <small>Manajemen Perpustakaan</small>
            </div>
        </div>

        <div class="head">
            <h1>Lupa Kata Sandi?</h1>
            <p>Masukkan email dan nomor telepon yang terdaftar untuk memverifikasi akun Anda.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="field">
                <label for="email">Email Terdaftar</label>
                <div class="field-input">
                    <i class="bi bi-envelope field-icon"></i>
                    <input type="email" id="email" name="email" class="input"
                           placeholder="nama@email.com" required autofocus>
                </div>
            </div>

            <div class="field">
                <label for="no_telp">Nomor Telepon Terdaftar</label>
                <div class="field-input">
                    <i class="bi bi-telephone field-icon"></i>
                    <input type="text" id="no_telp" name="no_telp" class="input"
                           placeholder="08xxxxxxxxxx" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Verifikasi Akun
                <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <div class="back-line">
            Ingat kata sandi?
            <a href="login.php">
                Kembali ke Login
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

    </div>
</div>

</body>
</html>