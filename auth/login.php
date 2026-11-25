<?php
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

// ==========================================
// 1. CEK COOKIE "INGAT SAYA" SAAT HALAMAN DIBUKA
// ==========================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE remember_token = ? AND status='aktif'");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['foto']    = $user['foto'] ?? null;
        redirect('index.php');
    }
}

// ==========================================
// 2. PROSES LOGIN SAAT FORM DISUBMIT
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass  = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND status='aktif'");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['foto']    = $user['foto'] ?? null; 

        // PROSES SIMPAN COOKIE INGAT SAYA
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $update = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $update->bind_param('si', $token, $user['id']);
            $update->execute();
            
            // Set cookie berlaku 30 hari (86400 detik * 30)
            setcookie('remember_me', $token, time() + (86400 * 30), "/", "", false, true);
        }

        redirect('index.php');
    } else {
        $error = 'Email atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html, body {
    min-height: 100%;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
    background: #0a1929;
}
a { text-decoration: none; color: inherit; }

.bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; }
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
    font-size: 24px; font-weight: 700; color: #0f172a;
    letter-spacing: -0.3px; line-height: 1.25; margin-bottom: 8px;
    min-height: 32px;
}
.head h1 em { font-style: normal; color: #0f5132; }
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
.alert-success { background: #f0fdf4; color: #166534; border-left: 3px solid #10b981; }
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
    width: 100%; padding: 14px 46px 14px 44px;
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

.toggle-pass {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #94a3b8; padding: 6px; font-size: 15px;
    transition: color 0.2s; border-radius: 6px;
    display: flex; align-items: center; justify-content: center; z-index: 3;
}
.toggle-pass:hover { color: #0f5132; background: #f1f5f9; }

/* ============================================================
   CSS UNTUK CUSTOM CHECKBOX "INGAT SAYA"
   ============================================================ */
.options {
    display: flex; justify-content: space-between; align-items: center;
    margin: 18px 0 22px; gap: 12px; flex-wrap: wrap;
}
.remember {
    display: flex; align-items: center; gap: 8px;
    font-size: 13px; color: #475569; cursor: pointer;
    user-select: none; font-weight: 500;
}
.remember input {
    width: 18px; height: 18px;
    accent-color: #0f5132;
    cursor: pointer;
}
.forgot {
    font-size: 13px; font-weight: 600; color: #0f5132; transition: color 0.2s;
}
.forgot:hover { color: #ffcc00; }

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

.register-line {
    text-align: center; font-size: 13px; color: #64748b;
    padding-top: 20px; border-top: 1px solid #f1f5f9;
}
.register-line a {
    color: #0f5132; font-weight: 700;
    display: inline-flex; align-items: center; gap: 4px;
    margin-left: 4px; transition: color 0.2s;
}
.register-line a:hover { color: #ffcc00; }
.register-line a i { font-size: 12px; transition: transform 0.25s; }
.register-line a:hover i { transform: translateX(3px); }

/* ============================================================
   MARQUEE QUOTES TENTANG PERPUSTAKAAN
   ============================================================ */
.quotes-marquee {
    position: fixed;
    top: 72px;
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
    gap: 4px;
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

/* Typewriter kursor */
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
    padding-top: 130px;
}

@media (max-width: 640px) {
    .top-bar { padding: 12px 18px; gap: 10px; }
    .top-logo { width: 36px; height: 36px; }
    .top-brand-text strong { font-size: 12px; }
    .top-brand-text small { font-size: 9.5px; }
    .top-link { padding: 7px 14px; font-size: 12.5px; }
    .page { padding: 120px 16px 32px; }
    .card { padding: 32px 24px 28px; border-radius: 16px; }
    .card::before { height: 3px; }
    .brand-head { margin-bottom: 24px; }
    .brand-icon { width: 78px; height: 78px; padding: 6px; }
    .brand-text strong { font-size: 15px; }
    .head { margin-bottom: 22px; }
    .head h1 { font-size: 21px; min-height: 28px; }
    .head p { font-size: 13px; }
    .field { margin-bottom: 14px; }
    .input { padding: 13px 42px 13px 42px; font-size: 14px; border-radius: 10px; }
    .field-icon { left: 14px; font-size: 14px; }
    .toggle-pass { right: 10px; font-size: 14px; }
    .btn-submit { padding: 14px 22px; font-size: 13px; }

    /* Marquee mobile */
    .quotes-marquee { top: 62px; padding: 8px 0; }
    .marquee-item { font-size: 11.5px; }
    .marquee-item::before,
    .marquee-item::after { font-size: 13px; }
    .marquee-track { gap: 40px; animation-duration: 30s; padding-left: 40px; }
}
@media (max-width: 400px) {
    .top-bar { padding: 10px 14px; }
    .top-brand-text small { display: none; }
    .top-link { padding: 6px 12px; font-size: 12px; }
    .page { padding: 108px 12px 28px; }
    .card { padding: 26px 20px 22px; border-radius: 14px; }
    .brand-icon { width: 68px; height: 68px; padding: 5px; }
    .brand-text strong { font-size: 14px; }
    .head h1 { font-size: 19px; min-height: 26px; }
    .input { padding: 12px 40px 12px 40px; font-size: 13.5px; }
    .btn-submit { padding: 13px 18px; font-size: 12.5px; }

    .quotes-marquee { top: 56px; }
    .marquee-item { font-size: 10.5px; }
    .marquee-item::before,
    .marquee-item::after { font-size: 12px; }
    .marquee-track { animation-duration: 25s; }
}
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
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
            <!-- TYPOGRAPHY BRAND (ID DISESUAIKAN UNTUK JS) -->
            <strong><span id="brandTyping"></span><span class="typing-cursor"></span></strong>
        </div>
    </a>
    <a href="register.php" class="top-link">
        <i class="bi bi-person-plus"></i>
        Daftar
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
            <h1><span id="loginTyping"></span><span class="typing-cursor"></span></h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($m = flash('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <span><?= htmlspecialchars($m) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">

            <div class="field">
                <label for="email">Email</label>
                <div class="field-input">
                    <i class="bi bi-envelope field-icon"></i>
                    <input type="email" id="email" name="email" class="input"
                           placeholder="nama@email.com" required autofocus>
                </div>
            </div>

            <div class="field">
                <label for="password">Kata Sandi</label>
                <div class="field-input">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" id="password" name="password"
                           class="input" placeholder="Masukkan kata sandi" required>
                    <button type="button" class="toggle-pass"
                            onclick="togglePass('password', this)"
                            aria-label="Tampilkan kata sandi">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="options">
                <!-- CUSTOM CHECKBOX INGAT SAYA -->
                <label class="remember" for="remember">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <span>Ingat saya</span>
                </label>
                <a href="forgot.php" class="forgot">Lupa kata sandi?</a>
            </div>

            <button type="submit" class="btn-submit">
                Masuk
                <i class="bi bi-arrow-right"></i>
            </button>

        </form>

        <div class="register-line">
            Belum punya akun?
            <a href="register.php">
                Daftar sekarang
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

    </div>
</div>

<script>
function togglePass(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// ==========================================
// ANIMASI TYPEWRITER UNTUK JUDUL LOGIN
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const el = document.getElementById('loginTyping');
    if (!el) return;

    const texts = ["Login", "Selamat Datang", "Masuk ke Akun"];
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

// ==========================================
// ANIMASI TYPEWRITER UNTUK BRAND (TOP BAR)
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const brandEl = document.getElementById('brandTyping');
    if (!brandEl) return;

    const brandTexts = ["SMK As-Syafi'iyah 01 Jakarta", "Perpustakaan Digital"];
    let bCount = 0;
    let bCurrentText = '';
    let bIsDeleting = false;
    let bSpeed = 100;
    let bDeletingSpeed = 50;
    let bDelayBetweenTexts = 2000;

    function typeBrand() {
        if (bIsDeleting) {
            bCurrentText = brandTexts[bCount].substring(0, bCurrentText.length - 1);
            bSpeed = bDeletingSpeed;
        } else {
            bCurrentText = brandTexts[bCount].substring(0, bCurrentText.length + 1);
            bSpeed = 100;
        }

        brandEl.textContent = bCurrentText;

        if (!bIsDeleting && bCurrentText === brandTexts[bCount]) {
            bIsDeleting = true;
            bSpeed = bDelayBetweenTexts;
        } else if (bIsDeleting && bCurrentText === '') {
            bIsDeleting = false;
            bCount = (bCount + 1) % brandTexts.length;
            bSpeed = 500;
        }

        setTimeout(typeBrand, bSpeed);
    }

    setTimeout(typeBrand, 800);
});
</script>

</body>
</html>