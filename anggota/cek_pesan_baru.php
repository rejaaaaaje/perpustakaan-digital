<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { exit; }

$uid = (int)$_SESSION['user_id'];

$q = $conn->query("SELECT id, judul, isi, kategori FROM pesan_pribadi WHERE user_id=$uid AND status_baca=0 ORDER BY id DESC LIMIT 1");

if ($q && $q->num_rows > 0) {
    $pesan = $q->fetch_assoc();
    echo json_encode([
        'status' => 'ada',
        'id' => $pesan['id'],
        'judul' => htmlspecialchars($pesan['judul']),
        'isi' => nl2br(htmlspecialchars($pesan['isi'])),
        'kategori' => $pesan['kategori'] // Kirim kategori ke JS
    ]);
} else {
    echo json_encode(['status' => 'kosong']);
}
?>