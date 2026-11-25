<?php
require_once 'config/database.php';
cekLogin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panduan Penggunaan - Perpustakaan Digital</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Tahoma, sans-serif; }
body {
    background: linear-gradient(135deg, #003d7a 0%, #0055a5 100%);
    background-size: 200% 200%;
    animation: bgShift 15s ease infinite;
    min-height: 100vh;
    padding: 40px 20px;
    color: #333;
}
@keyframes bgShift {
    0%,100% { background-position: 0% 50%; }
    50%     { background-position: 100% 50%; }
}
@keyframes fadeUp {
    from { opacity:0; transform:translateY(30px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes slideIn {
    from { opacity:0; transform:translateX(-20px); }
    to   { opacity:1; transform:translateX(0); }
}
.panduan-wrap {
    max-width: 900px;
    margin: 0 auto;
    background: #fff;
    border-radius: 20px;
    padding: 40px 44px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: fadeUp 0.6s ease-out;
}
.panduan-wrap h1 {
    color: #003d7a;
    font-size: 30px;
    margin-bottom: 8px;
    font-family: Georgia, serif;
    letter-spacing: 0.5px;
}
.panduan-wrap .sub {
    color: #888;
    font-size: 14px;
    margin-bottom: 32px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}
.step-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 34px;
}
.step-item {
    display: flex;
    gap: 18px;
    padding: 20px 22px;
    background: #f9fafb;
    border-radius: 14px;
    border-left: 5px solid #ffcc00;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    animation: slideIn 0.5s ease-out both;
}
.step-item:nth-child(1) { animation-delay: 0.1s; }
.step-item:nth-child(2) { animation-delay: 0.2s; }
.step-item:nth-child(3) { animation-delay: 0.3s; }
.step-item:nth-child(4) { animation-delay: 0.4s; }
.step-item:nth-child(5) { animation-delay: 0.5s; }
.step-item:hover {
    background: #f0f4ff;
    transform: translateX(8px);
    box-shadow: 0 10px 25px rgba(0,61,122,0.12);
    border-left-color: #003d7a;
}
.step-num {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #003d7a;
    color: #ffcc00;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 17px;
    flex-shrink: 0;
    transition: 0.3s;
    box-shadow: 0 4px 12px rgba(0,61,122,0.25);
}
.step-item:hover .step-num {
    transform: scale(1.1) rotate(-8deg);
    background: #ffcc00;
    color: #003d7a;
}
.step-body h3 {
    color: #003d7a;
    font-size: 16px;
    margin-bottom: 6px;
    font-weight: 800;
}
.step-body p {
    font-size: 13.5px;
    color: #666;
    line-height: 1.65;
}
.step-body p b {
    color: #003d7a;
    font-weight: 700;
}
.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 30px;
    background: #003d7a;
    color: #fff;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 700;
    font-size: 13px;
    transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-transform: uppercase;
    letter-spacing: 1px;
    position: relative;
    overflow: hidden;
}
.btn-back::before {
    content: "";
    position: absolute;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: 0.6s;
}
.btn-back:hover::before { left: 100%; }
.btn-back:hover {
    background: #0055a5;
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,61,122,0.35);
    color: #fff;
    text-decoration: none;
}
@media (max-width: 600px) {
    .panduan-wrap { padding: 30px 24px; }
    .panduan-wrap h1 { font-size: 22px; }
}
</style>
</head>
<body>
<div class="panduan-wrap">
    <h1>Panduan Penggunaan</h1>
    <p class="sub">Pelajari cara menggunakan perpustakaan digital dalam 5 langkah mudah</p>

    <div class="step-list">
        <div class="step-item">
            <div class="step-num">1</div>
            <div class="step-body">
                <h3>Login ke Akun Anda</h3>
                <p>Masukkan <b>email</b> dan <b>password</b> yang sudah terdaftar. Jika belum punya akun, lakukan registrasi terlebih dahulu melalui halaman daftar.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-num">2</div>
            <div class="step-body">
                <h3>Cari Buku</h3>
                <p>Gunakan menu <b>Katalog</b> atau fitur <b>One Search</b> untuk mencari buku berdasarkan judul, pengarang, atau ISBN.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-num">3</div>
            <div class="step-body">
                <h3>Lihat Detail Buku</h3>
                <p>Klik tombol <b>Baca</b> pada kartu buku untuk melihat sinopsis, pengarang, tahun terbit, lokasi rak, dan ketersediaan stok.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-num">4</div>
            <div class="step-body">
                <h3>Pinjam Buku</h3>
                <p>Klik tombol <b>Pinjam</b>. Permintaan Anda akan diproses oleh petugas. Setelah disetujui, status berubah menjadi <b>Dipinjam</b> dan buku dapat diambil di rak yang tertera.</p>
            </div>
        </div>
        <div class="step-item">
            <div class="step-num">5</div>
            <div class="step-body">
                <h3>Kembalikan Buku</h3>
                <p>Kembalikan buku sebelum <b>jatuh tempo (7 hari)</b> untuk menghindari denda sebesar <b>Rp 1.000/hari</b> keterlambatan. Cek status di menu <b>Riwayat Pinjam</b>.</p>
            </div>
        </div>
    </div>

    <a href="javascript:history.back()" class="btn-back">← Kembali</a>
</div>
</body>
</html>