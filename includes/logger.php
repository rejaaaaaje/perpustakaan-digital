<?php
/**
 * Fungsi untuk mencatat log aktivitas user
 * 
 * @param mysqli $conn Koneksi database
 * @param string $aktivitas Judul singkat aktivitas (contoh: "Tambah Buku")
 * @param string $detail Detail aktivitas (contoh: "Menambahkan buku Laskar Pelangi")
 */
function catatLog($conn, $aktivitas, $detail = '') {
    if (!isset($_SESSION['user_id'])) return;

    $user_id   = (int)$_SESSION['user_id'];
    $nama_user = $_SESSION['nama'] ?? 'Unknown';
    $role      = $_SESSION['role'] ?? 'unknown';
    
    // Ambil IP Address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    $aktivitas = $conn->real_escape_string($aktivitas);
    $detail    = $conn->real_escape_string($detail);
    $nama_user = $conn->real_escape_string($nama_user);
    $role      = $conn->real_escape_string($role);

    $stmt = $conn->prepare("INSERT INTO log_aktivitas (user_id, nama_user, role, aktivitas, detail, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssss', $user_id, $nama_user, $role, $aktivitas, $detail, $ip_address);
    $stmt->execute();
}
?>