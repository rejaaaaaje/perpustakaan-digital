<?php
require_once 'config/database.php';

$users = [
    'admin@perpus.com'   => 'admin123',
    'petugas@perpus.com' => 'petugas123',
    'anggota@perpus.com' => 'anggota123',
];

echo "<h2>🔧 Fix Password Hash</h2>";
echo "<pre>";

foreach ($users as $email => $pass) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
    $stmt->bind_param('ss', $hash, $email);
    
    if ($stmt->execute()) {
        echo "✅ $email → password: $pass\n";
        echo "   Hash: $hash\n\n";
    } else {
        echo "❌ Gagal update: $email\n\n";
    }
}

echo "</pre>";
echo "<h3 style='color:green'>✅ Selesai! Sekarang coba login lagi.</h3>";
echo "<a href='auth/login.php'>→ Ke Halaman Login</a><br><br>";
echo "<b style='color:red'>⚠️ HAPUS file ini setelah selesai!</b>";
?>