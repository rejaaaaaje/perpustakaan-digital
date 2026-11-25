<?php
$title = 'Kelola User';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

date_default_timezone_set('Asia/Jakarta');

// Proses Simpan / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $telp = $_POST['no_telp'];

    if ($id) {
        if (!empty($_POST['password'])) {
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET nama=?,email=?,password=?,role=?,status=?,no_telp=? WHERE id=?");
            $stmt->bind_param('ssssssi', $nama,$email,$pass,$role,$status,$telp,$id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nama=?,email=?,role=?,status=?,no_telp=? WHERE id=?");
            $stmt->bind_param('sssssi', $nama,$email,$role,$status,$telp,$id);
        }
        flash('success','User berhasil diperbarui!');
    } else {
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (nama,email,password,role,status,no_telp) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssssss', $nama,$email,$pass,$role,$status,$telp);
        flash('success','User baru berhasil ditambahkan!');
    }
    $stmt->execute();
    redirect('admin/user.php');
}

// Proses Hapus
if (isset($_GET['hapus']) && $_GET['hapus'] != $_SESSION['user_id']) {
    $conn->query("DELETE FROM users WHERE id=".(int)$_GET['hapus']);
    flash('success','User berhasil dihapus!');
    redirect('admin/user.php');
}

// Ambil data edit (jika ada)
$edit = null;
if (isset($_GET['edit'])) {
    $edit = $conn->query("SELECT * FROM users WHERE id=".(int)$_GET['edit'])->fetch_assoc();
}

// FITUR PENCARIAN USER
$keyword = isset($_GET['q']) ? $conn->real_escape_string(trim($_GET['q'])) : '';

$sql = "SELECT * FROM users";
if ($keyword !== '') {
    $sql .= " WHERE nama LIKE '%$keyword%' OR email LIKE '%$keyword%' OR no_telp LIKE '%$keyword%'";
}
$sql .= " ORDER BY id DESC";
$list = $conn->query($sql);
?>

<style>
    /* Warna Utama */
    :root {
        --primary: #0f5132;
        --primary-hover: #146c43;
        --border-color: #e2e8f0;
    }

    /* Header Halaman */
    .page-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;
    }
    .page-title h2 { margin: 0 0 6px 0; font-size: 24px; font-weight: 800; color: #1e293b; }
    .page-title p { margin: 0; font-size: 14px; color: #64748b; }
    
    .btn-primary {
        background: var(--primary); color: #fff; padding: 12px 24px; border-radius: 8px;
        font-weight: 700; font-size: 14px; border: none; cursor: pointer; text-decoration: none; transition: 0.2s;
        display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(15,81,50,0.2);
    }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(15,81,50,0.3); }

    /* Search & Filter Bar */
    .toolbar {
        display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;
    }
    .search-box { position: relative; width: 100%; max-width: 400px; flex: 1; }
    .search-box input {
        width: 100%; padding: 12px 16px 12px 42px; border-radius: 10px;
        border: 1.5px solid var(--border-color); font-size: 14px; outline: none; transition: 0.2s; background: #fff;
    }
    .search-box input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(15,81,50,0.08); }
    .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; }

    /* =========================================
       TAMPILAN TABEL MODERN (DESKTOP)
       ========================================= */
    .table-responsive {
        background: #fff; border-radius: 12px; border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); overflow-x: auto;
    }
    .modern-table {
        width: 100%; border-collapse: collapse; min-width: 800px; text-align: left;
    }
    .modern-table th {
        background: #f8fafc; padding: 16px 24px; font-size: 13px; font-weight: 700; color: #64748b;
        text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color);
    }
    .modern-table td {
        padding: 16px 24px; border-bottom: 1px solid var(--border-color); vertical-align: middle;
    }
    .modern-table tr:last-child td { border-bottom: none; }
    .modern-table tr:hover { background: #f8fafc; }

    /* User Info Cell */
    .user-cell { display: flex; align-items: center; gap: 16px; }
    .avatar {
        width: 44px; height: 44px; border-radius: 50%; background: var(--primary-light);
        color: var(--primary); display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 16px; flex-shrink: 0; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .user-info-text h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 700; color: #1e293b; }
    .user-info-text p { margin: 0; font-size: 13px; color: #64748b; }

    /* Badges */
    .badge-group { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .badge {
        font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .badge-admin { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-petugas { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .badge-anggota { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    
    .badge-aktif { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .badge-nonaktif { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    /* Status Online */
    .status-online {
        display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #64748b;
    }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .dot-online { background-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.2); }
    .dot-offline { background-color: #cbd5e1; }

    /* Tombol Aksi */
    .action-buttons { display: flex; gap: 8px; justify-content: flex-end; }
    .btn-action {
        width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
        font-size: 16px; text-decoration: none; border: none; cursor: pointer; transition: 0.2s;
    }
    .btn-edit { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .btn-edit:hover { background: #fef3c7; transform: translateY(-2px); }
    .btn-delete { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .btn-delete:hover { background: #fee2e2; transform: translateY(-2px); }
    .btn-disabled { background: #f1f5f9; color: #cbd5e1; border: 1px solid #e2e8f0; cursor: not-allowed; }

    /* =========================================
       TAMPILAN KARTU MOBILE (UNTUK HP)
       ========================================= */
    .user-cards-mobile { display: none; flex-direction: column; gap: 16px; }
    .user-card-mobile {
        background: #fff; border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .card-header-mobile { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
    
    @media (max-width: 900px) {
        .table-responsive { display: none; }
        .user-cards-mobile { display: flex; }
        
        .page-header { flex-direction: column; align-items: flex-start; }
        .btn-primary { width: 100%; justify-content: center; }
        .search-box { max-width: 100%; }
    }

    /* Modal */
    .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px; }
    .modal-overlay.active { display: flex; }
    .modal-box { background: #fff; width: 100%; max-width: 520px; border-radius: 16px; padding: 32px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; }
    .modal-header h3 { margin: 0; font-size: 20px; font-weight: 800; color: #1e293b; }
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-size: 14px; font-weight: 700; margin-bottom: 8px; color: #334155; }
    .form-control { width: 100%; padding: 12px 16px; border: 1.5px solid var(--border-color); border-radius: 10px; font-size: 14px; outline: none; transition: 0.2s; background: #f8fafc; }
    .form-control:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 4px rgba(15,81,50,0.08); }
</style>

<div class="main">
    <div class="content">
        
        <!-- HEADER HALAMAN -->
        <div class="page-header">
            <div class="page-title">
                <h2>Manajemen Pengguna</h2>
                <p>Kelola data admin, petugas, dan anggota perpustakaan.</p>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <i class="bi bi-person-plus-fill"></i> Tambah User Baru
            </button>
        </div>

        <?php if ($m = flash('success')): ?>
            <div class="alert alert-success" style="background:#d1fae5; color:#065f46; border-left:4px solid #10b981; border-radius:10px; margin-bottom:24px; padding:16px 20px; font-weight:600; font-size:14px; display:flex; align-items:center; gap:10px;">
                <i class="bi bi-check-circle-fill" style="font-size:18px;"></i> <?= $m ?>
            </div>
        <?php endif; ?>

        <!-- TOOLBAR PENCARIAN -->
        <div class="toolbar">
            <div class="search-box">
                <form method="GET" action="">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" placeholder="Cari nama, email, atau nomor telepon..." value="<?= htmlspecialchars($keyword) ?>">
                </form>
            </div>
        </div>

        <?php if ($list->num_rows > 0): 
            $now = time(); 
            $users = [];
            while ($u = $list->fetch_assoc()) { $users[] = $u; }
        ?>

            <!-- 1. TAMPILAN TABEL (DESKTOP) -->
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Informasi User</th>
                            <th>Role Akses</th>
                            <th>Status Akun</th>
                            <th>Kehadiran</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): 
                            $is_online = false;
                            if (!empty($u['last_activity'])) {
                                if (($now - strtotime($u['last_activity'])) <= 5) { $is_online = true; }
                            }
                            
                            // Tentukan class badge role
                            $role_class = 'badge-anggota';
                            if ($u['role'] === 'admin') $role_class = 'badge-admin';
                            if ($u['role'] === 'petugas') $role_class = 'badge-petugas';
                        ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <?php if (!empty($u['foto']) && file_exists('../assets/img/profil/' . $u['foto'])): ?>
                                        <img src="../assets/img/profil/<?= htmlspecialchars($u['foto']) ?>" class="avatar">
                                    <?php else: ?>
                                        <div class="avatar"><?= strtoupper(substr($u['nama'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                    <div class="user-info-text">
                                        <h4><?= htmlspecialchars($u['nama']) ?></h4>
                                        <p><i class="bi bi-envelope" style="margin-right:4px;"></i> <?= htmlspecialchars($u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $role_class ?>"><?= ucfirst($u['role']) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $u['status'] == 'aktif' ? 'badge-aktif' : 'badge-nonaktif' ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="status-online" data-id="<?= $u['id'] ?>">
                                    <span class="status-dot <?= $is_online ? 'dot-online' : 'dot-offline' ?>"></span>
                                    <span class="status-label"><?= $is_online ? 'Online' : 'Offline' ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?= $u['id'] ?>" class="btn-action btn-edit" title="Edit User"><i class="bi bi-pencil-square"></i></a>
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="?hapus=<?= $u['id'] ?>" class="btn-action btn-delete" title="Hapus User"
                                           data-confirm="Yakin ingin menghapus user ini?"
                                           data-confirm-title="Hapus User"
                                           data-confirm-ok="Ya, Hapus"
                                           data-confirm-type="danger"><i class="bi bi-trash-fill"></i></a>
                                    <?php else: ?>
                                        <span class="btn-action btn-disabled" title="Tidak bisa menghapus diri sendiri"><i class="bi bi-trash-fill"></i></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- 2. TAMPILAN KARTU (MOBILE) -->
            <div class="user-cards-mobile">
                <?php foreach ($users as $u): 
                    $is_online = false;
                    if (!empty($u['last_activity'])) {
                        if (($now - strtotime($u['last_activity'])) <= 5) { $is_online = true; }
                    }
                    $role_class = 'badge-anggota';
                    if ($u['role'] === 'admin') $role_class = 'badge-admin';
                    if ($u['role'] === 'petugas') $role_class = 'badge-petugas';
                ?>
                <div class="user-card-mobile">
                    <div class="card-header-mobile">
                        <div class="user-cell">
                            <?php if (!empty($u['foto']) && file_exists('../assets/img/profil/' . $u['foto'])): ?>
                                <img src="../assets/img/profil/<?= htmlspecialchars($u['foto']) ?>" class="avatar">
                            <?php else: ?>
                                <div class="avatar"><?= strtoupper(substr($u['nama'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div class="user-info-text">
                                <h4><?= htmlspecialchars($u['nama']) ?></h4>
                                <p><?= htmlspecialchars($u['email']) ?></p>
                            </div>
                        </div>
                        <div class="status-online" data-id="<?= $u['id'] ?>">
                            <span class="status-dot <?= $is_online ? 'dot-online' : 'dot-offline' ?>"></span>
                        </div>
                    </div>
                    
                    <div class="badge-group" style="margin-bottom: 16px;">
                        <span class="badge <?= $role_class ?>"><?= ucfirst($u['role']) ?></span>
                        <span class="badge <?= $u['status'] == 'aktif' ? 'badge-aktif' : 'badge-nonaktif' ?>"><?= ucfirst($u['status']) ?></span>
                    </div>

                    <div class="action-buttons" style="justify-content: flex-start; border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <a href="?edit=<?= $u['id'] ?>" class="btn-action btn-edit" style="width:auto; padding: 0 16px; gap:8px;"><i class="bi bi-pencil-square"></i> Edit</a>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <a href="?hapus=<?= $u['id'] ?>" class="btn-action btn-delete" style="width:auto; padding: 0 16px; gap:8px;"
                               data-confirm="Yakin ingin menghapus user ini?"
                               data-confirm-title="Hapus User"
                               data-confirm-ok="Ya, Hapus"
                               data-confirm-type="danger"><i class="bi bi-trash-fill"></i> Hapus</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <i class="bi bi-people" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                <h3 style="margin:0 0 8px 0; color:#1e293b; font-size: 18px;">User Tidak Ditemukan</h3>
                <p style="margin:0; font-size:14px; color:#64748b;">Tidak ada pengguna yang cocok dengan kata kunci "<?= htmlspecialchars($keyword) ?>".</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL FORM (TAMBAH / EDIT) -->
<div class="modal-overlay" id="userModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><?= $edit ? 'Edit Informasi User' : 'Tambah User Baru' ?></h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:24px;color:#94a3b8;cursor:pointer;"><i class="bi bi-x-lg"></i></button>
        </div>
        
        <form method="post">
            <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
            
            <div class="form-group">
                <label>Nama Lengkap <span style="color:#ef4444;">*</span></label>
                <input name="nama" class="form-control" required value="<?= htmlspecialchars($edit['nama'] ?? '') ?>" placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label>Alamat Email <span style="color:#ef4444;">*</span></label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($edit['email'] ?? '') ?>" placeholder="contoh@email.com">
            </div>
            
            <div class="form-group">
                <label>No. Telepon / WhatsApp</label>
                <input name="no_telp" class="form-control" value="<?= htmlspecialchars($edit['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
            </div>

            <div class="form-group">
                <label>Password <?= $edit ? '<span style="font-weight:normal; color:#64748b;">(Biarkan kosong jika tidak diubah)</span>' : '<span style="color:#ef4444;">*</span>' ?></label>
                <input type="password" name="password" class="form-control" <?= $edit?'':'required' ?> placeholder="••••••••">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label>Role Akses</label>
                    <select name="role" class="form-control">
                        <?php foreach (['admin','petugas','anggota'] as $r): ?>
                            <option value="<?= $r ?>" <?= ($edit['role']??'')==$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Status Akun</label>
                    <select name="status" class="form-control">
                        <option value="aktif" <?= ($edit['status']??'')=='aktif'?'selected':'' ?>>Aktif</option>
                        <option value="nonaktif" <?= ($edit['status']??'')=='nonaktif'?'selected':'' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" style="padding: 12px 20px; border-radius: 8px; font-weight: 700; border: 1px solid var(--border-color); background: #fff; color: #64748b; cursor: pointer; transition: 0.2s;">Batal</button>
                <button type="submit" class="btn-primary"
                        data-confirm="<?= $edit ? 'Simpan perubahan data user?' : 'Tambahkan user ini ke sistem?' ?>"
                        data-confirm-title="Konfirmasi Simpan"
                        data-confirm-ok="Ya, Simpan"
                        data-confirm-type="default">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('userModal').classList.add('active'); }
    function closeModal() { document.getElementById('userModal').classList.remove('active'); }

    <?php if ($edit): ?>
        document.addEventListener('DOMContentLoaded', function() { openModal(); });
    <?php endif; ?>

    // AJAX Update Status Online Real-time
    function updateStatusOnline() {
        fetch('cek_status_user.php')
            .then(response => response.json())
            .then(data => {
                for (const [id, isOnline] of Object.entries(data)) {
                    const indicators = document.querySelectorAll(`.status-online[data-id="${id}"]`);
                    indicators.forEach(indicator => {
                        const dot = indicator.querySelector('.status-dot');
                        const label = indicator.querySelector('.status-label'); // Ada di desktop
                        
                        if (isOnline) {
                            dot.className = 'status-dot dot-online';
                            if (label) label.textContent = 'Online';
                        } else {
                            dot.className = 'status-dot dot-offline';
                            if (label) label.textContent = 'Offline';
                        }
                    });
                }
            })
            .catch(err => console.log('Gagal update status:', err));
    }

    document.addEventListener('DOMContentLoaded', updateStatusOnline);
    setInterval(updateStatusOnline, 10000);
</script>

<?php require_once '../includes/footer.php'; ?>