<?php
// ==========================================
// PROSES AJAX DILAKUKAN DI PALING ATAS (SEBELUM HEADER)
// ==========================================
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'petugas') { redirect('index.php'); }

// PROSES HAPUS
if (isset($_GET['hapus_ajax'])) {
    $id_hapus = (int)$_GET['hapus_ajax'];
    $conn->query("DELETE FROM komentar WHERE id = $id_hapus");
    echo "success"; exit;
}

// PROSES BALAS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_balasan_ajax'])) {
    $id_komentar = (int)$_POST['id_komentar'];
    $balasan = $conn->real_escape_string(trim($_POST['balasan']));
    if (!empty($balasan)) {
        $conn->query("UPDATE komentar SET balasan = '$balasan' WHERE id = $id_komentar");
        echo "success";
    } else { echo "error"; }
    exit;
}

// ==========================================
// BARU SETELAH ITU PANGGIL HEADER & SIDEBAR
// ==========================================
$title = 'Daftar Komentar';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$conn->query("UPDATE komentar SET status_baca = 1 WHERE status_baca = 0");
?>

<style>
    /* Layout Utama */
    .komentar-wrap { padding: 32px 24px 80px; background: #f8fafc; min-height: 100vh; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    .page-header { display: flex; justify-content: space-between; align-items: center; max-width: 900px; margin: 0 auto 30px; flex-wrap: wrap; gap: 16px; }
    .page-header h2 { margin: 0; font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .page-header p { margin: 6px 0 0 0; font-size: 15px; color: #64748b; }
    
    .live-indicator { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #10b981; font-weight: 700; background: #f0fdf4; padding: 8px 16px; border-radius: 20px; border: 1px solid #bbf7d0; }
    .live-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: blink 1.5s infinite; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

    .search-wrap { max-width: 900px; margin: 0 auto 20px; position: relative; }
    .search-wrap input { width: 100%; padding: 14px 20px 14px 46px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 14px; outline: none; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s; }
    .search-wrap input:focus { border-color: #0f5132; box-shadow: 0 0 0 4px rgba(15,81,50,0.08); }
    .search-wrap i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; }

    /* =========================================
       DESAIN DROPDOWN BUKU (AKORDEON)
       ========================================= */
    .buku-list { max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
    .buku-dropdown { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .buku-dropdown.active { border-color: #0f5132; box-shadow: 0 4px 15px rgba(15,81,50,0.08); }

    .buku-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; cursor: pointer; background: #fff; transition: background 0.2s; }
    .buku-header:hover { background: #f8fafc; }
    .buku-info { display: flex; align-items: center; gap: 16px; }
    .buku-cover-icon { width: 42px; height: 56px; border-radius: 6px; background: #f1f5f9; color: #0f5132; display: flex; align-items: center; justify-content: center; font-size: 20px; overflow: hidden; flex-shrink: 0; border: 1px solid #e2e8f0; }
    .buku-cover-icon img { width: 100%; height: 100%; object-fit: cover; }
    .buku-title { font-size: 16px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0; }
    .buku-author { font-size: 13px; color: #64748b; margin: 0; }

    .buku-stats { display: flex; align-items: center; gap: 16px; }
    .badge-count { background: #eff6ff; color: #2563eb; font-weight: 700; font-size: 12px; padding: 6px 12px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }
    .dropdown-arrow { width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b; transition: transform 0.3s ease; }
    .buku-dropdown.active .dropdown-arrow { transform: rotate(180deg); background: #e2e8f0; }

    .buku-body { display: none; padding: 20px; border-top: 1px dashed #e2e8f0; background: #fafbfc; }
    .buku-dropdown.active .buku-body { display: block; animation: slideDown 0.3s ease; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    .review-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .review-item:last-child { margin-bottom: 0; }
    .review-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .review-user { display: flex; align-items: center; gap: 12px; }
    .review-avatar { width: 40px; height: 40px; border-radius: 50%; background: #0f5132; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; overflow: hidden; }
    .review-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .review-name { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
    .review-date { font-size: 12px; color: #94a3b8; margin: 0; }
    .review-rating { display: flex; gap: 2px; color: #f59e0b; font-size: 12px; background: #fffbeb; padding: 4px 8px; border-radius: 6px; }
    .review-rating .bi-star { color: #cbd5e1; }
    .review-text { font-size: 14px; color: #334155; line-height: 1.6; margin-bottom: 12px; }
    .review-reply { background: #f0fdf4; border-left: 3px solid #10b981; padding: 10px 14px; border-radius: 0 6px 6px 0; font-size: 13px; color: #475569; margin-bottom: 12px; }
    .review-reply strong { display: block; margin-bottom: 2px; font-size: 11px; text-transform: uppercase; color: #047857; }

    .review-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 12px; }
    .btn-sm { padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; }
    .btn-sm-balas { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .btn-sm-balas:hover { background: #dbeafe; }
    .btn-sm-hapus { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .btn-sm-hapus:hover { background: #fee2e2; }

    .form-balasan { display: none; margin-top: 12px; gap: 8px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .form-balasan.active { display: flex; animation: slideDown 0.3s ease; }
    .form-balasan input { flex: 1; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; }
    .form-balasan input:focus { border-color: #0f5132; }
    .form-balasan button { padding: 10px 16px; background: #0f5132; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; }

    .toast-success { position: fixed; top: 24px; right: 24px; background: #10b981; color: #fff; padding: 14px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; z-index: 9999; transform: translateY(-100px); opacity: 0; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); box-shadow: 0 10px 25px rgba(16,185,129,0.4); display: flex; align-items: center; gap: 10px; }
    .toast-success.show { transform: translateY(0); opacity: 1; }

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
        <span class="live-indicator"><span class="live-dot"></span> Live</span>
    </div>
    
    <!-- Search Bar -->
    <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="searchBuku" placeholder="Cari judul buku yang diulas..." onkeyup="filterBuku()">
    </div>

    <!-- WADAH DROPDOWN BUKU (Diisi oleh AJAX) -->
    <div class="buku-list" id="bukuList">
        <div style="text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1;">
            <i class="bi bi-arrow-repeat" style="font-size: 32px; color: #0f5132; display:inline-block; animation: spin 1s linear infinite;"></i>
            <p style="color: #64748b; margin-top: 12px; font-weight: 600;">Memuat ulasan...</p>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast-success" id="toast">
    <i class="bi bi-check-circle-fill" style="font-size: 18px;"></i> <span id="toast-text">Berhasil!</span>
</div>

<style> @keyframes spin { 100% { transform: rotate(360deg); } } </style>

<script>
    // ==========================================
    // 1. FITUR REALTIME LOAD (AJAX)
    // ==========================================
    let lastData = "";
    
    function muatKomentarRealtime() {
        // Memanggil load_komentar.php dari folder admin
        const urlLoad = '<?= base_url('admin/load_komentar.php') ?>';
        
        fetch(urlLoad)
            .then(response => response.text())
            .then(html => {
                if (html !== lastData) {
                    const wrapper = document.getElementById('bukuList');
                    
                    // Simpan status buka-tutup dropdown
                    const openIds = [];
                    document.querySelectorAll('.buku-dropdown.active').forEach(el => {
                        openIds.push(el.getAttribute('data-judul'));
                    });

                    wrapper.innerHTML = html;
                    lastData = html;

                    // Kembalikan status buka-tutup
                    document.querySelectorAll('.buku-dropdown').forEach(el => {
                        if (openIds.includes(el.getAttribute('data-judul'))) {
                            el.classList.add('active');
                        }
                    });

                    // Jalankan filter jika sedang mencari
                    const searchInput = document.getElementById('searchBuku');
                    if (searchInput.value.trim() !== '') filterBuku();
                }
            })
            .catch(error => console.log('Gagal load komentar:', error));
    }

    document.addEventListener('DOMContentLoaded', muatKomentarRealtime);
    setInterval(muatKomentarRealtime, 3000);

    // ==========================================
    // 2. FITUR BUKA-TUTUP DROPDOWN BUKU
    // ==========================================
    function toggleDropdown(element) {
        const parent = element.closest('.buku-dropdown');
        document.querySelectorAll('.buku-dropdown').forEach(item => {
            if (item !== parent) item.classList.remove('active');
        });
        parent.classList.toggle('active');
    }

    // ==========================================
    // 3. FITUR PENCARIAN BUKU (LIVE FILTER)
    // ==========================================
    function filterBuku() {
        const input = document.getElementById('searchBuku').value.toLowerCase();
        const items = document.querySelectorAll('.buku-dropdown');
        items.forEach(item => {
            const judul = item.getAttribute('data-judul');
            item.style.display = judul.includes(input) ? 'block' : 'none';
        });
    }

    // ==========================================
    // 4. FITUR BALAS KOMENTAR (AJAX)
    // ==========================================
    function kirimBalasan(e, id) {
        e.preventDefault();
        
        const form = document.getElementById('form-balas-' + id);
        const input = form.querySelector('input[name="balasan"]');
        const btn = form.querySelector('button[type="submit"]');
        
        if (!input.value.trim()) {
            alert('Balasan tidak boleh kosong.');
            return;
        }

        const formData = new FormData();
        formData.append('id_komentar', id);
        formData.append('balasan', input.value);
        formData.append('kirim_balasan_ajax', '1');

        const originalBtnText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-arrow-repeat" style="display:inline-block; animation:spin 1s linear infinite;"></i> Kirim...';
        btn.disabled = true;

        const urlKirim = '<?= base_url('petugas/komentar.php') ?>';

        fetch(urlKirim, { method: 'POST', body: formData })
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                tampilkanToast('Balasan berhasil dikirim!');
                muatKomentarRealtime(); 
                form.classList.remove('active');
                input.value = ''; 
            } else {
                alert('Gagal mengirim balasan. Respon server tidak valid.');
                console.log('Respon Server:', data);
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan koneksi saat mengirim balasan.');
            btn.innerHTML = originalBtnText;
            btn.disabled = false;
        });
    }

    // ==========================================
    // 5. FITUR HAPUS KOMENTAR (AJAX)
    // ==========================================
    function hapusKomentar(id) {
        if (confirm('Yakin ingin menghapus komentar ini secara permanen?')) {
            const urlHapus = '<?= base_url('petugas/komentar.php') ?>?hapus_ajax=' + id;
            fetch(urlHapus)
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'success') {
                        tampilkanToast('Komentar berhasil dihapus!');
                        muatKomentarRealtime();
                    } else {
                        alert('Gagal menghapus komentar.');
                    }
                });
        }
    }

    // ==========================================
    // 6. FUNGSI TOAST & TOGGLE FORM BALAS
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