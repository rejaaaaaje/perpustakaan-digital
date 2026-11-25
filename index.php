<?php
require_once 'config/database.php';

// ==========================================
// SISTEM TETAP SAMA (TIDAK DIUBAH)
// ==========================================
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':   redirect('admin/dashboard.php'); break;
        case 'petugas': redirect('petugas/dashboard.php'); break;
        case 'anggota': redirect('anggota/dashboard.php'); break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perpustakaan Digital — SMK As-Syafi'iyah Jakarta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- Font Klasik (Playfair Display & Cinzel untuk kesan mewah) -->
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<style>
    /* ============================================================
       RESET & DASAR
       ============================================================ */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Playfair Display', serif;
        color: #fff;
        margin: 0;
        overflow: hidden; /* Menghilangkan scrollbar */
        background-color: #000;
    }

    a { text-decoration: none; color: inherit; transition: 0.3s; }

    /* ============================================================
       HERO SECTION (LAYAR PENUH)
       ============================================================ */
    .hero-full {
        position: relative;
        width: 100vw;
        height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        
        /* 👇 GANTI URL DI BAWAH INI DENGAN GAMBAR PERPUSTAKAAN KAMU 👇 */
        background: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
    }

    /* Overlay gelap (tanpa hijau) */
    .hero-full::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(10, 10, 10, 0.75); /* Hitam pekat transparan */
        z-index: 1;
    }

    /* Garis Tepi Bingkai (Sangat Tipis) */
    .hero-frame {
        position: absolute;
        inset: 20px; /* Jarak dari pinggir layar */
        border: 1px solid rgba(255, 255, 255, 0.15);
        z-index: 2;
        pointer-events: none;
    }

    /* ============================================================
       TYPOGRAPHY TENGAH (MEGAH)
       ============================================================ */
    .hero-center {
        position: relative;
        z-index: 3;
        text-align: center;
        padding: 0 20px;
    }

    .text-top {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        font-style: italic;
        color: #e5b869; /* Warna Emas */
        letter-spacing: 2px;
        margin-bottom: 10px;
        line-height: 1;
    }
    
    .text-top span {
        font-size: 22px;
        display: block;
        margin: 10px 0;
        font-style: normal;
        color: #fff;
    }

    .hero-center h1 {
        font-family: 'Cinzel', serif; /* Font Romawi/Klasik */
        font-size: clamp(50px, 8vw, 120px);
        font-weight: 400;
        letter-spacing: 4px;
        line-height: 1.1;
        color: #fff;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8);
    }

    /* ============================================================
       BOTTOM BAR (KIRI & KANAN BAWAH)
       ============================================================ */
    .hero-bottom {
        position: absolute;
        bottom: 40px;
        left: 0;
        right: 0;
        z-index: 3;
        display: flex;
        justify-content: space-between;
        padding: 0 50px;
        font-family: 'Playfair Display', serif;
        font-size: 15px;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.8);
    }

    .hero-bottom-left {
        display: flex;
        gap: 15px;
    }

    .hero-bottom-right a {
        border-bottom: 1px solid transparent;
        padding-bottom: 2px;
        font-weight: 600;
    }

    .hero-bottom-right a:hover {
        color: #e5b869;
        border-bottom: 1px solid #e5b869;
    }

    /* ============================================================
       ANIMASI FADE IN
       ============================================================ */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .anim-1 { animation: fadeIn 1.5s ease-out forwards; opacity: 0; animation-delay: 0.3s; }
    .anim-2 { animation: fadeIn 1.5s ease-out forwards; opacity: 0; animation-delay: 0.8s; }
    .anim-3 { animation: fadeIn 1.5s ease-out forwards; opacity: 0; animation-delay: 1.3s; }

    /* Responsive Mobile */
    @media (max-width: 768px) {
        .hero-bottom { 
            flex-direction: column-reverse; 
            align-items: center; 
            gap: 20px; 
            bottom: 30px;
            padding: 0 20px;
            text-align: center;
        }
        .hero-center h1 { font-size: 40px; letter-spacing: 2px; }
        .text-top { font-size: 22px; }
        .text-top span { font-size: 16px; }
    }
</style>
</head>
<body>

    <div class="hero-full">
        <!-- Bingkai Garis Tipis -->
        <div class="hero-frame"></div>

        <!-- Teks Utama Tengah -->
        <div class="hero-center">
            <div class="text-top anim-1">
                Selamat Datang
                <span>di</span>
            </div>
            <h1 class="anim-2">PERPUSTAKAAN DIGITAL</h1>
        </div>

        <!-- Teks Kiri & Kanan Bawah -->
        <div class="hero-bottom anim-3">
            <div class="hero-bottom-left">
                <span>Setiap Hari</span>
                <span>07:00 - 16:00 WIB</span>
            </div>
            <div class="hero-bottom-right">
                <a href="auth/login.php">Masuk / Daftar &rarr;</a>
            </div>
        </div>
    </div>

</body>
</html>