<?php
$title = 'Kelola pinjaman';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

date_default_timezone_set('Asia/Jakarta');

// ==========================================
// PROSES AKSI (SISTEM TETAP SAMA)
// ==========================================
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    if ($aksi === 'approve') {
        $t = $conn->query("SELECT * FROM pinjaman WHERE id=$id")->fetch_assoc();
        if ($t && $t['status'] === 'pending') {
            $conn->query("UPDATE pinjaman SET status='dipinjam', petugas_id=".$_SESSION['user_id']." WHERE id=$id");
            $conn->query("UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id=".$t['buku_id']);
            flash('success','Peminjaman disetujui!');
        }
    } elseif ($aksi === 'tolak') {
        $conn->query("UPDATE pinjaman SET status='ditolak' WHERE id=$id");
        flash('success','Peminjaman ditolak!');
    } elseif ($aksi === 'kembali') {
        $t = $conn->query("SELECT * FROM pinjaman WHERE id=$id")->fetch_assoc();
        if ($t && $t['status'] === 'dipinjam') {
            $tgl_kembali = date('Y-m-d');
            $denda = isset($_GET['denda']) ? (int)$_GET['denda'] : 0;

            if ($denda === 0 && strtotime($tgl_kembali) > strtotime($t['tgl_jatuh_tempo'])) {
                $hari = floor((strtotime($tgl_kembali) - strtotime($t['tgl_jatuh_tempo'])) / 86400);
                $denda = $hari * 1000;
            }

            $conn->query("UPDATE pinjaman SET status='dikembalikan', tgl_kembali='$tgl_kembali', denda=$denda WHERE id=$id");
            $conn->query("UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id=".$t['buku_id']);
            flash('success','Buku dikembalikan! Denda: Rp '.number_format($denda,0,',','.'));
        }
    } elseif ($aksi === 'lunasi') {
        $conn->query("UPDATE pinjaman SET denda = 0 WHERE id=$id");
        flash('success','Denda berhasil dilunasi!');
    }
    redirect('petugas/pinjaman.php');
}

// ==========================================
// QUERY DATA (TAMBAHKAN b.cover)
// ==========================================
$sql = "SELECT t.*, u.nama AS nama_user, b.judul, b.pengarang, b.cover 
        FROM pinjaman t 
        JOIN users u ON t.user_id=u.id 
        JOIN buku b ON t.buku_id=b.id 
        ORDER BY b.judul ASC, t.id DESC";
$result = $conn->query($sql);

// Grouping data khusus untuk tampilan List
$buku_grouped = [];
while ($row = $result->fetch_assoc()) {
    $buku_id = $row['buku_id'];
    if (!isset($buku_grouped[$buku_id])) {
        $buku_grouped[$buku_id] = [
            'judul' => $row['judul'],
            'pengarang' => $row['pengarang'],
            'cover' => $row['cover'], // Simpan data cover
            'pinjaman' => []
        ];
    }
    $buku_grouped[$buku_id]['pinjaman'][] = $row;
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    .page-wrapper { padding: 24px; max-width: 1000px; margin: 0 auto; }
    
    /* Header & Search Bar */
    .page-header { margin-bottom: 24px; }
    .page-header h2 { margin: 0; font-size: 24px; font-weight: 700; color: #1e293b; }
    .search-wrapper { position: relative; margin-bottom: 24px; max-width: 400px; }
    .search-wrapper input { width: 100%; padding: 12px 16px 12px 44px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; background: #fff; }
    .search-wrapper input:focus { border-color: #0f5132; box-shadow: 0 0 0 4px rgba(15,81,50,0.1); }
    .search-wrapper i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; }

    /* Card List Buku (Accordion) */
    .list-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; transition: 0.3s; overflow: hidden; }
    .list-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    
    .card-header-custom { padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; }
    .card-title-wrap { display: flex; flex-direction: row; align-items: center; gap: 16px; flex: 1; }
    
    /* TAMBAHAN CSS UNTUK GAMBAR COVER */
    .book-cover-thumb {
        width: 44px; 
        height: 60px; 
        border-radius: 6px; 
        background: #f1f5f9; 
        color: #94a3b8;
        display: flex; 
        align-items: center; 
        justify-content: center;
        font-size: 20px;
        overflow: hidden; 
        flex-shrink: 0;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .book-cover-thumb img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
    }

    .card-text-wrap { display: flex; flex-direction: column; gap: 4px; }
    .card-title { font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; margin: 0; }
    .card-author { font-size: 13px; color: #64748b; }
    
    .card-badges { display: flex; align-items: center; gap: 10px; }
    .badge-stat { font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 20px; background: #eff6ff; color: #3b82f6; }
    .badge-stat.aktif { background: #f0fdf4; color: #16a34a; }
    .chevron-icon { color: #94a3b8; font-size: 20px; transition: transform 0.3s; }

    .card-body-custom { display: none; background: #fff; padding: 0; border-top: 1px solid #e2e8f0; }
    .card-body-custom.show { display: block; animation: slideDown 0.3s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Tabel di dalam Card */
    .table-responsive { overflow-x: auto; }
    .table-custom { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .table-custom th { text-align: left; padding: 12px 24px; background: #f8fafc; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
    .table-custom td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .table-custom tr:last-child td { border-bottom: none; }
    .table-custom tbody tr:hover { background: #f8fafc; }

    .status-pill { display: inline-flex; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-dipinjam { background: #eff6ff; color: #2563eb; }
    .status-dikembalikan { background: #f0fdf4; color: #16a34a; }
    .status-ditolak { background: #fef2f2; color: #dc2626; }

    .btn-aksi { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; border: none; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.2s; }
    .btn-acc { background: #10b981; color: #fff; }
    .btn-tolak { background: #ef4444; color: #fff; }
    .btn-kembali { background: #3b82f6; color: #fff; }
    .btn-lunasi { background: #f59e0b; color: #fff; }

    /* Modal Pop Up (TETAP) */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: #fff; width: 90%; max-width: 400px; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out forwards; transform: translateY(20px); opacity: 0; }
    @keyframes slideUp { to { transform: translateY(0); opacity: 1; } }
    .modal-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .modal-icon { width: 45px; height: 45px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px; }
    .modal-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .modal-desc { font-size: 13.5px; color: #64748b; margin-bottom: 20px; line-height: 1.5; }
    .modal-input { width: 100%; padding: 12px 16px; font-size: 16px; font-weight: 700; color: #0f5132; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; outline: none; transition: 0.2s; margin-bottom: 24px; }
    .modal-input:focus { border-color: #0f5132; background: #fff; box-shadow: 0 0 0 4px rgba(15,81,50,0.1); }
    .modal-footer { display: flex; gap: 10px; justify-content: flex-end; }
    .modal-btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; }
    .btn-cancel { background: #f1f5f9; color: #475569; }
    .btn-save { background: #0f5132; color: #fff; }

    @media (max-width: 768px) {
        .page-wrapper { padding: 16px; }
        .card-header-custom { flex-direction: column; align-items: flex-start; gap: 12px; }
        .card-badges { width: 100%; justify-content: space-between; }
        .chevron-icon { position: absolute; right: 20px; top: 20px; }
    }
</style>

<div class="page-wrapper">
    
    <div class="page-header">
        <h2>Pinjaman Buku</h2>
    </div>

    <div class="search-wrapper">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" placeholder="Cari judul buku atau nama peminjam...">
    </div>

    <?php if ($m = flash('success')): ?>
        <div class="alert alert-success" style="background:#d1fae5; color:#065f46; border-left:4px solid #10b981; border-radius:10px; margin-bottom:24px; padding:14px 20px; font-weight:600;">
            <?= htmlspecialchars($m) ?>
        </div>
    <?php endif; ?>

    <!-- LIST BUKU (ACCORDION REALTIME) -->
    <div id="list-container">
        <?php if (count($buku_grouped) > 0): ?>
            <?php foreach ($buku_grouped as $buku_id => $data): 
                $total_pinjam = count($data['pinjaman']);
                $total_aktif = 0;
                foreach($data['pinjaman'] as $p) { if($p['status'] == 'dipinjam') $total_aktif++; }
                
                // Cek Cover
                $hasCover = !empty($data['cover']) && file_exists('../assets/img/cover/' . $data['cover']);
            ?>
            <div class="list-card book-item">
                
                <!-- HEADER (Klik untuk buka/tutup) -->
                <div class="card-header-custom" onclick="toggleCard(this)">
                    <div class="card-title-wrap">
                        
                        <!-- TAMBAHKAN GAMBAR COVER DI SINI -->
                        <div class="book-cover-thumb">
                            <?php if ($hasCover): ?>
                                <img src="../assets/img/cover/<?= htmlspecialchars($data['cover']) ?>" alt="Cover">
                            <?php else: ?>
                                <i class="bi bi-book"></i>
                            <?php endif; ?>
                        </div>

                        <div class="card-text-wrap">
                            <h4 class="card-title">
                                <?= htmlspecialchars($data['judul']) ?>
                            </h4>
                            <span class="card-author">Pengarang: <?= htmlspecialchars($data['pengarang']) ?></span>
                        </div>
                    </div>
                    
                    <div class="card-badges">
                        <span class="badge-stat"><?= $total_pinjam ?> Riwayat</span>
                        <span class="badge-stat aktif"><?= $total_aktif ?> DIPINJAM</span>
                        <i class="bi bi-chevron-down chevron-icon"></i>
                    </div>
                </div>

                <!-- BODY (Tabel Detail) -->
                <div class="card-body-custom">
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Peminjam</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Denda</th>
                                    <th style="text-align:right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['pinjaman'] as $t): 
                                    $terlambat = ($t['status'] === 'dipinjam' && strtotime($t['tgl_jatuh_tempo']) < strtotime(date('Y-m-d')));
                                ?>
                                <tr class="row-pinjaman">
                                    <td><span style="font-family:monospace; font-weight:600;"><?= htmlspecialchars($t['kode_pinjam']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($t['nama_user']) ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($t['tgl_pinjam'])) ?></td>
                                    <td style="<?= $terlambat ? 'color:#dc2626; font-weight:700;' : '' ?>">
                                        <?= date('d/m/Y', strtotime($t['tgl_jatuh_tempo'])) ?>
                                    </td>
                                    <td><span class="status-pill status-<?= $t['status'] ?>"><?= $t['status'] ?></span></td>
                                    <td style="font-weight:700; color: <?= $t['denda'] > 0 ? '#dc2626' : '#16a34a' ?>;">
                                        Rp <?= number_format($t['denda'],0,',','.') ?>
                                    </td>
                                    <td style="text-align:right; white-space:nowrap;">
                                        <?php if ($t['status'] === 'pending'): ?>
                                            <a href="?aksi=approve&id=<?= $t['id'] ?>" class="btn-aksi btn-acc" data-confirm="Setujui peminjaman buku ini?" data-confirm-title="Setujui" data-confirm-ok="Ya, Setujui" data-confirm-type="default">ACC</a>
                                            <a href="?aksi=tolak&id=<?= $t['id'] ?>" class="btn-aksi btn-tolak" data-confirm="Tolak peminjaman ini?" data-confirm-title="Tolak" data-confirm-ok="Ya, Tolak" data-confirm-type="danger">Tolak</a>
                                        <?php elseif ($t['status'] === 'dipinjam'): ?>
                                            <button type="button" onclick="bukaModalKembali(<?= $t['id'] ?>, '<?= htmlspecialchars($t['judul']) ?>')" class="btn-aksi btn-kembali">Kembalikan</button>
                                        <?php endif; ?>

                                        <?php if ($t['denda'] > 0): ?>
                                            <a href="?aksi=lunasi&id=<?= $t['id'] ?>" class="btn-aksi btn-lunasi" data-confirm="Anggota sudah membayar denda ini?" data-confirm-title="Lunasi Denda" data-confirm-ok="Ya, Lunasi" data-confirm-type="warn">Lunasi</a>
                                        <?php endif; ?>

                                        <?php if (in_array($t['status'], ['ditolak', 'dikembalikan']) && $t['denda'] == 0): ?>
                                            <span style="color:#94a3b8; font-size:12px; font-weight:600;">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 50px 20px; background: #f8fafc; border-radius: 14px; border: 2px dashed #cbd5e1; color: #64748b;">
                <h4 style="margin:0 0 5px 0; color:#334155;">Belum Ada Data Pinjaman</h4>
                <p style="margin:0; font-size:14px;">Belum ada riwayat peminjaman dari anggota.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL KEMBALIKAN (TETAP) -->
<div class="modal-overlay" id="modalDenda">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon"><i class="bi bi-arrow-return-left"></i></div>
            <div><h3 class="modal-title">Konfirmasi Pengembalian</h3></div>
        </div>
        <p class="modal-desc">
            Buku <strong id="modalJudulBuku" style="color:#0f5132;"></strong> akan dikembalikan. <br>Silakan masukkan nominal denda jika ada (isi 0 jika tidak ada).
        </p>
        <form id="formKembali" method="GET">
            <input type="hidden" name="aksi" value="kembali">
            <input type="hidden" name="id" id="modalIdTransaksi">
            <div style="position: relative;">
                <span style="position:absolute; left:16px; top:12px; font-weight:700; color:#64748b;">Rp</span>
                <input type="number" name="denda" id="modalInputDenda" class="modal-input" style="padding-left: 46px;" value="0" min="0" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn btn-cancel" onclick="tutupModal()">Batal</button>
                <button type="submit" class="modal-btn btn-save">Proses Kembalikan</button>
            </div>
        </form>
    </div>
</div>

<!-- GLOBAL CONFIRM POPUP (TETAP) -->
<div class="modal-overlay" id="confirmPopup">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon" id="confirmIcon"><i class="bi bi-question-lg"></i></div>
            <div><h3 class="modal-title" id="confirmTitle">Konfirmasi</h3></div>
        </div>
        <p class="modal-desc" id="confirmDesc">Apakah Anda yakin?</p>
        <div class="modal-footer">
            <button type="button" class="modal-btn btn-cancel" id="confirmCancel">Batal</button>
            <button type="button" class="modal-btn btn-save" id="confirmOk">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
    // ====== FITUR SEARCH REALTIME (TANPA REFRESH) ======
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let cards = document.querySelectorAll('.book-item');
        
        cards.forEach(function(card) {
            let title = card.querySelector('.card-title').textContent.toLowerCase();
            let author = card.querySelector('.card-author').textContent.toLowerCase();
            let hasMatch = title.includes(filter) || author.includes(filter);
            
            let peminjamMatch = false;
            card.querySelectorAll('.row-pinjaman td:nth-child(2)').forEach(td => {
                if(td.textContent.toLowerCase().includes(filter)) peminjamMatch = true;
            });

            if (hasMatch || peminjamMatch) {
                card.style.display = '';
                if (peminjamMatch && !hasMatch) {
                    card.querySelector('.card-body-custom').classList.add('show');
                }
            } else {
                card.style.display = 'none';
            }
        });
    });

    // ====== FITUR BUKA TUTUP ACCORDION (TANPA REFRESH) ======
    function toggleCard(header) {
        let body = header.nextElementSibling;
        let icon = header.querySelector('.chevron-icon');
        
        if (body.classList.contains('show')) {
            body.classList.remove('show');
            icon.style.transform = 'rotate(0deg)';
        } else {
            body.classList.add('show');
            icon.style.transform = 'rotate(180deg)';
        }
    }

    // ====== MODAL KEMBALIKAN ======
    function bukaModalKembali(id, judul) {
        document.getElementById('modalIdTransaksi').value = id;
        document.getElementById('modalJudulBuku').innerText = judul;
        document.getElementById('modalInputDenda').value = "0";
        document.getElementById('modalDenda').classList.add('show');
        setTimeout(() => {
            var inp = document.getElementById('modalInputDenda');
            inp.focus(); inp.select();
        }, 100);
    }
    function tutupModal() {
        document.getElementById('modalDenda').classList.remove('show');
    }
    document.getElementById('modalDenda').addEventListener('click', function(e) {
        if (e.target === this) tutupModal();
    });

    // ====== GLOBAL CONFIRM POPUP ======
    (function () {
        var popup   = document.getElementById('confirmPopup');
        var iconEl  = document.getElementById('confirmIcon');
        var titleEl = document.getElementById('confirmTitle');
        var descEl  = document.getElementById('confirmDesc');
        var okBtn   = document.getElementById('confirmOk');
        var cancelBtn = document.getElementById('confirmCancel');
        var pendingAction = null;

        function showPopup(opts) {
            var type = opts.type || 'default';
            var iconHtml = opts.icon || '<i class="bi bi-question-lg"></i>';
            iconEl.className = 'modal-icon' + (type === 'warn' ? ' warn' : type === 'danger' ? ' danger' : '');
            iconEl.innerHTML = iconHtml;
            titleEl.textContent = opts.title || 'Konfirmasi';
            descEl.textContent  = opts.message || 'Apakah Anda yakin?';
            okBtn.textContent   = opts.okText || 'Ya, Lanjutkan';
            okBtn.className     = 'modal-btn btn-save' + (type === 'warn' ? ' warn' : type === 'danger' ? ' danger' : '');
            pendingAction       = opts.onConfirm;
            popup.classList.add('show');
        }
        function hidePopup() {
            popup.classList.remove('show');
            pendingAction = null;
        }
        okBtn.addEventListener('click', function () {
            var action = pendingAction;
            hidePopup();
            if (typeof action === 'function') action();
        });
        cancelBtn.addEventListener('click', hidePopup);
        popup.addEventListener('click', function (e) { if (e.target === popup) hidePopup(); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && popup.classList.contains('show')) hidePopup();
        });

        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) return;
            e.preventDefault();
            var type    = el.getAttribute('data-confirm-type') || 'default';
            var title   = el.getAttribute('data-confirm-title') || 'Konfirmasi';
            var message = el.getAttribute('data-confirm-message') || el.getAttribute('data-confirm');
            var okText  = el.getAttribute('data-confirm-ok') || 'Ya, Lanjutkan';
            var icon    = el.getAttribute('data-confirm-icon');
            var href    = el.getAttribute('href');
            showPopup({
                type: type, title: title, message: message, okText: okText, icon: icon,
                onConfirm: function () {
                    if (href && href !== '#') window.location.href = href;
                }
            });
        });
    })();
</script>

<?php require_once '../includes/footer.php'; ?>