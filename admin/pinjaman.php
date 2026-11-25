<?php
$title = 'Pinjaman Buku';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Approve / Tolak / Kembali / Lunasi Denda
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    if ($aksi === 'approve') {
        $t = $conn->query("SELECT * FROM pinjaman WHERE id=$id")->fetch_assoc();
        if ($t && $t['status'] === 'pending') {
            $conn->query("UPDATE pinjaman SET status='dipinjam', petugas_id=".$_SESSION['user_id']." WHERE id=$id");
            $conn->query("UPDATE buku SET stok_tersedia = stok_tersedia - 1 WHERE id=".$t['buku_id']);
            flash('success','Peminjaman buku berhasil disetujui!');
        }
    } elseif ($aksi === 'tolak') {
        $conn->query("UPDATE pinjaman SET status='ditolak' WHERE id=$id");
        flash('success','Peminjaman buku ditolak!');
    } elseif ($aksi === 'kembali') {
        $t = $conn->query("SELECT * FROM pinjaman WHERE id=$id")->fetch_assoc();
        if ($t && $t['status'] === 'dipinjam') {
            $tgl_kembali = date('Y-m-d');
            $denda = isset($_GET['denda']) ? (int)$_GET['denda'] : 0; 
            $conn->query("UPDATE pinjaman SET status='dikembalikan', tgl_kembali='$tgl_kembali', denda=$denda WHERE id=$id");
            $conn->query("UPDATE buku SET stok_tersedia = stok_tersedia + 1 WHERE id=".$t['buku_id']);
            flash('success','Buku berhasil dikembalikan! Denda: Rp '.number_format($denda,0,',','.'));
        }
    } elseif ($aksi === 'lunasi') {
        $conn->query("UPDATE pinjaman SET denda = 0 WHERE id=$id");
        flash('success','Denda berhasil dilunasi!');
    }
    redirect('admin/pinjaman.php');
}
?>

<style>
    :root { --primary: #0f5132; --primary-hover: #146c43; }

    /* Live Indicator */
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #16a34a;
        background: #f0fdf4;
        padding: 4px 10px;
        border-radius: 20px;
        margin-left: 10px;
        border: 1px solid #bbf7d0;
    }
    .live-dot {
        width: 8px; height: 8px; background: #16a34a; border-radius: 50%;
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); }
        70% { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
        100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    /* Search Bar Global */
    .search-container { margin-bottom: 20px; position: relative; max-width: 400px; }
    .search-input { width: 100%; padding: 12px 16px 12px 40px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 14px; outline: none; transition: 0.2s; box-sizing: border-box; }
    .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15,81,50,0.1); }
    .search-icon { position: absolute; left: 14px; top: 13px; color: #94a3b8; font-size: 16px; }

    /* List Layout */
    .list-container { display: flex; flex-direction: column; gap: 12px; }

    /* Item Card */
    .list-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; transition: 0.2s; }
    .list-item:hover { border-color: #cbd5e1; }

    /* Header */
    .item-header { padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; background: #fff; transition: 0.2s; user-select: none; }
    .item-header:hover { background: #f8fafc; }
    .item-header.active { border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .header-info { display: flex; align-items: center; gap: 16px; flex: 1; }
    .book-title { font-weight: 700; color: #1e293b; font-size: 16px; margin: 0 0 4px 0; }
    .book-author { font-size: 13px; color: #64748b; }

    /* Tambahan CSS untuk Cover Buku */
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

    /* Badge Count */
    .badge-count { background: #eff6ff; color: #2563eb; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 20px; margin-right: 10px; }

    /* Chevron */
    .chevron { transition: transform 0.3s ease; color: #94a3b8; font-size: 18px; margin-left: 12px; }
    .item-header.active .chevron { transform: rotate(180deg); color: var(--primary); }

    /* Detail Section */
    .item-detail { display: none; padding: 0; background: #f8fafc; }
    .item-detail.show { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* Search User */
    .detail-search-container { padding: 16px 20px; background: #fff; border-bottom: 1px solid #e2e8f0; position: relative; }
    .detail-search-input { width: 100%; max-width: 300px; padding: 10px 16px 10px 38px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; transition: 0.2s; box-sizing: border-box; }
    .detail-search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,81,50,0.1); }
    .detail-search-icon { position: absolute; left: 32px; top: 26px; color: #94a3b8; font-size: 14px; }

    /* Tabel User */
    .user-table { width: 100%; border-collapse: collapse; background: #fff; }
    .user-table th { background: #f1f5f9; padding: 10px 20px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
    .user-table td { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; vertical-align: middle; }
    .user-table tr:last-child td { border-bottom: none; }
    .user-table tr:hover { background: #f8fafc; }

    /* Badge Status Kecil */
    .badge-sm { font-size: 10px; font-weight: 800; padding: 4px 8px; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .badge-pending { background: #fef3c7; color: #b45309; }
    .badge-dipinjam { background: #eff6ff; color: #2563eb; }
    .badge-dikembalikan { background: #f0fdf4; color: #16a34a; }
    .badge-ditolak { background: #fef2f2; color: #dc2626; }

    /* Action Buttons */
    .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
    .btn-action { padding: 5px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; }
    .btn-acc { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .btn-acc:hover { background: #dcfce7; color: #15803d; }
    .btn-decline { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .btn-decline:hover { background: #fee2e2; color: #b91c1c; }
    .btn-kembali { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    .btn-kembali:hover { background: #dbeafe; color: #1d4ed8; }
    .btn-lunasi { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .btn-lunasi:hover { background: #fef3c7; color: #b45309; }

    .empty-row td { text-align: center; padding: 30px; color: #94a3b8; font-style: italic; }

    /* Modal Styles */
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal-box { background: #fff; width: 90%; max-width: 400px; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out forwards; transform: translateY(20px); opacity: 0; }
    @keyframes slideUp { to { transform: translateY(0); opacity: 1; } }
    .modal-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
    .modal-icon { width: 45px; height: 45px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .modal-icon.warn { background: #fef3c7; color: #b45309; }
    .modal-icon.danger { background: #fee2e2; color: #b91c1c; }
    .modal-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
    .modal-desc { font-size: 13.5px; color: #64748b; margin-bottom: 20px; line-height: 1.5; }
    .modal-input { width: 100%; padding: 12px 16px; font-size: 16px; font-weight: 700; color: #0f5132; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; outline: none; transition: 0.2s; margin-bottom: 24px; box-sizing: border-box; }
    .modal-input:focus { border-color: #0f5132; background: #fff; box-shadow: 0 0 0 4px rgba(15,81,50,0.1); }
    .modal-footer { display: flex; gap: 10px; justify-content: flex-end; }
    .modal-btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; font-family: inherit; }
    .btn-cancel { background: #f1f5f9; color: #475569; }
    .btn-cancel:hover { background: #e2e8f0; }
    .btn-save { background: #0f5132; color: #fff; }
    .btn-save:hover { background: #146c43; box-shadow: 0 4px 12px rgba(15,81,50,0.3); }
    .btn-save.warn { background: #b45309; }
    .btn-save.warn:hover { background: #92400e; }
    .btn-save.danger { background: #b91c1c; }
    .btn-save.danger:hover { background: #991b1b; }

    @media (max-width: 768px) {
        .user-table th:nth-child(2), .user-table td:nth-child(2),
        .user-table th:nth-child(4), .user-table td:nth-child(4) { display: none; }
        .detail-search-input { max-width: 100%; }
    }
</style>

<div class="main">
    <div class="topbar" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div style="display: flex; align-items: center;">
            <h2 style="margin:0; font-size:22px; font-weight:700; color:#1e293b;">Pinjaman Buku</h2>
            <div class="live-indicator"><div class="live-dot"></div> LIVE</div>
        </div>
    </div>
    <div class="content">
        <?php if ($m = flash('success')): ?>
            <div class="alert alert-success" style="background:#d1fae5; color:#065f46; border-left:4px solid #10b981; border-radius:10px; margin-bottom:24px; padding:14px 20px; font-weight:600;">
                <?= $m ?>
            </div>
        <?php endif; ?>

        <!-- SEARCH BAR GLOBAL -->
        <div class="search-container">
            <i class="bi bi-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input" placeholder="Cari judul buku..." onkeyup="filterBuku()">
        </div>

        <!-- KONTAINER REALTIME -->
        <div class="list-container" id="bukuList">
            <div style="text-align: center; padding: 30px; color: #94a3b8;">
                <i class="bi bi-arrow-repeat" style="font-size: 24px; display:inline-block; animation: spin 1s linear infinite;"></i><br>
                Memuat data...
            </div>
        </div>
        
        <div id="noResult" style="display: none; text-align: center; padding: 30px; color: #64748b;">
            Buku tidak ditemukan.
        </div>
    </div>
</div>

<!-- MODAL KONFIRMASI PENGEMBALIAN -->
<div class="modal-overlay" id="modalDenda">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon"><i class="bi bi-arrow-return-left"></i></div>
            <div>
                <h3 class="modal-title">Konfirmasi Pengembalian</h3>
            </div>
        </div>
        <p class="modal-desc">
            Konfirmasi pengembalian untuk <strong id="modalJudulBuku" style="color:#0f5132;"></strong>. <br>Silakan masukkan nominal denda jika ada (isi 0 jika tidak ada).
        </p>
        <form id="formKembali" method="GET">
            <input type="hidden" name="aksi" value="kembali">
            <input type="hidden" name="id" id="modalIdTransaksi">
            <div style="position: relative;">
                <span style="position:absolute; left:16px; top:12px; font-weight:700; color:#64748b;">Rp</span>
                <input type="number" name="denda" id="modalInputDenda" class="modal-input" style="padding-left: 46px;" value="0" min="0" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn btn-cancel" onclick="tutupModalKembali()">Batal</button>
                <button type="submit" class="modal-btn btn-save">Proses Kembalikan</button>
            </div>
        </form>
    </div>
</div>

<!-- GLOBAL CONFIRM POPUP -->
<div class="modal-overlay" id="confirmPopup">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon" id="confirmIcon"><i class="bi bi-question-lg"></i></div>
            <div>
                <h3 class="modal-title" id="confirmTitle">Konfirmasi</h3>
            </div>
        </div>
        <p class="modal-desc" id="confirmDesc">Apakah Anda yakin?</p>
        <div class="modal-footer">
            <button type="button" class="modal-btn btn-cancel" id="confirmCancel">Batal</button>
            <button type="button" class="modal-btn btn-save" id="confirmOk">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
// ====== LOGIKA REALTIME FETCH ======
let lastData = ""; // Menyimpan data terakhir untuk cek perubahan

function fetchRealtimeData() {
    fetch('api_pinjaman.php')
        .then(response => response.text())
        .then(data => {
            // Jika data dari server BEDA dengan data yang terakhir ditampilkan
            if (data !== lastData) {
                const listContainer = document.getElementById('bukuList');
                // Simpan status buka-tutup accordion sebelum replace
                const openIds = [];
                document.querySelectorAll('.item-header.active').forEach(header => {
                    // Cari index atau judul untuk dijadikan penanda
                    const title = header.querySelector('.book-title').innerText.trim();
                    openIds.push(title);
                });

                // Ganti HTML dengan data baru
                listContainer.innerHTML = data;
                lastData = data;

                // Kembalikan status buka-tutup accordion
                document.querySelectorAll('.item-header').forEach(header => {
                    const title = header.querySelector('.book-title').innerText.trim();
                    if (openIds.includes(title)) {
                        header.classList.add('active');
                        header.nextElementSibling.classList.add('show');
                    }
                });

                // Jalankan ulang filter jika kolom search sedang terisi
                var searchInput = document.getElementById('searchInput');
                if (searchInput.value.trim() !== '') {
                    filterBuku();
                }
            }
        })
        .catch(error => console.error('Error fetching data:', error));
}

// Mulai fetch realtime setiap 3 detik
document.addEventListener('DOMContentLoaded', () => {
    fetchRealtimeData(); // Panggil pertama kali
    setInterval(fetchRealtimeData, 3000); // Panggil setiap 3000ms (3 detik)
    
    // Inisialisasi event listener untuk confirm popup setelah data dimuat
    initConfirmListeners();
});

// ====== FUNGSI ACCORDION BUKU ======
function toggleBuku(header, id) {
    var detail = header.nextElementSibling;
    header.classList.toggle('active');
    
    if (detail.classList.contains('show')) {
        detail.classList.remove('show');
    } else {
        var allDetails = document.querySelectorAll('.item-detail');
        var allHeaders = document.querySelectorAll('.item-header');
        allDetails.forEach(function(el) { el.classList.remove('show'); });
        allHeaders.forEach(function(el) { el.classList.remove('active'); });
        detail.classList.add('show');
    }
}

// ====== SEARCH BUKU (Global) ======
function filterBuku() {
    var input = document.getElementById('searchInput');
    var filter = input.value.toLowerCase();
    var items = document.querySelectorAll('#bukuList .list-item');
    var noResult = document.getElementById('noResult');
    var found = 0;

    items.forEach(function(item) {
        var searchData = item.getAttribute('data-search');
        if (searchData.includes(filter)) {
            item.style.display = '';
            found++;
        } else {
            item.style.display = 'none';
        }
    });

    if (found === 0 && filter !== '') {
        noResult.style.display = 'block';
    } else {
        noResult.style.display = 'none';
    }
}

// ====== SEARCH USER ======
function filterUser(input) {
    var filter = input.value.toLowerCase();
    var detailContainer = input.closest('.item-detail');
    var rows = detailContainer.querySelectorAll('.user-row');
    rows.forEach(function(row) {
        var userName = row.getAttribute('data-user');
        if (userName.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ====== MODAL KEMBALIKAN ======
function bukaModalKembali(id, nama) {
    document.getElementById('modalIdTransaksi').value = id;
    document.getElementById('modalJudulBuku').textContent = nama;
    document.getElementById('modalInputDenda').value = "0";
    document.getElementById('modalDenda').classList.add('show');
    setTimeout(() => {
        document.getElementById('modalInputDenda').focus();
        document.getElementById('modalInputDenda').select();
    }, 100);
}

function tutupModalKembali() {
    document.getElementById('modalDenda').classList.remove('show');
}

document.getElementById('modalDenda').addEventListener('click', function(e) {
    if (e.target === this) tutupModalKembali();
});

// ====== GLOBAL CONFIRM POPUP (Fungsi Init) ======
let confirmInitialized = false;
function initConfirmListeners() {
    if (confirmInitialized) return;
    confirmInitialized = true;

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

    // GUNAKAN EVENT DELEGATION AGAR ELEMEN BARU HASIL FETCH JUGA BISA DIKLIK
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
                if (href && href !== '#') { window.location.href = href; }
            }
        });
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>