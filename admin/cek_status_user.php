<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'admin') { exit; }

date_default_timezone_set('Asia/Jakarta');
$now = time();

$query = $conn->query("SELECT id, last_activity FROM users");
$data = [];

while ($u = $query->fetch_assoc()) {
    $is_online = false;
    if (!empty($u['last_activity'])) {
        $last_activity = strtotime($u['last_activity']);
        // Toleransi 15 detik (karena heartbeat berjalan tiap 10 detik)
        if (($now - $last_activity) <= 15) {
            $is_online = true;
        }
    }
    $data[$u['id']] = $is_online;
}

header('Content-Type: application/json');
echo json_encode($data);
?>