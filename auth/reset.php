<?php
require_once '../config/database.php';

// Cek session reset
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['reset_time'])) {
    redirect('auth/forgot.php');
}

// Token expired setelah 15 menit
if (time() - $_SESSION['reset_time'] > 900) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_token'], $_SESSION['reset_time']);
    flash('error', 'Sesi reset sudah kadaluarsa. Silakan ulangi.');
    redirect('auth/forgot.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass1 = $_POST['password'];
    $pass2 = $_POST['password2'];

    if (strlen($pass1) < 6) {
        $error = 'Kata sandi minimal 6 karakter.';
    } elseif ($pass1 !== $pass2) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $uid  = (int)$_SESSION['reset_user_id'];
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $uid);
        $stmt->execute();

        // Hapus session reset
        unset($_SESSION['reset_user_id'], $_SESSION['reset_token'], $_SESSION['reset_time']);

        flash('success', 'Kata sandi berhasil diubah. Silakan login dengan kata sandi baru.');
        redirect('auth/login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Kata Sandi - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html, body {
    min-height: 100%;
    font-family: 'Segoe UI', system-ui, -apple-system, Tahoma, sans-serif;
    background: #0a1929;
    -webkit-font-smoothing: antialiased;
}
a { text-decoration: none; color: inherit; }

/* ===== BACKGROUND ===== */
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

/* ===== TOP BAR ===== */
.top-bar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    padding: 16px 32px;
    display: flex; justify-content: space-between; align-items: center; gap: 16px;
}
.top-brand { display: flex; align-items: center; gap: 12px; color: #fff; }
.top-logo {
    width: 40px; height: 40px; border-radius: 50%;
    object-fit: contain; background: #fff; padding: 3px; flex-shrink: 0;
}
.top-brand-text strong { display: block; font-size: 13px; font-weight: 700; color: #fff; line-height: 1.2; }
.top-brand-text small { display: block; font-size: 10.5px; color: rgba(255,255,255,0.7); margin-top: 2px; }
.top-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: #fff; font-size: 13px; font-weight: 600;
    padding: 8px 18px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.25s;
}
.top-link:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.4); }
.top-link i { font-size: 14px; }

/* ===== PAGE & CARD ===== */
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
    box-shadow: 0 30px 70px rgba(0,0,0,0.35);
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

/* ===== BRAND ===== */
.brand-head {
    display: flex; flex-direction: column;
    align-items: center; text-align: center;
    gap: 12px; margin-bottom: 30px;
}
.brand-icon {
    width: 90px; height: 90px; border-radius: 50%;
    background: #fff;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 22px rgba(15,81,50,0.18), 0 0 0 4px rgba(15,81,50,0.06);
    overflow: hidden; padding: 8px;
    transition: transform 0.3s;
}
.brand-icon:hover { transform: scale(1.05) rotate(-3deg); }
.brand-icon img { width: 100%; height: 100%; object-fit: contain; display: block; }

.brand-text strong { display: block; font-size: 16px; font-weight: 800; color: #0f5132; }
.brand-text small { display: block; font-size: 10.5px; color: #94a3b8; font-weight: 600; letter-spacing: 1.3px; margin-top: 4px; text-transform: uppercase; }

/* ===== HEAD ===== */
.head { margin-bottom: 26px; text-align: center; }
.head h1 { font-size: 25px; font-weight: 700; color: #0f172a; margin-bottom: 8px; letter-spacing: -0.3px; }
.head p { font-size: 13.5px; color: #64748b; line-height: 1.55; }

/* ===== ALERT ===== */
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

/* ===== FORM ===== */
.field { margin-bottom: 16px; }
.field label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 7px; }
.field-input { position: relative; }
.field-icon {
    position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
    font-size: 15px; color: #94a3b8; pointer-events: none; z-index: 2;
}
.input {
    width: 100%; padding: 14px 46px 14px 44px;
    border: 1.5px solid #e2e8f0; border-radius: 11px;
    font-size: 14px; color: #0f172a; background: #f8fafc;
    transition: all 0.2s; outline: none; font-weight: 500;
}
.input::placeholder { color: #94a3b8; font-weight: 400; }
.input:focus { border-color: #0f5132; background: #fff; box-shadow: 0 0 0 4px rgba(15,81,50,0.08); }
.field-input:focus-within .field-icon { color: #0f5132; }

.toggle-pass {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #94a3b8; padding: 6px; font-size: 15px;
    border-radius: 6px; z-index: 3; transition: color 0.2s;
}
.toggle-pass:hover { color: #0f5132; background: #f1f5f9; }

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

/* ===== BUTTON ===== */
.btn-submit {
    width: 100%; padding: 15px 24px;
    background: #0f172a; color: #fff;
    border: none; border-radius: 11px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    transition: all 0.25s; text-transform: uppercase;
    display: inline-flex; align-items: center; justify-content: center;
    gap: 10px; margin-top: 10px;
    box-shadow: 0 6px 16px rgba(15,23,42,0.15);
    letter-spacing: 0.5px;
}
.btn-submit:hover { background: #0f5132; transform: translateY(-2px); box-shadow: 0 12px 26px rgba(15,81,50,0.3); }
.btn-submit:active { transform: translateY(0) scale(0.98); }

/* ===== RESPONSIVE ===== */
@media (max-width: 640px) {
    .top-bar { padding: 12px 18px; }
    .top-brand-text small { display: none; }
    .page { padding: 80px 16px 32px; }
    .card { padding: 32px 24px 28px; }
    .card::before { height: 3px; }
    .brand-icon { width: 78px; height: 78px; }
    .head h1 { font-size: 22px; }
    .input { padding: 13px 42px 13px 42px; font-size: 16px; } /* 16px cegah auto-zoom di HP */
    .btn-submit { padding: 14px 22px; font-size: 13px; }
}
@media (prefers-reduced-motion: reduce) {
    * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
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
        <i class="bi bi-x-circle"></i> Batal
    </a>
</div>

<div class="page">
    <div class="card">

        <div class="brand-head">
            <div class="brand-icon">
                <img src="../assets/img/foto.png" alt="Logo">
            </div>
            <div class="brand-text">
                <strong>Perpus Digital</strong>
                <small>Manajemen Perpustakaan</small>
            </div>
        </div>

        <div class="head">
            <h1>Buat Kata Sandi Baru</h1>
            <p>Masukkan kata sandi baru untuk akun Anda.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="field">
                <label for="password">Kata Sandi Baru</label>
                <div class="field-input">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" id="password" name="password" class="input"
                           placeholder="Minimal 6 karakter" minlength="6" required autofocus>
                    <button type="button" class="toggle-pass"
                            onclick="togglePass('password', this)" aria-label="Tampilkan">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="pass-hint" id="passHint">Minimal 6 karakter</div>
            </div>

            <div class="field">
                <label for="password2">Konfirmasi Kata Sandi</label>
                <div class="field-input">
                    <i class="bi bi-lock-fill field-icon"></i>
                    <input type="password" id="password2" name="password2" class="input"
                           placeholder="Ulangi kata sandi" minlength="6" required>
                    <button type="button" class="toggle-pass"
                            onclick="togglePass('password2', this)" aria-label="Tampilkan">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Simpan Kata Sandi
                <i class="bi bi-check-lg"></i>
            </button>
        </form>

    </div>
</div>

<script>
    // Fitur Lihat/Sembunyikan Sandi
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

    // Fitur Validasi Kekuatan Sandi (Real-time)
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
</script>

</body>
</html>