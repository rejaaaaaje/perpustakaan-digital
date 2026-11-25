<?php
// PENTING: Kita panggil header.php dulu agar variabel $conn (koneksi DB) terbaca.
// Pastikan folder 'includes' ada di sebelah folder 'admin'.
require_once '../includes/header.php'; 

// Ambil semua buku yang memiliki riwayat peminjaman
$buku_list = $conn->query("
    SELECT b.id, b.judul, b.pengarang, b.cover, COUNT(t.id) as total_pinjam,
           (SELECT COUNT(*) FROM pinjaman WHERE buku_id = b.id AND status = 'dipinjam') as sedang_dipinjam
    FROM buku b
    JOIN pinjaman t ON t.buku_id = b.id
    GROUP BY b.id
    ORDER BY b.judul ASC
");

if ($buku_list && $buku_list->num_rows > 0):
    while ($b = $buku_list->fetch_assoc()):
        // Cek apakah file cover ada
        $hasCover = !empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover']);
?>
<div class="list-item" data-search="<?= strtolower(htmlspecialchars($b['judul'] . ' ' . $b['pengarang'])) ?>">
    
    <!-- HEADER BUKU -->
    <div class="item-header" onclick="toggleBuku(this, <?= $b['id'] ?>)">
        <div class="header-info">
            
            <!-- TAMBAHKAN GAMBAR COVER DI SINI -->
            <div class="book-cover-thumb">
                <?php if ($hasCover): ?>
                    <img src="../assets/img/cover/<?= htmlspecialchars($b['cover']) ?>" alt="Cover">
                <?php else: ?>
                    <i class="bi bi-book"></i>
                <?php endif; ?>
            </div>

            <div>
                <h4 class="book-title"><?= htmlspecialchars($b['judul']) ?></h4>
                <div class="book-author">Pengarang: <?= htmlspecialchars($b['pengarang'] ?? '-') ?></div>
            </div>
        </div>
        <div style="display:flex; align-items:center;">
            <span class="badge-count"><?= $b['total_pinjam'] ?> Riwayat</span>
            <span class="badge-sm badge-dipinjam" style="margin-right:12px;"><?= $b['sedang_dipinjam'] ?> Dipinjam</span>
            <i class="bi bi-chevron-down chevron"></i>
        </div>
    </div>

    <!-- DETAIL -->
    <div class="item-detail">
        <div class="detail-search-container">
            <i class="bi bi-person-search detail-search-icon"></i>
            <input type="text" class="detail-search-input" placeholder="Cari nama user di buku ini..." onkeyup="filterUser(this)">
        </div>

        <table class="user-table">
            <thead>
                <tr>
                    <th>Nama Peminjam</th>
                    <th>Tanggal Pinjam</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $buku_id = $b['id'];
                $users = $conn->query("
                    SELECT t.*, u.nama AS nama_user 
                    FROM pinjaman t 
                    JOIN users u ON t.user_id = u.id 
                    WHERE t.buku_id = $buku_id 
                    ORDER BY t.id DESC
                ");
                
                if ($users && $users->num_rows > 0):
                    while ($u = $users->fetch_assoc()):
                        $badgeClass = 'badge-' . $u['status'];
                ?>
                <tr class="user-row" data-user="<?= strtolower(htmlspecialchars($u['nama_user'])) ?>">
                    <td><b><?= htmlspecialchars($u['nama_user']) ?></b></td>
                    <td><?= date('d/m/Y', strtotime($u['tgl_pinjam'])) ?></td>
                    <td><span class="badge-sm <?= $badgeClass ?>"><?= $u['status'] ?></span></td>
                    <td>
                        <div class="action-btns">
                            <?php if ($u['status'] === 'pending'): ?>
                                <a href="?aksi=approve&id=<?= $u['id'] ?>" class="btn-action btn-acc" 
                                   data-confirm="Setujui peminjaman buku ini?" data-confirm-title="Setujui"
                                   data-confirm-ok="Ya, Setujui" data-confirm-type="default">ACC</a>
                                <a href="?aksi=tolak&id=<?= $u['id'] ?>" class="btn-action btn-decline"
                                   data-confirm="Tolak peminjaman ini?" data-confirm-title="Tolak"
                                   data-confirm-ok="Ya, Tolak" data-confirm-type="danger">Tolak</a>
                            <?php endif; ?>

                            <?php if ($u['status'] === 'dipinjam'): ?>
                                <button type="button" class="btn-action btn-kembali"
                                   onclick="bukaModalKembali(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nama_user']) ?>')">Kembali</button>
                            <?php endif; ?>

                            <?php if ($u['denda'] > 0): ?>
                                <a href="?aksi=lunasi&id=<?= $u['id'] ?>" class="btn-action btn-lunasi"
                                   data-confirm="Lunasi denda ini?" data-confirm-title="Lunasi Denda"
                                   data-confirm-ok="Ya, Lunasi" data-confirm-type="warn">Lunasi</a>
                            <?php endif; ?>
                            
                            <?php if (in_array($u['status'], ['ditolak', 'dikembalikan']) && $u['denda'] == 0): ?>
                                <span style="font-size: 11px; color: #94a3b8;">Selesai</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr class="empty-row"><td colspan="4">Belum ada yang meminjam buku ini.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php 
    endwhile; 
else: 
?>
<div style="text-align: center; padding: 50px 20px; background: #f8fafc; border-radius: 14px; border: 2px dashed #cbd5e1; color: #64748b; width: 100%;">
    <h4 style="margin:0 0 5px 0; color:#334155;">Belum Ada Data</h4>
    <p style="margin:0; font-size:14px;">Belum ada buku yang memiliki riwayat peminjaman.</p>
</div>
<?php endif; ?>