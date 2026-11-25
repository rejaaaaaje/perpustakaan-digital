<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
$old = ['nama'=>'', 'email'=>'', 'no_telp'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama  = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $telp  = trim($_POST['no_telp']);

    $old = ['nama'=>$nama, 'email'=>$email, 'no_telp'=>$telp];

    if (!isset($_POST['terms'])) {
        $error = 'Anda harus menyetujui Syarat & Ketentuan untuk mendaftar.';
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE email=?");
        $cek->bind_param('s', $email);
        $cek->execute();
        if ($cek->get_result()->num_rows > 0) {
            $error = 'Email sudah terdaftar. Gunakan email lain.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (nama,email,password,no_telp,role) VALUES (?,?,?,?,'anggota')");
            $stmt->bind_param('ssss', $nama, $email, $pass, $telp);
            if ($stmt->execute()) {
                flash('success', 'Registrasi berhasil. Silakan masuk dengan akun Anda.');
                redirect('auth/login.php');
            } else {
                $error = 'Gagal mendaftar. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#0f5132">
<title>Daftar - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

html {
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
}

html, body {
    min-height: 100%;
    font-family: 'Segoe UI', system-ui, -apple-system, Roboto, 'Helvetica Neue', Tahoma, sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    background: #0a1929;
    -webkit-tap-highlight-color: transparent;
}

a { text-decoration: none; color: inherit; }

/* ===== BACKGROUND ===== */
.bg {
    position: fixed; inset: 0; z-index: 0; overflow: hidden;
}
.bg::before {
    content: "";
    position: absolute; inset: 0;
    background: url('../assets/img/perpustakaan.png') center center no-repeat;
    background-size: cover;
    transform: scale(1.05);
    animation: slowZoom 30s ease-in-out infinite alternate;
}
.bg::after {
    content: "";
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(5,40,25,0.9) 0%, rgba(15,81,50,0.8) 50%, rgba(5,40,25,0.94) 100%);
}
@keyframes slowZoom {
    0%   { transform: scale(1.05); }
    100% { transform: scale(1.15); }
}

/* ===== TOP BAR ===== */
.top-bar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    padding: calc(14px + env(safe-area-inset-top)) 24px 14px;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.top-brand {
    display: flex; align-items: center; gap: 10px; color: #fff;
    transition: opacity 0.25s; min-width: 0;
}
.top-brand:hover { opacity: 0.85; }
.top-logo {
    width: 40px; height: 40px; border-radius: 50%;
    object-fit: contain; background: #fff; padding: 3px; flex-shrink: 0;
}
.top-brand-text { min-width: 0; }
.top-brand-text strong {
    display: block; font-size: 13px; font-weight: 700; color: #fff;
    letter-spacing: -0.2px; line-height: 1.2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.top-brand-text small {
    display: block; font-size: 10.5px; color: rgba(255,255,255,0.7);
    letter-spacing: 0.5px; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.top-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: #fff; font-size: 13px; font-weight: 600;
    padding: 9px 18px; border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.25);
    transition: all 0.25s; white-space: nowrap;
    min-height: 42px;
}
.top-link:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.4); }
.top-link:active { transform: scale(0.96); }
.top-link i { font-size: 14px; }

/* ===== PAGE ===== */
.page {
    position: relative; z-index: 2;
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    padding: calc(84px + env(safe-area-inset-top)) 20px calc(32px + env(safe-area-inset-bottom));
}

/* ===== CARD ===== */
.card {
    width: 100%; max-width: 460px;
    background: #ffffff;
    border-radius: 22px;
    box-shadow: 0 30px 70px rgba(0,0,0,0.35), 0 0 0 1px rgba(255,255,255,0.05);
    padding: 36px 32px 30px;
    position: relative; overflow: hidden;
    animation: cardIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
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

/* ===== BRAND ===== */
.brand-head {
    display: flex; flex-direction: column; align-items: center;
    text-align: center; gap: 12px; margin-bottom: 26px;
}
.brand-icon {
    width: 82px; height: 82px; border-radius: 50%;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 22px rgba(15,81,50,0.18), 0 0 0 4px rgba(15,81,50,0.06);
    flex-shrink: 0; overflow: hidden; padding: 8px;
    transition: transform 0.3s;
}
.brand-icon:hover { transform: scale(1.05) rotate(-3deg); }
.brand-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }

.brand-text strong {
    display: block; font-size: 16px; font-weight: 800;
    color: #0f5132; letter-spacing: -0.2px; line-height: 1.2;
}
.brand-text small {
    display: block; font-size: 10.5px; color: #94a3b8;
    font-weight: 600; letter-spacing: 1.3px; margin-top: 4px;
    text-transform: uppercase;
}

/* ===== HEAD ===== */
.head { margin-bottom: 24px; text-align: center; }
.head h1 {
    font-size: 24px; font-weight: 700; color: #0f172a;
    letter-spacing: -0.3px; line-height: 1.3; margin-bottom: 8px;
    min-height: 32px;
}
.head h1 em { font-style: normal; color: #0f5132; font-weight: 700; }
.head p { font-size: 13.5px; color: #64748b; line-height: 1.55; }

/* ===== ALERT ===== */
.alert {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 13px 16px; border-radius: 12px;
    font-size: 13px; font-weight: 500; margin-bottom: 18px;
    animation: shake 0.4s ease-out;
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25%      { transform: translateX(-5px); }
    75%      { transform: translateX(5px); }
}
.alert-danger { background: #fef2f2; color: #991b1b; border-left: 3px solid #ef4444; }
.alert i { font-size: 16px; flex-shrink: 0; margin-top: 1px; }

/* ===== FORM ===== */
.field { margin-bottom: 16px; }
.field label {
    display: block; font-size: 13px; font-weight: 700; color: #334155;
    margin-bottom: 8px; letter-spacing: 0.2px;
}
.field-input { position: relative; }
.field-icon {
    position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
    font-size: 16px; color: #94a3b8; pointer-events: none;
    transition: color 0.2s; z-index: 2;
}
.input {
    width: 100%;
    padding: 15px 18px 15px 46px;
    border: 1.5px solid #e2e8f0; border-radius: 12px;
    font-size: 16px;
    font-family: inherit;
    color: #0f172a; background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    outline: none; font-weight: 500;
    min-height: 52px;
    -webkit-appearance: none; appearance: none;
}
.input::placeholder { color: #94a3b8; font-weight: 400; }
.input:focus {
    border-color: #0f5132; background: #fff;
    box-shadow: 0 0 0 4px rgba(15,81,50,0.08);
}
.field-input:focus-within .field-icon { color: #0f5132; }

/* ===== PASSWORD HINT ===== */
.pass-hint {
    display: flex; align-items: center; gap: 6px;
    font-size: 11.5px; color: #94a3b8; margin-top: 8px; font-weight: 500;
    transition: color 0.3s;
}
.pass-hint::before {
    content: ""; width: 5px; height: 5px; border-radius: 50%;
    background: #cbd5e1; transition: background 0.3s; flex-shrink: 0;
}
.pass-hint.valid { color: #16a34a; }
.pass-hint.valid::before { background: #10b981; }
.pass-hint.weak { color: #ea580c; }
.pass-hint.weak::before { background: #f59e0b; }

/* ===== TERMS ===== */
.terms {
    display: flex; align-items: flex-start; gap: 10px;
    margin: 14px 0 22px; font-size: 12.5px; color: #64748b; line-height: 1.55;
}
.terms input {
    width: 18px; height: 18px; accent-color: #0f5132;
    cursor: pointer; flex-shrink: 0; margin-top: 1px;
    -webkit-appearance: none; appearance: none;
    border: 2px solid #cbd5e1; border-radius: 5px;
    background: #fff; position: relative;
    transition: all 0.2s;
}
.terms input:checked {
    background: #0f5132; border-color: #0f5132;
}
.terms input:checked::after {
    content: "";
    position: absolute; left: 5px; top: 1px;
    width: 5px; height: 10px;
    border: solid #fff; border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
.terms label {
    cursor: pointer; font-weight: 400; color: #64748b;
    font-size: 12.5px; text-transform: none; letter-spacing: 0; margin: 0;
}
.terms a {
    color: #0f5132; font-weight: 600;
    border-bottom: 1px dotted #94a3b8; transition: color 0.2s;
}
.terms a:hover { color: #ffcc00; border-color: #ffcc00; }

/* ===== SUBMIT ===== */
.btn-submit {
    width: 100%;
    padding: 16px 24px;
    background: #0f172a; color: #fff;
    border: none; border-radius: 12px;
    font-size: 14px; font-weight: 700; font-family: inherit;
    letter-spacing: 0.5px; cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: inline-flex; align-items: center; justify-content: center;
    gap: 10px;
    box-shadow: 0 6px 16px rgba(15,23,42,0.15);
    text-transform: uppercase;
    min-height: 54px;
    -webkit-tap-highlight-color: transparent;
}
.btn-submit:hover {
    background: #0f5132; transform: translateY(-2px);
    box-shadow: 0 12px 26px rgba(15,81,50,0.3);
}
.btn-submit:active { transform: translateY(0) scale(0.98); }
.btn-submit i { font-size: 16px; transition: transform 0.25s; }
.btn-submit:hover i { transform: translateX(3px); }

/* ===== LOGIN LINE ===== */
.login-line {
    text-align: center; font-size: 13px; color: #64748b;
    margin-top: 22px; padding-top: 20px; border-top: 1px solid #f1f5f9;
}
.login-line a {
    color: #0f5132; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    margin-left: 4px; transition: color 0.2s;
}
.login-line a:hover { color: #ffcc00; }
.login-line a i { font-size: 12px; transition: transform 0.25s; }
.login-line a:hover i { transform: translateX(3px); }

/* ============================================================
   MARQUEE QUOTES TENTANG PERPUSTAKAAN
   ============================================================ */
.quotes-marquee {
    position: fixed;
    top: calc(68px + env(safe-area-inset-top));
    left: 0; right: 0;
    z-index: 90;
    background: linear-gradient(90deg, rgba(15,81,50,0.88), rgba(10,54,34,0.88), rgba(15,81,50,0.88));
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-top: 1px solid rgba(255,204,0,0.3);
    border-bottom: 1px solid rgba(255,204,0,0.3);
    padding: 10px 0;
    overflow: hidden;
    pointer-events: none;
}

.marquee-track {
    display: flex;
    gap: 60px;
    white-space: nowrap;
    animation: marqueeScroll 40s linear infinite;
    width: max-content;
    padding-left: 60px;
}

.marquee-item {
    display: inline-flex;
    align-items: center;
    color: #ffcc00;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.3px;
    font-style: italic;
    text-shadow: 0 1px 4px rgba(0,0,0,0.3);
}

.marquee-item::before {
    content: "❝";
    font-size: 16px;
    color: #ffcc00;
    opacity: 0.75;
    font-style: normal;
    margin-right: 4px;
}

.marquee-item::after {
    content: "❞";
    font-size: 16px;
    color: #ffcc00;
    opacity: 0.75;
    font-style: normal;
    margin-left: 4px;
}

.marquee-separator {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ffcc00;
    opacity: 0.6;
    flex-shrink: 0;
}

@keyframes marqueeScroll {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* Typewriter kursor untuk judul */
.typing-cursor {
    display: inline-block;
    width: 2px;
    height: 1em;
    background-color: currentColor;
    margin-left: 3px;
    animation: blink 0.7s infinite;
    vertical-align: text-bottom;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}

/* Geser page supaya tidak tertutup marquee */
.page {
    padding-top: calc(130px + env(safe-area-inset-top));
}

/* ===== RESPONSIVE — ANDROID / HP ===== */
@media (max-width: 640px) {
    .top-bar {
        padding: calc(12px + env(safe-area-inset-top)) 16px 12px;
        gap: 8px;
    }
    .top-logo { width: 36px; height: 36px; }
    .top-brand-text strong { font-size: 12px; }
    .top-brand-text small { font-size: 9.5px; }
    .top-link { padding: 8px 14px; font-size: 12.5px; min-height: 40px; }

    .page {
        padding: calc(120px + env(safe-area-inset-top)) 14px calc(24px + env(safe-area-inset-bottom));
        align-items: flex-start;
    }

    .card {
        padding: 30px 22px 26px;
        border-radius: 18px;
        margin-top: 6px;
    }
    .card::before { height: 3px; }

    .brand-head { margin-bottom: 22px; gap: 10px; }
    .brand-icon { width: 74px; height: 74px; padding: 7px; }
    .brand-text strong { font-size: 15px; }
    .brand-text small { font-size: 9.5px; letter-spacing: 1.1px; }

    .head { margin-bottom: 20px; }
    .head h1 { font-size: 22px; min-height: 28px; }
    .head p { font-size: 13px; }

    .field { margin-bottom: 14px; }
    .field label { font-size: 12.5px; margin-bottom: 7px; }
    .input {
        padding: 14px 16px 14px 44px;
        font-size: 16px;
        border-radius: 11px;
        min-height: 50px;
    }
    .field-icon { left: 14px; font-size: 15px; }

    .terms { margin: 12px 0 20px; font-size: 12px; }
    .terms label { font-size: 12px; }
    .terms input { width: 17px; height: 17px; }

    .btn-submit {
        padding: 15px 20px;
        font-size: 13.5px;
        min-height: 52px;
        border-radius: 11px;
    }

    .login-line { font-size: 12.5px; margin-top: 20px; padding-top: 18px; }

    /* Marquee mobile */
    .quotes-marquee { top: calc(60px + env(safe-area-inset-top)); padding: 8px 0; }
    .marquee-item { font-size: 11.5px; }
    .marquee-item::before,
    .marquee-item::after { font-size: 13px; }
    .marquee-track { gap: 40px; animation-duration: 30s; padding-left: 40px; }
}

/* HP kecil (≤ 400px) */
@media (max-width: 400px) {
    .top-bar { padding: calc(10px + env(safe-area-inset-top)) 12px 10px; }
    .top-brand-text small { display: none; }
    .top-link { padding: 7px 12px; font-size: 12px; min-height: 38px; }

    .page {
        padding: calc(108px + env(safe-area-inset-top)) 10px calc(20px + env(safe-area-inset-bottom));
    }
    .card { padding: 24px 18px 22px; border-radius: 16px; }

    .brand-icon { width: 66px; height: 66px; padding: 6px; }
    .brand-text strong { font-size: 14px; }

    .head h1 { font-size: 20px; min-height: 26px; }
    .head p { font-size: 12.5px; }

    .input { padding: 13px 14px 13px 42px; font-size: 16px; }
    .field-icon { left: 13px; font-size: 14px; }

    .btn-submit { padding: 14px 18px; font-size: 13px; min-height: 50px; }

    .quotes-marquee { top: calc(54px + env(safe-area-inset-top)); }
    .marquee-item { font-size: 10.5px; }
    .marquee-item::before,
    .marquee-item::after { font-size: 12px; }
    .marquee-track { animation-duration: 25s; }
}

/* HP sangat kecil / landscape pendek */
@media (max-height: 640px) and (max-width: 640px) {
    .page { align-items: flex-start; padding-top: calc(120px + env(safe-area-inset-top)); }
    .brand-head { margin-bottom: 16px; }
    .brand-icon { width: 60px; height: 60px; }
    .head { margin-bottom: 16px; }
    .head h1 { font-size: 20px; }
    .field { margin-bottom: 12px; }
}

/* Motion reduce */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
    .typing-cursor { display: none; }
}
</style>
</head>
<body>

<div class="bg"></div>

<div class="top-bar">
    <a href="../index.php" class="top-brand">
        <img src="../assets/img/foto.png" alt="Logo" class="top-logo">
        <div class="top-brand-text">
            <strong>SMK As-Syafi'iyah 01 Jakarta</strong>
        </div>
    </a>
    <a href="login.php" class="top-link">
        <i class="bi bi-box-arrow-in-right"></i>
        Login
    </a>
</div>

<!-- ============================================================ -->
<!-- MARQUEE QUOTES TENTANG PERPUSTAKAAN -->
<!-- ============================================================ -->
<div class="quotes-marquee">
    <div class="marquee-track">
        <span class="marquee-item">Membaca adalah jendela dunia</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Perpustakaan adalah gudang ilmu yang tak pernah habis</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Buku adalah teman setia sepanjang waktu</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Membaca hari ini, sukses di masa depan</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Ilmu adalah cahaya, perpustakaan adalah sumbernya</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Satu buku dibaca, sejuta wawasan didapat</span>
        <span class="marquee-separator"></span>
        <!-- Duplikat untuk loop seamless -->
        <span class="marquee-item">Membaca adalah jendela dunia</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Perpustakaan adalah gudang ilmu yang tak pernah habis</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Buku adalah teman setia sepanjang waktu</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Membaca hari ini, sukses di masa depan</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Ilmu adalah cahaya, perpustakaan adalah sumbernya</span>
        <span class="marquee-separator"></span>
        <span class="marquee-item">Satu buku dibaca, sejuta wawasan didapat</span>
        <span class="marquee-separator"></span>
    </div>
</div>

<div class="page">
    <div class="card">

        <div class="brand-head">
            <div class="brand-icon">
                <img src="../assets/img/foto.png" alt="Logo Perpus Digital">
            </div>
        </div>

        <div class="head">
            <h1><span id="registerTyping"></span><span class="typing-cursor"></span></h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" id="formRegister">

            <div class="field">
                <label for="nama">Nama Lengkap</label>
                <div class="field-input">
                    <i class="bi bi-person field-icon"></i>
                    <input type="text" id="nama" name="nama" class="input"
                           placeholder="Masukkan nama lengkap"
                           value="<?= htmlspecialchars($old['nama']) ?>" required autofocus>
                </div>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <div class="field-input">
                    <i class="bi bi-envelope field-icon"></i>
                    <input type="email" id="email" name="email" class="input"
                           placeholder="nama@email.com"
                           inputmode="email"
                           value="<?= htmlspecialchars($old['email']) ?>" required>
                </div>
            </div>

            <div class="field">
                <label for="no_telp">Nomor Telepon</label>
                <div class="field-input">
                    <i class="bi bi-telephone field-icon"></i>
                    <input type="tel" id="no_telp" name="no_telp" class="input"
                           placeholder="08xxxxxxxxxx"
                           inputmode="numeric"
                           value="<?= htmlspecialchars($old['no_telp']) ?>"
                           pattern="[0-9]{10,15}"
                           title="Masukkan 10-15 digit angka" required>
                </div>
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <div class="field-input">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" id="password" name="password" class="input"
                           placeholder="Minimal 6 karakter" minlength="6" required>
                </div>
                <div class="pass-hint" id="passHint">Minimal 6 karakter</div>
            </div>

            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">
                    Saya menyetujui <a href="#">Syarat &amp; Ketentuan</a> serta 
                    <a href="#">Kebijakan Privasi</a>.
                </label>
            </div>

            <button type="submit" class="btn-submit">
                Buat Akun
                <i class="bi bi-arrow-right"></i>
            </button>

        </form>

        <div class="login-line">
            Sudah punya akun?
            <a href="login.php">
                Masuk sekarang
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

    </div>
</div>

<script>
// ==========================================
// PASSWORD STRENGTH HINT
// ==========================================
const pwd = document.getElementById('password');
const hint = document.getElementById('passHint');
if (pwd && hint) {
    pwd.addEventListener('input', function() {
        const len = this.value.length;
        hint.classList.remove('valid', 'weak');
        if (len === 0) {
            hint.textContent = 'Minimal 6 karakter';
        } else if (len < 6) {
            hint.textContent = 'Kurang ' + (6 - len) + ' karakter lagi';
            hint.classList.add('weak');
        } else if (len < 10) {
            hint.textContent = 'Kata sandi cukup kuat';
            hint.classList.add('valid');
        } else {
            hint.textContent = 'Kata sandi sangat kuat';
            hint.classList.add('valid');
        }
    });
}

// ==========================================
// ANIMASI TYPEWRITER UNTUK JUDUL REGISTER
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('registerTyping');
    if (!el) return;

    const texts = ["Register", "Buat Akun Baru", "Bergabung Sekarang"];
    let count = 0;
    let currentText = '';
    let isDeleting = false;
    let speed = 120;
    let deletingSpeed = 60;
    let delayBetweenTexts = 1800;

    function type() {
        if (isDeleting) {
            currentText = texts[count].substring(0, currentText.length - 1);
            speed = deletingSpeed;
        } else {
            currentText = texts[count].substring(0, currentText.length + 1);
            speed = 120;
        }

        el.textContent = currentText;

        if (!isDeleting && currentText === texts[count]) {
            isDeleting = true;
            speed = delayBetweenTexts;
        } else if (isDeleting && currentText === '') {
            isDeleting = false;
            count = (count + 1) % texts.length;
            speed = 500;
        }

        setTimeout(type, speed);
    }

    setTimeout(type, 600);
});
</script>

</body>
</html>