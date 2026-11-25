<?php
$title = 'Kelola Buku';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

/* ============================================================
   HANDLE AJAX UNTUK MENAMPILKAN DAFTAR BUKU
   ============================================================ */
if (isset($_GET['action']) && $_GET['action'] == 'fetch_buku') {
    $cari = trim($_GET['cari'] ?? '');
    $filter_kat = (int)($_GET['filter_kat'] ?? 0);

    // Query menggunakan GROUP_CONCAT untuk multi-kategori dan multi-genre
    $sql = "SELECT b.*, 
            GROUP_CONCAT(DISTINCT k.nama_kategori SEPARATOR ', ') as nama_kategori,
            GROUP_CONCAT(DISTINCT g.nama_genre SEPARATOR ', ') as nama_genre
            FROM buku b 
            LEFT JOIN buku_kategori bk ON b.id = bk.buku_id 
            LEFT JOIN kategori k ON bk.kategori_id = k.id 
            LEFT JOIN buku_genre bg ON b.id = bg.buku_id 
            LEFT JOIN genre g ON bg.genre_id = g.id 
            WHERE 1=1";

    if (!empty($cari)) {
        $cari_esc = $conn->real_escape_string($cari);
        $sql .= " AND (b.judul LIKE '%$cari_esc%' OR b.pengarang LIKE '%$cari_esc%' OR b.isbn LIKE '%$cari_esc%')";
    }

    if ($filter_kat > 0) {
        $sql .= " AND b.id IN (SELECT buku_id FROM buku_kategori WHERE kategori_id = $filter_kat)";
    }

    $sql .= " GROUP BY b.id ORDER BY b.id DESC";
    $query = $conn->query($sql);

    if ($query->num_rows > 0) {
        while ($b = $query->fetch_assoc()) {
            $cover_html = '<i class="bi bi-book"></i>';
            if (!empty($b['cover']) && file_exists('../assets/img/cover/' . $b['cover'])) {
                $cover_html = '<img src="../assets/img/cover/'.htmlspecialchars($b['cover']).'" alt="Cover">';
            } elseif (!empty($b['cover']) && filter_var($b['cover'], FILTER_VALIDATE_URL)) {
                $cover_html = '<img src="'.htmlspecialchars($b['cover']).'" alt="Cover">';
            }

            $pdf_badge = !empty($b['file_pdf']) ? '<span style="color:#38a169;"><i class="bi bi-file-pdf"></i> PDF</span>' : '<span style="color:#e53e3e;"><i class="bi bi-x-circle"></i> No PDF</span>';
            $kategori_name = $b['nama_kategori'] ?? 'Umum';
            $genre_name = $b['nama_genre'] ?? '-';
            
            ?>
            <div class="buku-card-item">
                <input type="checkbox" name="pilih_buku[]" value="<?= $b['id'] ?>" class="cek-box cek-item buku-id">
                
                <div class="buku-thumb">
                    <?= $cover_html ?>
                </div>
                
                <div class="buku-details">
                    <div class="judul"><?= htmlspecialchars($b['judul']) ?></div>
                    <div class="meta">
                        <span><i class="bi bi-tag"></i> <?= htmlspecialchars($kategori_name) ?></span> • 
                        <span><i class="bi bi-bookmark"></i> <?= htmlspecialchars($genre_name) ?></span> • 
                        <?= $pdf_badge ?> • 
                        <span><i class="bi bi-box-seam"></i> Stok: <?= $b['stok_tersedia'] ?>/<?= $b['stok'] ?></span>
                    </div>
                </div>
                
                <div class="buku-actions" style="display: flex; gap: 6px; align-items: center;">
                    <form method="POST" style="display: flex; gap: 4px; margin: 0;">
                        <input type="hidden" name="update_stok_langsung" value="1">
                        <input type="hidden" name="id_buku_update" value="<?= $b['id'] ?>">
                        <input type="number" name="stok_baru" value="<?= $b['stok'] ?>" min="0" style="width: 60px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-weight: 700; color: #0f5132;">
                        <button type="submit" class="btn btn-stok" style="padding: 6px 10px;" title="Simpan Stok"><i class="bi bi-check-lg"></i></button>
                    </form>
                    
                    <a href="buku.php?edit=<?= $b['id'] ?>" class="btn btn-edit"><i class="bi bi-pencil"></i> Edit</a>
                    <a href="buku.php?hapus=<?= $b['id'] ?>" class="btn btn-delete" onclick="return confirm('Yakin ingin menghapus buku ini?')"><i class="bi bi-trash"></i> Hapus</a>
                </div>
            </div>
            <?php
        }
    } else {
        echo '<div style="text-align:center; padding:40px; color:#64748b;">Tidak ada buku yang ditemukan.</div>';
    }
    exit;
}

/* ============================================================
   HANDLE UPDATE STOK LANGSUNG
   ============================================================ */
if (isset($_POST['update_stok_langsung'])) {
    $id_buku = (int)$_POST['id_buku_update'];
    $stok_baru = (int)$_POST['stok_baru'];
    
    if ($stok_baru >= 0) {
        $b = $conn->query("SELECT stok, stok_tersedia FROM buku WHERE id = $id_buku")->fetch_assoc();
        if ($b) {
            $selisih = $stok_baru - $b['stok'];
            $stok_tersedia_baru = $b['stok_tersedia'] + $selisih;
            if ($stok_tersedia_baru < 0) $stok_tersedia_baru = 0;

            $conn->query("UPDATE buku SET stok = $stok_baru, stok_tersedia = $stok_tersedia_baru WHERE id = $id_buku");
            flash('success', "Stok buku berhasil diperbarui menjadi $stok_baru!");
        }
    }
    redirect('admin/buku.php');
}

/* ============================================================
   HANDLE EDIT KATEGORI MASSAL
   ============================================================ */
if (isset($_POST['edit_kategori_massal']) && !empty($_POST['pilih_buku']) && !empty($_POST['kategori_baru'])) {
    $ids = $_POST['pilih_buku'];
    $id_list = implode(',', array_map('intval', $ids));
    $kategori_baru = (int)$_POST['kategori_baru'];
    
    $conn->query("DELETE FROM buku_kategori WHERE buku_id IN ($id_list)");
    
    $values = [];
    foreach ($ids as $id) {
        $values[] = "(" . (int)$id . ", $kategori_baru)";
    }
    if (!empty($values)) {
        $conn->query("INSERT INTO buku_kategori (buku_id, kategori_id) VALUES " . implode(", ", $values));
    }

    flash('success', count($ids) . ' buku berhasil diubah kategorinya!');
    redirect('admin/buku.php');
}

/* ============================================================
   HANDLE IMPORT CSV (GENRE OPSIONAL DITAMBAHKAN)
   ============================================================ */
if (isset($_POST['import_csv'])) {
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $file_content = file_get_contents($_FILES['file_csv']['tmp_name']);
            $lines = explode("\n", $file_content);
            $is_header = true;
            
            $cover_default = "https://placehold.co/400x600/0f5132/FFF?text=Buku";

            $kategori_map = [];
            $q_kat = $conn->query("SELECT id, nama_kategori FROM kategori");
            while($r = $q_kat->fetch_assoc()) {
                $kategori_map[strtolower($r['nama_kategori'])] = $r['id'];
            }

            $genre_map = [];
            $q_gen = $conn->query("SELECT id, nama_genre FROM genre");
            while($r = $q_gen->fetch_assoc()) {
                $genre_map[strtolower($r['nama_genre'])] = $r['id'];
            }

            $berhasil = 0;

            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                $data = explode(';', $line);
                
                if ($is_header) { $is_header = false; continue; }

                $judul     = $conn->real_escape_string(trim($data[0] ?? ''));
                $tahun     = (int)(trim($data[1] ?? 0));
                $penerbit  = $conn->real_escape_string(trim($data[2] ?? ''));
                
                $stok_mentah = trim($data[3] ?? '');
                $stok = !empty($stok_mentah) ? (int) preg_replace('/[^0-9]/', '', $stok_mentah) : 1;
                
                $nama_kat  = trim($data[4] ?? '');
                $nama_gen  = trim($data[6] ?? ''); // Kolom ke-7 untuk genre
                
                $link_cover = trim($data[5] ?? '');
                $link_cover = str_replace('"', '', $link_cover); 
                $cover = !empty($link_cover) ? $conn->real_escape_string($link_cover) : $cover_default;

                if (!empty($judul) && $judul != 'Jumlah Buku: 106') {
                    // Insert Buku
                    $conn->query("INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, stok, stok_tersedia, lokasi_rak, deskripsi, cover, file_pdf) VALUES ('$judul', '', '$penerbit', $tahun, '', $stok, $stok, '', '', '$cover', '')");
                    $buku_id = $conn->insert_id;

                    // Insert Kategori
                    if (!empty($nama_kat)) {
                        $nama_kat_lower = strtolower($nama_kat);
                        $kategori_id = $kategori_map[$nama_kat_lower] ?? null;
                        if (!$kategori_id) {
                            $conn->query("INSERT INTO kategori (nama_kategori) VALUES ('$conn->real_escape_string($nama_kat)')");
                            $kategori_id = $conn->insert_id;
                            $kategori_map[$nama_kat_lower] = $kategori_id;
                        }
                        $conn->query("INSERT INTO buku_kategori (buku_id, kategori_id) VALUES ($buku_id, $kategori_id)");
                    }

                    // Insert Genre
                    if (!empty($nama_gen)) {
                        $nama_gen_lower = strtolower($nama_gen);
                        $genre_id = $genre_map[$nama_gen_lower] ?? null;
                        if (!$genre_id) {
                            $conn->query("INSERT INTO genre (nama_genre) VALUES ('$conn->real_escape_string($nama_gen)')");
                            $genre_id = $conn->insert_id;
                            $genre_map[$nama_gen_lower] = $genre_id;
                        }
                        $conn->query("INSERT INTO buku_genre (buku_id, genre_id) VALUES ($buku_id, $genre_id)");
                    }
                    $berhasil++;
                }
            }
            
            flash('success', "Import kilat selesai! $berhasil buku berhasil ditambahkan.");
        } else {
            flash('error', 'File harus berformat CSV!');
        }
    } else {
        flash('error', 'Gagal mengupload file.');
    }
    redirect('admin/buku.php');
}

/* ============================================================
   HANDLE HAPUS MASSAL & SATUAN
   ============================================================ */
if (isset($_POST['hapus_massal']) && !empty($_POST['pilih_buku'])) {
    $ids = $_POST['pilih_buku'];
    $id_list = implode(',', array_map('intval', $ids));
    $conn->query("DELETE FROM buku WHERE id IN ($id_list)");
    flash('success', count($ids) . ' buku berhasil dihapus sekaligus!');
    redirect('admin/buku.php');
}

if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $conn->query("DELETE FROM buku WHERE id=$id");
    flash('success', 'Buku berhasil dihapus!');
    redirect('admin/buku.php');
}

/* ============================================================
   HANDLE TAMBAH / UPDATE BUKU MANUAL (TAMBAH GENRE)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['hapus_massal']) && !isset($_POST['import_csv']) && !isset($_POST['update_stok_langsung']) && !isset($_POST['edit_kategori_massal'])) {
    $id        = $_POST['id'] ?? '';
    $judul     = trim($_POST['judul'] ?? '');
    $pengarang = trim($_POST['pengarang'] ?? '');
    $penerbit  = trim($_POST['penerbit'] ?? '');
    $tahun     = (int)($_POST['tahun_terbit'] ?? 0);
    $isbn      = trim($_POST['isbn'] ?? '');
    $kategori_ids = $_POST['kategori_id'] ?? []; 
    $genre_ids    = $_POST['genre_id'] ?? []; // Array Genre
    $lokasi    = trim($_POST['lokasi_rak'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $cover_name = $_POST['cover_lama'] ?? '';

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === 0) {
        $upload_dir = '../assets/img/cover/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $file_ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp']) && $_FILES['cover']['size'] <= 2097152) {
            if (!empty($cover_name) && file_exists($upload_dir . $cover_name)) unlink($upload_dir . $cover_name);
            $cover_name = 'cover_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            move_uploaded_file($_FILES['cover']['tmp_name'], $upload_dir . $cover_name);
        } else { flash('error', 'Format cover salah!'); redirect('admin/buku.php'); }
    } elseif (!empty($_POST['auto_cover_base64'])) {
        $upload_dir = '../assets/img/cover/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $data = $_POST['auto_cover_base64'];
        if (strpos($data, 'data:image') === 0) {
            list($type, $data) = explode(';', $data);
            list(, $data)      = explode(',', $data);
            $data = base64_decode($data);
            $cover_name = 'cover_auto_' . time() . '_' . rand(100, 999) . '.jpg';
            file_put_contents($upload_dir . $cover_name, $data);
        }
    } elseif (!empty($_POST['auto_cover_url'])) {
        $cover_name = trim($_POST['auto_cover_url']);
    }

    $pdf_name = $_POST['pdf_lama'] ?? '';
    if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === 0) {
        $pdf_dir = '../assets/file_buku/';
        if (!is_dir($pdf_dir)) mkdir($pdf_dir, 0755, true);
        if (strtolower(pathinfo($_FILES['file_pdf']['name'], PATHINFO_EXTENSION)) === 'pdf') {
            if (!empty($pdf_name) && file_exists($pdf_dir . $pdf_name)) unlink($pdf_dir . $pdf_name);
            $pdf_name = 'buku_' . time() . '_' . rand(100, 999) . '.pdf';
            move_uploaded_file($_FILES['file_pdf']['tmp_name'], $pdf_dir . $pdf_name);
        } else { flash('error', 'File harus PDF!'); redirect('admin/buku.php'); }
    }

    if ($id) {
        // UPDATE BUKU
        $stmt = $conn->prepare("UPDATE buku SET judul=?, pengarang=?, penerbit=?, tahun_terbit=?, isbn=?, lokasi_rak=?, deskripsi=?, cover=?, file_pdf=? WHERE id=?");
        $stmt->bind_param('ssssissssi', $judul, $pengarang, $penerbit, $tahun, $isbn, $lokasi, $deskripsi, $cover_name, $pdf_name, $id);
        $stmt->execute();
        
        // Update Pivot Kategori
        $conn->query("DELETE FROM buku_kategori WHERE buku_id=$id");
        if (!empty($kategori_ids)) {
            $values = [];
            foreach ($kategori_ids as $kat_id) {
                $values[] = "($id, " . (int)$kat_id . ")";
            }
            $conn->query("INSERT INTO buku_kategori (buku_id, kategori_id) VALUES " . implode(", ", $values));
        }

        // Update Pivot Genre
        $conn->query("DELETE FROM buku_genre WHERE buku_id=$id");
        if (!empty($genre_ids)) {
            $values = [];
            foreach ($genre_ids as $gen_id) {
                $values[] = "($id, " . (int)$gen_id . ")";
            }
            $conn->query("INSERT INTO buku_genre (buku_id, genre_id) VALUES " . implode(", ", $values));
        }
        
        flash('success', 'Informasi buku berhasil diupdate!');
    } else {
        // INSERT BUKU BARU
        $stok = (int)($_POST['stok'] ?? 1);
        $stmt = $conn->prepare("INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, stok, stok_tersedia, lokasi_rak, deskripsi, cover, file_pdf) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssiiisss', $judul, $pengarang, $penerbit, $tahun, $isbn, $stok, $stok, $lokasi, $deskripsi, $cover_name, $pdf_name);
        $stmt->execute();
        $new_id = $conn->insert_id;

        // Insert Pivot Kategori
        if (!empty($kategori_ids)) {
            $values = [];
            foreach ($kategori_ids as $kat_id) {
                $values[] = "($new_id, " . (int)$kat_id . ")";
            }
            $conn->query("INSERT INTO buku_kategori (buku_id, kategori_id) VALUES " . implode(", ", $values));
        }

        // Insert Pivot Genre
        if (!empty($genre_ids)) {
            $values = [];
            foreach ($genre_ids as $gen_id) {
                $values[] = "($new_id, " . (int)$gen_id . ")";
            }
            $conn->query("INSERT INTO buku_genre (buku_id, genre_id) VALUES " . implode(", ", $values));
        }

        flash('success', 'Buku baru berhasil ditambahkan!');
    }
    redirect('admin/buku.php');
}

$edit = null;
$edit_kategori_ids = [];
$edit_genre_ids = [];
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM buku WHERE id=$id")->fetch_assoc();
    
    // Ambil kategori buku ini
    $q_kat = $conn->query("SELECT kategori_id FROM buku_kategori WHERE buku_id=$id");
    while ($r = $q_kat->fetch_assoc()) {
        $edit_kategori_ids[] = $r['kategori_id'];
    }

    // Ambil genre buku ini
    $q_gen = $conn->query("SELECT genre_id FROM buku_genre WHERE buku_id=$id");
    while ($r = $q_gen->fetch_assoc()) {
        $edit_genre_ids[] = $r['genre_id'];
    }
}

$cari = trim($_GET['cari'] ?? '');
$kategoriList = $conn->query("SELECT * FROM kategori ORDER BY nama_kategori");
$genreList = $conn->query("SELECT * FROM genre ORDER BY nama_genre");
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
/* ... (SALIN CSS MILIKMU DARI SINI KE BAWAH, TIDAK ADA YANG DIUBAH) ... */
.buku-wrap, .buku-wrap *, .bcard, .buku-list { box-sizing: border-box !important; max-width: 100% !important; }
.buku-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 12px; flex-wrap: wrap; }
.buku-head h2 { font-size: 22px; font-weight: 700; color: #1a202c; margin: 0 0 4px 0; }
.buku-head p { font-size: 13px; color: #718096; margin: 0; }
.bcard { background: #fff; border: 1px solid #e9ecef; border-radius: 12px; margin-bottom: 20px; overflow: hidden; width: 100%; }
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
.search-bar { padding: 16px 22px; background: #fafbfc; border-bottom: 1px solid #f1f3f5; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; width: 100%; }
.search-bar input { flex: 1; min-width: 200px; padding: 10px 16px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13.5px; outline: none; background: #fff; }
.b-alert { padding: 14px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.b-alert-success { background: #f0fff4; color: #2f855a; border-left: 4px solid #38a169; }
.b-alert-error { background: #fef2f2; color: #991b1b; border-left: 4px solid #e53e3e; }

.upload-section { display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px dashed #cbd5e1; margin-bottom: 20px; width: 100%; }
.cover-preview-box { width: 130px; height: 180px; border-radius: 8px; background: #fff; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #cbd5e1; overflow: hidden; flex-shrink: 0; position: relative; }
.cover-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.cover-preview-box .loading-cover { position: absolute; inset: 0; background: rgba(255,255,255,0.85); display: none; align-items: center; justify-content: center; font-size: 12px; color: #0f5132; font-weight: 600; text-align: center; padding: 8px; }
.file-inputs-wrap { flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 16px; }
.file-input-group { display: flex; flex-direction: column; gap: 6px; }
.file-input-group label { font-size: 13px; font-weight: 700; color: #1e293b; }
.file-input-group input[type="file"] { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; background: #fff; cursor: pointer; font-family: inherit; color: #475569; }
.file-input-group small { font-size: 11.5px; color: #64748b; }

.buku-list { display: flex; flex-direction: column; gap: 10px; padding: 16px 22px; width: 100%; }
.buku-card-item { display: flex; align-items: center; gap: 16px; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; width: 100%; }
.buku-thumb { width: 50px; height: 70px; border-radius: 6px; background: #0f5132; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px; overflow: hidden; flex-shrink: 0; }
.buku-thumb img { width: 100%; height: 100%; object-fit: cover; }
.buku-details { flex: 1; min-width: 0; }
.buku-details .judul { font-weight: 700; color: #0f5132; font-size: 14px; margin-bottom: 4px; line-height: 1.3; }
.buku-details .meta { font-size: 11.5px; color: #718096; display: flex; gap: 6px; flex-wrap: wrap; }
.buku-actions { display: flex; gap: 6px; flex-shrink: 0; }

.bulk-header { display: flex; align-items: center; gap: 8px; padding: 12px 22px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; width: 100%; }
.cek-box { width: 16px; height: 16px; cursor: pointer; accent-color: #0f5132; }

.import-box { display: none; background: #f0fdf4; border: 1px dashed #0f5132; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
.import-box.active { display: block; }
.import-box h4 { margin: 0 0 10px 0; color: #0f5132; font-size: 15px; }
.import-box ul { margin: 0 0 15px 0; padding-left: 20px; font-size: 12.5px; color: #166534; }
.import-box .file-input-wrap { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

/* Styling Custom untuk Multi-Select (Checkbox) */
.kategori-checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 8px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    padding: 12px;
    border-radius: 8px;
    max-height: 150px;
    overflow-y: auto;
}
.kategori-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
    background: #fff;
    padding: 6px 10px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    transition: 0.2s;
    margin: 0;
}
.kategori-checkbox-group label:hover {
    border-color: #0f5132;
    background: #f0fdf4;
}
.kategori-checkbox-group input[type="checkbox"] {
    accent-color: #0f5132;
    width: 16px;
    height: 16px;
    margin: 0;
}
.kategori-checkbox-group input[type="checkbox"]:checked + span {
    font-weight: 700;
    color: #0f5132;
}

@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .buku-card-item { flex-wrap: wrap; padding: 12px; gap: 12px; }
    .buku-thumb { width: 45px; height: 60px; font-size: 20px; }
    .buku-details { width: calc(100% - 60px); }
    .buku-details .judul { font-size: 13px; }
    .buku-details .meta { font-size: 10px; }
    .buku-actions { width: 100%; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-top: 4px; border-top: 1px dashed #e2e8f0; padding-top: 10px; }
    .buku-actions .btn { padding: 8px 2px; font-size: 10px; border-radius: 6px; }
    .buku-actions .btn i { font-size: 12px; margin-bottom: 2px; }
    .bcard-head { padding: 12px 16px; }
    .bcard-body { padding: 16px; }
    .buku-list { padding: 12px; }
    .search-bar { padding: 12px 16px; }
    .kategori-checkbox-group { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="main">
    <div class="topbar">
        <h2>Kelola Buku</h2>
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
                <h2>Manajemen Koleksi Buku</h2>
                <p>Tambah, edit, atau hapus buku beserta file PDF digital</p>
            </div>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('importCsvBox').classList.toggle('active')" style="background: #0284c7; color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);">
                <i class="bi bi-file-earmark-spreadsheet"></i> Import dari CSV
            </button>
        </div>

        <div class="import-box" id="importCsvBox">
            <h4><i class="bi bi-info-circle"></i> Panduan Import CSV</h4>
            <ul>
                <li>Pastikan urutan kolom Excel: <b>Judul | Tahun | Penerbit | Stok | Kategori | Link Cover | Genre (Opsional)</b></li>
                <li>Gambar cover akan otomatis terisi dengan gambar default yang rapi.</li>
            </ul>
            <form method="post" enctype="multipart/form-data" class="file-input-wrap">
                <input type="file" name="file_csv" accept=".csv" class="form-control" style="flex:1; min-width:200px;" required>
                <button type="submit" name="import_csv" class="btn btn-primary"><i class="bi bi-upload"></i> Proses Import</button>
            </form>
        </div>

        <?php if ($m = flash('success')): ?>
            <div class="b-alert b-alert-success"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($m) ?></div>
        <?php endif; ?>
        <?php if ($m = flash('error')): ?>
            <div class="b-alert b-alert-error"><i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <div class="bcard">
            <div class="bcard-head">
                <h3><i class="bi bi-<?= $edit ? 'pencil-square' : 'plus-circle' ?>"></i> <?= $edit ? 'Edit Buku' : 'Tambah Buku Baru' ?></h3>
                <?php if ($edit): ?>
                    <a href="buku.php" class="btn btn-secondary" style="padding:7px 14px;font-size:12px;"><i class="bi bi-x"></i> Batal Edit</a>
                <?php endif; ?>
            </div>
            <div class="bcard-body">
                <form method="post" autocomplete="off" enctype="multipart/form-data" id="formBuku">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                    <input type="hidden" name="cover_lama" value="<?= $edit['cover'] ?? '' ?>">
                    <input type="hidden" name="pdf_lama" value="<?= $edit['file_pdf'] ?? '' ?>">
                    <input type="hidden" name="auto_cover_base64" id="auto_cover_base64" value="">
                    <input type="hidden" name="auto_cover_url" id="auto_cover_url" value="">

                    <div style="background:#f0fdf4; border:1px dashed #0f5132; padding:15px; border-radius:10px; margin-bottom:20px;">
                        <label style="font-weight:800; color:#0f5132; display:block; margin-bottom:8px;"><i class="bi bi-upc-scan"></i> Scan atau Ketik ISBN (Otomatis Isi Data)</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="scanIsbn" class="form-control" placeholder="Scan barcode / ketik ISBN di sini..." autofocus>
                            <button type="button" id="btnCariIsbn" class="btn btn-primary" onclick="cariBukuByIsbn()" style="white-space:nowrap; padding:10px 20px;"><i class="bi bi-search"></i> Cari Otomatis</button>
                        </div>
                        <small style="color:#64748b; display:block; margin-top:5px;">Data akan ditarik otomatis via API ISBN.</small>
                    </div>

                    <div class="upload-section">
                        <div class="cover-preview-box" id="coverPreview">
                            <?php if (!empty($edit['cover']) && file_exists('../assets/img/cover/' . $edit['cover'])): ?>
                                <img src="../assets/img/cover/<?= htmlspecialchars($edit['cover']) ?>" alt="Cover">
                            <?php elseif (!empty($edit['cover']) && filter_var($edit['cover'], FILTER_VALIDATE_URL)): ?>
                                <img src="<?= htmlspecialchars($edit['cover']) ?>" alt="Cover">
                            <?php else: ?>
                                <i class="bi bi-image"></i>
                            <?php endif; ?>
                            <div class="loading-cover" id="loadingCover">Memuat cover...</div>
                        </div>

                        <div class="file-inputs-wrap">
                            <div class="file-input-group">
                                <label><i class="bi bi-image"></i> Upload Gambar Cover (Opsional)</label>
                                <input type="file" name="cover" accept="image/*" onchange="previewManualCover(this)">
                                <small>Kosongkan jika menggunakan cover dari ISBN / halaman 1 PDF.</small>
                            </div>
                            
                            <div class="file-input-group">
                                <label><i class="bi bi-file-earmark-pdf"></i> Upload File PDF Buku (Opsional)</label>
                                <input type="file" name="file_pdf" accept="application/pdf" id="pdfInput" onchange="generateCoverFromPDF(this)">
                                <small style="color:#0f5132; font-weight:600;">
                                    <?php if (!empty($edit['file_pdf'])): ?>
                                        File saat ini: <b><?= htmlspecialchars($edit['file_pdf']) ?></b>
                                    <?php else: ?>
                                        Format .pdf (Jika ada versi digital).
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Judul Buku <span class="req">*</span></label>
                            <input type="text" name="judul" class="form-control" required placeholder="Contoh: Laskar Pelangi" value="<?= htmlspecialchars($edit['judul'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Pengarang</label>
                            <input type="text" name="pengarang" class="form-control" placeholder="Contoh: Andrea Hirata" value="<?= htmlspecialchars($edit['pengarang'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Penerbit</label>
                            <input type="text" name="penerbit" class="form-control" placeholder="Contoh: Bentang Pustaka" value="<?= htmlspecialchars($edit['penerbit'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Tahun Terbit</label>
                            <input type="number" name="tahun_terbit" class="form-control" placeholder="<?= date('Y') ?>" min="1900" max="<?= date('Y') ?>" value="<?= htmlspecialchars($edit['tahun_terbit'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>ISBN</label>
                            <input type="text" name="isbn" class="form-control" placeholder="Contoh: 9789793062792" value="<?= htmlspecialchars($edit['isbn'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group full">
                            <label>Kategori Buku (Bisa pilih lebih dari 1) <span class="req">*</span></label>
                            <div class="kategori-checkbox-group">
                                <?php $kategoriList->data_seek(0); while ($k = $kategoriList->fetch_assoc()): ?>
                                    <label>
                                        <input type="checkbox" name="kategori_id[]" value="<?= $k['id'] ?>" <?= in_array($k['id'], $edit_kategori_ids) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($k['nama_kategori']) ?></span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- TAMBAHAN: MULTI-SELECT GENRE -->
                        <div class="form-group full">
                            <label>Genre Buku (Bisa pilih lebih dari 1)</label>
                            <div class="kategori-checkbox-group">
                                <?php $genreList->data_seek(0); while ($g = $genreList->fetch_assoc()): ?>
                                    <label>
                                        <input type="checkbox" name="genre_id[]" value="<?= $g['id'] ?>" <?= in_array($g['id'], $edit_genre_ids) ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($g['nama_genre']) ?></span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Stok <?= $edit ? '<small style="color:#ca8a04; font-weight:normal;">(Gunakan form edit stok di daftar buku)</small>' : '<span class="req">*</span>' ?></label>
                            <input type="number" name="stok" class="form-control" <?= $edit ? 'readonly' : 'required' ?> min="1" placeholder="5" value="<?= htmlspecialchars($edit['stok'] ?? 1) ?>" <?= $edit ? 'style="background:#e2e8f0; cursor:not-allowed;"' : '' ?>>
                        </div>
                        <div class="form-group">
                            <label>Lokasi Rak</label>
                            <input type="text" name="lokasi_rak" class="form-control" placeholder="Contoh: A-01" value="<?= htmlspecialchars($edit['lokasi_rak'] ?? '') ?>">
                        </div>
                        <div class="form-group full">
                            <label>Deskripsi / Sinopsis</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Ringkasan isi buku..."><?= htmlspecialchars($edit['deskripsi'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-<?= $edit ? 'check-lg' : 'plus-lg' ?>"></i> <?= $edit ? 'Update Buku' : 'Simpan Buku' ?></button>
                        <?php if ($edit): ?>
                            <a href="buku.php" class="btn btn-secondary"><i class="bi bi-x-lg"></i> Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="bcard">
            <div class="bcard-head">
                <h3><i class="bi bi-collection"></i> Daftar Koleksi Buku <span class="live-indicator" style="font-size:10px; padding:2px 8px; margin-left:8px; display:inline-flex; align-items:center; gap:4px; background:#f0fdf4; color:#16a34a; border-radius:10px; border:1px solid #bbf7d0;"><span class="live-dot" style="width:6px; height:6px; background:#16a34a; border-radius:50%; animation:pulse 1.5s infinite;"></span> LIVE</span></h3>
                <span style="font-size:12px;color:#718096;font-weight:600;" id="totalBukuText"></span>
            </div>
            
            <form method="get" class="search-bar" onsubmit="event.preventDefault(); fetchBukuData();">
                <i class="bi bi-search" style="color:#718096;font-size:15px;"></i>
                <input type="text" name="cari" id="searchInput" placeholder="Cari judul, pengarang..." value="<?= htmlspecialchars($cari) ?>" onkeyup="fetchBukuDataDebounced()">
                
                <select id="filterKategori" class="form-control" style="width: auto; min-width: 150px; max-width: 250px;" onchange="fetchBukuData()">
                    <option value="0">Semua Kategori</option>
                    <?php $kategoriList->data_seek(0); while ($k = $kategoriList->fetch_assoc()): ?>
                        <option value="<?= $k['id'] ?>">
                            <?= htmlspecialchars($k['nama_kategori']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="btn btn-primary" style="padding:10px 20px;">Cari</button>
            </form>

            <form method="post" id="formHapus"> 
                <div class="bulk-header">
                    <input type="checkbox" class="cek-box" id="checkAll" title="Pilih Semua">
                    <span style="font-size:13px; font-weight:600; color:#475569;">Pilih Semua</span>
                </div>

                <div class="buku-list" id="bukuContainer">
                    <div style="text-align:center; padding:40px; color:#64748b;">
                        <i class="bi bi-arrow-repeat" style="display:inline-block; animation: spin 1s linear infinite;"></i> Memuat data...
                    </div>
                </div>
                
                <!-- AREA EDIT MASSAL (MUNCUL SAAT ADA YANG DICENTANG) -->
                <div id="bulkActionContainer" style="display: none; padding: 15px 22px; background: #fef2f2; border-top: 1px solid #fee2e2; text-align: right; position: sticky; bottom: 0; z-index: 10; box-shadow: 0 -4px 10px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span style="color: #991b1b; font-weight: 600; font-size: 14px;">
                            <span id="countSelected">0</span> buku dipilih
                        </span>
                        
                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            <!-- Form Edit Kategori Massal -->
                            <select name="kategori_baru" class="form-control" style="width: auto; padding: 8px 12px; font-size: 13px;">
                                <option value="">-- Pilih Kategori Baru --</option>
                                <?php 
                                $kat_query = $conn->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori");
                                while ($k = $kat_query->fetch_assoc()): 
                                ?>
                                    <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" name="edit_kategori_massal" class="btn btn-stok" style="background: #eab308; color: #fff; border: none; padding: 10px 15px;">
                                <i class="bi bi-pencil-square"></i> Ubah Kategori
                            </button>
                            
                            <!-- Tombol Hapus Massal -->
                            <button type="button" onclick="konfirmasiHapusMassal()" class="btn btn-delete" style="background: #e53e3e; color: #fff; padding: 10px 15px;">
                                <i class="bi bi-trash-fill"></i> Hapus Terpilih
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); } 100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); } }
</style>

<script>
// (SALIN SEMUA JS MILIKMU DARI SINI KE BAWAH, TIDAK ADA YANG DIUBAH)
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
let lastBukuData = ""; let debounceTimer;

async function cariBukuByIsbn() {
    const isbnInput = document.getElementById('scanIsbn');
    const isbn = isbnInput.value.replace(/[^0-9X]/gi, '');
    if (isbn.length < 10) { alert('ISBN tidak valid. Minimal 10 digit angka.'); return; }

    const btn = document.getElementById('btnCariIsbn');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-arrow-repeat" style="display:inline-block; animation:spin 1s linear infinite;"></i> Mencari...';
    btn.disabled = true;

    try {
        const response = await fetch(`api_isbn.php?isbn=${encodeURIComponent(isbn)}`);
        const data = await response.json();
        if (data.status === 'success') {
            document.querySelector('input[name="judul"]').value = data.judul || '';
            document.querySelector('input[name="pengarang"]').value = data.pengarang || '';
            document.querySelector('input[name="penerbit"]').value = data.penerbit || '';
            document.querySelector('input[name="tahun_terbit"]').value = data.tahun || '';
            document.querySelector('input[name="isbn"]').value = isbn;
            if (data.cover) {
                document.getElementById('coverPreview').innerHTML = '<img src="' + data.cover + '" alt="Cover">';
                document.getElementById('auto_cover_url').value = data.cover;
                document.getElementById('auto_cover_base64').value = '';
            }
            alert(`Data buku berhasil ditemukan dari ${data.source || 'API'}!`);
        } else { alert(data.message || 'Buku tidak ditemukan.'); }
    } catch (error) {
        console.error(error); alert('Gagal terhubung ke server API ISBN.');
    } finally {
        btn.innerHTML = originalHtml; btn.disabled = false; isbnInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const scanInput = document.getElementById('scanIsbn');
    if (scanInput) { scanInput.addEventListener('keypress', function(e) { if (e.key === 'Enter') { e.preventDefault(); cariBukuByIsbn(); } }); }
    fetchBukuData();
});

function fetchBukuData() {
    const cari = document.getElementById('searchInput').value;
    const filterKat = document.getElementById('filterKategori').value;
    const container = document.getElementById('bukuContainer');
    fetch(`buku.php?action=fetch_buku&cari=${encodeURIComponent(cari)}&filter_kat=${filterKat}`)
        .then(response => response.text())
        .then(data => {
            if (data !== lastBukuData) {
                const checkedIds = Array.from(document.querySelectorAll('.buku-id:checked')).map(cb => cb.value);
                container.innerHTML = data; lastBukuData = data;
                document.querySelectorAll('.buku-id').forEach(cb => { if (checkedIds.includes(cb.value)) cb.checked = true; });
                document.getElementById('totalBukuText').innerText = document.querySelectorAll('.buku-id').length + ' buku';
                updateBulkAction(); attachCheckboxListeners();
            }
        }).catch(err => { container.innerHTML = `<div style="text-align:center;padding:40px;color:#dc2626;">Gagal memuat data.</div>`; });
}

function fetchBukuDataDebounced() { clearTimeout(debounceTimer); debounceTimer = setTimeout(fetchBukuData, 400); }

function updateBulkAction() {
    const checkedCount = document.querySelectorAll('.buku-id:checked').length;
    document.getElementById('bulkActionContainer').style.display = checkedCount > 0 ? 'block' : 'none';
    document.getElementById('countSelected').textContent = checkedCount;
    document.getElementById('checkAll').checked = (checkedCount > 0 && checkedCount === document.querySelectorAll('.buku-id').length);
}

function attachCheckboxListeners() {
    const checkAll = document.getElementById('checkAll');
    if (!checkAll) return;
    const newCheckAll = checkAll.cloneNode(true);
    checkAll.parentNode.replaceChild(newCheckAll, checkAll);
    newCheckAll.addEventListener('change', function() {
        document.querySelectorAll('.buku-id').forEach(item => { item.checked = this.checked; });
        updateBulkAction();
    });
    document.querySelectorAll('.buku-id').forEach(item => { item.onclick = function() { updateBulkAction(); }; });
}

function konfirmasiHapusMassal() {
    const checked = document.querySelectorAll('.buku-id:checked');
    if(checked.length === 0) { alert('Pilih minimal satu buku untuk dihapus!'); return; }
    if(confirm(`Yakin ingin menghapus ${checked.length} buku terpilih secara permanen?`)) {
        const form = document.createElement('form'); form.method = 'POST'; form.action = 'buku.php';
        const actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'hapus_massal'; actionInput.value = '1'; form.appendChild(actionInput);
        checked.forEach(cb => { const idInput = document.createElement('input'); idInput.type = 'hidden'; idInput.name = 'pilih_buku[]'; idInput.value = cb.value; form.appendChild(idInput); });
        document.body.appendChild(form); form.submit();
    }
}

function previewManualCover(input) {
    const preview = document.getElementById('coverPreview');
    const autoInputBase64 = document.getElementById('auto_cover_base64');
    const autoInputUrl = document.getElementById('auto_cover_url');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">'; };
        reader.readAsDataURL(input.files[0]);
        autoInputBase64.value = ''; autoInputUrl.value = '';
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
        canvas.height = viewport.height; canvas.width = viewport.width;
        await page.render({ canvasContext: context, viewport: viewport }).promise;
        const imgData = canvas.toDataURL('image/jpeg', 0.8);
        preview.innerHTML = '<img src="' + imgData + '" alt="Auto Cover">';
        autoInput.value = imgData;
    } catch (error) {
        console.error("Gagal ekstrak cover dari PDF: ", error);
        alert("Gagal membuat cover otomatis. Silakan upload gambar cover secara manual.");
    } finally { loadingCover.style.display = 'none'; }
}
</script>

<?php require_once '../includes/footer.php'; ?>