<?php
// ==========================================
// 1. PANGGIL DATABASE & CEK LOGIN DULU
// ==========================================
require_once '../config/database.php';
cekLogin();

// ==========================================
// 2. FILTER TANGGAL & JENIS LAPORAN
// ==========================================
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');
$jenis       = $_GET['jenis'] ?? 'peminjaman';

$whereDate = "DATE(t.tgl_pinjam) BETWEEN '$tgl_mulai' AND '$tgl_selesai'";

// ==========================================
// 3. QUERY BERDASARKAN JENIS LAPORAN
// ==========================================
if ($jenis === 'peminjaman') {
    $sql = "SELECT t.*, u.nama, b.judul, p.nama AS nama_petugas 
            FROM pinjaman t 
            JOIN users u ON t.user_id = u.id 
            JOIN buku b ON t.buku_id = b.id 
            LEFT JOIN users p ON t.petugas_id = p.id
            WHERE $whereDate 
            ORDER BY t.tgl_pinjam DESC";
} elseif ($jenis === 'keterlambatan') {
    $sql = "SELECT t.*, u.nama, b.judul, 
            DATEDIFF(IFNULL(t.tgl_kembali, CURDATE()), t.tgl_jatuh_tempo) as hari_telat
            FROM pinjaman t 
            JOIN users u ON t.user_id = u.id 
            JOIN buku b ON t.buku_id = b.id 
            WHERE $whereDate 
            AND (t.status = 'dipinjam' OR t.status = 'dikembalikan')
            AND IFNULL(t.tgl_kembali, CURDATE()) > t.tgl_jatuh_tempo
            ORDER BY hari_telat DESC";
} else {
    $sql = "SELECT * FROM users WHERE role = 'anggota' ORDER BY nama ASC";
}

$result = $conn->query($sql);

// ==========================================
// 4. PROSES EXPORT KE EXCEL (DIPINDAH KE PALING ATAS)
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Bersihkan buffer output agar tidak ada HTML yang ikut terkirim
    if (ob_get_length()) ob_end_clean();

    // Header Excel
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=Laporan_Perpustakaan_".date('Ymd').".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<style>
            table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 12px; }
            th { background-color: #0f5132; color: #ffffff; padding: 10px; text-align: left; border: 1px solid #ccc; }
            td { padding: 8px; border: 1px solid #ccc; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .title { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 5px; }
            .subtitle { font-size: 14px; text-align: center; margin-bottom: 20px; }
          </style>';
    echo '</head>';
    echo '<body>';

    echo '<div class="title">Perpustakaan Digital SMK As-Syafi\'iyah Jakarta</div>';
    echo '<div class="subtitle">Laporan '.ucfirst($jenis).' | Periode: '.date('d/m/Y', strtotime($tgl_mulai)).' s/d '.date('d/m/Y', strtotime($tgl_selesai)).'</div>';

    echo '<table>';
    
    if ($jenis === 'peminjaman') {
        echo '<thead><tr>
                <th>No</th><th>Kode Pinjam</th><th>Anggota</th><th>Buku</th>
                <th>Tgl Pinjam</th><th>Jatuh Tempo</th><th>Status</th><th>Denda</th>
              </tr></thead><tbody>';
        
        if ($result->num_rows === 0) {
            echo '<tr><td colspan="8" style="text-align:center;">Tidak ada data</td></tr>';
        } else {
            $no = 1;
            while ($r = $result->fetch_assoc()) {
                echo '<tr>
                        <td>'.$no++.'</td>
                        <td>'.$r['kode_pinjam'].'</td>
                        <td>'.htmlspecialchars($r['nama']).'</td>
                        <td>'.htmlspecialchars($r['judul']).'</td>
                        <td>'.date('d/m/Y', strtotime($r['tgl_pinjam'])).'</td>
                        <td>'.date('d/m/Y', strtotime($r['tgl_jatuh_tempo'])).'</td>
                        <td>'.ucfirst($r['status']).'</td>
                        <td>Rp '.number_format($r['denda'] ?? 0, 0, ',', '.').'</td>
                      </tr>';
            }
        }
    } elseif ($jenis === 'keterlambatan') {
        echo '<thead><tr>
                <th>No</th><th>Kode Pinjam</th><th>Anggota</th><th>Buku</th>
                <th>Jatuh Tempo</th><th>Dikembalikan</th><th>Telat (Hari)</th><th>Denda</th>
              </tr></thead><tbody>';
        
        if ($result->num_rows === 0) {
            echo '<tr><td colspan="8" style="text-align:center;">Tidak ada data</td></tr>';
        } else {
            $no = 1;
            while ($r = $result->fetch_assoc()) {
                $tgl_kembali = $r['tgl_kembali'] ? date('d/m/Y', strtotime($r['tgl_kembali'])) : 'Belum Kembali';
                echo '<tr>
                        <td>'.$no++.'</td>
                        <td>'.$r['kode_pinjam'].'</td>
                        <td>'.htmlspecialchars($r['nama']).'</td>
                        <td>'.htmlspecialchars($r['judul']).'</td>
                        <td>'.date('d/m/Y', strtotime($r['tgl_jatuh_tempo'])).'</td>
                        <td>'.$tgl_kembali.'</td>
                        <td>'.$r['hari_telat'].'</td>
                        <td>Rp '.number_format($r['denda'] ?? 0, 0, ',', '.').'</td>
                      </tr>';
            }
        }
    } else {
        echo '<thead><tr>
                <th>No</th><th>Nama Anggota</th><th>Email</th><th>No. Telepon</th><th>Role</th>
              </tr></thead><tbody>';
        
        if ($result->num_rows === 0) {
            echo '<tr><td colspan="5" style="text-align:center;">Tidak ada data</td></tr>';
        } else {
            $no = 1;
            while ($r = $result->fetch_assoc()) {
                echo '<tr>
                        <td>'.$no++.'</td>
                        <td>'.htmlspecialchars($r['nama']).'</td>
                        <td>'.htmlspecialchars($r['email']).'</td>
                        <td>'.htmlspecialchars($r['no_telp'] ?? '-').'</td>
                        <td>'.$r['role'].'</td>
                      </tr>';
            }
        }
    }

    echo '</tbody></table>';
    echo '</body></html>';
    exit; // Hentikan eksekusi agar tampilan web tidak ikut muncul
}

// ==========================================
// 5. TAMPILAN WEB (DIMULAI SETELAH EXPORT EXCEL)
// ==========================================
$title = 'Laporan Perpustakaan';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// ==========================================
// STATISTIK RINGKAS
// ==========================================
$totalData = $result->num_rows;
$totalDenda = 0;
$totalTelat = 0;

if ($jenis === 'peminjaman' || $jenis === 'keterlambatan') {
    $sumDenda = $conn->query("SELECT SUM(denda) as total FROM pinjaman t WHERE $whereDate")->fetch_assoc();
    $totalDenda = $sumDenda['total'] ?? 0;
    
    if ($jenis === 'keterlambatan') {
        $totalTelat = $totalData;
    }
}
?>

<!-- (SISA KODE HTML SEPERTI SEBELUMNYA) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
/* CSS UMUM */
.topbar .user-info { display: flex; align-items: center; gap: 10px; background: #fff; padding: 5px 5px 5px 14px; border-radius: 50px; border: 1px solid #e9ecef; font-size: 13px; font-weight: 500; color: #4a5568; text-decoration: none; flex-shrink: 0; }
.topbar .user-info span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
.topbar .avatar { width: 34px; height: 34px; border-radius: 50%; background: #0f5132; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; flex-shrink: 0; object-fit: cover; }

.filter-card { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
.form-group label { display: block; font-size: 12.5px; font-weight: 700; color: #4a5568; margin-bottom: 6px; }
.form-control { width: 100%; padding: 10px 14px; font-size: 13.5px; font-family: inherit; color: #1a202c; background: #fafbfc; border: 1.5px solid #e2e8f0; border-radius: 8px; outline: none; transition: 0.2s; }
.form-control:focus { border-color: #0f5132; background: #fff; box-shadow: 0 0 0 3px rgba(15, 81, 50, 0.08); }
.btn-filter { background: #0f5132; color: #fff; border: none; padding: 11px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; width: 100%; display: flex; justify-content: center; gap: 8px; }
.btn-filter:hover { background: #146c43; }

.rep-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.rep-box { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; border-left: 4px solid #0f5132; }
.rep-box-title { font-size: 12px; color: #718096; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
.rep-box-val { font-size: 24px; font-weight: 800; color: #1a202c; line-height: 1; }
.rep-box.red { border-left-color: #e53e3e; }
.rep-box.orange { border-left-color: #dd6b20; }

.table-card { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; overflow: hidden; }
.table-header { padding: 18px 22px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.table-header h3 { margin: 0; font-size: 16px; font-weight: 700; color: #1a202c; display: flex; align-items: center; gap: 8px; }

/* TOMBOL EXPORT EXCEL */
.btn-excel { background: #0f5132; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; text-decoration: none; }
.btn-excel:hover { background: #146c43; transform: translateY(-1px); }

.table-wrap { overflow-x: auto; }
table.data { width: 100%; border-collapse: collapse; font-size: 13.5px; }
table.data th { text-align: left; padding: 14px 18px; background: #fafbfc; font-size: 11px; font-weight: 700; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e9ecef; white-space: nowrap; }
table.data td { padding: 14px 18px; border-bottom: 1px solid #f1f3f5; color: #2d3748; vertical-align: middle; }
table.data tr:hover { background: #fafbfc; }
.badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: capitalize; }
.badge-success { background: #f0fff4; color: #2f855a; }
.badge-warning { background: #fffaf0; color: #c05621; }
.badge-danger { background: #fff5f5; color: #c53030; }
.text-danger { color: #e53e3e; font-weight: 700; }
</style>

<div class="main">
    <div class="topbar">
        <h2>Laporan Perpustakaan</h2>
        <a href="<?= base_url('pengaturan.php') ?>" class="user-info" title="Pengaturan Akun">
            <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
            <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
                <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" class="avatar" alt="Profil">
            <?php else: ?>
                <div class="avatar"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
            <?php endif; ?>
        </a>
    </div>

    <div class="content">
        
        <!-- FILTER -->
        <form method="get" class="filter-card">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Jenis Laporan</label>
                    <select name="jenis" class="form-control" onchange="this.form.submit()">
                        <option value="peminjaman" <?= $jenis == 'peminjaman' ? 'selected' : '' ?>>Laporan Peminjaman</option>
                        <option value="keterlambatan" <?= $jenis == 'keterlambatan' ? 'selected' : '' ?>>Laporan Keterlambatan & Denda</option>
                        <option value="anggota" <?= $jenis == 'anggota' ? 'selected' : '' ?>>Laporan Data Anggota</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dari Tanggal (Pinjam)</label>
                    <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
                </div>
                <div class="form-group">
                    <label>Sampai Tanggal (Pinjam)</label>
                    <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-filter"><i class="bi bi-filter"></i> Tampilkan Data</button>
                </div>
            </div>
        </form>

        <!-- STATISTIK -->
        <div class="rep-stats">
            <div class="rep-box">
                <div class="rep-box-title">Total Data Ditemukan</div>
                <div class="rep-box-val"><?= number_format($totalData) ?> <span style="font-size:14px;font-weight:600;color:#718096;">data</span></div>
            </div>
            <?php if ($jenis !== 'anggota'): ?>
            <div class="rep-box <?= $totalDenda > 0 ? 'red' : '' ?>">
                <div class="rep-box-title">Total Denda Terkumpul</div>
                <div class="rep-box-val">Rp <?= number_format($totalDenda, 0, ',', '.') ?></div>
            </div>
            <?php endif; ?>
            <?php if ($jenis === 'keterlambatan'): ?>
            <div class="rep-box orange">
                <div class="rep-box-title">Kasus Keterlambatan</div>
                <div class="rep-box-val"><?= number_format($totalTelat) ?> <span style="font-size:14px;font-weight:600;color:#718096;">kali</span></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- TABEL LAPORAN -->
        <div class="table-card">
            <div class="table-header">
                <h3><i class="bi bi-file-earmark-bar-graph"></i> Data Laporan</h3>
                
                <!-- HANYA TOMBOL EXPORT EXCEL -->
                <a href="?export=excel&jenis=<?= $jenis ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>" class="btn-excel">
                    <i class="bi bi-file-earmark-excel"></i> Export ke Excel
                </a>
            </div>

            <div class="table-wrap">
                <table class="data">
                    <?php if ($jenis === 'peminjaman'): ?>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalData === 0): ?>
                                <tr><td colspan="8" style="text-align:center;padding:40px;">Tidak ada data peminjaman pada periode ini.</td></tr>
                            <?php else: $no=1; while($r = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span style="font-family:monospace;font-weight:700;color:#0f5132;"><?= $r['kode_pinjam'] ?></span></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td><?= htmlspecialchars($r['judul']) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['tgl_pinjam'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['tgl_jatuh_tempo'])) ?></td>
                                <td><span class="badge badge-<?= $r['status']=='dipinjam'?'warning':($r['status']=='dikembalikan'?'success':'danger') ?>"><?= $r['status'] ?></span></td>
                                <td>Rp <?= number_format($r['denda'] ?? 0, 0, ',', '.') ?></td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>

                    <?php elseif ($jenis === 'keterlambatan'): ?>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Jatuh Tempo</th>
                                <th>Dikembalikan</th>
                                <th>Telat (Hari)</th>
                                <th>Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalData === 0): ?>
                                <tr><td colspan="8" style="text-align:center;padding:40px;">Alhamdulillah, tidak ada keterlambatan pada periode ini.</td></tr>
                            <?php else: $no=1; while($r = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><span style="font-family:monospace;font-weight:700;color:#0f5132;"><?= $r['kode_pinjam'] ?></span></td>
                                <td><?= htmlspecialchars($r['nama']) ?></td>
                                <td><?= htmlspecialchars($r['judul']) ?></td>
                                <td><?= date('d/m/Y', strtotime($r['tgl_jatuh_tempo'])) ?></td>
                                <td><?= $r['tgl_kembali'] ? date('d/m/Y', strtotime($r['tgl_kembali'])) : '<span style="color:#e53e3e;font-weight:bold;">Belum Kembali</span>' ?></td>
                                <td class="text-danger"><?= $r['hari_telat'] ?> hari</td>
                                <td class="text-danger">Rp <?= number_format($r['denda'] ?? 0, 0, ',', '.') ?></td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>

                    <?php else: ?>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Anggota</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($totalData === 0): ?>
                                <tr><td colspan="5" style="text-align:center;padding:40px;">Tidak ada data anggota.</td></tr>
                            <?php else: $no=1; while($r = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><b><?= htmlspecialchars($r['nama']) ?></b></td>
                                <td><?= htmlspecialchars($r['email']) ?></td>
                                <td><?= htmlspecialchars($r['no_telp'] ?? '-') ?></td>
                                <td><span class="badge badge-success"><?= $r['role'] ?></span></td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>