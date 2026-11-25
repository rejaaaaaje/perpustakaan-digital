<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'petugas') { exit; }

$keyword = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

if ($keyword) {
    // Cari berdasarkan nama atau email
    $query = $conn->query("SELECT id, nama, email FROM users 
                           WHERE role='anggota' 
                           AND (nama LIKE '%$keyword%' OR email LIKE '%$keyword%') 
                           LIMIT 5");
    
    $result = [];
    while ($row = $query->fetch_assoc()) {
        $result[] = $row;
    }
    echo json_encode($result);
} else {
    echo json_encode([]);
}
?>