<?php
require_once 'config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'petugas') { exit; }

// Hitung komentar yang status_baca = 0
$q = $conn->query("SELECT COUNT(*) as total FROM komentar WHERE status_baca = 0");
$total = $q->fetch_assoc()['total'];

echo $total;
?>