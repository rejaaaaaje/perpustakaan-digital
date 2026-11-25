<?php
// 1. Selalu mulai session paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Panggil koneksi database & fungsi-fungsi
require_once __DIR__ . '/../config/database.php';

// 3. Baru cek login (admin/petugas/anggota)
// Pastikan di database.php kamu punya fungsi cekLogin()
if (function_exists('cekLogin')) {
    cekLogin(); 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?= $title ?? 'Perpustakaan Digital' ?></title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    /* Tambahan style untuk foto profil di topbar */
    .topbar-user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: inherit;
    }
    .topbar-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #0f5132;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 15px;
        object-fit: cover;
        border: 2px solid #e2e8f0;
        transition: transform 0.2s;
    }
    .topbar-user-profile:hover .topbar-avatar {
        transform: scale(1.05);
        border-color: #0f5132;
    }
</style>
</head>
<body>
<div class="app">

<script>
    // ==========================================
    // FITUR HEARTBEAT: Mengirim sinyal online
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        // Gunakan URL absolut yang mengarah ke root domain
        const urlHeartbeat = '/heartbeat.php';
        
        // Kirim sinyal pertama kali
        fetch(urlHeartbeat)
            .then(res => console.log('Heartbeat terkirim!'))
            .catch(err => console.log('Heartbeat error:', err));

        // Kirim sinyal berulang setiap 15 detik agar lebih responsif
        setInterval(function() {
            fetch(urlHeartbeat + '?t=' + new Date().getTime())
                .catch(err => console.log('Heartbeat error:', err));
        }, 15000); // 15.000 ms = 15 detik
    });
</script>