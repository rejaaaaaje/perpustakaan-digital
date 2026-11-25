<?php
$title = 'Kirim Pesan';
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'petugas') { redirect('../index.php'); }

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Proses Kirim Pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim'])) {
    $user_id = (int)$_POST['user_id'];
    $judul = $conn->real_escape_string(trim($_POST['judul']));
    $isi = $conn->real_escape_string(trim($_POST['isi']));
    $kategori = $conn->real_escape_string(trim($_POST['kategori']));
    
    if ($user_id > 0 && !empty($judul) && !empty($isi) && !empty($kategori)) {
        $conn->query("INSERT INTO pesan_pribadi (user_id, judul, isi, kategori) VALUES ($user_id, '$judul', '$isi', '$kategori')");
        echo "<script>alert('Pesan berhasil dikirim!'); window.location='pesan.php';</script>";
    } else {
        echo "<script>alert('Semua kolom wajib diisi!');</script>";
    }
}
?>

<style>
    .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 600px; margin: 24px auto; }
    .form-group { margin-bottom: 15px; position: relative; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; }
    
    /* Style untuk Live Search */
    .search-result { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; max-height: 200px; overflow-y: auto; z-index: 10; display: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .search-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #f1f5f9; }
    .search-item:hover { background: #f8fafc; }
    .search-item .name { font-weight: 600; color: #1e293b; display: block; }
    .search-item .email { font-size: 12px; color: #64748b; }
    
    /* Style untuk Tombol Kirim */
    .btn { background: #0f5132; color: #fff; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; width: 100%; }
</style>

<div class="card">
    <h2 style="margin-bottom: 20px; font-size: 18px;">Kirim Pesan Pribadi</h2>
    
    <form method="POST" id="formPesan">
        <div class="form-group">
            <label>Cari Anggota (Ketik Nama atau Email):</label>
            <!-- Input untuk live search -->
            <input type="text" id="searchUser" class="form-control" placeholder="Mulai ketik nama atau email..." autocomplete="off">
            <!-- Hasil pencarian akan muncul di sini -->
            <div id="searchResults" class="search-result"></div>
            <!-- Hidden input untuk menyimpan ID yang dipilih -->
            <input type="hidden" name="user_id" id="selectedUserId" required>
            <small id="selectedUserText" style="color: #0f5132; font-weight: bold; display: none; margin-top: 5px;">Terpilih: -</small>
        </div>

        <div class="form-group">
            <label>Kategori Pesan:</label>
            <select name="kategori" class="form-control" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="warning">⚠️ Peringatan (Warning)</option>
                <option value="pribadi">✉️ Pesan Pribadi</option>
                <option value="info">📢 Informasi Penting</option>
            </select>
        </div>

        <div class="form-group">
            <label>Judul Pesan:</label>
            <input type="text" name="judul" class="form-control" placeholder="Contoh: Peringatan Keterlambatan" required>
        </div>

        <div class="form-group">
            <label>Isi Pesan:</label>
            <textarea name="isi" class="form-control" rows="4" placeholder="Tulis pesan detail di sini..." required></textarea>
        </div>

        <button type="submit" name="kirim" class="btn">Kirim Pesan</button>
    </form>
</div>

<script>
    const searchInput = document.getElementById('searchUser');
    const searchResults = document.getElementById('searchResults');
    const selectedUserId = document.getElementById('selectedUserId');
    const selectedUserText = document.getElementById('selectedUserText');

    // Fungsi Live Search
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Jika input kosong, sembunyikan hasil
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        // Ambil data via AJAX ke file cari_anggota.php
        fetch('cari_anggota.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                searchResults.innerHTML = ''; // Kosongkan hasil sebelumnya
                
                if (data.length > 0) {
                    searchResults.style.display = 'block';
                    data.forEach(user => {
                        const div = document.createElement('div');
                        div.className = 'search-item';
                        div.innerHTML = `<span class="name">${user.nama}</span><span class="email">${user.email}</span>`;
                        
                        // Saat item diklik
                        div.addEventListener('click', function() {
                            selectedUserId.value = user.id; // Simpan ID ke hidden input
                            searchInput.value = user.nama; // Tampilkan nama di input
                            selectedUserText.style.display = 'block';
                            selectedUserText.textContent = 'Terpilih: ' + user.nama + ' (' + user.email + ')';
                            searchResults.style.display = 'none'; // Sembunyikan hasil
                        });
                        
                        searchResults.appendChild(div);
                    });
                } else {
                    searchResults.style.display = 'block';
                    searchResults.innerHTML = '<div class="search-item" style="color:#ef4444;">Anggota tidak ditemukan.</div>';
                }
            })
            .catch(error => console.error('Error:', error));
    });

    // Sembunyikan hasil jika user klik di luar area pencarian
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Pastikan user memilih dari hasil pencarian sebelum submit
    document.getElementById('formPesan').addEventListener('submit', function(e) {
        if (selectedUserId.value === '') {
            e.preventDefault();
            alert('Silakan pilih anggota dari daftar pencarian terlebih dahulu!');
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>