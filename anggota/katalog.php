<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { redirect('index.php'); }

$uid = (int)$_SESSION['user_id'];

// ==========================================
// PROSES SIMPAN KOMENTAR VIA AJAX
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar_ajax'])) {
    $buku_id_ajax = (int)$_POST['buku_id'];
    $isi_ajax = trim($_POST['isi_komentar']);
    $rating_ajax = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;

    if (!empty($isi_ajax)) {
        $stmt = $conn->prepare("INSERT INTO komentar (buku_id, user_id, isi_komentar, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisi', $buku_id_ajax, $uid, $isi_ajax, $rating_ajax);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "empty";
    }
    exit; 
}
// ==========================================

/* Statistik untuk My Library */
$riwayat      = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE user_id=$uid")->fetch_assoc()['c'] ?? 0;
$aktif        = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE user_id=$uid AND status='dipinjam'")->fetch_assoc()['c'] ?? 0;
$totalKembali = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE user_id=$uid AND status='dikembalikan'")->fetch_assoc()['c'] ?? 0;

/* Mode: Detail vs Katalog */
$detail_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$detail = null;

if ($detail_id) {
    // QUERY DETAIL BUKU + KATEGORI + GENRE
    $q = $conn->query("SELECT b.*, 
                       GROUP_CONCAT(DISTINCT k.nama_kategori SEPARATOR ', ') as nama_kategori,
                       GROUP_CONCAT(DISTINCT k.id) as kategori_ids,
                       GROUP_CONCAT(DISTINCT g.nama_genre SEPARATOR ', ') as nama_genre
                       FROM buku b 
                       LEFT JOIN buku_kategori bk ON b.id = bk.buku_id 
                       LEFT JOIN kategori k ON bk.kategori_id = k.id 
                       LEFT JOIN buku_genre bg ON b.id = bg.buku_id 
                       LEFT JOIN genre g ON bg.genre_id = g.id 
                       WHERE b.id=$detail_id GROUP BY b.id");
    $detail = $q ? $q->fetch_assoc() : null;
}

/* Proses Pinjam */
if (isset($_GET['pinjam'])) {
    $buku_id = (int)$_GET['pinjam'];
    $b = $conn->query("SELECT * FROM buku WHERE id=$buku_id")->fetch_assoc();
    
    if ($b && $b['stok_tersedia'] > 0) {
        $cek = $conn->query("SELECT id FROM pinjaman WHERE user_id=$uid AND buku_id=$buku_id AND status IN ('pending','dipinjam')");
        if ($cek->num_rows > 0) {
            flash('error','Anda masih meminjam/menunggu buku ini!');
        } else {
            $kode = 'ASV' . date('Ymd') . str_pad(rand(1,999),3,'0',STR_PAD_LEFT);
            $tgl_pinjam = date('Y-m-d');
            $tempo = date('Y-m-d', strtotime('+7 days')); 
            
            $stmt = $conn->prepare("INSERT INTO pinjaman (kode_pinjam,user_id,buku_id,tgl_pinjam,tgl_jatuh_tempo,status) VALUES (?,?,?,?,?,'pending')");
            $stmt->bind_param('siiss', $kode,$uid,$buku_id,$tgl_pinjam,$tempo);
            $stmt->execute();
            
            flash('success','Permintaan pinjam berhasil! Menunggu persetujuan petugas.');
        }
    } else {
        flash('error','Stok buku tidak tersedia!');
    }
    redirect('anggota/katalog.php');
}

/* Cek Status Pinjam */
$statusPinjam = null;
if ($detail_id) {
    $cek_pinjam = $conn->query("SELECT status FROM pinjaman WHERE user_id=$uid AND buku_id=$detail_id AND status IN ('pending','dipinjam') LIMIT 1");
    if ($cek_pinjam->num_rows > 0) {
        $statusPinjam = $cek_pinjam->fetch_assoc()['status'];
    }
}

/* Data Katalog dengan RATING & FILTER KATEGORI */
$keyword = $_GET['q'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$genre_filter = $_GET['genre'] ?? ''; // TAMBAHAN: Filter Genre

$sql = "SELECT b.*, 
        GROUP_CONCAT(DISTINCT k.nama_kategori SEPARATOR ', ') as nama_kategori,
        (SELECT AVG(rating) FROM komentar WHERE buku_id = b.id) as avg_rating,
        (SELECT COUNT(id) FROM komentar WHERE buku_id = b.id) as total_ulasan
        FROM buku b 
        LEFT JOIN buku_kategori bk ON b.id = bk.buku_id 
        LEFT JOIN kategori k ON bk.kategori_id = k.id 
        LEFT JOIN buku_genre bg ON b.id = bg.buku_id
        LEFT JOIN genre g ON bg.genre_id = g.id
        WHERE 1";

if ($keyword) {
    $kw = $conn->real_escape_string($keyword);
    $sql .= " AND (b.judul LIKE '%$kw%' OR b.pengarang LIKE '%$kw%' OR b.isbn LIKE '%$kw%')";
}
if ($kategori_filter && $kategori_filter !== 'semua') {
    $kf = $conn->real_escape_string($kategori_filter);
    $sql .= " AND k.nama_kategori='$kf'";
}
if ($genre_filter && $genre_filter !== 'semua') {
    $gf = $conn->real_escape_string($genre_filter);
    $sql .= " AND g.nama_genre='$gf'";
}
$sql .= " GROUP BY b.id ORDER BY b.judul";
$list = $conn->query($sql);

$kategoriList = $conn->query("SELECT k.nama_kategori, COUNT(DISTINCT bk.buku_id) as total FROM kategori k LEFT JOIN buku_kategori bk ON bk.kategori_id = k.id GROUP BY k.id ORDER BY k.nama_kategori");
$genreList = $conn->query("SELECT g.nama_genre, COUNT(DISTINCT bg.buku_id) as total FROM genre g LEFT JOIN buku_genre bg ON bg.genre_id = g.id GROUP BY g.id ORDER BY g.nama_genre");

/* Rekomendasi */
$rekomendasi = null;
if ($detail) {
    $rekomendasi = $conn->query("SELECT b.*, 
                                GROUP_CONCAT(DISTINCT k.nama_kategori SEPARATOR ', ') as nama_kategori
                                FROM buku b 
                                LEFT JOIN buku_kategori bk ON b.id = bk.buku_id 
                                LEFT JOIN kategori k ON bk.kategori_id = k.id 
                                WHERE b.id != $detail_id 
                                  AND b.id IN (
                                      SELECT bk2.buku_id FROM buku_kategori bk2 
                                      WHERE bk2.kategori_id IN (
                                          SELECT kategori_id FROM buku_kategori WHERE buku_id = $detail_id
                                      )
                                  )
                                GROUP BY b.id ORDER BY RAND() LIMIT 6");
}

/* Ambil Komentar & Foto User */
$komentarList = null;
if ($detail_id) {
    $komentarList = $conn->query("SELECT k.*, u.nama, u.foto FROM komentar k JOIN users u ON k.user_id = u.id WHERE k.buku_id = $detail_id ORDER BY k.id DESC");
    
    $q_rating = $conn->query("SELECT AVG(rating) as avg_r, COUNT(id) as total FROM komentar WHERE buku_id = $detail_id")->fetch_assoc();
    $detail_avg_rating = round($q_rating['avg_r'] ?? 0, 1);
    $detail_total_ulasan = $q_rating['total'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $detail ? htmlspecialchars($detail['judul']) . ' - ' : 'Katalog Buku - ' ?>Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
/* (SALIN SEMUA CSS MILIKMU DARI SINI KE BAWAH, TIDAK ADA YANG DIUBAH) */
:root { --primary: #0f5132; --primary-dark: #0a3622; --primary-light: #f0fdf4; --text-dark: #1e293b; --text-muted: #64748b; --bg-light: #f8fafc; --white: #ffffff; --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.05); --shadow-lg: 0 20px 40px -10px rgba(15, 81, 50, 0.15); --radius-md: 16px; --radius-lg: 24px; }
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

/* PERBAIKAN INPUT RATING (BINTANG KOSONG DULU) */
.rating-input { 
    display: inline-flex; 
    flex-direction: row-reverse; 
    gap: 4px; 
}
.rating-input input { display: none; }
.rating-input label { 
    font-size: 28px; 
    color: #e2e8f0; /* Warna abu-abu untuk bintang kosong */
    cursor: pointer; 
    transition: color 0.2s; 
    margin-bottom: 0;
}
.rating-input input:checked ~ label,
.rating-input label:hover,
.rating-input label:hover ~ label { 
    color: #f59e0b; /* Warna kuning saat dipilih/hover */
}

.filter-wrap { display: flex; gap: 10px; overflow-x: auto; padding: 10px 0 20px; margin: 0 auto 20px; max-width: 1280px; scrollbar-width: none; -webkit-overflow-scrolling: touch; }
.filter-wrap::-webkit-scrollbar { display: none; }
.filter-btn { padding: 8px 18px; background: var(--white); border: 1px solid #e2e8f0; border-radius: 50px; font-size: 13px; font-weight: 600; color: var(--text-muted); white-space: nowrap; transition: 0.2s; cursor: pointer; }
.filter-btn:hover { border-color: var(--primary); color: var(--primary); }
.filter-btn.active { background: var(--primary); color: var(--white); border-color: var(--primary); box-shadow: 0 4px 10px rgba(15,81,50,0.2); }
.filter-container-inner { padding: 0 24px; }
.m-mobile-header { display: none; }
.main-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; transition: all 0.4s ease-in-out; background: transparent; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
.main-nav.scrolled { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
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
.katalog-hero { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); padding: 160px 20px 110px; text-align: center; position: relative; overflow: hidden; color: #fff; border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; }
.katalog-hero::before { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.05) 1px, transparent 1px); background-size: 40px 40px; transform: rotate(15deg); z-index: 1; }
.hero-inner { position: relative; z-index: 2; max-width: 800px; margin: 0 auto; animation: fadeUp 0.8s ease-out; }
.hero-inner h1 { font-size: clamp(36px, 5vw, 52px); font-weight: 800; margin-bottom: 16px; }
.hero-inner p { font-size: clamp(15px, 2vw, 18px); color: #cbd5e1; font-weight: 400; }
.search-wrap { position: relative; z-index: 10; max-width: 700px; margin: -35px auto 20px; padding: 0 20px; animation: fadeUp 0.8s ease-out 0.2s both; }
.search-bar { display: flex; background: #fff; border-radius: 50px; padding: 8px; box-shadow: 0 15px 35px rgba(15,81,50,0.15); border: 1px solid #e2e8f0; transition: 0.3s; }
.search-bar:focus-within { box-shadow: 0 20px 40px rgba(15,81,50,0.25); transform: translateY(-2px); }
.search-bar input { flex: 1; padding: 14px 24px; border: none; border-radius: 50px; font-size: 15px; outline: none; background: transparent; font-family: inherit; }
.search-bar button { padding: 14px 32px; background: var(--primary); color: #fff; border: none; border-radius: 50px; font-weight: 600; font-size: 15px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
.search-bar button:hover { background: var(--primary-dark); }
.container { max-width: 1280px; margin: 0 auto; padding: 0 24px 80px; }
.buku-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 24px; }
.buku-card { background: var(--white); border-radius: var(--radius-md); overflow: hidden; border: 1px solid #e2e8f0; transition: 0.4s; display: flex; flex-direction: column; position: relative; }
.buku-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-lg); border-color: var(--primary); }
.buku-cover { height: 300px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 64px; position: relative; overflow: hidden; }
.buku-cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
.buku-card:hover .buku-cover img { transform: scale(1.05); }
.buku-cover::after { content: ""; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent 50%); z-index: 1; }

/* ============================================================
   MENYEMBUNYIKAN BADGE KATEGORI (GLOBAL, SEMUA UKURAN LAYAR)
   ============================================================ */
.buku-kat-badge {
    display: none !important;
}

.buku-info { padding: 24px; flex: 1; display: flex; flex-direction: column; background: var(--white); position: relative; z-index: 2; }
.buku-info h4 { font-size: 18px; color: var(--text-dark); font-weight: 700; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.buku-info p { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; flex: 1; font-weight: 500; }
.buku-action { display: flex; align-items: center; justify-content: space-between; padding-top: 16px; border-top: 1px solid #f1f5f9; }
.buku-action span { font-size: 14px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px; transition: 0.3s; }
.buku-card:hover .buku-action span { gap: 12px; }
.detail-wrap { max-width: 1100px; margin: 140px auto 80px; padding: 0 24px; animation: fadeUp 0.6s ease-out; }
.back-link { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 14px; font-weight: 600; margin-bottom: 24px; transition: 0.2s; padding: 10px 20px; background: var(--white); border-radius: 30px; border: 1px solid #e2e8f0; }
.back-link:hover { color: var(--primary); border-color: var(--primary); background: var(--primary-light); }
.detail-hero { display: grid; grid-template-columns: 340px 1fr; gap: 50px; align-items: start; background: var(--white); padding: 50px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid #e2e8f0; }
.detail-cover { width: 100%; aspect-ratio: 2/3; border-radius: var(--radius-md); overflow: hidden; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; position: relative; }
.detail-cover img { width: 100%; height: 100%; object-fit: cover; }
.detail-info h1 { font-size: clamp(28px, 4vw, 42px); font-weight: 800; color: var(--text-dark); line-height: 1.2; margin-bottom: 12px; }
.detail-author { font-size: 16px; color: var(--text-muted); font-weight: 500; margin-bottom: 30px; padding-bottom: 24px; border-bottom: 2px dashed #e2e8f0; }
.detail-author strong { color: var(--primary); font-weight: 700; }
.detail-tabs { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid #f1f5f9; padding-bottom: 16px; }
.tab-btn { padding: 10px 24px; border: none; background: transparent; font-size: 15px; font-weight: 700; color: var(--text-muted); cursor: pointer; transition: 0.3s; font-family: inherit; position: relative; }
.tab-btn::after { content: ""; position: absolute; bottom: -18px; left: 0; width: 100%; height: 3px; background: transparent; transition: 0.3s; }
.tab-btn.active { color: var(--primary); }
.tab-btn.active::after { background: var(--primary); }
.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeUp 0.3s ease-out; }
.detail-sinopsis p { font-size: 15px; color: #475569; line-height: 1.8; }

/* ============================================================
   DATA BUKU — PROFESSIONAL TABLE/LIST STYLE (PERPUSNAS STYLE)
   ============================================================ */
.data-table-professional {
    width: 100%;
    border-collapse: collapse;
    font-size: 14.5px;
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.data-table-professional tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}
.data-table-professional tr:last-child {
    border-bottom: none;
}
.data-table-professional tr:hover {
    background: #f8fafc;
}
.data-table-professional th {
    width: 35%;
    padding: 14px 20px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
    vertical-align: top;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.data-table-professional td {
    padding: 14px 20px;
    color: #1e293b;
    font-weight: 600;
    vertical-align: top;
}
.data-table-professional th i {
    margin-right: 8px;
    color: var(--primary);
    font-size: 15px;
}

.detail-actions { display: flex; gap: 16px; margin-top: 40px; flex-wrap: wrap; }
.btn-pinjam-big { flex: 1; padding: 16px 32px; background: var(--primary); color: #fff; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-flex; justify-content: center; align-items: center; gap: 10px; border: none; cursor: pointer; transition: 0.3s; }
.btn-pinjam-big:hover { background: var(--primary-dark); transform: translateY(-3px); box-shadow: var(--shadow-lg); }
.btn-bagikan { padding: 16px 28px; background: var(--white); color: var(--text-muted); border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: 0.3s; }
.btn-bagikan:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }

/* ============================================================
   REVIEW SECTION (PROFESIONAL STYLE)
   ============================================================ */
.komentar-section { margin-top: 40px; padding-top: 30px; border-top: 2px dashed #e2e8f0; }
.komentar-header-title { 
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; 
}
.komentar-header-title h3 { font-size: 20px; font-weight: 800; color: var(--text-dark); margin: 0; }
.komentar-count { 
    background: var(--primary-light); color: var(--primary); 
    padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; 
}

.review-list { display: flex; flex-direction: column; gap: 20px; margin-bottom: 30px; max-height: 500px; overflow-y: auto; padding-right: 10px; }
.review-card { 
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; 
    padding: 20px; display: flex; gap: 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}
.review-avatar { 
    width: 48px; height: 48px; border-radius: 50%; background: var(--primary); 
    color: #fff; display: flex; align-items: center; justify-content: center; 
    font-weight: 700; font-size: 18px; flex-shrink: 0; overflow: hidden; 
}
.review-avatar img { width: 100%; height: 100%; object-fit: cover; }
.review-body { flex: 1; min-width: 0; }
.review-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.review-user-info { display: flex; flex-direction: column; gap: 4px; }
.review-name { font-weight: 700; color: var(--text-dark); font-size: 15px; }
.review-stars { color: #f59e0b; font-size: 12px; display: flex; gap: 2px; }
.review-date { font-size: 12px; color: #94a3b8; font-weight: 500; white-space: nowrap; }
.review-text { font-size: 14.5px; color: #475569; line-height: 1.7; }
.review-reply { 
    background: #f8fafc; border-left: 3px solid var(--primary); 
    padding: 12px 16px; border-radius: 0 8px 8px 0; font-size: 14px; color: #334155; margin-top: 14px; 
}
.review-reply strong { display: block; margin-bottom: 4px; font-size: 12px; text-transform: uppercase; color: var(--primary); }

/* FORM TULIS ULASAN */
.form-review-box {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px;
}
.form-review-box h4 { font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--text-dark); }
.form-komentar { display: flex; flex-direction: column; gap: 16px; }
.form-komentar textarea { 
    width: 100%; padding: 16px; border: 1.5px solid #cbd5e1; border-radius: 12px; 
    font-family: inherit; font-size: 14px; resize: vertical; min-height: 100px; 
    outline: none; transition: 0.3s; background: #fff;
}
.form-komentar textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15,81,50,0.08); }
.btn-kirim-komentar { 
    padding: 12px 24px; background: var(--primary); color: #fff; border: none; 
    border-radius: 10px; font-weight: 700; cursor: pointer; align-self: flex-end; transition: 0.3s; 
}
.btn-kirim-komentar:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(15,81,50,0.2); }

.reco-section { max-width: 1100px; margin: 0 auto; padding: 0 24px 80px; }
.reco-section h2 { font-size: 24px; font-weight: 800; color: var(--text-dark); margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
.reco-carousel { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
.reco-card { background: var(--white); border: 1px solid #e2e8f0; border-radius: var(--radius-md); overflow: hidden; transition: 0.3s; }
.reco-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); border-color: var(--primary); }
.reco-cover { height: 240px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #cbd5e1; position: relative; }
.reco-cover img { width: 100%; height: 100%; object-fit: cover; }
.reco-body { padding: 20px; }
.reco-body h4 { font-size: 16px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; line-height: 1.3; }
.reco-body p { font-size: 13px; color: var(--text-muted); }
.empty-state { text-align: center; padding: 80px 20px; grid-column: 1 / -1; background: var(--white); border-radius: var(--radius-lg); border: 2px dashed #cbd5e1; }
.footer-ut { background: var(--primary-dark); color: var(--white); padding: 80px 24px 30px; }
.footer-inner { max-width: 1280px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1.5fr; gap: 60px; }
.footer-brand { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.footer-brand img { width: 48px; height: 48px; border-radius: 50%; background: var(--white); padding: 4px; }
.footer-brand h4 { font-size: 20px; font-weight: 700; margin: 0; }
.footer-brand p { font-size: 13px; color: #94a3b8; margin: 4px 0 0 0; }
.footer-desc { font-size: 15px; color: #94a3b8; line-height: 1.8; max-width: 400px; }
.footer-ut h5 { font-size: 16px; font-weight: 700; color: var(--white); margin-bottom: 24px; }
.footer-ut ul { list-style: none; }
.footer-ut ul li { margin-bottom: 14px; }
.footer-ut ul a { color: #94a3b8; font-size: 14px; font-weight: 500; transition: 0.2s; }
.footer-ut ul a:hover { color: var(--white); padding-left: 5px; }
.footer-contact p { display: flex; align-items: flex-start; gap: 12px; font-size: 14px; color: #94a3b8; margin-bottom: 16px; line-height: 1.6; }
.footer-copy { max-width: 1280px; margin: 60px auto 0; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; font-size: 13px; color: #64748b; }

/* ============================================================
   TAMBAHAN CSS: EFEK KETIK
   ============================================================ */
.typing-cursor {
    display: inline-block;
    width: 2px;
    height: 1em;
    background-color: currentColor;
    margin-left: 2px;
    animation: blink 0.7s infinite;
    vertical-align: text-bottom;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}

/* ============================================================
   TAMBAHAN CSS: MODAL PESAN & BACKSOUND & ANIMASI LONCENG
   ============================================================ */
.pesan-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: 0.3s; }
.pesan-overlay.show { opacity: 1; pointer-events: auto; }
.pesan-box { background: #fff; width: 90%; max-width: 400px; border-radius: 16px; padding: 24px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.3); transform: translateY(20px); transition: 0.3s; }
.pesan-overlay.show .pesan-box { transform: translateY(0); }
.pesan-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 15px; }
.pesan-box h3 { font-size: 18px; color: #1e293b; margin-bottom: 10px; }
.pesan-box p { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 20px; }
.pesan-box button { background: #0f5132; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s; }
.pesan-box button:hover { background: #146c43; transform: translateY(-2px); }

@keyframes ringBell {
    0% { transform: rotate(0); }
    10% { transform: rotate(15deg); }
    20% { transform: rotate(-15deg); }
    30% { transform: rotate(10deg); }
    40% { transform: rotate(-10deg); }
    50% { transform: rotate(0); }
    100% { transform: rotate(0); }
}

.music-btn { position: fixed; bottom: 90px; right: 20px; width: 48px; height: 48px; border-radius: 50%; background: #0f5132; color: #fff; border: 2px solid #ffcc00; display: flex; align-items: center; justify-content: center; font-size: 20px; cursor: pointer; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: all 0.3s ease; }
.music-btn:hover { transform: scale(1.1); background: #146c43; }
.music-btn.playing { animation: spinMusic 3s linear infinite; background: #ffcc00; color: #0f5132; border-color: #0f5132; }
@keyframes spinMusic { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 1024px) {
    .detail-hero { grid-template-columns: 1fr; padding: 30px; gap: 30px; }
    .detail-cover { max-width: 300px; margin: 0 auto; }
    .footer-inner { grid-template-columns: 1fr 1fr; }
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
    .katalog-hero { padding: 40px 16px 70px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; }
    .hero-inner h1 { font-size: 26px; margin-bottom: 10px; }
    .hero-inner p { font-size: 13px; line-height: 1.5; padding: 0 8px; }
    .search-wrap { margin: -30px auto 15px; padding: 0 16px; }
    .search-bar { flex-direction: column; border-radius: 16px; background: transparent; box-shadow: none; gap: 8px; padding: 0; }
    .search-bar input { background: #fff; border-radius: 12px; padding: 14px 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); font-size: 13.5px; }
    .search-bar button { width: 100%; justify-content: center; border-radius: 12px; padding: 12px; font-size: 14px; box-shadow: 0 4px 12px rgba(15,81,50,0.25); }
    .filter-wrap { padding: 5px 16px 15px; gap: 8px; }
    .filter-container-inner { padding: 0; }
    .filter-btn { padding: 6px 14px; font-size: 12px; }
    .container { padding: 0 14px 40px; }
    .buku-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .buku-cover { height: 180px; font-size: 40px; }
    .buku-info { padding: 12px; }
    .buku-info h4 { font-size: 13px; margin-bottom: 4px; line-height: 1.3; }
    .buku-info p { font-size: 11px; margin-bottom: 8px; }
    .buku-action { padding-top: 8px; }
    .buku-action span { font-size: 11px; }
    .detail-wrap { margin: 20px auto 30px; padding: 0 14px; }
    .back-link { margin-bottom: 14px; padding: 8px 16px; font-size: 12.5px; }
    .detail-hero { padding: 18px 14px; gap: 20px; border-radius: 16px; }
    .detail-cover { max-width: 100%; border-radius: 12px; }
    .detail-info h1 { font-size: 22px; margin-bottom: 8px; line-height: 1.3; }
    .detail-author { font-size: 13px; margin-bottom: 18px; padding-bottom: 14px; }
    .detail-tabs { gap: 4px; padding-bottom: 10px; margin-bottom: 18px; }
    .tab-btn { padding: 8px 14px; font-size: 13px; }
    .tab-btn::after { bottom: -12px; height: 2px; }
    .detail-sinopsis p { font-size: 13px; line-height: 1.7; }
    
    /* Mobile Table */
    .data-table-professional th { font-size: 11px; padding: 10px 12px; }
    .data-table-professional td { font-size: 13px; padding: 10px 12px; }
    
    .detail-actions { flex-direction: column; gap: 10px; margin-top: 20px; }
    .btn-pinjam-big, .btn-bagikan { width: 100%; padding: 14px; font-size: 13.5px; justify-content: center; }
    
    /* Mobile Review */
    .review-card { flex-direction: column; gap: 12px; padding: 16px; }
    .review-avatar { width: 40px; height: 40px; font-size: 16px; }
    .review-top { flex-direction: column; align-items: flex-start; gap: 4px; }
    .review-date { margin-top: 4px; }
    
    .btn-kirim-komentar { width: 100%; text-align: center; }
    .reco-section { padding: 0 14px 30px; }
    .reco-section h2 { font-size: 18px; margin-bottom: 16px; }
    .reco-carousel { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .reco-cover { height: 150px; font-size: 40px; }
    .reco-body { padding: 12px; }
    .reco-body h4 { font-size: 13px; }
    .reco-body p { font-size: 11px; }
    .alert { padding: 12px 16px !important; font-size: 12.5px !important; }
    .empty-state { padding: 50px 20px; }
    .empty-state h3 { font-size: 18px !important; }
    .empty-state p { font-size: 13px !important; }
    .footer-ut { padding: 40px 16px 20px; }
    .footer-inner { grid-template-columns: 1fr; gap: 24px; }
    .footer-brand img { width: 36px; height: 36px; }
    .footer-brand h4 { font-size: 16px; }
    .footer-desc { font-size: 12.5px; }
    .footer-ut h5 { font-size: 14px; margin-bottom: 12px; }
    .footer-ut ul li { margin-bottom: 10px; }
    .footer-ut ul a { font-size: 13px; }
    .footer-contact p { font-size: 13px; }
    .footer-copy { margin-top: 24px; padding-top: 16px; font-size: 11px; }
    .m-bottom-nav { position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%); width: calc(100% - 30px); max-width: 430px; background: #0a3622; border-radius: 50px; display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .m-nav-item { display: flex; flex-direction: column; align-items: center; gap: 3px; color: #94a3b8; font-size: 9.5px; font-weight: 600; text-decoration: none; transition: 0.3s; width: 52px; }
    .m-nav-item i { font-size: 19px; }
    .m-nav-item.active { color: #ffcc00; }
    .m-nav-item.logout { color: #fca5a5; }
    .m-nav-item.logout:active { color: #ef4444; }
    .m-nav-center { width: 52px; height: 52px; background: #ffcc00; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #0a3622; font-size: 22px; margin-top: -30px; box-shadow: 0 6px 15px rgba(255,204,0,0.4); transition: 0.3s; border: 3px solid #f8fafc; flex-shrink: 0; }
    .m-nav-center:active { transform: scale(0.9); }
    .m-nav-center.is-active { background: #ffcc00; box-shadow: 0 0 0 4px rgba(255,204,0,0.35), 0 6px 15px rgba(255,204,0,0.5); }
    .music-btn { bottom: 85px; right: 15px; width: 40px; height: 40px; font-size: 16px; }
}
@media (max-width: 380px) {
    .katalog-hero { padding: 30px 14px 60px; }
    .hero-inner h1 { font-size: 22px; }
    .buku-cover { height: 150px; }
    .detail-cover { max-height: 320px; }
    .detail-info h1 { font-size: 19px; }
    .reco-cover { height: 130px; }
    .m-mobile-header { padding: 8px 12px; }
    .m-mobile-header .brand-text strong { font-size: 12.5px; }
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
        <div class="pesan-icon" id="pesanIconBox" style="background:#f0fdf4; color:#0f5132; animation: ringBell 1s infinite;">
            <i class="bi bi-bell-fill" id="pesanIcon"></i>
        </div>
        <h3 id="pesanJudul">Judul Pesan</h3>
        <p id="pesanIsi">Isi Pesan</p>
        <button onclick="tutupPesan()">Mengerti</button>
    </div>
</div>

<!-- BACKSOUND MUSIK -->
<audio id="bg-music" loop>
    <source src="../assets/audio/backsound.mp3" type="audio/mpeg">
</audio>
<button id="music-toggle" class="music-btn" title="Putar Musik">
    <i class="bi bi-volume-mute-fill" id="music-icon"></i>
</button>

<!-- HEADER MINI MOBILE -->
<div class="m-mobile-header">
    <a href="dashboard.php" class="brand">
        <img src="../assets/img/foto.png" alt="Logo">
        <div class="brand-text"><strong class="typing-text-mobile"></strong></div>
    </a>
    <a href="../pengaturan.php" class="m-avatar" title="Akun Saya">
        <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
            <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" alt="Profil">
        <?php else: ?>
            <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
        <?php endif; ?>
    </a>
</div>

<!-- NAVBAR DESKTOP -->
<div class="main-nav" id="navbar">
    <div class="main-nav-inner">
        <a href="dashboard.php" class="logo-box">
            <img src="../assets/img/foto.png" alt="Logo" class="nav-logo">
            <div class="logo-text">
                <strong class="typing-text-desktop"></strong>
            </div>
        </a>
        
        <div class="nav-links">
            <a href="dashboard.php">Home</a>
            <a href="katalog.php" class="active">Katalog Buku</a>
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

<?php if ($detail): ?>

<!-- BAGIAN DETAIL BUKU -->
<div class="detail-wrap">
    <?php if ($m = flash('success')): ?>
        <div class="alert alert-success" style="padding:14px 20px;background:#d1fae5;color:#065f46;border-left:4px solid #10b981;border-radius:10px;font-size:14px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <?= htmlspecialchars($m) ?>
        </div>
    <?php endif; ?>
    <?php if ($m = flash('error')): ?>
        <div class="alert alert-danger" style="padding:14px 20px;background:#fee2e2;color:#991b1b;border-left:4px solid #ef4444;border-radius:10px;font-size:14px;font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
            <?= htmlspecialchars($m) ?>
        </div>
    <?php endif; ?>

    <a href="katalog.php" class="back-link">Kembali</a>

    <?php 
    $hasCover = !empty($detail['cover']) && file_exists('../assets/img/cover/' . $detail['cover']); 
    $penulis = trim($detail['pengarang'] ?? '');
    ?>

    <div class="detail-hero">
        <div>
            <div class="detail-cover">
                <?php if ($hasCover): ?>
                    <img src="../assets/img/cover/<?= htmlspecialchars($detail['cover']) ?>" alt="<?= htmlspecialchars($detail['judul']) ?>">
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-info">
            <h1><?= htmlspecialchars($detail['judul']) ?></h1>
            
            <div style="display:flex; align-items:center; margin-bottom:12px;">
                <div class="star-rating">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <i class="bi <?= $i <= round($detail_avg_rating) ? 'bi-star-fill' : 'bi-star star-rating-gray' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="rating-text"><?= $detail_avg_rating ?> (<?= $detail_total_ulasan ?> ulasan)</span>
            </div>

            <?php if (!empty($penulis) && $penulis !== '-'): ?>
                <p class="detail-author">Oleh <strong><?= htmlspecialchars($penulis) ?></strong></p>
            <?php endif; ?>

            <div class="detail-tabs">
                <button class="tab-btn active" data-tab="sinopsis" onclick="switchTab('sinopsis')">Sinopsis</button>
                <button class="tab-btn" data-tab="data" onclick="switchTab('data')">Data Buku</button>
            </div>

            <div id="tab-sinopsis" class="tab-content active">
                <div class="detail-sinopsis">
                    <p><?= nl2br(htmlspecialchars($detail['deskripsi'] ?? 'Tidak ada deskripsi untuk buku ini.')) ?></p>
                </div>
            </div>

            <!-- ============================================
                 DATA BUKU — PROFESSIONAL TABLE STYLE
                 ============================================ -->
            <div id="tab-data" class="tab-content">
                <table class="data-table-professional">
                    <tbody>
                        <?php 
                        $penerbit = trim($detail['penerbit'] ?? '');
                        if (!empty($penerbit) && $penerbit !== '-' && $penerbit !== '0'): 
                        ?>
                        <tr>
                            <th><i class="bi bi-building"></i> Penerbit</th>
                            <td><?= htmlspecialchars($penerbit) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php 
                        $tahun = trim($detail['tahun_terbit'] ?? '');
                        if (!empty($tahun) && $tahun !== '-' && $tahun !== '0' && $tahun !== '0000'): 
                        ?>
                        <tr>
                            <th><i class="bi bi-calendar3"></i> Tahun Terbit</th>
                            <td><?= htmlspecialchars($tahun) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php 
                        $isbn = trim($detail['isbn'] ?? '');
                        if (!empty($isbn) && $isbn !== '-' && $isbn !== '0'): 
                        ?>
                        <tr>
                            <th><i class="bi bi-upc-scan"></i> ISBN</th>
                            <td><?= htmlspecialchars($isbn) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php 
                        $kategori = trim($detail['nama_kategori'] ?? '');
                        if (!empty($kategori) && $kategori !== '-' && $kategori !== '0'): 
                        ?>
                        <tr>
                            <th><i class="bi bi-tag-fill"></i> Kategori</th>
                            <td><?= htmlspecialchars($kategori) ?></td>
                        </tr>
                        <?php endif; ?>

                        <!-- TAMBAHAN: GENRE -->
                        <?php 
                        $genre = trim($detail['nama_genre'] ?? '');
                        if (!empty($genre) && $genre !== '-' && $genre !== '0'): 
                        ?>
                        <tr>
                            <th><i class="bi bi-bookmark-fill"></i> Genre</th>
                            <td><?= htmlspecialchars($genre) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php 
                        $lokasi = trim($detail['lokasi_rak'] ?? '');
                        if (!empty($lokasi) && $lokasi !== '-' && $lokasi !== '0'): 
                        ?>
                        <tr>
                            <th><i class="bi bi-geo-alt-fill"></i> Lokasi Rak</th>
                            <td><?= htmlspecialchars($lokasi) ?></td>
                        </tr>
                        <?php endif; ?>

                        <tr>
                            <th><i class="bi bi-box-seam-fill"></i> Stok Tersedia</th>
                            <td><span style="background:#f0fdf4; color:#0f5132; padding:4px 10px; border-radius:6px; font-weight:700;"><?= $detail['stok_tersedia'] ?> / <?= $detail['stok'] ?></span></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="detail-actions">
                <?php 
                $pdfFile = $detail['file_pdf'] ?? $detail['pdf'] ?? $detail['file'] ?? '';
                if (!empty($pdfFile)): 
                ?>
                    <a href="baca.php?id=<?= $detail['id'] ?>" target="_blank" class="btn-pinjam-big" style="background: #0f5132; color: #fff; box-shadow: 0 4px 15px rgba(15,81,50,0.3);">
                        Baca Buku Ini
                    </a>
                <?php endif; ?>

                <?php if ($statusPinjam === 'pending'): ?>
                    <span class="btn-pinjam-big" style="background:#f59e0b;cursor:not-allowed;">Menunggu Persetujuan</span>
                <?php elseif ($statusPinjam === 'dipinjam'): ?>
                    <span class="btn-pinjam-big" style="background:#3b82f6;cursor:not-allowed;">Sedang Dipinjam</span>
                <?php elseif ($detail['stok_tersedia'] > 0): ?>
                    <a href="?pinjam=<?= $detail['id'] ?>" class="btn-pinjam-big" onclick="return confirm('Pinjam buku ini?')">Pinjam Sekarang</a>
                <?php else: ?>
                    <span class="btn-pinjam-big" style="background:#cbd5e1;cursor:not-allowed;">Stok Habis</span>
                <?php endif; ?>
                
                <button class="btn-bagikan" onclick="shareBuku()">Bagikan</button>
            </div>

            <!-- ============================================
                 ULASAN & RATING SECTION (RE-DESIGN)
                 ============================================ -->
            <div class="komentar-section">
                <div class="komentar-header-title">
                    <h3>Ulasan Pembaca</h3>
                    <span class="komentar-count"><?= $komentarList ? $komentarList->num_rows : 0 ?> Ulasan</span>
                </div>
                
                <div class="review-list">
                    <?php if ($komentarList && $komentarList->num_rows > 0): ?>
                        <?php while ($k = $komentarList->fetch_assoc()): 
                            $hasFotoUser = !empty($k['foto']) && file_exists('../assets/img/profil/' . $k['foto']);
                            $user_rating = $k['rating'] ?? 5;
                        ?>
                            <div class="review-card">
                                <div class="review-avatar">
                                    <?php if ($hasFotoUser): ?>
                                        <img src="../assets/img/profil/<?= htmlspecialchars($k['foto']) ?>" alt="Profil">
                                    <?php else: ?>
                                        <?= strtoupper(substr($k['nama'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="review-body">
                                    <div class="review-top">
                                        <div class="review-user-info">
                                            <span class="review-name"><?= htmlspecialchars($k['nama']) ?></span>
                                            <div class="review-stars">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="bi <?= $i <= $user_rating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <span class="review-date"><?= date('d M Y', strtotime($k['created_at'])) ?></span>
                                    </div>
                                    <div class="review-text"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></div>
                                    
                                    <?php if (!empty($k['balasan'])): ?>
                                        <div class="review-reply">
                                            <strong>Balasan Petugas:</strong>
                                            <?= nl2br(htmlspecialchars($k['balasan'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; padding: 30px; color:var(--text-muted); background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1;">
                            <i class="bi bi-chat-square-text" style="font-size:32px; color:#cbd5e1; margin-bottom:10px; display:block;"></i>
                            Belum ada ulasan. Jadilah yang pertama memberikan ulasan!
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FORM TULIS ULASAN -->
                <div class="form-review-box">
                    <h4>Tulis Ulasan Anda</h4>
                    <form id="formKomentar" class="form-komentar">
                        <input type="hidden" name="buku_id" value="<?= $detail['id'] ?>">
                        
                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:4px;">
                            <span style="font-size:14px; font-weight:600; color:var(--text-dark);">Rating:</span>
                            <div class="rating-input">
                                <input type="radio" id="star5_<?= $detail['id'] ?>" name="rating" value="5" /><label for="star5_<?= $detail['id'] ?>" title="Sangat Bagus"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star4_<?= $detail['id'] ?>" name="rating" value="4" /><label for="star4_<?= $detail['id'] ?>" title="Bagus"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star3_<?= $detail['id'] ?>" name="rating" value="3" /><label for="star3_<?= $detail['id'] ?>" title="Cukup"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star2_<?= $detail['id'] ?>" name="rating" value="2" /><label for="star2_<?= $detail['id'] ?>" title="Kurang"><i class="bi bi-star-fill"></i></label>
                                <input type="radio" id="star1_<?= $detail['id'] ?>" name="rating" value="1" /><label for="star1_<?= $detail['id'] ?>" title="Sangat Kurang"><i class="bi bi-star-fill"></i></label>
                            </div>
                        </div>
                        
                        <textarea name="isi_komentar" placeholder="Bagikan pendapat Anda tentang buku ini..." required></textarea>
                        <button type="submit" name="kirim_komentar" class="btn-kirim-komentar"><i class="bi bi-send-fill" style="margin-right:6px;"></i> Kirim Ulasan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($rekomendasi && $rekomendasi->num_rows > 0): ?>
<div class="reco-section">
    <h2>Mungkin Kamu Suka</h2>
    <div class="reco-carousel">
        <?php $delay = 0; while ($r = $rekomendasi->fetch_assoc()): 
            $hasCoverR = !empty($r['cover']) && file_exists('../assets/img/cover/' . $r['cover']);
        ?>
        <a href="katalog.php?id=<?= $r['id'] ?>" class="reco-card reveal" style="transition-delay: <?= $delay ?>s;">
            <div class="reco-cover">
                <?php if ($hasCoverR): ?><img src="../assets/img/cover/<?= htmlspecialchars($r['cover']) ?>" alt="<?= htmlspecialchars($r['judul']) ?>"><?php endif; ?>
            </div>
            <div class="reco-body">
                <h4><?= htmlspecialchars($r['judul']) ?></h4>
                <p><?= htmlspecialchars($r['pengarang']) ?></p>
            </div>
        </a>
        <?php $delay += 0.1; endwhile; ?>
    </div>
</div>
<?php endif; ?>

<?php else: ?>

<!-- BAGIAN KATALOG BUKU -->
<div class="katalog-hero">
    <div class="hero-inner">
        <h1>Koleksi Buku</h1>
    </div>
</div>

<!-- FORM PENCARIAN & DROPDOWN FILTER DIGABUNG -->
<div class="search-wrap" style="max-width: 1000px;">
    <form method="GET" action="katalog.php" class="search-bar" style="flex-wrap: wrap;">
        <input type="text" name="q" placeholder="Cari judul buku, nama pengarang, atau ISBN..." value="<?= htmlspecialchars($keyword) ?>" style="min-width: 200px;">
        
        <!-- Dropdown Kategori -->
        <select name="kategori" style="padding: 14px 16px; border: none; outline: none; background: transparent; font-size: 14px; font-weight: 600; color: var(--text-dark); cursor: pointer; border-left: 1px solid #e2e8f0;">
            <option value="semua" <?= (empty($kategori_filter) || $kategori_filter == 'semua') ? 'selected' : '' ?>>Semua Kategori</option>
            <?php $kategoriList->data_seek(0); while ($k = $kategoriList->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($k['nama_kategori']) ?>" <?= ($kategori_filter == $k['nama_kategori']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <!-- Dropdown Genre -->
        <select name="genre" style="padding: 14px 16px; border: none; outline: none; background: transparent; font-size: 14px; font-weight: 600; color: var(--text-dark); cursor: pointer; border-left: 1px solid #e2e8f0;">
            <option value="semua" <?= (empty($genre_filter) || $genre_filter == 'semua') ? 'selected' : '' ?>>Semua Genre</option>
            <?php $genreList->data_seek(0); while ($g = $genreList->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($g['nama_genre']) ?>" <?= ($genre_filter == $g['nama_genre']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['nama_genre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" style="margin-left: auto;">Cari Buku</button>
    </form>
</div>

<div class="container">
    <div class="buku-grid">
        <?php if ($list && $list->num_rows > 0): ?>
            <?php $delay = 0; while ($b = $list->fetch_assoc()): 
                $hasCover = !empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover']);
                $avg = round($b['avg_rating'] ?? 0, 1);
            ?>
                <a href="katalog.php?id=<?= $b['id'] ?>" class="buku-card reveal" style="transition-delay: <?= $delay ?>s;">
                    <div class="buku-cover">
                        <?php if ($hasCover): ?><img src="../assets/img/cover/<?= htmlspecialchars($b['cover']) ?>" alt="Cover"><?php endif; ?>
                    </div>
                    <div class="buku-info">
                        <h4><?= htmlspecialchars($b['judul']) ?></h4>
                        <div style="display:flex; align-items:center; margin-bottom:6px;">
                            <div class="star-rating" style="font-size:12px;">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi <?= $i <= round($avg) ? 'bi-star-fill' : 'bi-star star-rating-gray' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text"><?= $avg > 0 ? $avg : '0' ?></span>
                        </div>
                        <p style="margin-bottom: 10px;"><?= htmlspecialchars($b['pengarang']) ?></p>
                        <div class="buku-action"><span>Lihat Detail</span></div>
                    </div>
                </a>
            <?php $delay += 0.05; endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3 style="color: var(--primary); font-size: 24px; font-weight: 800;">Buku Tidak Ditemukan</h3>
                <p style="color: var(--text-muted); margin-top: 8px;">Maaf, tidak ada buku dengan filter tersebut.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
<!-- BOTTOM NAVIGATION BAR -->
<div class="m-bottom-nav">
    <a href="dashboard.php" class="m-nav-item">
        <i class="bi bi-house-door"></i><span>Beranda</span>
    </a>
    <a href="riwayat.php" class="m-nav-item">
        <i class="bi bi-clock-history"></i><span>Riwayat</span>
    </a>
    <a href="katalog.php" class="m-nav-center is-active" title="Katalog Buku">
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
    // FITUR KIRIM KOMENTAR INSTAN (AJAX)
    // ==========================================
    const formKomentar = document.getElementById('formKomentar');
    if (formKomentar) {
        formKomentar.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-arrow-repeat" style="display:inline-block; animation:spin 1s linear infinite;"></i> Mengirim...';
            btn.disabled = true;

            const formData = new FormData(this);
            formData.append('kirim_komentar_ajax', '1');

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(response => response.text())
            .then(data => {
                if (data.trim() === 'success') { 
                    location.reload(); 
                } else {
                    alert('Gagal mengirim komentar. Pastikan kolom terisi.');
                    btn.innerHTML = originalText; btn.disabled = false;
                }
            }).catch(error => {
                console.error('Error:', error); 
                alert('Terjadi kesalahan sistem.');
                btn.innerHTML = originalText; btn.disabled = false;
            });
        });
    }

    let currentPesanId = 0;
    function cekPesanBaru() {
        fetch('cek_pesan_baru.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ada') {
                    const overlay = document.getElementById('pesanOverlay');
                    if (!overlay.classList.contains('show')) {
                        currentPesanId = data.id;
                        document.getElementById('pesanJudul').innerHTML = data.judul;
                        document.getElementById('pesanIsi').innerHTML = data.isi;
                        overlay.classList.add('show');
                        const iconBox = document.getElementById('pesanIconBox');
                        const icon = document.getElementById('pesanIcon');
                        if (data.kategori === 'warning') {
                            iconBox.style.background = '#fef2f2'; iconBox.style.color = '#ef4444'; icon.className = 'bi bi-exclamation-triangle-fill';
                        } else if (data.kategori === 'info') {
                            iconBox.style.background = '#f0fdf4'; iconBox.style.color = '#10b981'; icon.className = 'bi bi-info-circle-fill';
                        } else {
                            iconBox.style.background = '#eff6ff'; iconBox.style.color = '#3b82f6'; icon.className = 'bi bi-envelope-fill';
                        }
                    }
                }
            }).catch(error => console.log('Cek pesan error:', error));
    }
    function tutupPesan() {
        document.getElementById('pesanOverlay').classList.remove('show');
        if (currentPesanId > 0) {
            fetch('baca_pesan.php?id=' + currentPesanId)
                .then(response => response.text())
                .then(data => { if (data.trim() === 'success') { currentPesanId = 0; } });
        }
    }
    setInterval(cekPesanBaru, 5000);
    document.addEventListener('DOMContentLoaded', cekPesanBaru);

    const bgMusic = document.getElementById('bg-music');
    const musicBtn = document.getElementById('music-toggle');
    const musicIcon = document.getElementById('music-icon');
    let isMusicPlaying = false;
    if (bgMusic) {
        bgMusic.volume = 0.3;
        musicBtn.addEventListener('click', function() {
            if (isMusicPlaying) {
                bgMusic.pause(); musicIcon.classList.remove('bi-volume-mute-fill'); musicIcon.classList.add('bi-volume-up-fill');
                musicBtn.classList.remove('playing'); musicBtn.title = "Putar Musik";
            } else {
                bgMusic.play(); musicIcon.classList.remove('bi-volume-up-fill'); musicIcon.classList.add('bi-volume-mute-fill');
                musicBtn.classList.add('playing'); musicBtn.title = "Jeda Musik";
            }
            isMusicPlaying = !isMusicPlaying;
        });
    }

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
            if (entry.isIntersecting) { entry.target.classList.add('active'); observer.unobserve(entry.target); }
        });
    }, { threshold: 0.15, rootMargin: "0px 0px -50px 0px" });
    revealElements.forEach(el => revealObserver.observe(el));

    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    }
    function shareBuku() {
        const url = window.location.href;
        if (navigator.share) { navigator.share({ title: 'Perpustakaan Digital', text: 'Lihat buku ini!', url: url }); } 
        else {
            navigator.clipboard.writeText(url).then(() => { alert('Link berhasil dicopy!'); }).catch(() => { prompt('Copy link ini:', url); });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlHeartbeat = '../heartbeat.php';
        fetch(urlHeartbeat).catch(err => console.log('Heartbeat error'));
        setInterval(function() { fetch(urlHeartbeat + '?t=' + new Date().getTime()).catch(err => console.log('Heartbeat error')); }, 10000); 
    });

    document.addEventListener('DOMContentLoaded', function() {
        const texts = ["SMK As-Syafi'iyah 01 Jakarta", "Pusat Literasi"];
        let count = 0; let currentText = ''; let isDeleting = false; let typingSpeed = 100; let deletingSpeed = 50; let delayBetweenTexts = 2000;
        function type() {
            const mobileEl = document.querySelector('.typing-text-mobile');
            const desktopEl = document.querySelector('.typing-text-desktop');
            if (!mobileEl && !desktopEl) return;
            if (isDeleting) { currentText = texts[count].substring(0, currentText.length - 1); typingSpeed = deletingSpeed; } 
            else { currentText = texts[count].substring(0, currentText.length + 1); typingSpeed = 100; }
            const htmlContent = currentText + '<span class="typing-cursor"></span>';
            if (mobileEl) mobileEl.innerHTML = htmlContent;
            if (desktopEl) desktopEl.innerHTML = htmlContent;
            if (!isDeleting && currentText === texts[count]) { isDeleting = true; typingSpeed = delayBetweenTexts; } 
            else if (isDeleting && currentText === '') { isDeleting = false; count = (count + 1) % texts.length; typingSpeed = 500; }
            setTimeout(type, typingSpeed);
        }
        setTimeout(type, 1000);
    });
</script>

</body>
</html>