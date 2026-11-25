<?php
$title = 'Daftar Komentar';
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'petugas') { redirect('index.php'); }

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// PROSES HAPUS & BALAS
if (isset($_GET['hapus_ajax'])) {
    $id_hapus = (int)$_GET['hapus_ajax'];
    $conn->query("DELETE FROM komentar WHERE id = $id_hapus");
    echo "success"; exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_balasan_ajax'])) {
    $id_komentar = (int)$_POST['id_komentar'];
    $balasan = $conn->real_escape_string(trim($_POST['balasan']));
    if (!empty($balasan)) {
        $conn->query("UPDATE komentar SET balasan = '$balasan' WHERE id = $id_komentar");
        echo "success";
    } else { echo "error"; }
    exit;
}

$conn->query("UPDATE komentar SET status_baca = 1 WHERE status_baca = 0");

// ==========================================
// AMBIL DATA BUKU YANG MEMILIKI ULASAN
// ==========================================
$query_buku = $conn->query("
    SELECT b.id, b.judul, b.pengarang, b.cover, COUNT(k.id) as total_ulasan 
    FROM buku b 
    JOIN komentar k ON b.id = k.buku_id 
    GROUP BY b.id 
    ORDER BY b.judul ASC
");
?>

<style>
    /* Layout Utama */
    .komentar-wrap { padding: 32px 24px 80px; background: #f8fafc; min-height: 100vh; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    
    /* Header Halaman */
    .page-header { 
        display: flex; justify-content: space-between; align-items: center; 
        max-width: 900px; margin: 0 auto 30px; flex-wrap: wrap; gap: 16px;
    }
    .page-header h2 { margin: 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .page-header p { margin: 6px 0 0 0; font-size: 15px; color: #64748b; }

    /* Search Box */
    .search-wrap { max-width: 900px; margin: 0 auto 20px; position: relative; }
    .search-wrap input {
        width: 100%; padding: 14px 20px 14px 46px; border-radius: 12px;
        border: 1px solid #e2e8f0; font-size: 14px; outline: none; background: #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s;
    }
    .search-wrap input:focus { border-color: #0f5132; box-shadow: 0 0 0 4px rgba(15,81,50,0.08); }
    .search-wrap i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; }

    /* =========================================
       DESAIN DROPDOWN BUKU (AKORDEON)
       ========================================= */
    .buku-list { max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

    .buku-dropdown {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
        transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .buku-dropdown.active { border-color: #0f5132; box-shadow: 0 4px 15px rgba(15,81,50,0.08); }

    /* Header Buku (Yang selalu terlihat) */
    .buku-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 20px; cursor: pointer; background: #fff; transition: background 0.2s;
    }
    .buku-header:hover { background: #f8fafc; }
    
    .buku-info { display: flex; align-items: center; gap: 16px; }
    .buku-cover-icon {
        width: 42px; height: 56px; border-radius: 6px; background: #f1f5f9; color: #0f5132;
        display: flex; align-items: center; justify-content: center; font-size: 20px;
        overflow: hidden; flex-shrink: 0; border: 1px solid #e2e8f0;
    }
    .buku-cover-icon img { width: 100%; height: 100%; object-fit: cover; }
    
    .buku-title { font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0; }
    .buku-author { font-size: 13px; color: #64748b; margin: 0; }

    .buku-stats { display: flex; align-items: center; gap: 16px; }
    .badge-count { 
        background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 12px; 
        padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 6px;
    }
    .dropdown-arrow {
        width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        color: #64748b; transition: transform 0.3s ease;
    }
    .buku-dropdown.active .dropdown-arrow { transform: rotate(180deg); background: #e2e8f0; }

    /* Body Buku (Isi Ulasan) */
    .buku-body {
        display: none; padding: 20px; border-top: 1px dashed #e2e8f0; background: #fafbfc;
    }
    .buku-dropdown.active .buku-body { display: block; animation: slideDown 0.3s ease; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Card Ulasan di dalam Dropdown */
    .review-item {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;
        margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .review-item:last-child { margin-bottom: 0; }
    
    .review-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .review-user { display: flex; align-items: center; gap: 12px; }
    .review-avatar { 
        width: 40px; height: 40px; border-radius: 50%; background: #0f5132; color: #fff; 
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; 
        overflow: hidden;
    }
    .review-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .review-name { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
    .review-date { font-size: 12px; color: #94a3b8; margin: 0; }
    
    .review-rating { display: flex; gap: 2px; color: #f59e0b; font-size: 12px; background: #fffbeb; padding: 4px 8px; border-radius: 6px; }
    .review-rating .bi-star { color: #cbd5e1; }

    .review-text { font-size: 14px; color: #334155; line-height: 1.6; margin-bottom: 12px; }
    
    .review-reply { 
        background: #f0fdf4; border-left: 3px solid #10b981; padding: 10px 14px; 
        border-radius: 0 6px 6px 0; font-size: 13px; color: #475569; margin-bottom: 12px; 
    }
    .review-reply strong { display: block; margin-bottom: 2px; font-size: 11px; text-transform: uppercase; color: #047857; }

    .review-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
    .btn-sm { 
        padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; 
        display: inline-flex; align-items: center; gap: 4px; text-decoration: none; border: none; cursor: pointer; transition: 0.2s;
    }
    .btn-sm-balas { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .btn-sm-balas:hover { background: #dbeafe; }
    .btn-sm-hapus { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .btn-sm-hapus:hover { background: #fee2e2; }

    /* Form Balasan Tersembunyi */
    .form-balasan { display: none; margin-top: 12px; gap: 8px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .form-balasan.active { display: flex; animation: slideDown 0.3s ease; }
    .form-balasan input { 
        flex: 1; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; 
    }
    .form-balasan input:focus { border-color: #0f5132; }
    .form-balasan button { 
        padding: 10px 16px; background: #0f5132; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; 
    }

    /* Toast Notification */
    .toast-success { 
        position: fixed; top: 24px; right: 24px; background: #10b981; color: #fff; 
        padding: 14px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; z-index: 9999; 
        transform: translateY(-100px); opacity: 0; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        box-shadow: 0 10px 25px rgba(16,185,129,0.4); display: flex; align-items: center; gap: 10px;
    }
    .toast-success.show { transform: translateY(0); opacity: 1; }

    /* Responsive */
    @media (max-width: 768px) {
        .komentar-wrap { padding: 20px 16px 80px; }
        .page-header h2 { font-size: 22px; }
        .buku-header { flex-direction: column; align-items: flex-start; gap: 12px; }
        .buku-stats { width: 100%; justify-content: space-between; }
        .buku-body { padding: 16px; }
        .review-item { padding: 12px; }
        .form-balasan.active { flex-direction: column; }
        .form-balasan button { width: 100%; }
    }
</style>

<div class="komentar-wrap">
    <div class="page-header">
        <div>
            <h2>Ulasan Buku</h2>
            <p>Lihat dan balas ulasan pembaca berdasarkan judul buku.</p>
        </div>
    </div>
    
    <!-- Search Bar (Bisa pakai JS untuk filter buku) -->
    <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="searchBuku" placeholder="Cari judul buku yang diulas..." onkeyup="filterBuku()">
    </div>

    <!-- WADAH DROPDOWN BUKU -->
    <div class="buku-list" id="bukuList">
        <?php if ($query_buku->num_rows > 0): ?>
            <?php while ($b = $query_buku->fetch_assoc()): 
                $hasCover = !empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover']);
                
                // Ambil komentar untuk buku ini
                $q_komentar = $conn->query("SELECT k.*, u.nama, u.foto FROM komentar k JOIN users u ON k.user_id = u.id WHERE k.buku_id = {$b['id']} ORDER BY k.id DESC");
            ?>
            <div class="buku-dropdown" data-judul="<?= strtolower(htmlspecialchars($b['judul'])) ?>">
                <!-- HEADER BUKU (Klik untuk buka) -->
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

                <!-- BODY BUKU (Daftar Ulasan) -->
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

                                <!-- Form Balasan Tersembunyi -->
                                <form id="form-balas-<?= $k['id'] ?>" class="form-balasan" onsubmit="kirimBalasan(event, <?= $k['id'] ?>)">
                                    <input type="text" name="balasan" placeholder="Ketik balasan Anda..." required value="<?= htmlspecialchars($k['balasan'] ?? '') ?>">
                                    <button type="submit"><i class="bi bi-send-fill"></i> Kirim</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #94a3b8; font-size: 13px;">Belum ada ulasan.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <i class="bi bi-chat-square-text" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                <h3 style="margin: 0 0 8px 0; font-size: 18px; color: #1e293b;">Belum Ada Ulasan</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Belum ada anggota yang memberikan ulasan pada buku manapun.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-success" id="toast">
    <i class="bi bi-check-circle-fill" style="font-size: 18px;"></i> <span id="toast-text">Berhasil!</span>
</div>

<style> @keyframes spin { 100% { transform: rotate(360deg); } } </style>

<script>
    // ==========================================
    // 1. FITUR BUKA-TUTUP DROPDOWN BUKU
    // ==========================================
    function toggleDropdown(element) {
        const parent = element.closest('.buku-dropdown');
        
        // Opsional: Tutup dropdown lain jika dibuka
        document.querySelectorAll('.buku-dropdown').forEach(item => {
            if (item !== parent) item.classList.remove('active');
        });

        parent.classList.toggle('active');
    }

    // ==========================================
    // 2. FITUR PENCARIAN BUKU (LIVE FILTER)
    // ==========================================
    function filterBuku() {
        const input = document.getElementById('searchBuku').value.toLowerCase();
        const items = document.querySelectorAll('.buku-dropdown');

        items.forEach(item => {
            const judul = item.getAttribute('data-judul');
            if (judul.includes(input)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // ==========================================
    // 3. FITUR BALAS KOMENTAR (AJAX)
    // ==========================================
    function kirimBalasan(e, id) {
        e.preventDefault();
        const form = document.getElementById('form-balas-' + id);
        const input = form.querySelector('input[name="balasan"]');
        const btn = form.querySelector('button[type="submit"]');
        
        const formData = new FormData();
        formData.append('id_komentar', id);
        formData.append('balasan', input.value);
        formData.append('kirim_balasan_ajax', '1');

        const originalBtnText = btn.innerHTML;
        btn.innerHTML = 'Mengirim...';
        btn.disabled = true;

        fetch('komentar.php', { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                tampilkanToast('Balasan berhasil dikirim!');
                // Reload halaman untuk melihat perubahan
                setTimeout(() => location.reload(), 1000); 
            } else {
                alert('Gagal mengirim balasan.');
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            }
        });
    }

    // ==========================================
    // 4. FITUR HAPUS KOMENTAR (AJAX)
    // ==========================================
    function hapusKomentar(id) {
        if (confirm('Yakin ingin menghapus komentar ini secara permanen?')) {
            fetch('komentar.php?hapus_ajax=' + id)
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'success') {
                        tampilkanToast('Komentar berhasil dihapus!');
                        setTimeout(() => location.reload(), 1000);
                    }
                });
        }
    }

    // ==========================================
    // 5. FUNGSI TOAST & TOGGLE FORM
    // ==========================================
    function tampilkanToast(pesan) {
        const toast = document.getElementById('toast');
        const toastText = document.getElementById('toast-text');
        toastText.textContent = pesan;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function toggleBalas(id) {
        var form = document.getElementById('form-balas-' + id);
        if (form.classList.contains('active')) {
            form.classList.remove('active');
        } else {
            document.querySelectorAll('.form-balasan').forEach(function(el) { el.classList.remove('active'); });
            form.classList.add('active');
            form.querySelector('input[name="balasan"]').focus();
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>