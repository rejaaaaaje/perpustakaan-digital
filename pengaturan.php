<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { redirect('auth/login.php'); }

$id_user = (int)$_SESSION['user_id'];
$msg_sukses = '';
$msg_error = '';

// PROSES SIMPAN DATA
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_baru = trim($_POST['nama']);
    $email_baru = trim($_POST['email']);
    $telp_baru = trim($_POST['no_telp'] ?? '');
    $pass_baru = $_POST['password'];
    
    // 1. Cek apakah email sudah dipakai user lain
    $cek_email = $conn->query("SELECT id FROM users WHERE email='$email_baru' AND id != $id_user");
    if ($cek_email->num_rows > 0) {
        $msg_error = "Email sudah digunakan oleh akun lain.";
    } else {
        // 2. Upload Foto (Jika ada)
        $nama_file = $_SESSION['foto'] ?? null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $file_baru = 'profil_' . $id_user . '_' . time() . '.' . $ext;
                $target_dir = 'assets/img/profil/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $file_baru)) {
                    if (!empty($nama_file) && file_exists($target_dir . $nama_file)) {
                        unlink($target_dir . $nama_file);
                    }
                    $nama_file = $file_baru;
                }
            } else {
                $msg_error = "Format foto harus JPG atau PNG.";
            }
        }

        // 3. Update Database
        if (empty($msg_error)) {
            if (!empty($pass_baru)) {
                $pass_hash = password_hash($pass_baru, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET nama='$nama_baru', email='$email_baru', no_telp='$telp_baru', password='$pass_hash', foto='$nama_file' WHERE id=$id_user");
            } else {
                $conn->query("UPDATE users SET nama='$nama_baru', email='$email_baru', no_telp='$telp_baru', foto='$nama_file' WHERE id=$id_user");
            }
            
            $_SESSION['nama'] = $nama_baru;
            $_SESSION['foto'] = $nama_file;
            
            $msg_sukses = "Pengaturan akun berhasil disimpan!";
        }
    }
}

// Ambil data terbaru
$user = $conn->query("SELECT * FROM users WHERE id=$id_user")->fetch_assoc();

// Back link dashboard by role
$kembali = 'index.php';
if ($_SESSION['role'] === 'admin') $kembali = 'admin/dashboard.php';
elseif ($_SESSION['role'] === 'petugas') $kembali = 'petugas/dashboard.php';
elseif ($_SESSION['role'] === 'anggota') $kembali = 'anggota/dashboard.php';

$hasFoto = !empty($user['foto']) && file_exists('assets/img/profil/'.$user['foto']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan Akun - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
:root {
    --primary: #0f5132;
    --primary-dark: #0a3622;
    --primary-light: #f0fdf4;
    --accent: #ffcc00;
    --text-dark: #1e293b;
    --text-muted: #64748b;
    --bg-light: #f8fafc;
    --white: #ffffff;
    --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 20px 40px -10px rgba(15, 81, 50, 0.15);
    --radius-md: 16px;
    --radius-lg: 24px;
}

* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
body { background: var(--bg-light); color: var(--text-dark); overflow-x: hidden; }
a { text-decoration: none; }

/* ANIMASI */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeDown {
    from { opacity: 0; transform: translateY(-20px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes popIn {
    0%   { opacity: 0; transform: scale(0.8); }
    60%  { opacity: 1; transform: scale(1.05); }
    100% { opacity: 1; transform: scale(1); }
}

/* TYPEWRITER */
.typing-cursor {
    display: inline-block;
    width: 2px;
    height: 1em;
    background-color: currentColor;
    margin-left: 2px;
    animation: blink 0.7s infinite;
    vertical-align: text-bottom;
}
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

/* NAVBAR DESKTOP */
.main-nav { background: #fff; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(0,0,0,0.05); animation: fadeDown 0.5s ease-out; }
.main-nav-inner { max-width: 1280px; margin: 0 auto; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.logo-box { display: flex; align-items: center; gap: 12px; }
.nav-logo { width: 44px; height: 44px; border-radius: 50%; object-fit: contain; }
.logo-text strong { color: var(--primary); font-size: 18px; font-weight: 700; display: block; line-height: 1.1; }
.logo-text small { color: var(--text-muted); font-size: 11px; font-weight: 500; }

.nav-links { display: flex; align-items: center; gap: 8px; }
.nav-links a { padding: 8px 16px; font-size: 14px; font-weight: 600; color: #475569; border-radius: 8px; transition: 0.3s; }
.nav-links a:hover { color: var(--primary); background: var(--primary-light); }
.nav-links a.active { color: var(--primary); background: var(--primary-light); }

.nav-profile-link { display: flex; align-items: center; gap: 10px; padding: 4px 16px 4px 4px; border-radius: 50px; background: #f1f5f9; border: 1px solid #e2e8f0; transition: 0.3s; margin-left: 12px; }
.nav-profile-link:hover { background: #e2e8f0; }
.nav-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; object-fit: cover; overflow: hidden; }
.nav-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.nav-profile-name { font-size: 13px; font-weight: 600; color: var(--primary); }

.nav-links a.cta { background: var(--primary); color: var(--white); padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; }
.nav-links a.cta:hover { background: var(--primary-dark); transform: translateY(-2px); }

/* HEADER MINI MOBILE */
.m-mobile-header { display: none; }

/* PAGE WRAPPER */
.page-wrap { max-width: 900px; margin: 40px auto 60px; padding: 0 24px; }
.page-title { animation: fadeUp 0.5s ease-out; margin-bottom: 24px; text-align: center; }
.page-title h1 { font-size: clamp(24px, 3.5vw, 32px); font-weight: 800; color: var(--primary); margin-bottom: 8px; display: inline-flex; align-items: center; gap: 10px; }
.page-title h1 i { color: var(--accent); font-size: 0.9em; }
.page-title p { font-size: 14px; color: var(--text-muted); }

/* ALERT */
.alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: center; animation: fadeUp 0.4s ease-out; display: flex; align-items: center; justify-content: center; gap: 8px; }
.alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert-danger  { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }

/* KARTU UTAMA */
.card {
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid #e2e8f0;
    animation: fadeUp 0.6s ease-out 0.1s both;
    position: relative;
    overflow: hidden;
}

/* BANNER GRADIENT */
.card-banner {
    height: 130px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, var(--accent) 100%);
    position: relative;
}
.card-banner::after {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.15) 1px, transparent 1px);
    background-size: 20px 20px;
}

/* FOTO PROFIL */
.foto-section {
    position: relative;
    padding: 0 32px;
    margin-bottom: 24px;
}
.foto-wrap {
    display: inline-block;
    position: relative;
    cursor: pointer;
    transition: 0.3s;
    margin-top: -60px;
}
.foto-wrap:hover { transform: scale(1.03); }
.foto-wrap:hover .foto-avatar { box-shadow: 0 15px 35px rgba(15,81,50,0.35); }

.foto-avatar {
    width: 120px; height: 120px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 48px; font-weight: 800;
    overflow: hidden;
    border: 5px solid #fff;
    box-shadow: 0 10px 25px rgba(15,81,50,0.2);
    margin: 0 auto;
    transition: 0.3s;
}
.foto-avatar img { width: 100%; height: 100%; object-fit: cover; }

.avatar-cam {
    position: absolute;
    bottom: 5px;
    right: calc(50% - 60px);
    width: 38px; height: 38px;
    border-radius: 50%;
    background: var(--accent);
    color: var(--primary-dark);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    border: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: 0.3s;
    pointer-events: none;
}
.foto-wrap:hover .avatar-cam { transform: scale(1.1); }

.foto-hint {
    display: block;
    margin-top: 8px;
    font-size: 12.5px;
    color: var(--text-muted);
    font-weight: 500;
    text-align: center;
}

.foto-input { display: none; }

/* FORM GRID 2 KOLOM */
.form-body { padding: 0 32px 32px 32px; }
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.form-grid .full { grid-column: 1 / -1; }

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.form-group label i { color: var(--primary); font-size: 14px; }
.form-group label .required { color: #ef4444; }

.input-wrap { position: relative; }
.input-wrap i.field-icon {
    position: absolute;
    left: 16px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
    pointer-events: none;
    transition: 0.3s;
    z-index: 1;
}
.input-wrap:focus-within i.field-icon { color: var(--primary); }

.form-control {
    width: 100%;
    padding: 14px 16px 14px 44px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14.5px;
    font-family: inherit;
    color: var(--text-dark);
    background: #f8fafc;
    transition: 0.3s;
    outline: none;
}
.form-control:focus {
    border-color: var(--primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(15, 81, 50, 0.08);
}
.form-control::placeholder { color: #94a3b8; }

.helper-text {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* TOMBOL */
.form-actions {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 2px dashed #e2e8f0;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn-submit {
    padding: 14px 32px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 14.5px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 6px 20px rgba(15,81,50,0.25);
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(15,81,50,0.35); }

.btn-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 24px;
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 600;
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    transition: 0.2s;
}
.btn-back-link:hover { background: #e2e8f0; color: var(--primary); }

/* FOOTER */
.footer-mini {
    text-align: center;
    padding: 30px 20px;
    color: var(--text-muted);
    font-size: 12.5px;
    border-top: 1px solid #e2e8f0;
    margin-top: 40px;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    body { padding-bottom: 110px; }
    .main-nav { display: none !important; }
    .m-mobile-header {
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 999;
        background: rgba(15, 81, 50, 0.97); backdrop-filter: blur(10px);
        padding: 10px 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    }
    .m-mobile-header .brand { display: flex; align-items: center; gap: 10px; }
    .m-mobile-header .brand img { width: 34px; height: 34px; border-radius: 50%; background: #fff; padding: 2px; object-fit: contain; }
    .m-mobile-header .brand-text strong { display: block; color: #fff; font-size: 13.5px; font-weight: 700; line-height: 1.2; }
    .m-mobile-header .m-avatar {
        width: 34px; height: 34px; border-radius: 50%; background: #ffcc00; color: var(--primary-dark);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 13px; border: 2px solid rgba(255,255,255,0.3);
        object-fit: cover; flex-shrink: 0; overflow: hidden;
    }
    .m-mobile-header .m-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

    .page-wrap { margin: 20px auto 30px; padding: 0 14px; }
    .page-title h1 { font-size: 22px; }
    
    .card { border-radius: 20px; }
    .card-banner { height: 100px; }
    .foto-section { padding: 0 20px; margin-bottom: 18px; }
    .foto-wrap { margin-top: -50px; }
    .foto-avatar { width: 100px; height: 100px; font-size: 40px; border-width: 4px; }
    .avatar-cam { width: 32px; height: 32px; font-size: 14px; right: calc(50% - 50px); border-width: 2px; }
    
    .form-body { padding: 0 20px 24px 20px; }
    .form-grid { grid-template-columns: 1fr; gap: 14px; }
    
    .form-control { padding: 12px 14px 12px 40px; font-size: 13.5px; border-radius: 10px; }
    .input-wrap i.field-icon { left: 14px; font-size: 15px; }
    
    .form-actions { flex-direction: column-reverse; }
    .btn-submit, .btn-back-link { width: 100%; justify-content: center; padding: 14px; font-size: 14px; }
    
    .m-bottom-nav {
        display: flex; position: fixed; left: 15px; right: 15px;
        bottom: calc(15px + env(safe-area-inset-bottom, 0px));
        margin: 0 auto; max-width: 430px;
        background: #0a3622; border-radius: 50px;
        justify-content: space-between; align-items: center;
        padding: 10px 14px; z-index: 1000;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .m-nav-item { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; color: #94a3b8; font-size: 9.5px; font-weight: 600; text-decoration: none; transition: 0.3s; width: 54px; height: 46px; }
    .m-nav-item i { font-size: 20px; }
    .m-nav-item.active { color: #ffcc00; }
    .m-nav-item.logout { color: #fca5a5; }
    .m-nav-center { width: 54px; height: 54px; background: #ffcc00; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0a3622; font-size: 22px; margin-top: -28px; box-shadow: 0 6px 15px rgba(255,204,0,0.4); transition: 0.3s; border: 3px solid #f8fafc; flex-shrink: 0; text-decoration: none; }
    .m-nav-center:active { transform: scale(0.92); }
}

@media (max-width: 380px) {
    .form-control { font-size: 13px; padding: 11px 12px 11px 36px; }
    .input-wrap i.field-icon { left: 12px; font-size: 14px; }
    .m-mobile-header { padding: 8px 12px; }
    .m-mobile-header .brand-text strong { font-size: 12.5px; }
    .m-nav-item { width: 46px; font-size: 9px; }
    .m-nav-item i { font-size: 18px; }
    .m-nav-center { width: 48px; height: 48px; font-size: 20px; margin-top: -24px; }
}
</style>
</head>
<body>

<!-- HEADER MINI MOBILE -->
<div class="m-mobile-header">
    <a href="<?= base_url($kembali) ?>" class="brand">
        <img src="assets/img/foto.png" alt="Logo">
        <div class="brand-text">
            <strong class="typing-text-mobile"></strong>
        </div>
    </a>
    <a href="pengaturan.php" class="m-avatar" title="Akun Saya">
        <?php if ($hasFoto): ?>
            <img src="<?= base_url('assets/img/profil/'.$user['foto']) ?>" alt="Profil">
        <?php else: ?>
            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
        <?php endif; ?>
    </a>
</div>

<!-- NAVBAR DESKTOP -->
<div class="main-nav">
    <div class="main-nav-inner">
        <a href="<?= base_url($kembali) ?>" class="logo-box">
            <img src="assets/img/foto.png" alt="Logo" class="nav-logo">
            <div class="logo-text">
                <strong class="typing-text-desktop"></strong>
                <small>SMK As-Syafi'iyah Jakarta</small>
            </div>
        </a>
        
        <div class="nav-links">
            <a href="<?= base_url($kembali) ?>">Home</a>
            <?php if ($_SESSION['role'] === 'anggota'): ?>
                <a href="anggota/katalog.php">Katalog Buku</a>
                <a href="anggota/riwayat.php">Riwayat Pinjam</a>
            <?php endif; ?>
            
            <a href="pengaturan.php" class="nav-profile-link" title="Pengaturan Akun">
                <?php if ($hasFoto): ?>
                    <div class="nav-avatar"><img src="<?= base_url('assets/img/profil/'.$user['foto']) ?>" alt="Profil"></div>
                <?php else: ?>
                    <div class="nav-avatar"><?= strtoupper(substr($user['nama'], 0, 1)) ?></div>
                <?php endif; ?>
                <span class="nav-profile-name"><?= htmlspecialchars($user['nama']) ?></span>
            </a>

            <a href="auth/logout.php" class="cta">LOGOUT</a>
        </div>
    </div>
</div>

<!-- PAGE CONTENT -->
<div class="page-wrap">

    <div class="page-title">
        <h1>Pengaturan Akun</h1>
        <p>Kelola informasi profil dan keamanan akun Anda</p>
    </div>

    <?php if ($msg_sukses): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($msg_sukses) ?></div>
    <?php endif; ?>
    <?php if ($msg_error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($msg_error) ?></div>
    <?php endif; ?>

    <div class="card">
        <!-- BANNER GRADIENT -->
        <div class="card-banner"></div>

        <form method="POST" enctype="multipart/form-data" id="formPengaturan">
            <input type="file" name="foto" id="foto" accept="image/jpeg, image/png" class="foto-input">

            <!-- FOTO PROFIL -->
            <div class="foto-section">
                <label for="foto" class="foto-wrap" title="Klik untuk ganti foto">
                    <div class="foto-avatar" id="avatarPreview">
                        <?php if ($hasFoto): ?>
                            <img src="<?= base_url('assets/img/profil/'.$user['foto']) ?>" alt="Foto Profil">
                        <?php else: ?>
                            <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="avatar-cam"><i class="bi bi-camera-fill"></i></div>
                    <span class="foto-hint">Klik foto untuk mengganti</span>
                </label>
            </div>

            <!-- FORM BODY -->
            <div class="form-body">
                <div class="form-grid">
                    <!-- NAMA -->
                    <div class="form-group">
                        <label><i class="bi bi-person"></i> Nama Lengkap <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="bi bi-person field-icon"></i>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($user['nama']) ?>" required placeholder="Masukkan nama lengkap">
                        </div>
                    </div>

                    <!-- EMAIL -->
                    <div class="form-group">
                        <label><i class="bi bi-envelope"></i> Alamat Email <span class="required">*</span></label>
                        <div class="input-wrap">
                            <i class="bi bi-envelope field-icon"></i>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required placeholder="nama@email.com">
                        </div>
                    </div>

                    <!-- NO TELP -->
                    <div class="form-group">
                        <label><i class="bi bi-telephone"></i> Nomor HP</label>
                        <div class="input-wrap">
                            <i class="bi bi-telephone field-icon"></i>
                            <input type="text" name="no_telp" class="form-control" value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <!-- PASSWORD -->
                    <div class="form-group">
                        <label><i class="bi bi-lock"></i> Password Baru <span style="color:#94a3b8; font-weight:500;">(opsional)</span></label>
                        <div class="input-wrap">
                            <i class="bi bi-lock field-icon"></i>
                            <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diganti">
                        </div>
                    </div>
                </div>

                <!-- TOMBOL -->
                <div class="form-actions">
                    <a href="<?= base_url($kembali) ?>" class="btn-back-link">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check2-circle"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- FOOTER MINI -->
<div class="footer-mini">
    © <?= date('Y') ?> SMK As-Syafiiyah — All Rights Reserved
</div>

<!-- BOTTOM NAV MOBILE -->
<div class="m-bottom-nav">
    <a href="<?= base_url($kembali) ?>" class="m-nav-item">
        <i class="bi bi-house-door"></i><span>Beranda</span>
    </a>
    
    <?php if ($_SESSION['role'] === 'anggota'): ?>
        <a href="anggota/riwayat.php" class="m-nav-item"><i class="bi bi-clock-history"></i><span>Riwayat</span></a>
        <a href="anggota/katalog.php" class="m-nav-center" title="Katalog Buku"><i class="bi bi-grid"></i></a>
        <a href="pengaturan.php" class="m-nav-item active"><i class="bi bi-person"></i><span>Akun</span></a>
    <?php else: ?>
        <a href="<?= base_url($kembali) ?>" class="m-nav-item"><i class="bi bi-house-door"></i><span>Dashboard</span></a>
        <a href="pengaturan.php" class="m-nav-center" title="Pengaturan"><i class="bi bi-grid"></i></a>
        <a href="pengaturan.php" class="m-nav-item active"><i class="bi bi-person"></i><span>Pengaturan</span></a>
    <?php endif; ?>
    
    <a href="auth/logout.php" class="m-nav-item logout"><i class="bi bi-box-arrow-right"></i><span>Keluar</span></a>
</div>

<script>
    // Preview foto realtime
    const fotoInput = document.getElementById('foto');
    const avatarPreview = document.getElementById('avatarPreview');

    if (fotoInput && avatarPreview) {
        fotoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    avatarPreview.innerHTML = '<img src="' + ev.target.result + '" alt="Preview">';
                    avatarPreview.style.animation = 'none';
                    void avatarPreview.offsetWidth;
                    avatarPreview.style.animation = 'popIn 0.4s ease-out';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // EFEK KETIK
    document.addEventListener('DOMContentLoaded', function() {
        const texts = ["SMK As-Syafi'iyah Jakarta", "Perpustakaan Digital"];
        let count = 0, currentText = '', isDeleting = false;
        let speed = 100, deletingSpeed = 50, delayBetweenTexts = 2000;

        function type() {
            const mobileEl = document.querySelector('.typing-text-mobile');
            const desktopEl = document.querySelector('.typing-text-desktop');
            if (!mobileEl && !desktopEl) return;

            if (isDeleting) {
                currentText = texts[count].substring(0, currentText.length - 1);
                speed = deletingSpeed;
            } else {
                currentText = texts[count].substring(0, currentText.length + 1);
                speed = 100;
            }

            const htmlContent = currentText + '<span class="typing-cursor"></span>';
            if (mobileEl) mobileEl.innerHTML = htmlContent;
            if (desktopEl) desktopEl.innerHTML = htmlContent;

            if (!isDeleting && currentText === texts[count]) {
                isDeleting = true; speed = delayBetweenTexts;
            } else if (isDeleting && currentText === '') {
                isDeleting = false; count = (count + 1) % texts.length; speed = 500;
            }
            setTimeout(type, speed);
        }
        setTimeout(type, 800);
    });
</script>

</body>
</html>