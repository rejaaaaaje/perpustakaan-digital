<?php
$title = 'Kelola Pengumuman';
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'petugas') { redirect('../index.php'); }

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM pengumuman WHERE id = $id");
    echo "<script>alert('Pengumuman dihapus!'); window.location='pengumuman.php';</script>";
}
?>

<style>
    .pengumuman-wrap { padding: 24px 28px 40px; background: #f7f8fa; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
    .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .btn-hapus { background: #ef4444; padding: 6px 12px; font-size: 12px; text-decoration: none; color: white; border-radius: 4px; }
    .list-item { border-bottom: 1px dashed #e2e8f0; padding: 15px 0; display: flex; justify-content: space-between; align-items: center; }
    .status { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .status.aktif { background: #d1fae5; color: #065f46; }
    .status.nonaktif { background: #fee2e2; color: #991b1b; }
</style>

<div class="pengumuman-wrap">
    <h2 style="margin-bottom: 20px; color: #1e293b;">Daftar Pengumuman</h2>

    <div class="card">
        <?php
        $list = $conn->query("SELECT * FROM pengumuman ORDER BY id DESC");
        if ($list->num_rows > 0):
            while ($p = $list->fetch_assoc()):
        ?>
            <div class="list-item">
                <div style="flex: 1; padding-right: 20px;">
                    <p style="font-size: 14px; color: #333; margin-bottom: 5px;"><?= htmlspecialchars($p['isi']) ?></p>
                    <span style="font-size: 11px; color: #94a3b8;"><?= date('d M Y H:i', strtotime($p['created_at'])) ?></span>
                </div>
                <div style="text-align: right; min-width: 150px;">
                    <span class="status <?= $p['status_aktif'] ? 'aktif' : 'nonaktif' ?>">
                        <?= $p['status_aktif'] ? 'Tampil' : 'Sembunyi' ?>
                    </span>
                    <br><br>
                    <a href="?hapus=<?= $p['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a>
                </div>
            </div>
        <?php 
            endwhile; 
        else:
            echo "<p style='color:#94a3b8;'>Belum ada pengumuman dari Admin.</p>";
        endif; 
        ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>