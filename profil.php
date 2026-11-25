<?php
require_once 'config/database.php';
// Catatan: Jika file ini diletakkan di dalam folder (misal auth/profil.php), 
// ubah path require_once menjadi '../config/database.php'.

if (!isset($_SESSION['user_id'])) {
    redirect('auth/login.php');
}

$id_user = (int)$_SESSION['user_id'];
$msg = '';

if (isset($_POST['upload_foto'])) {
    $foto = $_FILES['foto'];
    
    if ($foto['error'] === 0) {
        $ext = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (in_array($ext, $allowed)) {
            $nama_file = 'profil_' . $id_user . '_' . time() . '.' . $ext;
            $target_dir = 'assets/img/profil/'; // Pastikan folder ini sejajar dengan file ini
            
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            // Hapus foto lama
            $q_lama = $conn->query("SELECT foto FROM users WHERE id=$id_user")->fetch_assoc();
            if ($q_lama && !empty($q_lama['foto']) && file_exists($target_dir . $q_lama['foto'])) {
                unlink($target_dir . $q_lama['foto']);
            }

            // Upload
            if (move_uploaded_file($foto['tmp_name'], $target_dir . $nama_file)) {
                $conn->query("UPDATE users SET foto='$nama_file' WHERE id=$id_user");
                $_SESSION['foto'] = $nama_file; // Update session
                flash('success', 'Foto profil berhasil diperbarui!');
                redirect('profil.php'); // Refresh halaman
            } else {
                $msg = 'Gagal mengunggah file.';
            }
        } else {
            $msg = 'Format harus JPG, JPEG, atau PNG.';
        }
    }
}

$user = $conn->query("SELECT * FROM users WHERE id=$id_user")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Profil — Perpustakaan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { font-family: sans-serif; background: #f4f7f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; text-align: center; }
        h2 { color: #0f5132; margin-top: 0; margin-bottom: 20px; }
        .preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #0f5132; margin-bottom: 15px; }
        .preview-initial { width: 120px; height: 120px; border-radius: 50%; background: #0f5132; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 48px; margin: 0 auto 15px; font-weight: bold; }
        input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 15px; box-sizing: border-box; }
        button { background: #0f5132; color: #fff; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; width: 100%; }
        button:hover { background: #146c43; }
        .btn-back { display: inline-block; margin-top: 15px; color: #64748b; text-decoration: none; font-size: 14px; }
        .alert { background: #d1fae5; color: #0f5132; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-weight: 600; }
        .alert-danger { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<div class="card">
    <h2>Ganti Foto Profil</h2>
    
    <?php if ($m = flash('success')): ?>
        <div class="alert"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
        <div class="alert alert-danger"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Tampilkan Foto -->
    <?php if (!empty($user['foto']) && file_exists('assets/img/profil/'.$user['foto'])): ?>
        <img src="<?= base_url('assets/img/profil/'.$user['foto']) ?>" class="preview">
    <?php else: ?>
        <div class="preview-initial"><?= strtoupper(substr($_SESSION['nama'], 0, 1)) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="foto" accept="image/jpeg, image/png" required>
        <button type="submit" name="upload_foto">Simpan Foto</button>
    </form>

    <?php 
    // Deteksi otomatis role untuk tombol kembali
    $kembali = 'index.php';
    if ($_SESSION['role'] === 'admin') $kembali = 'admin/dashboard.php';
    elseif ($_SESSION['role'] === 'petugas') $kembali = 'petugas/dashboard.php';
    elseif ($_SESSION['role'] === 'anggota') $kembali = 'anggota/dashboard.php';
    ?>
    <a href="<?= base_url($kembali) ?>" class="btn-back"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>
</div>

</body>
</html>