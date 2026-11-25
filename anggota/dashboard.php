<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { redirect('index.php'); }

$uid = (int)$_SESSION['user_id'];

$aktif     = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE user_id=$uid AND status='dipinjam'")->fetch_assoc()['c'];
$riwayat   = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE user_id=$uid")->fetch_assoc()['c'];
$denda     = $conn->query("SELECT COALESCE(SUM(denda),0) c FROM pinjaman WHERE user_id=$uid")->fetch_assoc()['c'];
$totalBuku = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'];

/* Koleksi Terbaru + Rating (Mendukung Multi Kategori) */
$bukuBaru = $conn->query("SELECT b.*, 
                          GROUP_CONCAT(DISTINCT k.nama_kategori SEPARATOR ', ') as nama_kategori, 
                          (SELECT AVG(rating) FROM komentar WHERE buku_id = b.id) as avg_rating 
                          FROM buku b 
                          LEFT JOIN buku_kategori bk ON b.id = bk.buku_id 
                          LEFT JOIN kategori k ON bk.kategori_id = k.id 
                          GROUP BY b.id 
                          ORDER BY b.id DESC LIMIT 5");

/* Rekomendasi + Rating (Mendukung Multi Kategori) */
$rekomendasi = $conn->query("SELECT b.*, 
                             GROUP_CONCAT(DISTINCT k.nama_kategori SEPARATOR ', ') as nama_kategori, 
                             (SELECT AVG(rating) FROM komentar WHERE buku_id = b.id) as avg_rating 
                             FROM buku b 
                             LEFT JOIN buku_kategori bk ON b.id = bk.buku_id 
                             LEFT JOIN kategori k ON bk.kategori_id = k.id 
                             WHERE b.stok_tersedia > 0 
                             GROUP BY b.id 
                             ORDER BY RAND() LIMIT 5");

require_once '../includes/berita-rss.php';
$beritaOtomatis = getBeritaRSS(6);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
:root {
    --primary: #0f5132;
    --primary-dark: #0a3622;
    --primary-light: #f0fdf4;
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
@keyframes fadeUp { from { opacity:0; transform:translateY(40px); } to { opacity:1; transform:translateY(0); } }
.reveal { opacity: 0; transform: translateY(40px); transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1); }
.reveal.active { opacity: 1; transform: translateY(0); }
.star-rating { color: #f59e0b; font-size: 14px; display: inline-flex; gap: 2px; }
.star-rating i { margin-right: 1px; }
.star-rating-gray { color: #cbd5e1; }
.rating-text { font-size: 12px; color: #64748b; font-weight: 600; margin-left: 6px; }

/* BANNER PENGUMUMAN */
.announcement-banner { background: #fef3c7; color: #92400e; padding: 12px 20px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #fde68a; position: relative; z-index: 1001; }
.announcement-banner i { font-size: 18px; }

/* MODAL PESAN PRIBADI */
.pesan-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: 0.3s; }
.pesan-overlay.show { opacity: 1; pointer-events: auto; }
.pesan-box { background: #fff; width: 90%; max-width: 400px; border-radius: 16px; padding: 24px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); transform: translateY(20px); transition: 0.3s; }
.pesan-overlay.show .pesan-box { transform: translateY(0); }
.pesan-icon { width: 60px; height: 60px; border-radius: 50%; background: #fef2f2; color: #ef4444; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px; }
.pesan-box h3 { font-size: 18px; color: #1e293b; margin-bottom: 10px; }
.pesan-box p { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px; }
.pesan-box button { background: #0f5132; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; width: 100%; font-size: 14px; }

/* HEADER MINI MOBILE */
.m-mobile-header { display: none; }

/* NAVBAR DESKTOP */
.main-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; transition: all 0.4s ease-in-out; background: transparent; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
.main-nav.scrolled { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
.main-nav-inner { max-width: 1280px; margin: 0 auto; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.logo-box { display: flex; align-items: center; gap: 12px; transition: 0.3s; }
.nav-logo { width: 44px; height: 44px; border-radius: 50%; object-fit: contain; background: #fff; padding: 2px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.logo-text strong { color: var(--white); font-size: 18px; font-weight: 700; display: block; line-height: 1.2; transition: color 0.3s; }
.logo-text small { color: rgba(255,255,255,0.8); font-size: 11px; font-weight: 500; letter-spacing: 0.5px; transition: color 0.3s; }
.main-nav.scrolled .logo-text strong { color: var(--primary); }
.main-nav.scrolled .logo-text small { color: var(--text-muted); }
.nav-links { display: flex; align-items: center; gap: 8px; }
.nav-links a { padding: 8px 16px; font-size: 14px; font-weight: 600; color: rgba(255,255,255,0.9); border-radius: 8px; transition: all 0.3s; }
.nav-links a:hover, .nav-links a.active { color: var(--white); background: rgba(255,255,255,0.15); }
.main-nav.scrolled .nav-links a { color: var(--text-dark); }
.main-nav.scrolled .nav-links a:hover, .main-nav.scrolled .nav-links a.active { color: var(--primary); background: var(--primary-light); }
.nav-profile-link { display: flex; align-items: center; gap: 10px; padding: 4px 16px 4px 4px; border-radius: 50px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s; margin-left: 12px; }
.main-nav.scrolled .nav-profile-link { background: #f1f5f9; border-color: #e2e8f0; }
.nav-profile-link:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.nav-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; object-fit: cover; overflow: hidden; }
.nav-avatar img { width: 100%; height: 100%; object-fit: cover; }
.nav-profile-name { font-size: 13px; font-weight: 600; color: var(--white); transition: color 0.3s; }
.main-nav.scrolled .nav-profile-name { color: var(--primary); }
.nav-links a.cta { background: var(--white); color: var(--primary); padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
.nav-links a.cta:hover { background: #f8fafc; transform: translateY(-2px); }
.main-nav.scrolled .nav-links a.cta { background: var(--primary); color: var(--white); }
.main-nav.scrolled .nav-links a.cta:hover { background: var(--primary-dark); }

/* HERO SLIDER */
.hero-slider { position: relative; width: 100%; height: 550px; overflow: hidden; background: var(--primary-dark); border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; }
.slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out; transform: scale(1.05); display: flex; align-items: center; z-index: 1; }
.slide.active { opacity: 1; transform: scale(1); z-index: 2; }
.slide-bg { position: absolute; inset: 0; background-size: cover; background-position: center; z-index: -1; }
.slide-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(15,81,50,0.95) 0%, rgba(10,54,34,0.8) 100%); z-index: 0; }
.hero-inner { max-width: 1280px; margin: 0 auto; padding: 100px 24px 0; width: 100%; position: relative; z-index: 3; display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; align-items: center; }
.hero-text h1 { font-size: clamp(36px, 4vw, 52px); font-weight: 800; line-height: 1.15; color: #fff; margin-bottom: 20px; }
.hero-text h1 span { display: block; }
.hero-text p { font-size: 17px; line-height: 1.8; color: #cbd5e1; margin-bottom: 32px; }
.btn-hero { padding: 14px 32px; border-radius: 8px; font-weight: 700; font-size: 15px; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; background: #fff; color: var(--primary); }
.btn-hero:hover { background: #f1f5f9; transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
.hero-image-box { width: 100%; height: 350px; background: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80') center/cover; border-radius: var(--radius-lg); border: 6px solid rgba(255,255,255,0.1); box-shadow: 0 30px 60px rgba(0,0,0,0.4); position: relative; }
.slider-nav { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); display: flex; gap: 12px; z-index: 10; }
.slider-dot { width: 12px; height: 12px; border-radius: 50%; background: rgba(255,255,255,0.3); cursor: pointer; transition: 0.3s; }
.slider-dot.active { background: #fff; transform: scale(1.3); }
.slider-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 50px; height: 50px; border-radius: 50%; background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; z-index: 10; transition: 0.3s; backdrop-filter: blur(4px); }
.slider-arrow:hover { background: rgba(255,255,255,0.3); }
.arrow-prev { left: 30px; }
.arrow-next { right: 30px; }

/* FLOATING STATS */
.stats-float { max-width: 1280px; margin: -50px auto 60px; padding: 0 24px; position: relative; z-index: 10; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
.stat-card-float { background: var(--white); padding: 24px 30px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 20px; box-shadow: var(--shadow-md); border: 1px solid #e2e8f0; transition: 0.3s; }
.stat-card-float:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
.stat-info h3 { font-size: 28px; font-weight: 800; color: var(--text-dark); margin: 0; line-height: 1; }
.stat-info p { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 6px 0 0 0; }

/* ============================================================
   SECTION TENTANG KAMI (DESAIN BARU SEPERTI REFERENSI)
   ============================================================ */
.tentang-section {
    max-width: 1100px;
    margin: 40px auto 80px;
    padding: 0 24px;
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 50px;
    align-items: center;
}
.tentang-image-wrap {
    position: relative;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}
.tentang-image {
    width: 100%;
    max-width: 380px;
    height: auto;
    object-fit: contain;
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 20px 30px rgba(0,0,0,0.15));
}
.tentang-text {
    position: relative;
    z-index: 1;
}
.tentang-text h2 {
    font-size: clamp(24px, 3vw, 32px);
    font-weight: 800;
    color: #1e293b;
    line-height: 1.25;
    margin-bottom: 16px;
}
.tentang-text p {
    font-size: 14.5px;
    color: #475569;
    line-height: 1.7;
    margin-bottom: 16px;
    text-align: justify;
}
/* Kotak Putih Pembungkus Teks */
.tentang-card-box {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
    margin-bottom: 24px;
    max-height: 400px;
    overflow-y: auto;
}
.tentang-card-box::-webkit-scrollbar { width: 6px; }
.tentang-card-box::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

/* Grid Statistik Bawah */
.tentang-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 24px;
}
.stat-box-custom {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.stat-box-custom h3 {
    font-size: 26px;
    font-weight: 800;
    color: #1e293b;
    margin: 0 0 4px 0;
}
.stat-box-custom p {
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
    margin: 0;
}
.btn-tentang-custom {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #0f5132;
    color: #fff;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: 0.3s;
}
.btn-tentang-custom:hover {
    background: #0a3622;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(15,81,50,0.3);
}

/* ============================================================
   KOLEKSI BUKU DIGITAL (DESAIN BARU SEPERTI DIGIDO)
   ============================================================ */
.koleksi-section {
    background: #fdfcf9; /* Background krem terang */
    padding: 80px 24px;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
}
.koleksi-header {
    text-align: center;
    margin-bottom: 50px;
}
.koleksi-header h2 {
    font-size: 32px;
    font-weight: 800;
    color: #4a3b4a;
    margin-bottom: 12px;
}
.koleksi-header p {
    font-size: 16px;
    color: #64748b;
}

.koleksi-grid {
    max-width: 1280px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 24px;
}
.buku-card-digido {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}
.buku-card-digido:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    border-color: #cbd5e1;
}
.buku-cover-digido {
    height: 300px;
    background: #f1f5f9;
    position: relative;
}
.buku-cover-digido img {
    width: 100%; height: 100%; object-fit: cover;
}
.buku-badge-top {
    position: absolute;
    top: 10px; right: 10px;
    background: #fff;
    color: #0f5132;
    font-size: 10px;
    font-weight: 800;
    padding: 4px 8px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.buku-info-digido {
    padding: 16px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.buku-info-digido h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.4;
    margin-bottom: 6px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.buku-info-digido .pengarang {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 16px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.buku-meta-digido {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}
.buku-rating-digido {
    display: flex;
    align-items: center;
    font-size: 11px;
    color: #64748b;
}
.buku-rating-digido .star {
    color: #f59e0b;
    margin-right: 4px;
}
.buku-stok-digido {
    font-size: 11px;
    font-weight: 700;
    color: #0f5132;
    background: #f0fdf4;
    padding: 2px 8px;
    border-radius: 10px;
}

/* BERITA SLIDER */
.berita-slider-container { position: relative; overflow: hidden; padding-bottom: 20px; }
.berita-track { display: flex; gap: 24px; transition: transform 0.5s ease-in-out; cursor: grab; }
.berita-track:active { cursor: grabbing; }
.news-card { flex: 0 0 calc(33.333% - 16px); background: var(--white); border-radius: var(--radius-md); overflow: hidden; display: flex; flex-direction: column; border: 1px solid #e2e8f0; transition: 0.3s; user-select: none; }
.news-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-5px); border-color: var(--primary); }
.news-img { height: 200px; background: #e2e8f0; position: relative; background-size: cover; background-position: center; pointer-events: none; }
.news-tag { position: absolute; top: 16px; left: 16px; background: var(--primary); color: var(--white); padding: 6px 14px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
.news-content { padding: 24px; flex: 1; display: flex; flex-direction: column; pointer-events: none; }
.news-content h3 { font-size: 18px; font-weight: 700; color: var(--text-dark); line-height: 1.4; margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.news-content p { font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 20px; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.news-meta { display: flex; gap: 15px; font-size: 12px; color: #94a3b8; font-weight: 600; }
.news-meta span { display: flex; align-items: center; gap: 6px; }
.berita-nav { display: flex; justify-content: center; gap: 10px; margin-top: 24px; }
.berita-dot { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; cursor: pointer; transition: 0.3s; }
.berita-dot.active { background: var(--primary); transform: scale(1.3); }

/* SECTION UMUM */
.section { max-width: 1280px; margin: 0 auto; padding: 60px 24px; }
.section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px; }
.section-title-wrap h2 { font-size: 28px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px; }
.section-title-wrap p { font-size: 15px; color: var(--text-muted); }
.section-link { font-size: 14px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px; transition: 0.2s; }
.section-link:hover { gap: 12px; }

/* REKOMENDASI */
.reco-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
.reco-card { background: var(--white); border-radius: var(--radius-md); overflow: hidden; border: 1px solid #e2e8f0; transition: 0.3s; display: flex; flex-direction: column; }
.reco-card:hover { border-color: var(--primary); box-shadow: var(--shadow-md); transform: translateY(-5px); }
.reco-cover { height: 240px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
.reco-cover img { width: 100%; height: 100%; object-fit: cover; }
.reco-cover::before { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.5), transparent 50%); z-index: 1; }
.reco-body { padding: 20px; display: flex; flex-direction: column; flex: 1; }
.reco-body h4 { font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; line-height: 1.3; }
.reco-body p { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; }

/* FOOTER */
.footer { background: var(--primary-dark); color: var(--white); padding: 80px 24px 30px; margin-top: 60px; }
.footer-inner { max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1.5fr; gap: 60px; }
.footer-brand { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.footer-brand img { width: 48px; height: 48px; border-radius: 50%; background: var(--white); padding: 4px; }
.footer-brand h4 { font-size: 20px; font-weight: 700; margin: 0; }
.footer-brand p { font-size: 13px; color: #94a3b8; margin: 4px 0 0 0; }
.footer-desc { font-size: 15px; color: #94a3b8; line-height: 1.8; max-width: 400px; }
.footer h5 { font-size: 16px; font-weight: 700; color: var(--white); margin-bottom: 24px; }
.footer ul { list-style: none; }
.footer ul li { margin-bottom: 14px; }
.footer ul a { color: #94a3b8; font-size: 14px; font-weight: 500; transition: 0.2s; }
.footer ul a:hover { color: var(--white); padding-left: 5px; }
.footer-contact p { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: #94a3b8; margin-bottom: 16px; line-height: 1.6; }
.footer-bottom { max-width: 1280px; margin: 60px auto 0; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; font-size: 13px; color: #64748b; }

/* TYPEWRITER */
.typing-cursor {
    display: inline-block; width: 2px; height: 1em; background-color: currentColor;
    margin-left: 2px; animation: blink 0.7s infinite; vertical-align: text-bottom;
}
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .hero-inner { grid-template-columns: 1fr; text-align: center; padding-top: 110px; }
    .hero-image-box { display: none; }
    .news-card { flex: 0 0 calc(50% - 12px); }
    .footer-inner { grid-template-columns: 1fr 1fr; }
    .tentang-section { gap: 30px; }
}

@media (max-width: 768px) {
    body { padding-bottom: 100px; }
    .main-nav { display: none !important; }
    
    .m-mobile-header { display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 999; background: rgba(15, 81, 50, 0.97); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 10px 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.15); }
    .m-mobile-header .brand { display: flex; align-items: center; gap: 10px; }
    .m-mobile-header .brand img { width: 34px; height: 34px; border-radius: 50%; background: #fff; padding: 2px; object-fit: contain; }
    .m-mobile-header .brand-text strong { display: block; color: #fff; font-size: 13.5px; font-weight: 700; line-height: 1.2; }
    .m-mobile-header .m-avatar { width: 34px; height: 34px; border-radius: 50%; background: #ffcc00; color: var(--primary-dark); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; border: 2px solid rgba(255,255,255,0.3); object-fit: cover; flex-shrink: 0; }
    .m-mobile-header .m-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

    .announcement-banner { font-size: 12px; padding: 10px 14px; }

    .hero-slider { height: 440px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
    .hero-inner { padding: 40px 16px 0; }
    .hero-text h1 { font-size: 26px; line-height: 1.25; margin-bottom: 12px; }
    .hero-text p { font-size: 13.5px; line-height: 1.6; margin-bottom: 20px; padding: 0 10px; }
    .btn-hero { padding: 12px 24px; font-size: 13px; }
    .slider-arrow { display: none; }
    .slider-nav { bottom: 15px; gap: 8px; }
    .slider-dot { width: 10px; height: 10px; }

    .stats-float { grid-template-columns: repeat(3, 1fr); margin-top: -30px; gap: 8px; padding: 0 12px; }
    .stat-card-float { padding: 12px 8px; flex-direction: column; text-align: center; gap: 8px; border-radius: 12px; }
    .stat-info h3 { font-size: 18px; }
    .stat-info p { font-size: 9px; margin-top: 2px; line-height: 1.2; }

    /* TENTANG KAMI MOBILE */
    .tentang-section { grid-template-columns: 1fr; gap: 30px; padding: 0 16px; margin-top: 20px; margin-bottom: 40px; }
    .tentang-image { max-width: 250px; }
    .tentang-card-box { padding: 20px; max-height: none; overflow-y: visible; }
    .tentang-text h2 { font-size: 22px; }
    .tentang-text p { font-size: 13.5px; line-height: 1.6; }
    .tentang-stats-grid { gap: 10px; }
    .stat-box-custom { padding: 15px 10px; }
    .stat-box-custom h3 { font-size: 20px; }
    .stat-box-custom p { font-size: 11px; }
    .btn-tentang-custom { width: 100%; justify-content: center; padding: 12px; font-size: 13px; }

    /* KOLEKSI BUKU DIGITAL MOBILE */
    .koleksi-section { padding: 40px 16px; }
    .koleksi-header h2 { font-size: 24px; }
    .koleksi-header p { font-size: 13px; }
    .koleksi-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .buku-cover-digido { height: 200px; }
    .buku-info-digido { padding: 12px; }
    .buku-info-digido h4 { font-size: 13px; }
    .buku-info-digido .pengarang { font-size: 11px; margin-bottom: 10px; }
    .buku-rating-digido { font-size: 10px; }
    .buku-stok-digido { font-size: 10px; padding: 2px 6px; }

    .section { padding: 40px 16px; }
    .section-header { flex-direction: column; align-items: flex-start; gap: 8px; margin-bottom: 20px; }
    .section-title-wrap h2 { font-size: 20px; }
    .section-title-wrap p { font-size: 12.5px; }

    .reco-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .reco-cover { height: 180px; }
    .reco-body { padding: 12px; }
    .reco-body h4 { font-size: 13px; margin-bottom: 4px; line-height: 1.3; }
    .reco-body p { font-size: 11px; margin-bottom: 8px; }
    
    .news-card { flex: 0 0 100%; }
    .news-img { height: 160px; }
    .news-content { padding: 16px; }
    .news-content h3 { font-size: 15px; margin-bottom: 8px; }
    .news-content p { font-size: 12px; margin-bottom: 12px; }
    .news-meta { font-size: 11px; gap: 10px; }
    .berita-nav { margin-top: 16px; }
    .berita-dot { width: 10px; height: 10px; }

    .footer { padding: 40px 16px 20px; margin-top: 30px; }
    .footer-inner { grid-template-columns: 1fr; gap: 24px; }
    .footer-brand img { width: 36px; height: 36px; }
    .footer-brand h4 { font-size: 16px; }
    .footer-desc { font-size: 12.5px; }
    .footer h5 { font-size: 14px; margin-bottom: 12px; }
    .footer ul li { margin-bottom: 10px; }
    .footer ul a { font-size: 13px; }
    .footer-contact p { font-size: 13px; }
    .footer-bottom { margin-top: 24px; padding-top: 16px; font-size: 11px; }

    /* BOTTOM NAVIGATION MOBILE */
    .m-bottom-nav { position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); width: calc(100% - 30px); max-width: 430px; background: #0a3622; border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .m-nav-item { display: flex; flex-direction: column; align-items: center; gap: 3px; color: #94a3b8; font-size: 9.5px; font-weight: 600; text-decoration: none; transition: 0.3s; width: 52px; }
    .m-nav-item i { font-size: 19px; }
    .m-nav-item.active { color: #ffcc00; }
    .m-nav-item.logout { color: #fca5a5; }
    .m-nav-center { width: 52px; height: 52px; background: #ffcc00; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0a3622; font-size: 22px; margin-top: -30px; box-shadow: 0 6px 15px rgba(255,204,0,0.4); transition: 0.3s; border: 3px solid #f8fafc; flex-shrink: 0; }
}

@media (max-width: 380px) {
    .hero-text h1 { font-size: 22px; }
    .buku-cover-digido { height: 170px; }
    .m-mobile-header { padding: 8px 12px; }
    .m-nav-item { width: 46px; font-size: 9px; }
    .m-nav-item i { font-size: 17px; }
    .m-nav-center { width: 48px; height: 48px; font-size: 20px; margin-top: -26px; }
}
</style>
</head>
<body>

<!-- MODAL PESAN PRIBADI -->
<div class="pesan-overlay" id="pesanOverlay">
    <div class="pesan-box">
        <div class="pesan-icon">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <h3 id="pesanJudul">Judul</h3>
        <p id="pesanIsi">Isi</p>
        <button onclick="tutupPesan()">Mengerti</button>
    </div>
</div>

<!-- BANNER PENGUMUMAN -->
<?php
$q_ann = $conn->query("SELECT isi FROM pengumuman WHERE status_aktif = 1 ORDER BY id DESC LIMIT 1");
if ($q_ann && $q_ann->num_rows > 0):
    $ann = $q_ann->fetch_assoc();
?>
    <div class="announcement-banner">
        <i class="bi bi-megaphone-fill"></i>
        <marquee behavior="scroll" direction="left" scrollamount="5" style="flex:1;"><?= htmlspecialchars($ann['isi']) ?></marquee>
    </div>
<?php endif; ?>

<div class="m-mobile-header">
    <a href="dashboard.php" class="brand">
        <img src="../assets/img/foto.png" alt="Logo">
        <div class="brand-text">
            <strong class="typing-text-mobile"></strong>
        </div>
    </a>
    <a href="../pengaturan.php" class="m-avatar" title="Akun Saya">
        <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
            <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" alt="Profil">
        <?php else: ?>
            <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
        <?php endif; ?>
    </a>
</div>

<div class="main-nav" id="navbar">
    <div class="main-nav-inner">
        <a href="dashboard.php" class="logo-box">
            <img src="../assets/img/foto.png" alt="Logo" class="nav-logo">
            <div class="logo-text">
                <strong class="typing-text-desktop"></strong>
            </div>
        </a>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Home</a>
            <a href="katalog.php">Katalog Buku</a>
            <a href="riwayat.php">Riwayat Pinjam</a>
            <a href="../pengaturan.php" class="nav-profile-link" title="Pengaturan Akun">
                <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
                    <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" class="nav-avatar" alt="Profil">
                <?php else: ?>
                    <div class="nav-avatar"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
                <?php endif; ?>
                <span class="nav-profile-name"><?= htmlspecialchars($_SESSION['nama']) ?></span>
            </a>
            <a href="../auth/logout.php" class="cta">LOGOUT</a>
        </div>
    </div>
</div>

<!-- HERO SLIDER -->
<div class="hero-slider" id="heroSlider">
    <div class="slide active">
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-overlay"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <h1>Selamat Datang di<br><span>Pusat Literasi Digital</span></h1>
                <p>Lihatlah Beberapa koleksi buku, jurnal, dan referensi akademik hanya dalam genggaman Anda. Tingkatkan wawasan tanpa batas ruang dan waktu.</p>
                <a href="katalog.php" class="btn-hero">Mulai Membaca</a>
            </div>
            <div class="hero-image-box"></div>
        </div>
    </div>
    <div class="slide">
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-overlay"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <h1>Temukan<br><span>Koleksi Terbaru Kami</span></h1>
                <p>Update pengetahuanmu dengan buku-buku terbaru dari berbagai kategori. Dari sains, fiksi, hingga sejarah.</p>
                <a href="katalog.php" class="btn-hero">Lihat Katalog</a>
            </div>
            <div class="hero-image-box" style="background-image: url('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');"></div>
        </div>
    </div>
    <div class="slide">
        <div class="slide-bg" style="background-image: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');"></div>
        <div class="slide-overlay"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <h1>Semangat Baru<br><span>Untuk Membaca</span></h1>
                <p>Buku adalah jendela dunia. Mari mulai kebiasaan membaca setiap hari untuk masa depan yang lebih cerah.</p>
                <a href="riwayat.php" class="btn-hero">Riwayat Saya</a>
            </div>
            <div class="hero-image-box" style="background-image: url('https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80');"></div>
        </div>
    </div>
    <button class="slider-arrow arrow-prev" onclick="moveSlide(-1)"><i class="bi bi-chevron-left"></i></button>
    <button class="slider-arrow arrow-next" onclick="moveSlide(1)"><i class="bi bi-chevron-right"></i></button>
    <div class="slider-nav">
        <div class="slider-dot active" onclick="currentSlide(0)"></div>
        <div class="slider-dot" onclick="currentSlide(1)"></div>
        <div class="slider-dot" onclick="currentSlide(2)"></div>
    </div>
</div>

<div class="stats-float">
    <a href="riwayat.php" class="stat-card-float reveal">
        <div class="stat-info"><h3><?= number_format($aktif) ?></h3><p>Dipinjam</p></div>
    </a>
    <a href="riwayat.php" class="stat-card-float reveal" style="transition-delay: 0.1s;">
        <div class="stat-info"><h3><?= number_format($riwayat) ?></h3><p>Total</p></div>
    </a>
    <a href="riwayat.php" class="stat-card-float reveal" style="transition-delay: 0.2s;">
        <div class="stat-info"><h3>Rp <?= number_format($denda, 0, ',', '.') ?></h3><p>Denda</p></div>
    </a>
</div>

<!-- SECTION TENTANG KAMI (DESAIN BARU) -->
<div class="tentang-section reveal">
    <div class="tentang-image-wrap">
        <!-- GANTI URL GAMBAR INI DENGAN GAMBAR/PNG DARI KAMU -->
        <img src="../assets/img/sekolah.png" alt="SMK As-Syafi'iyah 01 Jakarta" class="tentang-image">
    </div>

    <div class="tentang-text">
        <h2>Tentang</h2>
        
        <div class="tentang-card-box">
            <p>
                <strong>SMK AS-SYAFI`IYAH JAKARTA</strong>, dengan NPSN 20102538, berdiri kokoh di JL BUKIT DURI SELATAN NO. 29, BUKIT DURI, KEC. TEBET, KOTA JAKARTA SELATAN, PROV. D.K.I. JAKARTA. Sekolah swasta ini berada di bawah naungan Yayasan Perguruan Islam As-Syafi'iyah Jakarta dan menyelenggarakan pendidikan dengan sistem sehari penuh selama 5 hari dalam seminggu.
            </p>
            <p>
                SMK AS-SYAFI`IYAH JAKARTA telah mendapatkan akreditasi B berdasarkan SK No. 906/BAN-SM/SK/2019 yang diterbitkan pada tanggal 21 Oktober 2019. Sekolah ini juga memiliki akses internet dan sumber listrik PLN untuk menunjang kegiatan belajar mengajar.
            </p>
            <p>
                Sebagai sekolah swasta yang berkualitas, SMK AS-SYAFI`IYAH JAKARTA memiliki visi untuk menghasilkan lulusan yang kompeten, berakhlak mulia, dan berdaya saing tinggi di era global. Misi sekolah ini adalah:
            </p>
            <ol style="margin-left: 20px; margin-bottom: 16px; color: #475569; font-size: 14.5px; line-height: 1.7;">
                <li>Menyelenggarakan pendidikan dan pelatihan yang berkualitas untuk menghasilkan lulusan yang kompeten di bidang keahliannya.</li>
                <li>Membentuk karakter siswa yang berakhlak mulia, beriman, bertaqwa, dan berdisiplin.</li>
                <li>Mengembangkan potensi dan bakat siswa agar menjadi manusia yang kreatif, inovatif, dan berdaya saing tinggi.</li>
                <li>Menjalin kerjasama dengan berbagai pihak untuk meningkatkan kualitas pendidikan dan memperluas akses pendidikan bagi masyarakat.</li>
            </ol>
            <p style="margin-bottom:0;">
                Bagi calon siswa yang ingin bergabung di SMK AS-SYAFI`IYAH JAKARTA, dapat menghubungi sekolah melalui telepon di 0218319994 atau melalui email di smk.assyafiiyah.jakarta@gmail.com. Informasi lebih lanjut mengenai sekolah ini juga dapat diakses melalui website resmi di http://https://www.smkassyafiiyahjkt.sch.id.
            </p>
        </div>

        <a href="katalog.php" class="btn-tentang-custom">
            Selengkapnya &rarr;
        </a>
    </div>
</div>

<!-- KOLEKSI BUKU DIGITAL (DESAIN BARU) -->
<div class="koleksi-section">
    <div class="koleksi-header reveal">
        <h2>Koleksi Buku</h2>
    </div>

    <div class="koleksi-grid">
        <?php $delay = 0; while ($b = $bukuBaru->fetch_assoc()): 
            $hasCover = !empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover']);
            $avg = round($b['avg_rating'] ?? 0, 1);
            $kategori_label = $b['nama_kategori'] ?? 'Umum';
        ?>
        <a href="katalog.php?id=<?= $b['id'] ?>" class="buku-card-digido reveal" style="transition-delay: <?= $delay ?>s;">
            <div class="buku-cover-digido">
                <?php if ($hasCover): ?>
                    <img src="../assets/img/cover/<?= htmlspecialchars($b['cover']) ?>" alt="Cover">
                <?php endif; ?>
                <span class="buku-badge-top" title="<?= htmlspecialchars($kategori_label) ?>"><?= htmlspecialchars($kategori_label) ?></span>
            </div>
            <div class="buku-info-digido">
                <h4><?= htmlspecialchars($b['judul']) ?></h4>
                <span class="pengarang"><?= htmlspecialchars($b['pengarang']) ?></span>
                
                <div class="buku-meta-digido">
                    <div class="buku-rating-digido">
                        <i class="bi bi-star-fill star"></i> <?= $avg > 0 ? $avg : '0.0' ?> (0)
                    </div>
                    <span class="buku-stok-digido">
                        <?= $b['stok_tersedia'] ?>H
                    </span>
                </div>
            </div>
        </a>
        <?php $delay += 0.1; endwhile; ?>
    </div>
</div>

<!-- BERITA SLIDER -->
<?php if (!empty($beritaOtomatis)): ?>
<div class="section" style="background: #fff; max-width: 100%;">
    <div style="max-width: 1280px; margin: 0 auto;">
        <div class="section-header reveal">
            <div class="section-title-wrap"><h2>Berita Literasi</h2></div>
        </div>
        <div class="berita-slider-container" id="beritaContainer">
            <div class="berita-track" id="beritaTrack">
                <?php foreach ($beritaOtomatis as $n): $hasImg = !empty($n['gambar']); ?>
                <a href="<?= htmlspecialchars($n['link']) ?>" target="_blank" class="news-card">
                    <div class="news-img" <?= $hasImg ? 'style="background-image:url(\'' . htmlspecialchars($n['gambar']) . '\');"' : '' ?>>
                        <span class="news-tag"><?= htmlspecialchars($n['kategori']) ?></span>
                    </div>
                    <div class="news-content">
                        <h3><?= htmlspecialchars($n['judul']) ?></h3>
                        <p><?= htmlspecialchars($n['ringkasan']) ?></p>
                        <div class="news-meta">
                            <span><?= date('d M Y', strtotime($n['tanggal'])) ?></span>
                            <span><?= htmlspecialchars($n['sumber']) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <div class="berita-nav" id="beritaNav"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- REKOMENDASI -->
<div class="section">
    <div class="section-header reveal">
        <div class="section-title-wrap"><h2>Rekomendasi Untukmu</h2></div>
        <a href="katalog.php" class="section-link">Lihat Semua</a>
    </div>

    <div class="reco-grid">
        <?php
        if ($rekomendasi && $rekomendasi->num_rows > 0):
            $delay = 0;
            while ($r = $rekomendasi->fetch_assoc()):
                $hasCoverR = !empty($r['cover']) && file_exists('../assets/img/cover/' . $r['cover']);
                $avgR = round($r['avg_rating'] ?? 0, 1);
        ?>
            <a href="katalog.php?id=<?= $r['id'] ?>" class="reco-card reveal" style="transition-delay: <?= $delay ?>s;">
                <div class="reco-cover">
                    <?php if ($hasCoverR): ?>
                        <img src="../assets/img/cover/<?= htmlspecialchars($r['cover']) ?>" alt="Cover">
                    <?php endif; ?>
                </div>
                <div class="reco-body">
                    <h4><?= htmlspecialchars($r['judul']) ?></h4>
                    
                    <div style="display:flex; align-items:center; margin-bottom:8px;">
                        <div class="star-rating" style="font-size:11px;">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <i class="bi <?= $i <= round($avgR) ? 'bi-star-fill' : 'bi-star star-rating-gray' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text" style="font-size:11px;"><?= $avgR > 0 ? $avgR : '0' ?></span>
                    </div>

                    <p><?= htmlspecialchars($r['pengarang']) ?></p>
                </div>
            </a>
        <?php $delay += 0.1; endwhile; else: ?>
            <div style="grid-column: 1/-1; text-align:center; color:#94a3b8;">Belum ada rekomendasi.</div>
        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    <div class="footer-inner">
        <div>
            <div class="footer-brand">
                <img src="../assets/img/foto.png" alt="Logo">
                <div>
                    <h4>SMK As-Syafi'iyah 01 Jakarta</h4>
                </div>
            </div>
            <p class="footer-desc">Menciptakan ekosistem literasi modern yang memudahkan siswa dan guru dalam mengakses ilmu pengetahuan secara digital.</p>
        </div>
        <div>
            <h5>Menu Cepat</h5>
            <ul>
                <li><a href="dashboard.php">Home</a></li>
                <li><a href="katalog.php">Katalog Buku</a></li>
                <li><a href="riwayat.php">Riwayat Pinjam</a></li>
                <li><a href="../pengaturan.php">Pengaturan Akun</a></li>
            </ul>
        </div>
        <div class="footer-contact">
            <h5>Hubungi Kami</h5>
            <p>Jl. Bukit Duri Selatan No. 29, RT.8/RW.3, Bukit Duri, Kec. Tebet, Kota Jakarta Selatan, DKI Jakarta 12840</p>
            <p>087786526095</p>
            <p>smk.assyafiiyah.jakarta@gmail.com</p>
        </div>
    </div>
    <div class="footer-bottom">
        © <?= date('Y') ?> SMK As-Syafi'iyah. Seluruh Hak Cipta Dilindungi.
    </div>
</div>

<!-- BOTTOM NAVIGATION BAR -->
<div class="m-bottom-nav">
    <a href="dashboard.php" class="m-nav-item active">
        <i class="bi bi-house-door"></i><span>Beranda</span>
    </a>
    <a href="riwayat.php" class="m-nav-item">
        <i class="bi bi-clock-history"></i><span>Riwayat</span>
    </a>
    <a href="katalog.php" class="m-nav-center" title="Katalog Buku">
        <i class="bi bi-grid"></i>
    </a>
    <a href="../pengaturan.php" class="m-nav-item">
        <i class="bi bi-person"></i><span>Akun</span>
    </a>
    <a href="../auth/logout.php" class="m-nav-item logout">
        <i class="bi bi-box-arrow-right"></i><span>Keluar</span>
    </a>
</div>

<script>
    // ==========================================
    // LOGIKA PESAN PRIBADI (REAL-TIME)
    // ==========================================
    let currentPesanId = 0;

    function cekPesanBaru() {
        fetch('cek_pesan_baru.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ada') {
                    const overlay = document.getElementById('pesanOverlay');
                    if (!overlay.classList.contains('show')) {
                        currentPesanId = data.id;
                        document.getElementById('pesanJudul').innerHTML = data.judul;
                        document.getElementById('pesanIsi').innerHTML = data.isi;
                        overlay.classList.add('show');
                    }
                }
            })
            .catch(error => console.log('Cek pesan error:', error));
    }

    function tutupPesan() {
        document.getElementById('pesanOverlay').classList.remove('show');
        fetch('baca_pesan.php?id=' + currentPesanId)
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    currentPesanId = 0;
                }
            });
    }

    setInterval(cekPesanBaru, 5000);
    document.addEventListener('DOMContentLoaded', cekPesanBaru);


    // ==========================================
    // LOGIKA NAVBAR & SLIDER (BAWAAN)
    // ==========================================
    const navbar = document.getElementById('navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
        if (window.scrollY > 50) navbar.classList.add('scrolled');
    }

    const revealElements = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: "0px 0px -50px 0px" });
    revealElements.forEach(el => revealObserver.observe(el));

    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    let slideIndex = 0;
    let slideInterval;

    function showSlide(n) {
        slides.forEach(s => s.classList.remove('active'));
        dots.forEach(d => d.classList.remove('active'));
        slideIndex = (n + slides.length) % slides.length;
        slides[slideIndex].classList.add('active');
        dots[slideIndex].classList.add('active');
    }
    function moveSlide(n) { showSlide(slideIndex + n); resetTimer(); }
    function currentSlide(n) { showSlide(n); resetTimer(); }
    function startTimer() { slideInterval = setInterval(() => moveSlide(1), 5000); }
    function resetTimer() { clearInterval(slideInterval); startTimer(); }
    startTimer();

    const track = document.getElementById('beritaTrack');
    const navContainer = document.getElementById('beritaNav');
    const cards = track ? track.querySelectorAll('.news-card') : [];
    
    if (track && cards.length > 0) {
        let beritaIndex = 0;
        let beritaInterval;
        
        function getCardsPerScreen() {
            if (window.innerWidth <= 768) return 1;
            if (window.innerWidth <= 1024) return 2;
            return 3;
        }

        function updateBeritaSlider() {
            const cardsPerScreen = getCardsPerScreen();
            const cardWidth = cards[0].offsetWidth;
            const gap = 24;
            const moveAmount = (cardWidth + gap) * beritaIndex;
            track.style.transform = `translateX(-${moveAmount}px)`;
            
            navContainer.innerHTML = '';
            const maxIndex = Math.max(0, cards.length - cardsPerScreen);
            for (let i = 0; i <= maxIndex; i++) {
                const dot = document.createElement('div');
                dot.className = `berita-dot ${i === beritaIndex ? 'active' : ''}`;
                dot.onclick = () => { beritaIndex = i; updateBeritaSlider(); resetBeritaTimer(); };
                navContainer.appendChild(dot);
            }
        }

        function nextBerita() {
            const cardsPerScreen = getCardsPerScreen();
            const maxIndex = Math.max(0, cards.length - cardsPerScreen);
            beritaIndex = beritaIndex >= maxIndex ? 0 : beritaIndex + 1;
            updateBeritaSlider();
        }
        function startBeritaTimer() { beritaInterval = setInterval(nextBerita, 4000); }
        function resetBeritaTimer() { clearInterval(beritaInterval); startBeritaTimer(); }

        let touchStartX = 0;
        track.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            clearInterval(beritaInterval);
        }, {passive: true});

        track.addEventListener('touchend', (e) => {
            let touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 50) {
                const cardsPerScreen = getCardsPerScreen();
                const maxIndex = Math.max(0, cards.length - cardsPerScreen);
                if(beritaIndex < maxIndex) beritaIndex++;
                updateBeritaSlider();
            } else if (touchEndX - touchStartX > 50) {
                if(beritaIndex > 0) beritaIndex--;
                updateBeritaSlider();
            }
            resetBeritaTimer();
        }, {passive: true});

        updateBeritaSlider();
        startBeritaTimer();
        
        window.addEventListener('resize', () => {
            beritaIndex = 0;
            updateBeritaSlider();
        });
    }

    // ==========================================
    // FITUR HEARTBEAT: Sinyal Online Anggota
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const urlHeartbeat = '../heartbeat.php';
        fetch(urlHeartbeat).catch(err => console.log('Heartbeat error'));
        setInterval(function() {
            fetch(urlHeartbeat + '?t=' + new Date().getTime())
                .catch(err => console.log('Heartbeat error'));
        }, 10000); 
    });

    // ==========================================
    // FITUR EFEK KETIK (TYPEWRITER LOOP)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        const texts = ["SMK As-Syafi'iyah 01 Jakarta", "Pusat Literasi"];
        let count = 0;
        let currentText = '';
        let isDeleting = false;
        let typingSpeed = 100;
        let deletingSpeed = 50;
        let delayBetweenTexts = 2000;

        function type() {
            const mobileEl = document.querySelector('.typing-text-mobile');
            const desktopEl = document.querySelector('.typing-text-desktop');

            if (!mobileEl && !desktopEl) return;

            if (isDeleting) {
                currentText = texts[count].substring(0, currentText.length - 1);
                typingSpeed = deletingSpeed;
            } else {
                currentText = texts[count].substring(0, currentText.length + 1);
                typingSpeed = 100;
            }

            const htmlContent = currentText + '<span class="typing-cursor"></span>';
            if (mobileEl) mobileEl.innerHTML = htmlContent;
            if (desktopEl) desktopEl.innerHTML = htmlContent;

            if (!isDeleting && currentText === texts[count]) {
                isDeleting = true;
                typingSpeed = delayBetweenTexts;
            } else if (isDeleting && currentText === '') {
                isDeleting = false;
                count = (count + 1) % texts.length;
                typingSpeed = 500;
            }

            setTimeout(type, typingSpeed);
        }

        setTimeout(type, 1000);
    });
</script>

</body>
</html>