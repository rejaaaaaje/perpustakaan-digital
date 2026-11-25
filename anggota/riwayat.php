<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { redirect('index.php'); }

$uid = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT t.*, b.judul, b.pengarang 
                        FROM pinjaman t 
                        JOIN buku b ON t.buku_id = b.id 
                        WHERE t.user_id = ? 
                        ORDER BY t.id DESC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$list = $stmt->get_result();

// Hitung statistik
$totalPinjam = $list->num_rows;
$list->data_seek(0);
$sedangDipinjam = 0;
$totalDenda = 0;
while ($r = $list->fetch_assoc()) {
    if ($r['status'] === 'dipinjam') $sedangDipinjam++;
    $totalDenda += $r['denda'];
}
$list->data_seek(0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Peminjaman - Perpustakaan Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
/* ============================================================
   RESET & ANIMASI
   ============================================================ */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
body { background:#f8fafc; color:#334155; overflow-x:hidden; }
a { text-decoration: none; }

@keyframes fadeUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeDown { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }

/* ============================================================
   HEADER MINI MOBILE (khusus HP)
   ============================================================ */
.m-mobile-header { display: none; }

/* ============================================================
   TOP BAR & NAVBAR (DESKTOP)
   ============================================================ */
.top-strip { background: #0f5132; color: #cbd5e1; padding: 10px 0; font-size: 13px; }
.top-strip-inner { max-width: 1300px; margin: 0 auto; padding: 0 24px; display: flex; justify-content: space-between; align-items: center; }
.top-strip a { color: #fff; margin-left: 20px; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; font-weight: 500; text-decoration: none; }
.top-strip a:hover { color: #a7f3d0; }

.main-nav { background: #fff; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 20px rgba(0,0,0,0.05); animation: fadeDown 0.5s ease-out; }
.main-nav-inner { max-width: 1300px; margin: 0 auto; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.logo-box { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.nav-logo { width: 48px; height: 48px; border-radius: 50%; object-fit: contain; }
.logo-text strong { color: #0f5132; font-size: 19px; font-weight: 700; display: block; line-height: 1.1; }
.logo-text small { color: #64748b; font-size: 12px; font-weight: 500; letter-spacing: 0.5px; }

.nav-links { display: flex; align-items: center; gap: 8px; }
.nav-links a { padding: 10px 16px; font-size: 15px; font-weight: 500; color: #475569; border-radius: 8px; transition: 0.3s; text-decoration: none; }
.nav-links a:hover { color: #0f5132; background: #f0fdf4; }
.nav-links a.active { color: #0f5132; font-weight: 700; background: #f0fdf4; }

.nav-links a.cta { background: #ffffff; color: #0f5132; padding: 12px 24px; border-radius: 4px; font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 0.5px; border: 1px solid #e2e8f0; }
.nav-links a.cta:hover { background: #f1f5f9; box-shadow: 0 6px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }

.nav-profile-link { display: flex; align-items: center; gap: 10px; padding: 6px 16px 6px 6px; border-radius: 50px; background: #f1f5f9; border: 1px solid #e2e8f0; transition: 0.3s; margin-left: 12px; text-decoration: none; }
.nav-profile-link:hover { background: #e2e8f0; }
.nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: #0f5132; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; object-fit: cover; overflow: hidden;}
.nav-avatar img { width: 100%; height: 100%; object-fit: cover; }
.nav-profile-name { font-size: 14px; font-weight: 600; color: #0f5132; }

/* ============================================================
   PAGE HEADER
   ============================================================ */
.page-header {
    position:relative; color:#fff;
    padding:60px 20px 100px; text-align:center; overflow:hidden;
    background: linear-gradient(135deg, #0f5132 0%, #0a3622 100%);
}
.page-header::after {
    content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background-image: radial-gradient(circle at 10% 20%, rgba(255,255,255,0.05) 2px, transparent 2px), radial-gradient(circle at 90% 80%, rgba(255,255,255,0.05) 2px, transparent 2px);
    background-size: 100px 100px, 120px 120px;
}
.page-header-inner { max-width:950px; margin:0 auto; position:relative; z-index:2; animation: fadeDown 0.6s ease-out 0.2s both; }
.page-header h1 { font-size:clamp(28px,3.5vw,42px); font-weight:700; margin-bottom:12px; color: #fff; }
.page-header h1 span { color: #ffffff; }
.page-header p { font-size:16px; color: #cbd5e1; }

/* CONTAINER */
.container { max-width:1300px; margin:-50px auto 60px; padding:0 24px; position:relative; z-index:10; }

/* ============================================================
   STAT MINI
   ============================================================ */
.stat-mini-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:20px; margin-bottom:30px; }
.stat-mini { display: flex; align-items: center; gap: 16px; background: #fff; border-radius: 100px; padding: 16px 24px; box-shadow: 0 15px 35px rgba(15,81,50,0.08); border: 1px solid #e2e8f0; border-left: 5px solid #0f5132; transition: 0.3s; animation: fadeUp 0.5s ease-out both; }
.stat-mini:nth-child(1) { animation-delay: 0.1s; }
.stat-mini:nth-child(2) { animation-delay: 0.2s; }
.stat-mini:nth-child(3) { animation-delay: 0.3s; }
.stat-mini:hover { transform:translateY(-5px); box-shadow:0 20px 40px rgba(15,81,50,0.15); }
.stat-icon-box { display: none; } 
.stat-info { display: flex; flex-direction: column; }
.stat-mini .label { font-size:12px; color:#718096; text-transform:uppercase; letter-spacing:0.8px; font-weight:700; margin-bottom:2px; }
.stat-mini .value { font-size:26px; font-weight:700; color:#0f5132; line-height:1.1; }

.stat-mini.orange { border-left-color:#f59e0b; }
.stat-mini.red { border-left-color:#ef4444; }
.stat-mini.green { border-left-color:#10b981; }

/* ============================================================
   ALERT
   ============================================================ */
.alert { padding:16px 24px; border-radius:12px; margin-bottom:24px; font-size:15px; font-weight:500; display:flex; align-items:center; gap:12px; animation: fadeUp 0.5s ease-out; }
.alert-success { background:#d1fae5; color:#065f46; border-left:4px solid #10b981; }

/* ============================================================
   TABLE CARD
   ============================================================ */
.table-card { background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 15px 40px rgba(15,81,50,0.06); border: 1px solid #e2e8f0; animation: fadeUp 0.6s ease-out 0.4s both; }
.table-card-header { padding:24px 30px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.table-card-header h2 { font-size:20px; font-weight:700; color:#0f5132; display:flex; align-items:center; gap:10px; }
.table-card-header h2::before { content:""; width:6px; height:24px; background:#e2e8f0; border-radius:3px; }
.table-card-header .count-badge { font-size:14px; font-weight:700; background:#f0fdf4; color:#0f5132; padding:8px 16px; border-radius:30px; border: 1px solid #d1fae5; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table.data-table { width:100%; border-collapse:collapse; font-size:15px; }
table.data-table thead th { background:#f8fafc; padding:16px 30px; text-align:left; font-size:13px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.8px; border-bottom:2px solid #e2e8f0; white-space:nowrap; }
table.data-table tbody td { padding:20px 30px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
table.data-table tbody tr:hover { background:#f8fafc; }
table.data-table tbody tr:last-child td { border-bottom:none; }

.kode-pinjam { display:inline-block; background:#f0fdf4; color:#0f5132; font-family: monospace; font-size:13px; font-weight:700; padding:6px 12px; border-radius:8px; letter-spacing:0.5px; border: 1px solid #d1fae5; }
.buku-info-cell .judul { font-weight:700; color:#0f5132; font-size:16px; margin-bottom:4px; line-height:1.3; }
.buku-info-cell .pengarang { font-size:14px; color:#64748b; font-weight: 400; }
.tgl-cell { font-size:15px; color:#334155; font-weight: 500; white-space:nowrap; }
.tgl-cell.empty { color:#94a3b8; font-style:italic; }

.status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:30px; font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap; }
.status-badge::before { content:""; width:8px; height:8px; border-radius:50%; background:currentColor; }
.status-pending    { background:#fef3c7; color:#d97706; }
.status-dipinjam   { background:#eff6ff; color:#2563eb; }
.status-dikembalikan { background:#d1fae5; color:#059669; }
.status-ditolak    { background:#fee2e2; color:#dc2626; }

.denda-cell { font-weight:700; font-size:15px; white-space:nowrap; }
.denda-cell.zero { color:#10b981; }
.denda-cell.ada { color:#ef4444; }

/* EMPTY STATE */
.empty-state { text-align:center; padding:80px 20px; }
.empty-state h3 { color:#0f5132; font-size:22px; margin-bottom:8px; font-weight:700; }
.empty-state p { color:#64748b; font-size:16px; margin-bottom:24px; max-width:380px; margin-left:auto; margin-right:auto; line-height:1.6; }
.btn-ut-primary { display:inline-flex; align-items:center; gap:8px; padding:14px 32px; background:#0f5132; color:#fff; border-radius:8px; text-decoration:none; font-weight:600; font-size:15px; transition:0.3s; }
.btn-ut-primary:hover { background:#146c43; transform:translateY(-3px); box-shadow:0 10px 25px rgba(15,81,50,0.3); }

/* ============================================================
   CARD LIST MOBILE (pengganti tabel)
   ============================================================ */
.riwayat-cards-mobile { display: none; }

/* ============================================================
   FOOTER
   ============================================================ */
.footer-ut { background: #0a3622; color: #fff; padding: 80px 24px 30px; position: relative; overflow: hidden; }
.footer-ut::before { content: ""; position: absolute; top: 0; right: 0; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%); }
.footer-inner { max-width: 1300px; margin: 0 auto; display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr; gap: 40px; position: relative; z-index: 2; }
.footer-brand { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.footer-brand img { width: 50px; height: 50px; border-radius: 50%; background: #fff; padding: 4px; }
.footer-brand h4 { font-size: 20px; font-weight: 700; margin: 0; color: #fff; }
.footer-brand p { font-size: 13px; color: #cbd5e1; margin: 4px 0 0 0; }
.footer-desc { font-size: 15px; color: #94a3b8; line-height: 1.8; margin-bottom: 24px; }
.footer-ut h5 { font-size: 16px; font-weight: 700; color: #ffffff; margin-bottom: 24px; }
.footer-ut ul { list-style: none; }
.footer-ut ul li { margin-bottom: 12px; }
.footer-ut ul a { color: #94a3b8; font-size: 15px; display: flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none; }
.footer-ut ul a:hover { color: #fff; transform: translateX(5px); }
.footer-contact p { display: flex; align-items: flex-start; gap: 12px; font-size: 15px; color: #94a3b8; margin-bottom: 16px; line-height: 1.6; }
.footer-copy { max-width: 1300px; margin: 60px auto 0; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; font-size: 14px; color: #64748b; position: relative; z-index: 2; }

/* ============================================================
   TAMBAHAN CSS: EFEK KETIK (TYPEWRITER LOOP)
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
   RESPONSIVE TABLET
   ============================================================ */
@media (max-width: 1024px) {
    .footer-inner { grid-template-columns: 1fr 1fr; }
}

/* ============================================================
   RESPONSIVE MOBILE
   ============================================================ */
@media (max-width: 768px) {
    body { padding-bottom: 100px; }

    .top-strip { display: none !important; }
    .main-nav { display: none !important; }

    .m-mobile-header {
        display: flex; align-items: center; justify-content: space-between;
        position: sticky; top: 0; z-index: 999;
        background: rgba(15, 81, 50, 0.97);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        padding: 10px 16px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    }
    .m-mobile-header .brand { display: flex; align-items: center; gap: 10px; }
    .m-mobile-header .brand img { width: 34px; height: 34px; border-radius: 50%; background: #fff; padding: 2px; object-fit: contain; }
    .m-mobile-header .brand-text strong { display: block; color: #fff; font-size: 13.5px; font-weight: 700; line-height: 1.2; }
    .m-mobile-header .brand-text small { display: block; color: rgba(255,255,255,0.7); font-size: 10px; font-weight: 500; margin-top: 1px; }
    .m-mobile-header .m-avatar {
        width: 34px; height: 34px; border-radius: 50%; background: #ffcc00; color: var(--primary-dark);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 13px; border: 2px solid rgba(255,255,255,0.3);
        object-fit: cover; flex-shrink: 0; overflow: hidden;
    }
    .m-mobile-header .m-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

    .page-header { padding: 36px 16px 80px; }
    .page-header h1 { font-size: 24px; }
    .page-header p { font-size: 13px; }

    .container { margin: -50px auto 40px; padding: 0 14px; }

    .stat-mini-grid { grid-template-columns: 1fr; gap: 10px; margin-bottom: 20px; }
    .stat-mini { border-radius: 14px; padding: 12px 16px; border-left-width: 4px; }
    .stat-mini .label { font-size: 10.5px; }
    .stat-mini .value { font-size: 20px; }
    .stat-mini .value[style] { font-size: 16px !important; }

    .table-card { border-radius: 16px; }
    .table-card-header { padding: 16px 18px; }
    .table-card-header h2 { font-size: 16px; }
    .table-card-header h2::before { width: 4px; height: 18px; }
    .table-card-header .count-badge { font-size: 12px; padding: 6px 12px; }

    .table-wrap { display: none; }
    .riwayat-cards-mobile { display: flex; flex-direction: column; gap: 12px; padding: 14px; }

    .r-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 14px 16px; box-shadow: 0 4px 12px rgba(15,81,50,0.04);
        display: flex; flex-direction: column; gap: 10px;
        animation: fadeUp 0.4s ease-out both;
    }
    .r-card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .r-card-kode {
        font-family: monospace; font-size: 11.5px; font-weight: 700;
        background: #f0fdf4; color: #0f5132; border: 1px solid #d1fae5;
        padding: 5px 10px; border-radius: 6px; letter-spacing: 0.3px;
        display: inline-block;
    }
    .r-card-judul { font-size: 14.5px; font-weight: 700; color: #0f5132; line-height: 1.35; margin-bottom: 2px; }
    .r-card-pengarang { font-size: 12px; color: #64748b; }

    .r-card-divider { height: 1px; background: #f1f5f9; margin: 2px 0; }

    .r-card-dates { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; }
    .r-date-item { display: flex; flex-direction: column; gap: 2px; }
    .r-date-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.6px; }
    .r-date-value { font-size: 12.5px; font-weight: 600; color: #334155; }
    .r-date-value.empty { color: #94a3b8; font-style: italic; font-weight: 500; }

    .r-card-bottom { display: flex; justify-content: space-between; align-items: center; gap: 10px; }
    .r-card-bottom .status-badge { font-size: 10.5px; padding: 5px 10px; gap: 4px; }
    .r-card-bottom .status-badge::before { width: 6px; height: 6px; }
    .r-card-bottom .denda-cell { font-size: 13px; }

    .empty-state { padding: 50px 20px; }
    .empty-state h3 { font-size: 18px; }
    .empty-state p { font-size: 13px; }
    .btn-ut-primary { padding: 12px 24px; font-size: 13px; }

    .footer-ut { padding: 40px 16px 20px; }
    .footer-inner { grid-template-columns: 1fr; gap: 24px; }
    .footer-brand img { width: 36px; height: 36px; }
    .footer-brand h4 { font-size: 16px; }
    .footer-desc { font-size: 12.5px; margin-bottom: 12px; }
    .footer-ut h5 { font-size: 14px; margin-bottom: 12px; }
    .footer-ut ul li { margin-bottom: 8px; }
    .footer-ut ul a { font-size: 13px; }
    .footer-contact p { font-size: 13px; margin-bottom: 12px; }
    .footer-copy { margin-top: 24px; padding-top: 16px; font-size: 11px; }

    .m-bottom-nav {
        position: fixed; bottom: 15px; left: 50%; transform: translateX(-50%);
        width: calc(100% - 30px); max-width: 430px;
        background: #0a3622;
        border-radius: 50px;
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px 14px; z-index: 1000;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    .m-nav-item {
        display: flex; flex-direction: column; align-items: center; gap: 3px;
        color: #94a3b8; font-size: 9.5px; font-weight: 600; text-decoration: none; transition: 0.3s;
        width: 52px;
    }
    .m-nav-item i { font-size: 19px; }
    .m-nav-item.active { color: #ffcc00; }
    .m-nav-item.logout { color: #fca5a5; }
    .m-nav-item.logout:active { color: #ef4444; }

    .m-nav-center {
        width: 52px; height: 52px; background: #ffcc00; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: #0a3622; font-size: 22px; margin-top: -30px;
        box-shadow: 0 6px 15px rgba(255,204,0,0.4); transition: 0.3s;
        border: 3px solid #f8fafc; flex-shrink: 0;
    }
    .m-nav-center:active { transform: scale(0.9); }
}

@media (max-width: 380px) {
    .page-header h1 { font-size: 20px; }
    .stat-mini .value { font-size: 18px; }
    .r-card-judul { font-size: 13.5px; }
    .r-date-value { font-size: 11.5px; }
    .m-mobile-header { padding: 8px 12px; }
    .m-mobile-header .brand-text strong { font-size: 12.5px; }
    .m-nav-item { width: 46px; font-size: 9px; }
    .m-nav-item i { font-size: 17px; }
    .m-nav-center { width: 48px; height: 48px; font-size: 20px; margin-top: -26px; }
}
</style>
</head>
<body>

<!-- HEADER MINI MOBILE -->
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

<!-- TOP STRIP (Desktop Only) -->
<?php require_once '../includes/topstrip.php'; ?>

<!-- MAIN NAVBAR (Desktop Only) -->
<div class="main-nav">
    <div class="main-nav-inner">
        <a href="dashboard.php" class="logo-box">
            <img src="../assets/img/foto.png" alt="Logo" class="nav-logo">
            <div class="logo-text">
                <strong class="typing-text-desktop"></strong>
            </div>
        </a>
        
        <div class="nav-links">
            <a href="dashboard.php">Home</a>
            <a href="katalog.php">Katalog Buku</a>
            <a href="riwayat.php" class="active">Riwayat Pinjam</a>

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

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="page-header-inner">
        <h1>Riwayat <span>Peminjaman</span></h1>
        <p>Daftar lengkap aktivitas peminjaman buku Anda</p>
    </div>
</div>

<!-- CONTENT -->
<div class="container">

    <?php if ($m = flash('success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <!-- STAT MINI -->
    <div class="stat-mini-grid">
        <div class="stat-mini">
            <div class="stat-info">
                <div class="label">Total Pinjaman</div>
                <div class="value"><?= $totalPinjam ?></div>
            </div>
        </div>

        <div class="stat-mini orange">
            <div class="stat-info">
                <div class="label">Sedang Dipinjam</div>
                <div class="value"><?= $sedangDipinjam ?></div>
            </div>
        </div>

        <div class="stat-mini <?= $totalDenda > 0 ? 'red' : 'green' ?>">
            <div class="stat-info">
                <div class="label">Total Denda</div>
                <div class="value" style="font-size:20px;">Rp <?= number_format($totalDenda,0,',','.') ?></div>
            </div>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
        <div class="table-card-header">
            <h2>Daftar Riwayat</h2>
            <span class="count-badge"><?= $totalPinjam ?> pinjaman</span>
        </div>

        <?php if ($list->num_rows === 0): ?>
            <div class="empty-state">
                <h3>Belum Ada Riwayat</h3>
                <p>Anda belum pernah meminjam buku. Mulai jelajahi koleksi perpustakaan kami sekarang.</p>
                <a href="katalog.php" class="btn-ut-primary">Cari Buku</a>
            </div>
        <?php else: ?>
            <!-- TABEL DESKTOP -->
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $list->data_seek(0);
                        while ($t = $list->fetch_assoc()): 
                            $statusClass = 'status-' . $t['status'];
                        ?>
                        <tr>
                            <td style="font-weight:600; color:#94a3b8;"><?= $no++ ?></td>
                            <td><span class="kode-pinjam"><?= htmlspecialchars($t['kode_pinjam']) ?></span></td>
                            <td class="buku-info-cell">
                                <div class="judul"><?= htmlspecialchars($t['judul']) ?></div>
                                <div class="pengarang"><?= htmlspecialchars($t['pengarang']) ?></div>
                            </td>
                            <td class="tgl-cell"><?= date('d M Y', strtotime($t['tgl_pinjam'])) ?></td>
                            <td class="tgl-cell"><?= date('d M Y', strtotime($t['tgl_jatuh_tempo'])) ?></td>
                            <td class="tgl-cell <?= $t['tgl_kembali'] ? '' : 'empty' ?>">
                                <?= $t['tgl_kembali'] ? date('d M Y', strtotime($t['tgl_kembali'])) : 'Belum kembali' ?>
                            </td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= ucfirst($t['status']) ?>
                                </span>
                            </td>
                            <td class="denda-cell <?= $t['denda'] > 0 ? 'ada' : 'zero' ?>">
                                <?= $t['denda'] > 0 ? 'Rp ' . number_format($t['denda'],0,',','.') : 'Gratis' ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- CARD LIST MOBILE -->
            <div class="riwayat-cards-mobile">
                <?php 
                $no = 1;
                $list->data_seek(0);
                while ($t = $list->fetch_assoc()): 
                    $statusClass = 'status-' . $t['status'];
                ?>
                <div class="r-card" style="animation-delay: <?= min($no * 0.05, 0.3) ?>s;">
                    <div class="r-card-top">
                        <div style="flex:1; min-width:0;">
                            <div class="r-card-judul"><?= htmlspecialchars($t['judul']) ?></div>
                            <div class="r-card-pengarang"><?= htmlspecialchars($t['pengarang']) ?></div>
                        </div>
                        <span class="r-card-kode"><?= htmlspecialchars($t['kode_pinjam']) ?></span>
                    </div>

                    <div class="r-card-divider"></div>

                    <div class="r-card-dates">
                        <div class="r-date-item">
                            <span class="r-date-label">Tgl Pinjam</span>
                            <span class="r-date-value"><?= date('d M Y', strtotime($t['tgl_pinjam'])) ?></span>
                        </div>
                        <div class="r-date-item">
                            <span class="r-date-label">Jatuh Tempo</span>
                            <span class="r-date-value"><?= date('d M Y', strtotime($t['tgl_jatuh_tempo'])) ?></span>
                        </div>
                        <div class="r-date-item" style="grid-column: 1 / -1;">
                            <span class="r-date-label">Tgl Kembali</span>
                            <span class="r-date-value <?= $t['tgl_kembali'] ? '' : 'empty' ?>">
                                <?= $t['tgl_kembali'] ? date('d M Y', strtotime($t['tgl_kembali'])) : 'Belum kembali' ?>
                            </span>
                        </div>
                    </div>

                    <div class="r-card-divider"></div>

                    <div class="r-card-bottom">
                        <span class="status-badge <?= $statusClass ?>"><?= ucfirst($t['status']) ?></span>
                        <span class="denda-cell <?= $t['denda'] > 0 ? 'ada' : 'zero' ?>">
                            <?= $t['denda'] > 0 ? 'Rp ' . number_format($t['denda'],0,',','.') : 'Gratis' ?>
                        </span>
                    </div>
                </div>
                <?php $no++; endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
<!-- BOTTOM NAVIGATION BAR -->
<div class="m-bottom-nav">
    <a href="dashboard.php" class="m-nav-item">
        <i class="bi bi-house-door"></i>
        <span>Beranda</span>
    </a>
    
    <a href="riwayat.php" class="m-nav-item active">
        <i class="bi bi-clock-history"></i>
        <span>Riwayat</span>
    </a>
    
    <a href="katalog.php" class="m-nav-center" title="Katalog Buku">
        <i class="bi bi-grid"></i>
    </a>

    <a href="../pengaturan.php" class="m-nav-item">
        <i class="bi bi-person"></i>
        <span>Akun</span>
    </a>
    
    <a href="../auth/logout.php" class="m-nav-item logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Keluar</span>
    </a>
</div>

<script>
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
    // FITUR AUTO REFRESH REALTIME (Khusus Riwayat)
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        // Halaman akan otomatis ter-refresh setiap 15 detik 
        // Jangan terlalu cepat agar server tidak terbebani
        setInterval(function() {
            // Hanya refresh jika tab browser sedang aktif (tidak sedang di-minimize)
            if (document.visibilityState === 'visible') {
                window.location.reload();
            }
        }, 15000); // 15000 ms = 15 detik
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