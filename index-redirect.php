<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    redirect('auth/login.php');
}

switch ($_SESSION['role']) {
    case 'admin':   redirect('admin/dashboard.php'); break;
    case 'petugas': redirect('petugas/dashboard.php'); break;
    case 'anggota': redirect('anggota/dashboard.php'); break;
    default: redirect('auth/login.php');
}
?>