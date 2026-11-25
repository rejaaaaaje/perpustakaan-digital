<?php
require_once '../config/database.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    
    // Hapus last_activity (untuk fitur online)
    $conn->query("UPDATE users SET last_activity = NULL WHERE id = $uid");
    
    // ==========================================
    // TAMBAHKAN INI: Hapus token remember_me
    // ==========================================
    $conn->query("UPDATE users SET remember_token = NULL WHERE id = $uid");
    setcookie('remember_me', '', time() - 3600, "/"); 
    // ==========================================
}

session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout...</title>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            background: #f7f8fa;
            color: #718096;
        }
        .loader {
            border: 4px solid #e2e8f0;
            border-top: 4px solid #0f5132;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader"></div>
    <h3>Sedang keluar...</h3>

    <script>
        // Hapus memori welcome screen
        sessionStorage.removeItem('has_seen_welcome');
        
        // Pindah ke login setelah 1 detik (opsional, agar loader terlihat sebentar)
        setTimeout(function() {
            window.location.href = "<?= base_url('auth/login.php') ?>";
        }, 800);
    </script>
</body>
</html>