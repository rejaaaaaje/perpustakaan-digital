<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'petugas') { exit; }

$query_buku = $conn->query("
    SELECT b.id, b.judul, b.pengarang, b.cover, COUNT(k.id) as total_ulasan 
    FROM buku b 
    JOIN komentar k ON b.id = k.buku_id 
    GROUP BY b.id 
    ORDER BY b.judul ASC
");

if ($query_buku->num_rows > 0):
    while ($b = $query_buku->fetch_assoc()): 
        $hasCover = !empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover']);
        $q_komentar = $conn->query("SELECT k.*, u.nama, u.foto FROM komentar k JOIN users u ON k.user_id = u.id WHERE k.buku_id = {$b['id']} ORDER BY k.id DESC");
?>
    <div class="buku-dropdown" data-judul="<?= strtolower(htmlspecialchars($b['judul'])) ?>">
        <div class="buku-header" onclick="toggleDropdown(this)">
            <div class="buku-info">
                <div class="buku-cover-icon">
                    <?php if ($hasCover): ?>
                        <img src="../assets/img/cover/<?= htmlspecialchars($b['cover']) ?>" alt="Cover">
                    <?php else: ?>
                        <i class="bi bi-book"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h4 class="buku-title"><?= htmlspecialchars($b['judul']) ?></h4>
                    <p class="buku-author">Pengarang: <?= htmlspecialchars($b['pengarang'] ?: '-') ?></p>
                </div>
            </div>
            
            <div class="buku-stats">
                <span class="badge-count"><i class="bi bi-chat-left-text"></i> <?= $b['total_ulasan'] ?> Ulasan</span>
                <div class="dropdown-arrow"><i class="bi bi-chevron-down"></i></div>
            </div>
        </div>

        <div class="buku-body">
            <?php if ($q_komentar->num_rows > 0): ?>
                <?php while ($k = $q_komentar->fetch_assoc()): 
                    $hasFoto = !empty($k['foto']) && file_exists('../assets/img/profil/' . $k['foto']);
                    $rating = (int)($k['rating'] ?? 5);
                ?>
                    <div class="review-item">
                        <div class="review-top">
                            <div class="review-user">
                                <div class="review-avatar">
                                    <?php if ($hasFoto): ?>
                                        <img src="../assets/img/profil/<?= htmlspecialchars($k['foto']) ?>" alt="Profil">
                                    <?php else: ?>
                                        <?= strtoupper(substr($k['nama'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h5 class="review-name"><?= htmlspecialchars($k['nama']) ?></h5>
                                    <p class="review-date"><?= date('d M Y, H:i', strtotime($k['created_at'])) ?></p>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="bi <?= $i <= $rating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="review-text">
                            "<?= nl2br(htmlspecialchars($k['isi_komentar'])) ?>"
                        </div>

                        <?php if (!empty($k['balasan'])): ?>
                            <div class="review-reply">
                                <strong>Balasan Anda:</strong>
                                <?= nl2br(htmlspecialchars($k['balasan'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="review-actions">
                            <button type="button" class="btn-sm btn-sm-balas" onclick="toggleBalas(<?= $k['id'] ?>)">
                                <i class="bi bi-reply-fill"></i> Balas
                            </button>
                            <button type="button" class="btn-sm btn-sm-hapus" onclick="hapusKomentar(<?= $k['id'] ?>)">
                                <i class="bi bi-trash-fill"></i> Hapus
                            </button>
                        </div>

                        <form id="form-balas-<?= $k['id'] ?>" class="form-balasan" onsubmit="kirimBalasan(event, <?= $k['id'] ?>)">
                            <input type="text" name="balasan" placeholder="Ketik balasan Anda di sini..." required value="<?= htmlspecialchars($k['balasan'] ?? '') ?>">
                            <button type="submit"><i class="bi bi-send-fill"></i> Kirim</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #94a3b8; font-size: 13px;">Belum ada ulasan.</p>
            <?php endif; ?>
        </div>
    </div>
<?php 
    endwhile; 
else: 
?>
    <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1;">
        <i class="bi bi-chat-square-text" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
        <h3 style="margin: 0 0 8px 0; font-size: 18px; color: #1e293b;">Belum Ada Ulasan</h3>
        <p style="margin: 0; font-size: 14px; color: #64748b;">Belum ada anggota yang memberikan ulasan pada buku manapun.</p>
    </div>
<?php endif; ?>