<?php
$title = 'Daftar Buku';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

$current_url = $_SERVER['PHP_SELF']; // Ini otomatis mendeteksi nama file (buku.php)

/* ============================================================
   HANDLE TAMBAH STOK 
   ============================================================ */
if (isset($_POST['tambah_stok'])) {
    $id_buku = (int)$_POST['id_buku_stok'];
    $jml_tambah = (int)$_POST['jml_tambah_stok'];
    
    if ($jml_tambah > 0) {
        $conn->query("UPDATE buku SET stok = stok + $jml_tambah, stok_tersedia = stok_tersedia + $jml_tambah WHERE id = $id_buku");
        flash('success', "Berhasil menambahkan $jml_tambah stok buku!");
    } else {
        flash('error', "Jumlah tambah stok harus lebih dari 0!");
    }
    redirect($current_url); 
}

/* ============================================================
   HANDLE HAPUS MASSAL (BULK DELETE)
   ============================================================ */
if (isset($_POST['hapus_massal']) && !empty($_POST['pilih_buku'])) {
    $ids = $_POST['pilih_buku'];
    $id_list = implode(',', array_map('intval', $ids));
    
    $q = $conn->query("SELECT cover, file_pdf FROM buku WHERE id IN ($id_list)");
    while ($b = $q->fetch_assoc()) {
        if (!empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover'])) {
            unlink('../assets/img/cover/' . $b['cover']);
        }
        if (!empty($b['file_pdf']) && file_exists('../assets/file_buku/' . $b['file_pdf'])) {
            unlink('../assets/file_buku/' . $b['file_pdf']);
        }
    }
    
    $conn->query("DELETE FROM buku WHERE id IN ($id_list)");
    flash('success', count($ids) . ' buku beserta file PDF-nya berhasil dihapus sekaligus!');
    redirect($current_url);
}

/* ============================================================
   HANDLE TAMBAH / UPDATE BUKU (UNTUK EDIT)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['hapus_massal']) && !isset($_POST['tambah_stok'])) {
    $id        = $_POST['id'] ?? '';
    $judul     = trim($_POST['judul']);
    $pengarang = trim($_POST['pengarang']);
    $penerbit  = trim($_POST['penerbit']);
    $tahun     = (int)$_POST['tahun_terbit'];
    $isbn      = trim($_POST['isbn']);
    $kategori  = (int)$_POST['kategori_id'];
    $lokasi    = trim($_POST['lokasi_rak']);
    $deskripsi = trim($_POST['deskripsi']);

    /* ============ HANDLE UPLOAD COVER ============ */
    $cover_name = $_POST['cover_lama'] ?? '';

    if (isset($_POST['auto_cover_base64']) && !empty($_POST['auto_cover_base64']) && empty($_FILES['cover']['name'])) {
        $upload_dir = '../assets/img/cover/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $data = $_POST['auto_cover_base64'];
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);

        $cover_name = 'cover_auto_' . time() . '_' . rand(100, 999) . '.jpg';
        file_put_contents($upload_dir . $cover_name, $data);
    } 
    elseif (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
        $upload_dir = '../assets/img/cover/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $file_ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp'];
        $max_size = 2 * 1024 * 1024;

        if (in_array($file_ext, $allowed) && $_FILES['cover']['size'] <= $max_size) {
            if (!empty($cover_name) && file_exists($upload_dir . $cover_name)) {
                unlink($upload_dir . $cover_name);
            }
            $cover_name = 'cover_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            move_uploaded_file($_FILES['cover']['tmp_name'], $upload_dir . $cover_name);
        } else {
            flash('error', 'Format gambar cover harus JPG/PNG/WEBP dan maksimal 2MB!');
            redirect($current_url);
        }
    }

    /* ============ HANDLE UPLOAD FILE PDF BUKU ============ */
    $pdf_name = $_POST['pdf_lama'] ?? '';
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === 0) {
        $pdf_dir = '../assets/file_buku/';
        if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0755, true);

        $pdf_ext = strtolower(pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION));
        
        if ($pdf_ext === 'pdf') {
            if (!empty($pdf_name) && file_exists($pdf_dir . $pdf_name)) {
                unlink($pdf_dir . $pdf_name);
            }
            $pdf_name = 'buku_' . time() . '_' . rand(100, 999) . '.pdf';
            move_uploaded_file($_FILES['file_pdf']['tmp_name'], $pdf_dir . $pdf_name);
        } else {
            flash('error', 'File digital harus berformat PDF!');
            redirect($current_url);
        }
    }

    if ($id) {
        $stmt = $conn->prepare("UPDATE buku SET judul=?, pengarang=?, penerbit=?, tahun_terbit=?, isbn=?, kategori_id=?, lokasi_rak=?, deskripsi=?, cover=?, file_pdf=? WHERE id=?");
        $stmt->bind_param('sssssissssi', $judul, $pengarang, $penerbit, $tahun, $isbn, $kategori, $lokasi, $deskripsi, $cover_name, $pdf_name, $id);
        $stmt->execute();
        flash('success', 'Informasi buku berhasil diupdate!');
    } else {
        $stok = (int)$_POST['stok'];
        $stmt = $conn->prepare("INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, kategori_id, stok, stok_tersedia, lokasi_rak, deskripsi, cover, file_pdf) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssiiissss', $judul, $pengarang, $penerbit, $tahun, $isbn, $kategori, $stok, $stok, $lokasi, $deskripsi, $cover_name, $pdf_name);
        $stmt->execute();
        flash('success', 'Buku baru berhasil ditambahkan!');
    }

    redirect($current_url);
}

/* ============================================================
   HANDLE HAPUS SATUAN
   ============================================================ */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $b = $conn->query("SELECT cover, file_pdf FROM buku WHERE id=$id")->fetch_assoc();
    if ($b) {
        if (!empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover'])) unlink('../assets/img/cover/' . $b['cover']);
        if (!empty($b['file_pdf']) && file_exists('../assets/file_buku/' . $b['file_pdf'])) unlink('../assets/file_buku/' . $b['file_pdf']);
    }
    $conn->query("DELETE FROM buku WHERE id=$id");
    flash('success', 'Buku berhasil dihapus!');
    redirect($current_url);
}

/* ============================================================
   MODE EDIT & KATEGORI
   ============================================================ */
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM buku WHERE id=$id")->fetch_assoc();
}
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
?>

<!-- Tambahkan PDF.js untuk Generate Cover Otomatis saat Edit -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
/* (CSS TETAP SAMA SEPERTI SEBELUMNYA) */
.buku-wrap * { box-sizing: border-box; }
.buku-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 12px; flex-wrap: wrap; }
.buku-head h2 { font-size: 22px; font-weight: 700; color: #1a202c; margin: 0 0 4px 0; }
.buku-head p { font-size: 13px; color: #718096; margin: 0; }
.bcard { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
.bcard-head { padding: 18px 22px; border-bottom: 1px solid #f1f3f5; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
.bcard-head h3 { font-size: 15px; font-weight: 700; color: #1a202c; margin: 0; display: flex; align-items: center; gap: 8px; }
.bcard-body { padding: 22px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid .full { grid-column: 1 / -1; }
.form-group label { display: block; font-size: 12.5px; font-weight: 700; color: #4a5568; margin-bottom: 6px; letter-spacing: 0.2px; }
.form-group label .req { color: #e53e3e; }
.form-control { width: 100%; padding: 11px 14px; font-size: 13.5px; font-family: inherit; color: #1a202c; background: #fafbfc; border: 1.5px solid #e2e8f0; border-radius: 8px; transition: all 0.2s; outline: none; }
.form-control:focus { border-color: #0f5132; background: #fff; box-shadow: 0 0 0 3px rgba(15, 81, 50, 0.08); }
.form-control[readonly] { background: #e2e8f0; cursor: not-allowed; color: #718096; }
textarea.form-control { resize: vertical; min-height: 80px; }
.form-actions { display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #f1f3f5; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 20px; font-size: 13px; font-weight: 700; font-family: inherit; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; transition: all 0.2s; }
.btn-primary { background: #0f5132; color: #fff; box-shadow: 0 4px 12px rgba(15, 81, 50, 0.2); }
.btn-primary:hover { background: #146c43; transform: translateY(-1px); }
.btn-secondary { background: #f1f5f9; color: #4a5568; }
.btn-edit { background: #fff7ed; color: #c05621; padding: 7px 12px; font-size: 12px; }
.btn-edit:hover { background: #ed8936; color: #fff; }
.btn-delete { background: #fef2f2; color: #c53030; padding: 7px 12px; font-size: 12px; }
.btn-delete:hover { background: #e53e3e; color: #fff; }
.btn-stok { background: #fefce8; color: #ca8a04; padding: 7px 12px; font-size: 12px; border: 1px solid #fde047; }
.btn-stok:hover { background: #eab308; color: #fff; border-color: #eab308; }
.search-bar { padding: 16px 22px; background: #fafbfc; border-bottom: 1px solid #f1f3f5; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.search-bar input { flex: 1; min-width: 200px; padding: 10px 16px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
.table-wrap { overflow-x: auto; }
table.data { width: 100%; border-collapse: collapse; font-size: 13.5px; }
table.data thead th { text-align: left; padding: 14px 18px; background: #fafbfc; font-size: 11px; font-weight: 700; color: #718096; text-transform: uppercase; border-bottom: 1px solid #e9ecef; white-space: nowrap; }
table.data tbody td { padding: 14px 18px; border-bottom: 1px solid #f1f3f5; color: #2d3748; vertical-align: middle; }
table.data tbody tr:hover { background: #fafbfc; }
.book-row { display: flex; gap: 12px; align-items: center; min-width: 220px; }
.book-thumb { width: 45px; height: 60px; border-radius: 6px; background: #0f5132; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 20px; overflow: hidden; flex-shrink: 0; }
.book-thumb img { width: 100%; height: 100%; object-fit: cover; }
.book-info .judul { font-weight: 700; color: #0f5132; font-size: 13.5px; margin-bottom: 3px; }
.book-info .meta { font-size: 11.5px; color: #718096; display: flex; gap: 10px; flex-wrap: wrap; }
.action-cell { display: flex; gap: 6px; justify-content: flex-end; white-space: nowrap; }
.b-alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.b-alert-success { background: #f0fff4; color: #2f855a; border-left: 4px solid #38a169; }
.b-alert-error { background: #fef2f2; color: #991b1b; border-left: 4px solid #e53e3e; }
@media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
.cek-box { width: 18px; height: 18px; cursor: pointer; accent-color: #0f5132; }
.upload-section { display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 20px; }
.cover-preview-box { width: 130px; height: 180px; border-radius: 8px; background: #fff; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #cbd5e1; overflow: hidden; flex-shrink: 0; position: relative; }
.cover-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.cover-preview-box .loading-cover { position: absolute; inset: 0; background: rgba(255,255,255,0.8); display: none; align-items: center; justify-content: center; font-size: 12px; color: #0f5132; font-weight: 600; }
.file-inputs-wrap { flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 16px; }
.file-input-group { display: flex; flex-direction: column; gap: 6px; }
.file-input-group label { font-size: 13px; font-weight: 700; color: #1e293b; }
.file-input-group input[type="file"] { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #fff; cursor: pointer; font-family: inherit; color: #475569; }
.file-input-group small { font-size: 11.5px; color: #64748b; }
.modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay.show { display: flex; }
.modal-box { background: #fff; width: 90%; max-width: 400px; border-radius: 16px; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.3s ease-out forwards; transform: translateY(20px); opacity: 0; }
@keyframes slideUp { to { transform: translateY(0); opacity: 1; } }
.modal-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.modal-icon { width: 45px; height: 45px; border-radius: 50%; background: #fefce8; color: #ca8a04; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
.modal-title { font-size: 18px; font-weight: 800; color: #1e293b; margin: 0; }
.modal-desc { font-size: 13.5px; color: #64748b; margin-bottom: 20px; line-height: 1.5; }
.modal-input { width: 100%; padding: 12px 16px; font-size: 16px; font-weight: 700; color: #0f5132; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; outline: none; transition: 0.2s; margin-bottom: 24px; box-sizing: border-box; }
.modal-input:focus { border-color: #0f5132; background: #fff; box-shadow: 0 0 0 4px rgba(15,81,50,0.1); }
.modal-footer { display: flex; gap: 10px; justify-content: flex-end; }
.modal-btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: 0.2s; font-family: inherit; }
.btn-cancel { background: #f1f5f9; color: #475569; }
.btn-cancel:hover { background: #e2e8f0; }
.btn-save { background: #ca8a04; color: #fff; }
.btn-save:hover { background: #a16207; box-shadow: 0 4px 12px rgba(202,138,4,0.3); }
@keyframes spin { 100% { transform: rotate(360deg); } }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); } 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); } }
</style>

<div class="main">
    <div class="topbar">
        <h2>Daftar Buku</h2>
        <a href="<?= base_url('pengaturan.php') ?>" class="user-info" title="Pengaturan Akun">
            <span><?= htmlspecialchars($_SESSION['nama']) ?></span>
            <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
                <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" class="avatar" alt="Profil" style="object-fit: cover;">
            <?php else: ?>
                <div class="avatar"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
            <?php endif; ?>
        </a>
    </div>

    <div class="content buku-wrap">
        <div class="buku-head">
            <div>
                <h2>Daftar Koleksi Buku</h2>
                <p>Kelola ketersediaan stok buku perpustakaan</p>
            </div>
        </div>

        <?php if ($m = flash('success')): ?>
            <div class="b-alert b-alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($m) ?></div>
        <?php endif; ?>
        <?php if ($m = flash('error')): ?>
            <div class="b-alert b-alert-error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <!-- FORM EDIT BUKU -->
        <?php if ($edit): ?>
        <div class="bcard">
            <div class="bcard-head">
                <h3><i class="bi bi-pencil-square"></i> Edit Buku</h3>
                <!-- LINK BATAL MENGGUNAKAN CURRENT_URL -->
                <a href="<?= $current_url ?>" class="btn btn-secondary" style="padding:7px 14px;font-size:12px;"><i class="bi bi-x"></i> Batal Edit</a>
            </div>
            <div class="bcard-body">
                <form method="post" autocomplete="off" enctype="multipart/form-data" id="formBuku">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                    <input type="hidden" name="cover_lama" value="<?= $edit['cover'] ?>">
                    <input type="hidden" name="pdf_lama" value="<?= $edit['file_pdf'] ?>">
                    <input type="hidden" name="auto_cover_base64" id="auto_cover_base64" value="">

                    <div class="upload-section">
                        <div class="cover-preview-box" id="coverPreview">
                            <?php if (!empty($edit['cover']) && file_exists('../assets/img/cover/' . $edit['cover'])): ?>
                                <img src="../assets/img/cover/<?= htmlspecialchars($edit['cover']) ?>" alt="Cover">
                            <?php else: ?>
                                <i class="bi bi-image"></i>
                            <?php endif; ?>
                            <div class="loading-cover" id="loadingCover">Memuat cover...</div>
                        </div>

                        <div class="file-inputs-wrap">
                            <div class="file-input-group">
                                <label><i class="bi bi-image"></i> Upload Gambar Cover (Opsional)</label>
                                <input type="file" name="cover" accept="image/*" onchange="previewManualCover(this)">
                            </div>
                            <div class="file-input-group">
                                <label><i class="bi bi-file-earmark-pdf"></i> Upload File PDF Buku</label>
                                <input type="file" name="file_pdf" accept="application/pdf" id="pdfInput" onchange="generateCoverFromPDF(this)">
                                <small style="color:#0f5132; font-weight:600;">
                                    File saat ini: <b><?= htmlspecialchars($edit['file_pdf'] ?? '-') ?></b>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Judul Buku <span class="req">*</span></label>
                            <input type="text" name="judul" class="form-control" required value="<?= htmlspecialchars($edit['judul']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Pengarang</label>
                            <input type="text" name="pengarang" class="form-control" value="<?= htmlspecialchars($edit['pengarang']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Penerbit</label>
                            <input type="text" name="penerbit" class="form-control" value="<?= htmlspecialchars($edit['penerbit']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Tahun Terbit</label>
                            <input type="number" name="tahun_terbit" class="form-control" value="<?= htmlspecialchars($edit['tahun_terbit']) ?>">
                        </div>
                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" class="form-control" value="<?= htmlspecialchars($edit['isbn']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori_id" class="form-control">
                                <option value="">-- Pilih Kategori --</option>
                                <?php $kategoriList->data_seek(0); while ($k = $kategoriList->fetch_assoc()): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($edit['kategori_id'] == $k['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['nama_kategori']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stok <small style="color:#ca8a04; font-weight:normal;">(Gunakan tombol + Stok di tabel)</small></label>
                            <input type="number" class="form-control" readonly value="<?= htmlspecialchars($edit['stok']) ?>" style="background:#e2e8f0; cursor:not-allowed;">
                        </div>
                        <div class="form-group">
                            <label>Lokasi Rak</label>
                            <input type="text" name="lokasi_rak" class="form-control" value="<?= htmlspecialchars($edit['lokasi_rak']) ?>">
                        </div>
                        <div class="form-group full">
                            <label>Deskripsi / Sinopsis</label>
                            <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($edit['deskripsi']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Buku</button>
                        <!-- LINK BATAL MENGGUNAKAN CURRENT_URL -->
                        <a href="<?= $current_url ?>" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabel Daftar Buku (REALTIME) -->
        <div class="bcard">
            <div class="bcard-head">
                <h3><i class="bi bi-collection"></i> Daftar Koleksi Buku <span class="live-indicator" style="font-size:10px; padding:2px 8px; margin-left:8px; display:inline-flex; align-items:center; gap:4px; background:#f0fdf4; color:#16a34a; border-radius:10px; border:1px solid #bbf7d0;"><span class="live-dot" style="width:6px; height:6px; background:#16a34a; border-radius:50%; animation:pulse 1.5s infinite;"></span> LIVE</span></h3>
                <span style="font-size:12px;color:#718096;font-weight:600;" id="totalBukuText"></span>
            </div>
            <form method="get" class="search-bar" onsubmit="event.preventDefault(); fetchBukuData();">
                <i class="bi bi-search" style="color:#718096;font-size:15px;"></i>
                <input type="text" name="cari" id="searchInput" placeholder="Cari judul, pengarang, atau ISBN..." onkeyup="fetchBukuDataDebounced()">
                <button type="submit" class="btn btn-primary" style="padding:10px 20px;">Cari</button>
            </form>
            
            <form method="post" id="formBulkHapus">
                <div class="table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th style="width:40px; text-align:center;"><input type="checkbox" class="cek-box" id="checkAll" title="Pilih Semua"></th>
                                <th style="width:50px;">No</th>
                                <th>Informasi Buku</th>
                                <th>Kategori</th>
                                <th style="width:120px;">Stok</th>
                                <th style="width:230px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="bukuTbody">
                            <tr><td colspan="6"><div style="text-align:center;padding:40px;"><i class="bi bi-arrow-repeat" style="display:inline-block; animation: spin 1s linear infinite;"></i> Memuat data...</div></td></tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="bulkActionContainer" style="display: none; padding: 15px 22px; background: #fef2f2; border-top: 1px solid #fee2e2; text-align: right;">
                    <span style="color: #991b1b; font-weight: 600; margin-right: 15px; font-size: 14px;">
                        <span id="countSelected">0</span> buku dipilih
                    </span>
                    <button type="submit" name="hapus_massal" class="btn btn-delete" style="background: #e53e3e; color: #fff; padding: 10px 20px;" onclick="return confirm('Yakin ingin menghapus semua buku terpilih secara permanen?')">
                        <i class="bi bi-trash-fill"></i> Hapus Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH STOK -->
<div class="modal-overlay" id="modalStok">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-icon"><i class="bi bi-box-seam"></i></div>
            <div>
                <h3 class="modal-title">Tambah Stok Buku</h3>
            </div>
        </div>
        <p class="modal-desc">
            Tambah stok untuk buku <strong id="stokJudulBuku" style="color:#0f5132;"></strong>.<br>
            Stok saat ini: <b id="stokSaatIni"></b>
        </p>
        <form id="formTambahStok" method="POST">
            <input type="hidden" name="tambah_stok" value="1">
            <input type="hidden" name="id_buku_stok" id="stokIdBuku">
            
            <label style="font-size:13px; font-weight:700; color:#475569; display:block; margin-bottom:8px;">Jumlah Tambahan Stok</label>
            <input type="number" name="jml_tambah_stok" id="stokJumlah" class="modal-input" value="1" min="1" required>

            <div class="modal-footer">
                <button type="button" class="modal-btn btn-cancel" onclick="tutupModalStok()">Batal</button>
                <button type="submit" class="modal-btn btn-save">Tambah Stok</button>
            </div>
        </form>
    </div>
</div>

<script>
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

let lastBukuData = "";
let debounceTimer;

function fetchBukuData() {
    const cari = document.getElementById('searchInput').value;
    const tbody = document.getElementById('bukuTbody');
    
    fetch(`api_daftar_buku.php?cari=${encodeURIComponent(cari)}`)
        .then(response => response.text())
        .then(data => {
            if (data !== lastBukuData) {
                const checkedIds = Array.from(document.querySelectorAll('.cek-item:checked')).map(cb => cb.value);
                tbody.innerHTML = data;
                lastBukuData = data;

                document.querySelectorAll('.cek-item').forEach(cb => {
                    if (checkedIds.includes(cb.value)) cb.checked = true;
                });

                document.getElementById('totalBukuText').innerText = document.querySelectorAll('.cek-item').length + ' buku';
                if (typeof updateBulkAction === 'function') updateBulkAction();
                attachCheckboxListeners();
            }
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="6"><div style="text-align:center;padding:40px;color:#dc2626;">Gagal memuat data. Pastikan api_daftar_buku.php ada.</div></td></tr>`;
        });
}

function fetchBukuDataDebounced() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchBukuData, 400);
}

document.addEventListener('DOMContentLoaded', () => {
    fetchBukuData();
    setInterval(fetchBukuData, 3000);
});

function updateBulkAction() {
    const checkItems = document.querySelectorAll('.cek-item');
    const checkAll = document.getElementById('checkAll');
    const bulkActionContainer = document.getElementById('bulkActionContainer');
    const countSelected = document.getElementById('countSelected');
    const checkedCount = document.querySelectorAll('.cek-item:checked').length;
    
    if (checkedCount > 1) {
        bulkActionContainer.style.display = 'block';
        countSelected.textContent = checkedCount;
    } else {
        bulkActionContainer.style.display = 'none';
    }

    if (checkItems.length > 0) {
        checkAll.checked = Array.from(checkItems).every(i => i.checked);
    } else {
        checkAll.checked = false;
    }
}

function attachCheckboxListeners() {
    const checkAll = document.getElementById('checkAll');
    const newCheckAll = checkAll.cloneNode(true);
    checkAll.parentNode.replaceChild(newCheckAll, checkAll);
    
    newCheckAll.addEventListener('change', function() {
        document.querySelectorAll('.cek-item').forEach(item => { item.checked = this.checked; });
        updateBulkAction();
    });

    document.querySelectorAll('.cek-item').forEach(item => {
        item.onclick = function() { updateBulkAction(); };
    });
}

function previewManualCover(input) {
    const preview = document.getElementById('coverPreview');
    const autoInput = document.getElementById('auto_cover_base64');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">'; };
        reader.readAsDataURL(input.files[0]);
        autoInput.value = '';
    }
}

async function generateCoverFromPDF(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.type !== 'application/pdf') return;

    const preview = document.getElementById('coverPreview');
    const autoInput = document.getElementById('auto_cover_base64');
    const loadingCover = document.getElementById('loadingCover');
    preview.innerHTML = '<i class="bi bi-image" style="opacity:0.3"></i>';
    loadingCover.style.display = 'flex';
    autoInput.value = '';

    try {
        const arrayBuffer = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
        const page = await pdf.getPage(1);
        const viewport = page.getViewport({ scale: 1.5 });
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        await page.render({ canvasContext: context, viewport: viewport }).promise;
        const imgData = canvas.toDataURL('image/jpeg', 0.8);
        preview.innerHTML = '<img src="' + imgData + '" alt="Auto Cover">';
        autoInput.value = imgData;
    } catch (error) {
        console.error("Gagal ekstrak cover dari PDF: ", error);
        alert("Gagal membuat cover otomatis. Silakan upload gambar cover secara manual.");
    } finally {
        loadingCover.style.display = 'none';
    }
}

function bukaModalStok(id, judul, stokSaatIni) {
    document.getElementById('stokIdBuku').value = id;
    document.getElementById('stokJudulBuku').innerText = judul;
    document.getElementById('stokSaatIni').innerText = stokSaatIni + ' buku';
    document.getElementById('stokJumlah').value = 1;
    document.getElementById('modalStok').classList.add('show');
    setTimeout(() => {
        document.getElementById('stokJumlah').focus();
        document.getElementById('stokJumlah').select();
    }, 100);
}

function tutupModalStok() {
    document.getElementById('modalStok').classList.remove('show');
}

document.getElementById('modalStok').addEventListener('click', function(e) {
    if (e.target === this) tutupModalStok();
});
</script>

<?php require_once '../includes/footer.php'; ?>