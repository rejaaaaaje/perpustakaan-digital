<?php
require_once '../includes/header.php'; // Memuat $conn

$cari = trim($_GET['cari'] ?? '');
$sql = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id";
if ($cari) {
    $kw = $conn->real_escape_string($cari);
    $sql .= " WHERE b.judul LIKE '%$kw%' OR b.pengarang LIKE '%$kw%' OR b.isbn LIKE '%$kw%'";
}
$sql .= " ORDER BY b.id DESC";
$list = $conn->query($sql);

if ($list->num_rows === 0): ?>
    <tr><td colspan="6"><div style="text-align:center;padding:40px;">Buku Tidak Ditemukan</div></td></tr>
<?php else: $no=1; while ($b = $list->fetch_assoc()): ?>
    <tr>
        <td style="text-align:center;">
            <input type="checkbox" name="pilih_buku[]" value="<?= $b['id'] ?>" class="cek-box cek-item">
        </td>
        <td style="color:#a0aec0;font-weight:700;"><?= $no++ ?></td>
        <td>
            <div class="book-row">
                <div class="book-thumb">
                    <?php if (!empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover'])): ?>
                        <img src="../assets/img/cover/<?= htmlspecialchars($b['cover']) ?>" alt="Cover">
                    <?php else: ?>
                        <i class="bi bi-book"></i>
                    <?php endif; ?>
                </div>
                <div class="book-info">
                    <div class="judul"><?= htmlspecialchars($b['judul']) ?></div>
                    <div class="meta">
                        <?php if (!empty($b['file_pdf'])): ?>
                            <span style="color:#0f5132; font-weight:700;"><i class="bi bi-file-earmark-pdf-fill"></i> PDF Tersedia</span>
                        <?php else: ?>
                            <span style="color:#e53e3e;"><i class="bi bi-x-circle"></i> PDF Belum Ada</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </td>
        <td><span style="background:#d1fae5;color:#0f5132;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:700;"><?= htmlspecialchars($b['nama_kategori'] ?? 'Umum') ?></span></td>
        <td>
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <span style="background:#f0fff4; color:#2f855a; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; display:inline-block; text-align:center; white-space:nowrap;">
                    Tersedia: <?= $b['stok_tersedia'] ?>
                </span>
                <span style="background:#f1f5f9; color:#64748b; padding:4px 8px; border-radius:6px; font-size:11px; font-weight:700; display:inline-block; text-align:center; white-space:nowrap;">
                    Total: <?= $b['stok'] ?>
                </span>
            </div>
        </td>
        <td>
            <div class="action-cell">
                <button type="button" class="btn btn-stok" onclick="bukaModalStok(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['judul'])) ?>', <?= $b['stok'] ?>)"><i class="bi bi-plus-circle"></i> Stok</button>
                <a href="?edit=<?= $b['id'] ?>" class="btn btn-edit"><i class="bi bi-pencil"></i> Edit</a>
                <a href="?hapus=<?= $b['id'] ?>" class="btn btn-delete" onclick="return confirm('Hapus buku ini beserta file PDF-nya?')"><i class="bi bi-trash"></i> Hapus</a>
            </div>
        </td>
    </tr>
<?php endwhile; endif; ?>