<?php
// Tambahkan baris ini di paling atas
date_default_timezone_set('Asia/Jakarta'); 

require_once 'config/database.php';
cekLogin();

$uid = (int)$_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

// Update waktu terakhir aktif
$conn->query("UPDATE users SET last_activity = '$now' WHERE id = $uid");
?>