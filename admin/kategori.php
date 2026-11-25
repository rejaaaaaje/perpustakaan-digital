<?php
$title = 'Kelola Kategori';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// ==========================================
// LOGIKA TETAP SAMA
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nama = $_POST['nama_kategori'];
    $desk = $_POST['deskripsi'];
    if ($id) {
        $stmt = $conn->prepare("UPDATE kategori SET nama_kategori=?,deskripsi=? WHERE id=?");
        $stmt->bind_param('ssi', $nama,$desk,$id);
        flash('success', 'Kategori berhasil diperbarui!');
    } else {
        $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori,deskripsi) VALUES (?,?)");
        $stmt->bind_param('ss', $nama,$desk);
        flash('success', 'Kategori baru berhasil ditambahkan!');
    }
    $stmt->execute();
    redirect('admin/kategori.php');
}

if (isset($_GET['hapus'])) {
    $conn->query("DELETE FROM kategori WHERE id=".(int)$_GET['hapus']);
    flash('success','Kategori berhasil dihapus!');
    redirect('admin/kategori.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = $conn->query("SELECT * FROM kategori WHERE id=".(int)$_GET['edit'])->fetch_assoc();
}

// LOGIKA SEARCHING
$search = isset($_GET['cari']) ? $conn->real_escape_string(trim($_GET['cari'])) : '';

if ($search !== '') {
    $sql = "SELECT * FROM kategori WHERE nama_kategori LIKE '%$search%' OR deskripsi LIKE '%$search%' ORDER BY id DESC";
} else {
    $sql = "SELECT * FROM kategori ORDER BY id DESC";
}
$list = $conn->query($sql);
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
    /* Layout Utama (Sangat Compact) */
    .page-wrapper { padding: 24px; max-width: 800px; margin: 0 auto; font-family: 'Segoe UI', sans-serif; }
    
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-header h2 { margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; }

    /* Toolbar (Search & Tombol Tambah) */
    .toolbar { display: flex; gap: 10px; margin-bottom: 16px; }
    
    .search-box { position: relative; flex: 1; }
    .search-box input {
        width: 100%; padding: 10px 16px 10px 38px; border: 1px solid #cbd5e1;
        border-radius: 8px; font-size: 13.5px; outline: none; transition: 0.2s; background: #fff;
    }
    .search-box input:focus { border-color: #0f5132; box-shadow: 0 0 0 3px rgba(15,81,50,0.1); }
    .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; }

    .btn-primary {
        background: #0f5132; color: #fff; padding: 10px 16px; border-radius: 8px;
        font-weight: 700; font-size: 13px; border: none; cursor: pointer; transition: 0.2s;
        display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
    }
    .btn-primary:hover { background: #146c43; }

    /* =========================================
       DESAIN DROPDOWN (AKORDEON) TIPIS
       ========================================= */
    .dropdown-list { display: flex; flex-direction: column; gap: 8px; }

    .dropdown-item {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;
        transition: 0.2s;
    }
    .dropdown-item:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }

    /* Header Dropdown (Yang selalu terlihat) */
    .dropdown-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; cursor: pointer; background: #fff;
    }
    .dropdown-header:hover { background: #f8fafc; }
    
    .header-info { display: flex; align-items: center; gap: 12px; }
    .icon-box {
        width: 32px; height: 32px; border-radius: 8px; background: #f0fdf4; color: #0f5132;
        display: flex; align-items: center; justify-content: center; font-size: 14px;
    }
    .header-title { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0; }
    
    .dropdown-icon {
        width: 24px; height: 24px; border-radius: 50%; background: #f1f5f9;
        display: flex; align-items: center; justify-content: center;
        color: #64748b; transition: transform 0.3s ease;
    }
    .dropdown-item.active .dropdown-icon { transform: rotate(180deg); background: #e2e8f0; color: #1e293b; }

    /* Body Dropdown (Tersembunyi) */
    .dropdown-body {
        display: none; padding: 0 16px 16px 16px; border-top: 1px dashed #e2e8f0;
        background: #fff;
    }
    .dropdown-item.active .dropdown-body { display: block; animation: slideDown 0.3s ease; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    .item-desc { font-size: 13px; color: #64748b; line-height: 1.6; margin: 12px 0 16px 0; }
    
    /* Tombol Aksi dalam Dropdown */
    .action-group { display: flex; gap: 8px; justify-content: flex-end; }
    .btn-action {
        padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
        text-decoration: none; border: 1px solid transparent; transition: 0.2s;
        display: inline-flex; align-items: center; gap: 4px; cursor: pointer;
    }
    .btn-edit { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .btn-edit:hover { background: #fef3c7; }
    .btn-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .btn-delete:hover { background: #fee2e2; }

    /* =========================================
       FORM INPUT TERSEMBUNYI (DROPDOWN TOGGLE)
       ========================================= */
    .form-panel {
        display: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 16px; margin-bottom: 16px;
    }
    .form-panel.active { display: block; animation: slideDown 0.3s ease; }

    .form-group { margin-bottom: 12px; }
    .form-group label { display: block; font-weight: 700; color: #475569; font-size: 12px; margin-bottom: 6px; }
    .form-control {
        width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px;
        font-size: 13.5px; transition: 0.2s; background: #fff; font-family: inherit;
    }
    .form-control:focus { border-color: #0f5132; box-shadow: 0 0 0 3px rgba(15,81,50,0.1); outline: none; }

    .empty-state { text-align: center; padding: 40px 20px; background: #fff; border-radius: 10px; border: 1px dashed #cbd5e1; }
</style>

<div class="page-wrapper">
    
    <!-- HEADER -->
    <div class="page-header">
        <h2>Kelola Kategori</h2>
    </div>

    <?php if ($m = flash('success')): ?>
        <div style="background:#d1fae5; color:#065f46; border-left:4px solid #10b981; border-radius:8px; margin-bottom:16px; padding:12px 16px; font-weight:600; font-size:13px;">
            <?= $m ?>
        </div>
    <?php endif; ?>

    <!-- TOOLBAR (SEARCH & TOMBOL TAMBAH) -->
    <div class="toolbar">
        <div class="search-box">
            <form method="GET" action="" style="margin:0;">
                <i class="bi bi-search"></i>
                <input type="text" name="cari" placeholder="Cari nama kategori..." value="<?= htmlspecialchars($search) ?>">
            </form>
        </div>
        <button type="button" class="btn-primary" onclick="toggleForm()">
            <i class="bi bi-plus-lg"></i> Kategori
        </button>
    </div>

    <!-- FORM PANEL (Tambah/Edit) - Tersembunyi secara default -->
    <div class="form-panel <?= $edit ? 'active' : '' ?>" id="formPanel">
        <form method="post">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            
            <div class="form-group">
                <label>Nama Kategori <span style="color:#ef4444;">*</span></label>
                <input name="nama_kategori" class="form-control" required value="<?= htmlspecialchars($edit['nama_kategori'] ?? '') ?>" placeholder="Contoh: Fiksi, Sains...">
            </div>
            
            <div class="form-group">
                <label>Deskripsi <span style="color:#94a3b8; font-weight:normal;">(Opsional)</span></label>
                <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi singkat..."><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px;">
                <button type="button" class="btn-action" onclick="toggleForm()" style="background:#f1f5f9; color:#475569; border:none; padding:8px 16px;">Batal</button>
                <button type="submit" class="btn-primary" style="padding:8px 16px;">Simpan</button>
            </div>
        </form>
    </div>

    <!-- DAFTAR KATEGORI (DROPDOWN) -->
    <div class="dropdown-list">
        <?php if ($list->num_rows > 0): ?>
            <?php while ($k = $list->fetch_assoc()): ?>
                <div class="dropdown-item">
                    <!-- Header (Klik untuk buka) -->
                    <div class="dropdown-header" onclick="toggleDropdown(this)">
                        <div class="header-info">
                            <div class="icon-box"><i class="bi bi-tag-fill"></i></div>
                            <h4 class="header-title"><?= htmlspecialchars($k['nama_kategori']) ?></h4>
                        </div>
                        <div class="dropdown-icon"><i class="bi bi-chevron-down"></i></div>
                    </div>
                    
                    <!-- Body (Isi & Aksi) -->
                    <div class="dropdown-body">
                        <p class="item-desc">
                            <?= !empty($k['deskripsi']) ? htmlspecialchars($k['deskripsi']) : '<em style="color:#94a3b8;">Tidak ada deskripsi untuk kategori ini.</em>' ?>
                        </p>
                        <div class="action-group">
                            <a href="?edit=<?= $k['id'] ?>" class="btn-action btn-edit">
                                <i class="bi bi-pencil-square"></i> Edit
                            </a>
                            <a href="?hapus=<?= $k['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                <i class="bi bi-trash-fill"></i> Hapus
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-folder-x" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                <p style="margin: 0; font-size: 13px; color: #64748b;">
                    <?= $search !== '' ? 'Kategori tidak ditemukan.' : 'Belum ada kategori.' ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    // Fungsi Buka-Tutup Form Panel
    function toggleForm() {
        const panel = document.getElementById('formPanel');
        panel.classList.toggle('active');
        if (panel.classList.contains('active')) {
            panel.querySelector('input[name="nama_kategori"]').focus();
        }
    }

    // Fungsi Buka-Tutup Dropdown List
    function toggleDropdown(element) {
        const parent = element.closest('.dropdown-item');
        
        // Opsional: Tutup dropdown lain yang sedang terbuka agar rapi
        document.querySelectorAll('.dropdown-item').forEach(item => {
            if (item !== parent) item.classList.remove('active');
        });

        parent.classList.toggle('active');
    }
</script>

<?php require_once '../includes/footer.php'; ?>