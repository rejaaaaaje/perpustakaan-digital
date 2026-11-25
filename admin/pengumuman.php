<?php
$title = 'Kelola Pengumuman';
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'admin') { redirect('../index.php'); }

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Proses Simpan Pengumuman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $isi = $conn->real_escape_string(trim($_POST['isi']));
    $status = (int)$_POST['status_aktif'];
    
    if (!empty($isi)) {
        $conn->query("INSERT INTO pengumuman (isi, status_aktif) VALUES ('$isi', $status)");
        echo "<script>alert('Pengumuman berhasil disimpan!'); window.location='pengumuman.php';</script>";
    }
}

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
    .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; margin-bottom: 15px; }
    .btn { background: #0f5132; color: #fff; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
    .btn-hapus { background: #ef4444; padding: 6px 12px; font-size: 12px; text-decoration: none; color: white; border-radius: 4px; }
    .list-item { border-bottom: 1px dashed #e2e8f0; padding: 15px 0; display: flex; justify-content: space-between; align-items: center; }
    .status { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .status.aktif { background: #d1fae5; color: #065f46; }
    .status.nonaktif { background: #fee2e2; color: #991b1b; }
</style>

<div class="pengumuman-wrap">
    <h2 style="margin-bottom: 20px; color: #1e293b;">Kelola Pengumuman</h2>

    <div class="card">
        <h3 style="margin-bottom: 15px; font-size: 16px;">Buat Pengumuman Baru</h3>
        <form method="POST">
            <label style="font-size: 13px; font-weight: 600;">Isi Pengumuman:</label>
            <textarea name="isi" class="form-control" rows="3" placeholder="Contoh: Perpustakaan akan tutup pada tanggal 17 Agustus..." required></textarea>
            
            <label style="font-size: 13px; font-weight: 600;">Status Tampil:</label>
            <select name="status_aktif" class="form-control" style="width: 200px;">
                <option value="1">Tampilkan (Aktif)</option>
                <option value="0">Sembunyikan</option>
            </select>

            <button type="submit" name="simpan" class="btn">Simpan Pengumuman</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom: 15px; font-size: 16px;">Daftar Pengumuman</h3>
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
            echo "<p style='color:#94a3b8;'>Belum ada pengumuman.</p>";
        endif; 
        ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>