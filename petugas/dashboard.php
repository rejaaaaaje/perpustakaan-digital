<?php
$title = 'Dashboard Petugas';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$totalPinjam  = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE status='dipinjam'")->fetch_assoc()['c'] ?? 0;
$totalPending = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE status='pending'")->fetch_assoc()['c'] ?? 0;
$totalKembali = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE status='dikembalikan'")->fetch_assoc()['c'] ?? 0;
$totalBuku    = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'] ?? 0;
$totalAnggota = $conn->query("SELECT COUNT(*) c FROM users WHERE role='anggota'")->fetch_assoc()['c'] ?? 0;

$pendingList = $conn->query("SELECT t.*, u.nama AS nama_user, b.judul 
                             FROM pinjaman t 
                             JOIN users u ON t.user_id=u.id 
                             JOIN buku b ON t.buku_id=b.id 
                             WHERE t.status='pending' 
                             ORDER BY t.id DESC LIMIT 5");

$dipinjamList = $conn->query("SELECT t.*, u.nama AS nama_user, b.judul 
                              FROM pinjaman t 
                              JOIN users u ON t.user_id=u.id 
                              JOIN buku b ON t.buku_id=b.id 
                              WHERE t.status='dipinjam' 
                              ORDER BY t.tgl_jatuh_tempo ASC LIMIT 5");
?>

<div class="main">
    <div class="topbar">
        <h2>Dashboard Petugas</h2>

        <!-- ===== USER PROFIL (SUDAH ADA FOTO) ===== -->
        <a href="<?= base_url('pengaturan.php') ?>" class="user-info" title="Pengaturan Akun">
            <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
            <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
                <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" class="avatar" alt="Profil">
            <?php else: ?>
                <div class="avatar"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
            <?php endif; ?>
        </a>
        <!-- ===== END USER PROFIL ===== -->
    </div>

    <div class="content">

        <!-- WELCOME -->
        <div class="welcome">
            <div class="welcome-text">
                <h3>Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?></h3>
                <p>Berikut ringkasan aktivitas perpustakaan hari ini.</p>
            </div>
            <div class="welcome-date">
                <span class="welcome-day"><?= date('d M Y') ?></span>
                <span class="welcome-status"><span class="dot"></span> Online</span>
            </div>
        </div>

        <!-- STAT -->
        <div class="stats">
            <div class="stat">
                <div class="stat-label">Menunggu Approval</div>
                <div class="stat-value">
                    <?= $totalPending ?>
                    <?php if ($totalPending > 0): ?><span class="stat-badge stat-badge-orange">Baru</span><?php endif; ?>
                </div>
            </div>
            <div class="stat">
                <div class="stat-label">Sedang Dipinjam</div>
                <div class="stat-value"><?= $totalPinjam ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Dikembalikan</div>
                <div class="stat-value"><?= $totalKembali ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Buku</div>
                <div class="stat-value"><?= $totalBuku ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Anggota</div>
                <div class="stat-value"><?= $totalAnggota ?></div>
            </div>
        </div>

        <!-- MENU CEPAT -->
        <div class="card">
            <div class="card-title">Menu Cepat</div>
            <div class="quick-grid">
                <a href="pinjaman.php" class="quick-link">
                    <span class="quick-label">Kelola Peminjaman</span>
                </a>
                <a href="buku.php" class="quick-link">
                    <span class="quick-label">Daftar Buku</span>
                </a>
                <a href="pinjaman.php?filter=pending" class="quick-link">
                    <span class="quick-label">Pending (<?= $totalPending ?>)</span>
                </a>
                <a href="pinjaman.php?filter=dipinjam" class="quick-link">
                    <span class="quick-label">Dipinjam (<?= $totalPinjam ?>)</span>
                </a>
            </div>
        </div>

        <!-- 2 PANEL -->
        <div class="panels">

            <!-- PENDING -->
            <div class="card panel">
                <div class="panel-head">
                    <h4>Perlu Diproses</h4>
                    <a href="pinjaman.php?filter=pending" class="panel-link">Lihat semua</a>
                </div>

                <?php if ($pendingList->num_rows === 0): ?>
                    <div class="empty-box">
                        <div class="empty-box-title">Tidak Ada Antrean</div>
                        <div class="empty-box-desc">Semua peminjaman sudah diproses.</div>
                    </div>
                <?php else: ?>
                    <div class="list">
                        <?php while ($t = $pendingList->fetch_assoc()): ?>
                        <div class="list-row">
                            <div class="list-avatar list-avatar-orange"></div>
                            <div class="list-info">
                                <div class="list-name"><?= htmlspecialchars($t['nama_user']) ?></div>
                                <div class="list-desc">Meminjam <b><?= htmlspecialchars($t['judul']) ?></b></div>
                            </div>
                            <a href="pinjaman.php?aksi=approve&id=<?= $t['id'] ?>" 
                               class="btn-act btn-act-green"
                               onclick="return confirm('Setujui peminjaman ini?')">Setujui</a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DIPINJAM -->
            <div class="card panel">
                <div class="panel-head">
                    <h4> Sedang Dipinjam</h4>
                    <a href="pinjaman.php?filter=dipinjam" class="panel-link">Lihat semua</a>
                </div>

                <?php if ($dipinjamList->num_rows === 0): ?>
                    <div class="empty-box">
                        <div class="empty-box-icon"></div>
                        <div class="empty-box-title">Tidak Ada Peminjaman</div>
                        <div class="empty-box-desc">Belum ada buku yang dipinjam.</div>
                    </div>
                <?php else: ?>
                    <div class="list">
                        <?php 
                        $today = date('Y-m-d');
                        while ($t = $dipinjamList->fetch_assoc()): 
                            $terlambat = strtotime($t['tgl_jatuh_tempo']) < strtotime($today);
                        ?>
                        <div class="list-row <?= $terlambat ? 'list-row-late' : '' ?>">
                            <div class="list-avatar <?= $terlambat ? 'list-avatar-red' : 'list-avatar-blue' ?>">
                                <?= $terlambat ? '!' : '' ?>
                            </div>
                            <div class="list-info">
                                <div class="list-name"><?= htmlspecialchars($t['nama_user']) ?></div>
                                <div class="list-desc">
                                    <b><?= htmlspecialchars($t['judul']) ?></b> · 
                                    Jatuh tempo <?= date('d/m/Y', strtotime($t['tgl_jatuh_tempo'])) ?>
                                    <?php if ($terlambat): ?>
                                        <span class="tag-late">TERLAMBAT</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="pinjaman.php?aksi=kembali&id=<?= $t['id'] ?>" 
                               class="btn-act btn-act-orange"
                               onclick="return confirm('Konfirmasi pengembalian?')">Kembalikan</a>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<style>
/* ============ USER PROFIL TOPBAR ============ */
.topbar .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    padding: 5px 5px 5px 14px;
    border-radius: 50px;
    border: 1px solid #e9ecef;
    font-size: 13px;
    font-weight: 500;
    color: #4a5568;
    text-decoration: none;
    flex-shrink: 0;
    transition: all .2s;
    cursor: pointer;
}
.topbar .user-info:hover {
    border-color: #0f5132;
    box-shadow: 0 4px 12px rgba(15,81,50,0.1);
}
.topbar .user-info span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 180px;
}
.topbar .avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #0f5132;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
    object-fit: cover;
    overflow: hidden;
}

/* ============ WELCOME ============ */
.welcome {
    background: linear-gradient(135deg, #0f5132, #146c43);
    border-radius: 12px;
    padding: 22px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    color: #fff;
    margin-bottom: 20px;
}
.welcome-text h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 4px 0;
}
.welcome-text p {
    font-size: 13px;
    margin: 0;
    opacity: 0.85;
}
.welcome-date {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}
.welcome-day {
    font-size: 13px;
    font-weight: 600;
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,0.2);
}
.welcome-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 700;
    color: #68d391;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.welcome-status .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 8px #10b981;
}

/* ============ STATS ============ */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}
.stat {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 16px 18px;
    min-width: 0;
}
.stat-label {
    font-size: 11.5px;
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}
.stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #1a202c;
    line-height: 1;
    display: flex;
    align-items: center;
    gap: 8px;
}
.stat-badge {
    font-size: 10px;
    font-weight: 800;
    padding: 3px 8px;
    border-radius: 4px;
    letter-spacing: 0.5px;
}
.stat-badge-orange {
    background: #fff7ed;
    color: #c05621;
}

/* ============ QUICK MENU ============ */
.quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-top: 6px;
}
.quick-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: #fafbfc;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #2d3748;
    font-size: 13px;
    font-weight: 600;
    transition: all .15s;
    min-width: 0;
}
.quick-link:hover {
    background: #0f5132;
    border-color: #0f5132;
    color: #fff;
}
.quick-emoji {
    font-size: 16px;
    flex-shrink: 0;
}
.quick-label {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ============ PANEL ============ */
.panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.panel {
    margin-bottom: 0 !important;
}
.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid #f1f3f5;
}
.panel-head h4 {
    font-size: 14px;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}
.panel-link {
    font-size: 12px;
    color: #4a5568;
    text-decoration: none;
    font-weight: 600;
}
.panel-link:hover { color: #0f5132; }

/* ============ LIST ============ */
.list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.list-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    background: #fafbfc;
    border: 1px solid #f0f0f0;
    border-radius: 8px;
    min-width: 0;
    transition: all .15s;
}
.list-row:hover {
    background: #fff;
    border-color: #cbd5e0;
}
.list-row-late {
    border-left: 3px solid #e53e3e;
    background: #fffafb;
}
.list-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    font-weight: 700;
}
.list-avatar-orange { background: #fff7ed; color: #c05621; }
.list-avatar-blue   { background: #d1fae5; color: #0f5132; }
.list-avatar-red    { background: #fff5f5; color: #c53030; }
.list-info {
    flex: 1;
    min-width: 0;
    overflow: hidden;
}
.list-name {
    font-size: 13px;
    font-weight: 700;
    color: #1a202c;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.list-desc {
    font-size: 11.5px;
    color: #718096;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.list-desc b {
    color: #4a5568;
}
.tag-late {
    color: #e53e3e;
    font-weight: 800;
    font-size: 10.5px;
    letter-spacing: 0.5px;
    margin-left: 4px;
}

/* ============ BUTTON ============ */
.btn-act {
    display: inline-flex;
    align-items: center;
    padding: 7px 14px;
    font-size: 11.5px;
    font-weight: 700;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all .15s;
    letter-spacing: 0.3px;
}
.btn-act-green { background: #38a169; color: #fff; }
.btn-act-green:hover { background: #2f855a; }
.btn-act-orange { background: #dd6b20; color: #fff; }
.btn-act-orange:hover { background: #c05621; }

/* ============ EMPTY ============ */
.empty-box {
    text-align: center;
    padding: 40px 20px;
}
.empty-box-icon {
    font-size: 40px;
    margin-bottom: 10px;
    opacity: 0.5;
}
.empty-box-title {
    font-size: 14px;
    font-weight: 700;
    color: #4a5568;
    margin-bottom: 4px;
}
.empty-box-desc {
    font-size: 12.5px;
    color: #a0aec0;
}

/* ============ RESPONSIVE ============ */
@media (max-width: 1000px) {
    .panels { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .welcome { padding: 18px 20px; }
    .welcome-text h3 { font-size: 16px; }
    .stat-value { font-size: 22px; }

    .topbar .user-info span {
        display: none; /* sembunyikan nama di HP kecil, foto tetap tampil */
    }
    .topbar .user-info {
        padding: 4px;
        border-radius: 50%;
        border: none;
        background: transparent;
    }
    .topbar .avatar {
        width: 38px;
        height: 38px;
        font-size: 15px;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>
<?php require_once '../includes/welcome_screen.php'; ?>