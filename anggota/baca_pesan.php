<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$uid = (int)$_SESSION['user_id'];

if ($id > 0) {
    $conn->query("UPDATE pesan_pribadi SET status_baca = 1 WHERE id = $id AND user_id = $uid");
    echo "success";
}
?>