<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'petugas') { exit; }

$query = "SELECT k.*, u.nama, u.foto, b.judul FROM komentar k 
          JOIN users u ON k.user_id = u.id 
          JOIN buku b ON k.buku_id = b.id 
          ORDER BY k.id DESC";
$list = $conn->query($query);

if ($list->num_rows > 0):
    while ($k = $list->fetch_assoc()): 
        $hasFoto = !empty($k['foto']) && file_exists('../assets/img/profil/' . $k['foto']);
    ?>
        <div class="k-item" id="komentar-item-<?= $k['id'] ?>">
            <div class="k-avatar">
                <?php if ($hasFoto): ?>
                    <img src="../assets/img/profil/<?= htmlspecialchars($k['foto']) ?>" alt="Profil">
                <?php else: ?>
                    <?= strtoupper(substr($k['nama'], 0, 1)) ?>
                <?php endif; ?>
            </div>

            <div class="k-body">
                <div class="k-meta">
                    <div>
                        <strong><?= htmlspecialchars($k['nama']) ?></strong> 
                        pada buku <span class="k-buku"><?= htmlspecialchars($k['judul']) ?></span>
                    </div>
                    <span class="k-tgl"><?= date('d M Y, H:i', strtotime($k['created_at'])) ?></span>
                </div>
                
                <div class="k-isi"><?= nl2br(htmlspecialchars($k['isi_komentar'])) ?></div>

                <div id="balasan-container-<?= $k['id'] ?>">
                    <?php if (!empty($k['balasan'])): ?>
                        <div class="k-balasan">
                            <strong>Balasan Admin/Petugas:</strong>
                            <?= nl2br(htmlspecialchars($k['balasan'])) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="k-actions">
                    <button class="btn-action btn-balas" onclick="toggleBalas(<?= $k['id'] ?>)">Balas</button>
                    <button class="btn-action btn-hapus" onclick="hapusKomentar(<?= $k['id'] ?>)">Hapus</button>
                </div>

                <form action="" method="POST" class="form-balasan" id="form-balas-<?= $k['id'] ?>" onsubmit="kirimBalasan(event, <?= $k['id'] ?>)">
                    <input type="hidden" name="id_komentar" value="<?= $k['id'] ?>">
                    <input type="text" name="balasan" id="input-balasan-<?= $k['id'] ?>" placeholder="Tulis balasan untuk <?= htmlspecialchars($k['nama']) ?>..." required>
                    <button type="submit" name="kirim_balasan">Kirim</button>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="color:#a0aec0; font-size:14px; text-align:center; padding:20px;">Belum ada komentar dari pengguna.</p>
<?php endif; ?>