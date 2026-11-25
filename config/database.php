<?php

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$host = 'sql104.infinityfree.com';     
$user = 'if0_43339648';                
$pass = 'b76eHVjO12';              
$db   = 'if0_43339648_db_perpustakaan';      

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function base_url($path = '') {
    // Di InfinityFree, folder utama adalah public_html, jadi aksesnya langsung dari '/'
    // Jangan pakai '/perpustakaan/' karena file kamu langsung ditaruh di dalam public_html
    $base = '/'; 
    return $base . ltrim($path, '/');
}

function redirect($path) {
    header("Location: " . base_url($path));
    exit;
}

function cekLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect('auth/login.php');
    }
}

function cekRole($roles) {
    cekLogin();
    if (!in_array($_SESSION['role'], (array)$roles)) {
        redirect('index.php');
    }
}

function flash($key, $msg = null) {
    if ($msg !== null) { 
        $_SESSION['flash'][$key] = $msg; 
        return; 
    }
    if (isset($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}
?>