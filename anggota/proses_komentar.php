<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar'])) {
    $uid = (int)$_SESSION['user_id'];
    $buku_id = (int)$_POST['buku_id'];
    $isi = trim($_POST['isi_komentar']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;

    // Validasi rating agar hanya 1-5
    if ($rating < 1 || $rating > 5) $rating = 5;

    if (!empty($isi)) {
        $stmt = $conn->prepare("INSERT INTO komentar (buku_id, user_id, isi_komentar, rating) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisi', $buku_id, $uid, $isi, $rating);
        
        if ($stmt->execute()) {
            // INI PENTING: Kirim teks "success" ke JavaScript
            echo "success"; 
        } else {
            echo "error";
        }
    } else {
        echo "empty";
    }
    exit; // Hentikan script agar tidak ada output lain
}
?>