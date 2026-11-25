<?php
$nama_user = $_SESSION['nama'] ?? 'User';
$role_user = $_SESSION['role'] ?? 'anggota';

$role_label = [
    'admin'   => 'Administrator',
    'petugas' => 'Petugas Perpustakaan',
    'anggota' => 'Anggota Perpustakaan'
][$role_user] ?? 'Pengguna';
?>

<!-- Pengecekan Awal: Jika sessionStorage 'showWelcome' tidak ada, jangan tampilkan apa-apa -->
<div id="welcomeScreen" style="display: none;">
    <div class="welcome-content">
        <div class="welcome-logo">
            <img src="<?= base_url('assets/img/foto.png') ?>" alt="Logo">
        </div>
        <h1 class="welcome-title">Halo, <?= htmlspecialchars($nama_user) ?>!</h1>
        <p class="welcome-subtitle">Selamat Datang di Perpus Digital</p>
        
        <div class="welcome-role"><?= htmlspecialchars($role_label) ?></div>
        <div class="welcome-loader"></div>
    </div>
</div>

<style>
/* ... (Copy semua CSS welcome screen dari kode sebelumnya ke sini) ... */
#welcomeScreen {
    position: fixed; inset: 0; z-index: 99999;
    background: linear-gradient(135deg, #0a3622 0%, #0f5132 100%);
    flex-direction: column; align-items: center; justify-content: center;
    color: #fff; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    transition: opacity 0.6s ease, visibility 0.6s ease;
    overflow: hidden;
}
#welcomeScreen.show { display: flex !important; }
#welcomeScreen::before {
    content: ""; position: absolute; width: 150vw; height: 150vw; border-radius: 50%;
    border: 1px solid rgba(255, 204, 0, 0.1); top: -50vw; right: -50vw;
    animation: spinSlow 20s linear infinite; pointer-events: none;
}
#welcomeScreen::after {
    content: ""; position: absolute; width: 100vw; height: 100vw; border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.05); bottom: -30vw; left: -30vw;
    animation: spinSlow 15s linear reverse infinite; pointer-events: none;
}
@keyframes spinSlow { 100% { transform: rotate(360deg); } }
.welcome-content {
    position: relative; z-index: 2; text-align: center; padding: 20px;
    animation: popIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: scale(0.9);
}
@keyframes popIn { to { opacity: 1; transform: scale(1); } }
.welcome-logo {
    width: 100px; height: 100px; border-radius: 50%; background: #ffffff; margin: 0 auto 20px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3), 0 0 0 6px rgba(255,204,0,0.2); padding: 10px;
}
.welcome-logo img { width: 100%; height: 100%; object-fit: contain; }
.welcome-title { font-size: 26px; font-weight: 800; margin-bottom: 8px; letter-spacing: -0.5px; }
.welcome-subtitle { font-size: 14px; color: rgba(255, 255, 255, 0.7); font-weight: 500; letter-spacing: 1px; text-transform: uppercase; }
.welcome-role {
    display: inline-block; margin-top: 15px; padding: 6px 16px;
    background: rgba(255, 204, 0, 0.15); border: 1px solid rgba(255, 204, 0, 0.3);
    border-radius: 20px; color: #ffcc00; font-size: 12px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
}
.welcome-loader { width: 180px; height: 4px; background: rgba(255, 255, 255, 0.15); border-radius: 4px; margin: 30px auto 0; overflow: hidden; position: relative; }
.welcome-loader::after { content: ""; position: absolute; left: 0; top: 0; height: 100%; width: 40%; background: #ffcc00; border-radius: 4px; animation: loadingBar 1.5s ease-in-out infinite; }
@keyframes loadingBar { 0% { left: -40%; width: 40%; } 50% { left: 20%; width: 60%; } 100% { left: 100%; width: 40%; } }
#welcomeScreen.hidden { opacity: 0; visibility: hidden; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const screen = document.getElementById('welcomeScreen');
        
        // Cek apakah ini login baru (sessionStorage belum ada)
        if (!sessionStorage.getItem('has_seen_welcome')) {
            // Jika baru login, tampilkan screen dan pasang penanda
            screen.classList.add('show');
            sessionStorage.setItem('has_seen_welcome', 'true');

            // Sembunyikan setelah 1.8 detik
            setTimeout(() => {
                screen.classList.add('hidden');
                setTimeout(() => screen.remove(), 600);
            }, 1800);
        } else {
            // Jika sudah pernah lihat (refresh), hapus elemen langsung
            screen.remove();
        }
    });
</script>