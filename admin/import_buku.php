<?php
$title = 'Import Buku CSV';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Proses jika file CSV diupload
if (isset($_POST['import'])) {
    $file = $_FILES['file_csv']['tmp_name'];
    $ext = strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION));

    if ($ext !== 'csv') {
        flash('error', 'File harus berformat CSV!');
        redirect('admin/import_buku.php');
    }

    $handle = fopen($file, "r");
    $row = 1;
    $berhasil = 0;
    
    // Lewati baris pertama (header judul kolom)
    fgetcsv($handle, 1000, ","); 

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Urutan kolom di CSV harus: 
        // Judul, Pengarang, Penerbit, Tahun, ISBN, Kategori ID, Stok, Lokasi Rak, Deskripsi
        $judul     = $conn->real_escape_string($data[0] ?? '');
        $pengarang = $conn->real_escape_string($data[1] ?? '');
        $penerbit  = $conn->real_escape_string($data[2] ?? '');
        $tahun     = (int)($data[3] ?? 0);
        $isbn      = $conn->real_escape_string($data[4] ?? '');
        $kategori  = !empty($data[5]) ? (int)$data[5] : 'NULL';
        $stok      = !empty($data[6]) ? (int)$data[6] : 1;
        $lokasi    = $conn->real_escape_string($data[7] ?? '');
        $deskripsi = $conn->real_escape_string($data[8] ?? '');

        if (!empty($judul)) {
            $sql = "INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, kategori_id, stok, stok_tersedia, lokasi_rak, deskripsi) 
                    VALUES ('$judul', '$pengarang', '$penerbit', $tahun, '$isbn', $kategori, $stok, $stok, '$lokasi', '$deskripsi')";
            
            if ($conn->query($sql)) {
                $berhasil++;
            }
        }
        $row++;
    }
    fclose($handle);
    flash('success', "$berhasil buku berhasil diimport dari CSV!");
    redirect('admin/buku.php');
}
?>

<div class="main">
    <div class="topbar">
        <h2>Import Data Buku</h2>
    </div>
    <div class="content">
        <div class="bcard" style="padding: 20px; max-width: 600px;">
            <h3><i class="bi bi-file-earmark-spreadsheet"></i> Upload File CSV</h3>
            <p style="font-size: 13px; color: #64748b;">Pastikan format CSV kamu sesuai dengan panduan di bawah ini.</p>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group" style="margin-bottom: 15px;">
                    <input type="file" name="file_csv" class="form-control" accept=".csv" required>
                </div>
                <button type="submit" name="import" class="btn btn-primary"><i class="bi bi-upload"></i> Mulai Import</button>
                <a href="buku.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
        
        <div class="bcard" style="padding: 20px; max-width: 600px; margin-top: 20px; background: #fff7ed; border-color: #fed7aa;">
            <h4 style="color: #c2410c; margin-top:0;">Panduan Format CSV</h4>
            <p style="font-size: 13px; color: #9a3412;">Buat file Excel dengan urutan kolom (tanpa judul di baris pertama, atau judul di baris 1 akan dilewati):</p>
            <ol style="font-size: 13px; color: #9a3412; padding-left: 20px;">
                <li>Judul Buku</li>
                <li>Pengarang</li>
                <li>Penerbit</li>
                <li>Tahun Terbit (Angka)</li>
                <li>ISBN</li>
                <li>ID Kategori (Angka, kosongkan jika tidak ada)</li>
                <li>Stok (Angka)</li>
                <li>Lokasi Rak</li>
                <li>Deskripsi</li>
            </ol>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>