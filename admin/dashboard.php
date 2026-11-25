<?php
$title = 'Dashboard Admin';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// ============ STATISTIK ============
$totalBuku    = $conn->query("SELECT COUNT(*) c FROM buku")->fetch_assoc()['c'] ?? 0;
$totalAnggota = $conn->query("SELECT COUNT(*) c FROM users WHERE role='anggota'")->fetch_assoc()['c'] ?? 0;
$totalPinjam  = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE status='dipinjam'")->fetch_assoc()['c'] ?? 0;
$totalPending = $conn->query("SELECT COUNT(*) c FROM pinjaman WHERE status='pending'")->fetch_assoc()['c'] ?? 0;

// ============ NOTIFIKASI KOMENTAR ============
$notifKomentar = $conn->query("SELECT COUNT(*) c FROM komentar WHERE status_baca = 0")->fetch_assoc()['c'] ?? 0;

$totalTelat = 0;
$cekKolom = $conn->query("SHOW COLUMNS FROM pinjaman LIKE 'tgl_jatuh_tempo'");
$hasJatuhTempo = ($cekKolom && $cekKolom->num_rows > 0);
if ($hasJatuhTempo) {
    $totalTelat = $conn->query("SELECT COUNT(*) c FROM pinjaman 
                                WHERE status='dipinjam' AND tgl_jatuh_tempo < CURDATE()")
                       ->fetch_assoc()['c'] ?? 0;
}

$cekKode = $conn->query("SHOW COLUMNS FROM pinjaman LIKE 'kode_pinjam'");
$hasKodePinjam = ($cekKode && $cekKode->num_rows > 0);
?>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- ================= CSS KHUSUS DASHBOARD ================= -->
<style>
/* ============================================================
   RESET & FORCE LAYOUT
   ============================================================ */
.dash-wrap,
.dash-wrap * {
    box-sizing: border-box;
}

.dash-wrap {
    padding: 24px 28px 40px;
    background: #f7f8fa;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
    color: #2d3748;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

/* ============================================================
   HEADER
   ============================================================ */
.dash-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.dash-header-text h2 {
    font-size: 22px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 4px 0;
    line-height: 1.2;
}
.dash-header-text p {
    font-size: 13px;
    color: #718096;
    margin: 0;
}
.dash-user {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    padding: 6px 6px 6px 16px;
    border-radius: 50px;
    border: 1px solid #e9ecef;
    font-size: 13px;
    font-weight: 500;
    color: #4a5568;
    flex-shrink: 0;
}
.dash-avatar {
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
    overflow: hidden; /* TAMBAHAN: Agar foto tidak melebar */
}
.dash-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ============================================================
   ALERT KOMENTAR BARU
   ============================================================ */
.alert-komentar {
    background: #fff5f5;
    border: 1px solid #feb2b2;
    color: #c53030;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    animation: fadeUp 0.4s ease-out;
}
.alert-komentar a {
    color: #c53030;
    text-decoration: underline;
    font-weight: 700;
}

/* ============================================================
   STATISTIK — AUTO RESPONSIVE (TANPA IKON)
   ============================================================ */
.dash-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
    width: 100%;
}

.dash-stat {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 22px 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    overflow: hidden;
    transition: all .2s;
    position: relative;
}
.dash-stat::before {
    content: "";
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: #cbd5e0;
    transition: all .3s;
}
.dash-stat:hover {
    border-color: #cbd5e0;
    box-shadow: 0 6px 16px rgba(0,0,0,.06);
    transform: translateY(-3px);
}
.dash-stat:hover::before {
    background: #0f5132;
}

.dash-stat.blue::before { background: #0f5132; }
.dash-stat.green::before { background: #48bb78; }
.dash-stat.orange::before { background: #ed8936; }
.dash-stat.red::before { background: #e53e3e; }

.dash-stat-info {
    min-width: 0;
    flex: 1;
    overflow: hidden;
}
.dash-stat-info h3 {
    font-size: 32px;
    font-weight: 800;
    color: #1a202c;
    margin: 0 0 6px 0;
    line-height: 1;
    letter-spacing: -0.5px;
}
.dash-stat-info p {
    font-size: 13px;
    color: #718096;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* ============================================================
   CARD
   ============================================================ */
.dash-card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 20px;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}
.dash-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 14px;
    margin-bottom: 8px;
    border-bottom: 1px solid #f1f3f5;
    gap: 12px;
    flex-wrap: wrap;
}
.dash-card-head h4 {
    font-size: 15px;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.dash-link {
    font-size: 13px;
    color: #4a5568;
    text-decoration: none;
    font-weight: 600;
    white-space: nowrap;
    transition: color .2s;
}
.dash-link:hover { color: #0f5132; }

/* ============================================================
   TABEL — DESKTOP
   ============================================================ */
.dash-table-wrap {
    overflow-x: auto;
    width: 100%;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
}
.dash-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
    min-width: 600px;
}
.dash-table thead th {
    text-align: left;
    padding: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: #718096;
    background: #fafbfc;
    border-bottom: 1px solid #e9ecef;
    white-space: nowrap;
}
.dash-table tbody td {
    padding: 14px 12px;
    border-bottom: 1px solid #f1f3f5;
    color: #2d3748;
    vertical-align: middle;
}
.dash-table tbody tr:last-child td { border-bottom: none; }
.dash-table tbody tr:hover { background: #fafbfc; }

.dash-code {
    display: inline-block;
    background: #f1f3f5;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 12px;
    color: #4a5568;
    font-family: 'Consolas', monospace;
    white-space: nowrap;
    font-weight: 700;
}

.dash-empty {
    text-align: center;
    padding: 50px 20px;
    color: #a0aec0;
}
.dash-empty i {
    font-size: 48px;
    display: block;
    margin-bottom: 12px;
    opacity: 0.4;
}

/* ============================================================
   BADGE
   ============================================================ */
.dash-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: capitalize;
    white-space: nowrap;
    letter-spacing: 0.3px;
}
.dash-badge.success { background: #f0fff4; color: #2f855a; }
.dash-badge.info    { background: #d1fae5; color: #0f5132; }
.dash-badge.warning { background: #fffaf0; color: #c05621; }
.dash-badge.danger  { background: #fff5f5; color: #c53030; }
.dash-badge.gray    { background: #f1f3f5; color: #4a5568; }

/* ============================================================
   QUICK ACTIONS
   ============================================================ */
.dash-quick {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
    margin-top: 14px;
    width: 100%;
}
.dash-quick a {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 12px;
    background: #fafbfc;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #2d3748;
    text-decoration: none;
    transition: all .2s;
    min-width: 0;
    text-align: center;
}
.dash-quick a:hover {
    background: #0f5132;
    border-color: #0f5132;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15,81,50,.2);
}

/* ============================================================
   MOBILE TABLE — CARD STYLE (muncul di HP)
   ============================================================ */
.dash-table-mobile { display: none; }
.mtable-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 10px;
    background: #fafbfc;
    transition: all .2s;
}
.mtable-card:last-child { margin-bottom: 0; }
.mtable-card:hover {
    background: #fff;
    border-color: #cbd5e0;
}
.mtable-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.mtable-code {
    background: #f1f3f5;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 11.5px;
    color: #4a5568;
    font-family: 'Consolas', monospace;
    font-weight: 700;
}
.mtable-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 6px 0;
    font-size: 13px;
    border-bottom: 1px dashed #e9ecef;
}
.mtable-row:last-child { border-bottom: none; }
.mtable-label {
    font-size: 11.5px;
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    flex-shrink: 0;
}
.mtable-value {
    color: #2d3748;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
}

/* ============================================================
   RESPONSIVE — MULTI BREAKPOINT
   ============================================================ */
@media (max-width: 1200px) {
    .dash-stats { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
}

@media (max-width: 900px) {
    .dash-wrap { padding: 20px 20px 40px; }
    .dash-header-text h2 { font-size: 20px; }
    .dash-stat-info h3 { font-size: 28px; }
    .dash-quick { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 700px) {
    .dash-wrap { padding: 16px 16px 32px; }
    .dash-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .dash-header-text h2 { font-size: 19px; }
    .dash-header-text p { font-size: 12.5px; }
    .dash-user { align-self: flex-start; }
    .dash-stats { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .dash-stat { padding: 16px 18px; }
    .dash-stat-info h3 { font-size: 24px; }
    .dash-stat-info p { font-size: 11px; }
    .dash-card { padding: 16px 16px; border-radius: 10px; }
    .dash-card-head h4 { font-size: 14px; }
    .dash-table-wrap { display: none; }
    .dash-table-mobile { display: block; }
    .dash-quick { grid-template-columns: 1fr; gap: 10px; }
    .dash-quick a { padding: 13px 12px; font-size: 13px; }
}

@media (max-width: 480px) {
    .dash-wrap { padding: 14px 14px 28px; }
    .dash-header-text h2 { font-size: 17px; }
    .dash-header-text p { font-size: 12px; }
    .dash-user { padding: 5px 5px 5px 12px; font-size: 12px; }
    .dash-avatar { width: 30px; height: 30px; font-size: 12px; }
    .dash-stat { padding: 14px 16px; }
    .dash-stat-info h3 { font-size: 22px; }
    .dash-stat-info p { font-size: 10px; }
    .dash-card { padding: 14px 14px; }
    .dash-card-head h4 { font-size: 13.5px; }
    .dash-link { font-size: 12px; }
    .mtable-card { padding: 12px; }
    .mtable-row { font-size: 12.5px; padding: 5px 0; }
    .mtable-label { font-size: 11px; }
}

@media (max-width: 360px) {
    .dash-wrap { padding: 12px 12px 24px; }
    .dash-header-text h2 { font-size: 16px; }
    .dash-stat-info h3 { font-size: 20px; }
    .dash-stat-info p { font-size: 9px; }
    .dash-quick a { font-size: 12px; padding: 12px 10px; }
    .mtable-row { font-size: 12px; }
}
</style>

<!-- ================= KONTEN DASHBOARD ================= -->
<div class="dash-wrap">

    <!-- HEADER -->
    <div class="dash-header">
        <div class="dash-header-text">
            <h2>Dashboard Administrator</h2>
            <p>Selamat datang kembali, <b><?= htmlspecialchars($_SESSION['nama']) ?></b></p>
        </div>
        <div class="dash-user">
            <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
            <div class="dash-avatar">
                <!-- UPDATE: TAMPILKAN FOTO PROFIL -->
                <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto']) && file_exists('../assets/img/profil/' . $_SESSION['foto'])): ?>
                    <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" alt="Profil">
                <?php else: ?>
                    <?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- NOTIFIKASI KOMENTAR BARU -->
    <?php if ($notifKomentar > 0): ?>
        <div class="alert-komentar">
            <span>Ada <strong><?= $notifKomentar ?></strong> komentar baru dari pengguna yang belum dibaca!</span>
            <a href="komentar.php">Lihat Komentar &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- STATISTIK (TANPA IKON) -->
    <div class="dash-stats">
        <div class="dash-stat blue">
            <div class="dash-stat-info">
                <h3><?= number_format($totalBuku) ?></h3>
                <p>Total Buku</p>
            </div>
        </div>
        <div class="dash-stat green">
            <div class="dash-stat-info">
                <h3><?= number_format($totalAnggota) ?></h3>
                <p>Total Anggota</p>
            </div>
        </div>
        <div class="dash-stat orange">
            <div class="dash-stat-info">
                <h3><?= number_format($totalPinjam) ?></h3>
                <p>Sedang Dipinjam</p>
            </div>
        </div>
        <div class="dash-stat <?= $totalPending > 0 ? 'red' : '' ?>">
            <div class="dash-stat-info">
                <h3><?= number_format($totalPending) ?></h3>
                <p>Menunggu Approval</p>
            </div>
        </div>
        <?php if ($totalTelat > 0): ?>
        <div class="dash-stat red">
            <div class="dash-stat-info">
                <h3><?= number_format($totalTelat) ?></h3>
                <p>Terlambat</p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- TABEL PEMINJAMAN TERBARU -->
    <div class="dash-card">
        <div class="dash-card-head">
            <h4>Peminjaman Terbaru</h4>
            <a href="pinjaman.php" class="dash-link">Lihat Semua &rarr;</a>
        </div>

        <?php
        $sql = "SELECT t.*, u.nama, b.judul 
                FROM pinjaman t 
                JOIN users u ON t.user_id = u.id 
                JOIN buku b ON t.buku_id = b.id 
                ORDER BY t.id DESC LIMIT 8";
        $q = $conn->query($sql);
        $rows = [];
        if ($q && $q->num_rows > 0) {
            while ($r = $q->fetch_assoc()) $rows[] = $r;
        }
        ?>

        <?php if (empty($rows)): ?>
            <div class="dash-empty">
                Belum ada data peminjaman
            </div>
        <?php else: ?>

            <!-- ============ TAMPILAN DESKTOP (TABEL) ============ -->
            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <?php if ($hasKodePinjam): ?><th>Kode</th><?php endif; ?>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <?php if ($hasJatuhTempo): ?><th>Jatuh Tempo</th><?php endif; ?>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r): 
                        $badgeMap = [
                            'pending'      => 'warning',
                            'dipinjam'     => 'info',
                            'dikembalikan' => 'success',
                            'ditolak'      => 'danger',
                            'terlambat'    => 'danger',
                        ];
                        $badge = $badgeMap[$r['status']] ?? 'gray';

                        $isLate = false;
                        if ($hasJatuhTempo && $r['status'] === 'dipinjam' && !empty($r['tgl_jatuh_tempo'])) {
                            $isLate = strtotime($r['tgl_jatuh_tempo']) < time();
                        }
                    ?>
                        <tr>
                            <?php if ($hasKodePinjam): ?>
                                <td><span class="dash-code"><?= htmlspecialchars($r['kode_pinjam']) ?></span></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td><?= htmlspecialchars($r['judul']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                            <?php if ($hasJatuhTempo): ?>
                                <td><?= !empty($r['tgl_jatuh_tempo']) ? date('d/m/Y', strtotime($r['tgl_jatuh_tempo'])) : '-' ?></td>
                            <?php endif; ?>
                            <td>
                                <?php if ($isLate): ?>
                                    <span class="dash-badge danger">Terlambat</span>
                                <?php else: ?>
                                    <span class="dash-badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ============ TAMPILAN MOBILE (CARD) ============ -->
            <div class="dash-table-mobile">
                <?php foreach ($rows as $r): 
                    $badgeMap = [
                        'pending'      => 'warning',
                        'dipinjam'     => 'info',
                        'dikembalikan' => 'success',
                        'ditolak'      => 'danger',
                        'terlambat'    => 'danger',
                    ];
                    $badge = $badgeMap[$r['status']] ?? 'gray';

                    $isLate = false;
                    if ($hasJatuhTempo && $r['status'] === 'dipinjam' && !empty($r['tgl_jatuh_tempo'])) {
                        $isLate = strtotime($r['tgl_jatuh_tempo']) < time();
                    }
                ?>
                    <div class="mtable-card">
                        <div class="mtable-card-top">
                            <?php if ($hasKodePinjam): ?>
                                <span class="mtable-code"><?= htmlspecialchars($r['kode_pinjam']) ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <?php if ($isLate): ?>
                                <span class="dash-badge danger">Terlambat</span>
                            <?php else: ?>
                                <span class="dash-badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mtable-row">
                            <span class="mtable-label">Anggota</span>
                            <span class="mtable-value"><?= htmlspecialchars($r['nama']) ?></span>
                        </div>
                        <div class="mtable-row">
                            <span class="mtable-label">Buku</span>
                            <span class="mtable-value"><?= htmlspecialchars($r['judul']) ?></span>
                        </div>
                        <div class="mtable-row">
                            <span class="mtable-label">Tgl Pinjam</span>
                            <span class="mtable-value"><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></span>
                        </div>
                        <?php if ($hasJatuhTempo && !empty($r['tgl_jatuh_tempo'])): ?>
                        <div class="mtable-row">
                            <span class="mtable-label">Jatuh Tempo</span>
                            <span class="mtable-value"><?= date('d/m/Y', strtotime($r['tgl_jatuh_tempo'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <!-- AKSI CEPAT -->
    <div class="dash-card">
        <div class="dash-card-head">
            <h4>Aksi Cepat</h4>
        </div>
        <div class="dash-quick">
            <a href="buku.php">Tambah Buku</a>
            <a href="user.php">Tambah Anggota</a>
            <a href="pinjaman.php">Kelola pinjaman</a>
            <a href="kategori.php">Kelola Kategori</a>
        </div>
    </div>

</div>

<script>
    // Opsional: Refresh notifikasi komentar setiap 30 detik tanpa reload halaman
    setInterval(function() {
        fetch('get_notif.php')
            .then(response => response.text())
            .then(data => {
                let count = parseInt(data);
                let alertBox = document.querySelector('.alert-komentar');
                if (count > 0) {
                    if (!alertBox) {
                        // Jika alert belum ada di DOM tapi ada notif baru, reload halaman
                        location.reload(); 
                    }
                } else {
                    if (alertBox) {
                        alertBox.remove(); // Hilangkan alert jika sudah dibaca semua
                    }
                }
            })
            .catch(err => console.log('Gagal cek notif:', err));
    }, 30000); // 30 detik
</script>

<?php require_once '../includes/footer.php'; ?>
<?php require_once '../includes/welcome_screen.php'; ?>