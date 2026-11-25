<?php
require_once '../includes/header.php';

$cari = trim($_GET['cari'] ?? '');
$sql = "SELECT b.*, k.nama_kategori FROM buku b LEFT JOIN kategori k ON b.kategori_id=k.id";
if ($cari) {
    $kw = $conn->real_escape_string($cari);
    $sql .= " WHERE b.judul LIKE '%$kw%' OR b.pengarang LIKE '%$kw%' OR b.isbn LIKE '%$kw%'";
}
$sql .= " ORDER BY b.id DESC";
$list = $conn->query($sql);
?>

<?php if ($list->num_rows === 0): ?>
    <div style="text-align:center; padding:40px; color:#64748b; font-weight:600;">
        Buku Tidak Ditemukan
    </div>
<?php else: ?>
    <?php while ($b = $list->fetch_assoc()): ?>
        <div class="buku-card-item">
            <!-- Checkbox -->
            <input type="checkbox" name="pilih_buku[]" value="<?= $b['id'] ?>" class="cek-box cek-item">
            
            <!-- Thumbnail (Kiri) -->
            <div class="buku-thumb">
                <?php 
                // Cek apakah cover berupa URL dari internet (Google Books) ATAU file lokal
                if (!empty($b['cover'])):
                    if (filter_var($b['cover'], FILTER_VALIDATE_URL)): // Jika Link Google Books
                ?>
                    <img src="<?= htmlspecialchars($b['cover']) ?>" alt="Cover">
                <?php 
                    elseif (file_exists('../assets/img/cover/' . $b['cover'])): // Jika File Lokal
                ?>
                    <img src="../assets/img/cover/<?= htmlspecialchars($b['cover']) ?>" alt="Cover">
                <?php 
                    else: 
                ?>
                    <i class="bi bi-book"></i>
                <?php 
                    endif;
                else: 
                ?>
                    <i class="bi bi-book"></i>
                <?php endif; ?>
            </div>
            
            <!-- Detail Info (Tengah) -->
            <div class="buku-details">
                <div class="judul"><?= htmlspecialchars($b['judul']) ?></div>
                <div class="meta">
                    <span><?= htmlspecialchars($b['pengarang']) ?></span>
                    <span>•</span>
                    <span><?= htmlspecialchars($b['nama_kategori'] ?? 'Umum') ?></span>
                </div>
                <div class="meta" style="margin-top:4px; font-weight:600;">
                    <?php if (!empty($b['file_pdf'])): ?>
                        <span style="color:#16a34a;"><i class="bi bi-file-pdf"></i> PDF</span>
                    <?php else: ?>
                        <span style="color:#dc2626;"><i class="bi bi-x-circle"></i> No PDF</span>
                    <?php endif; ?>
                    <span style="color:#0f5132;"><i class="bi bi-box"></i> Stok: <?= $b['stok_tersedia'] ?>/<?= $b['stok'] ?></span>
                </div>
            </div>

            <!-- Tombol Aksi (Kanan) -->
            <div class="buku-actions">
                <button type="button" class="btn btn-stok" onclick="bukaModalStok(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['judul'])) ?>', <?= $b['stok'] ?>)">
                    <i class="bi bi-plus-circle"></i> Stok
                </button>
                <a href="?edit=<?= $b['id'] ?>" class="btn btn-edit">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="?hapus=<?= $b['id'] ?>" class="btn btn-delete" onclick="return confirm('Hapus buku ini?')">
                    <i class="bi bi-trash"></i> Hapus
                </a>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>