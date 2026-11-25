<?php
header('Content-Type: application/json');

$kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $_GET['isbn'] ?? ''));

if (empty($kode)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode buku tidak valid.']);
    exit;
}

// ==========================================
// 1. CEK DATABASE LOKAL DULU
// ==========================================
require_once '../config/database.php'; 

$stmt = $conn->prepare("SELECT judul, pengarang, penerbit, tahun_terbit, cover FROM buku WHERE isbn = ? LIMIT 1");
$stmt->bind_param('s', $kode);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'status'    => 'success',
        'judul'     => $row['judul'],
        'pengarang' => $row['pengarang'],
        'penerbit'  => $row['penerbit'],
        'tahun'     => $row['tahun_terbit'],
        'cover'     => $row['cover'],
        'source'    => 'Database Perpustakaan'
    ]);
    exit;
}

// ==========================================
// 2. API OPEN LIBRARY (Lebih Ramah Hosting Gratisan)
// ==========================================
$url_ol = "https://openlibrary.org/api/books?bibkeys=ISBN:" . $kode . "&format=json&jscmd=data";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_ol);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'); // Pura-pura jadi browser
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    $key = "ISBN:" . $kode;
    
    if (isset($data[$key])) {
        $book = $data[$key];
        
        $cover = $book['cover']['large'] ?? ($book['cover']['medium'] ?? '');

        echo json_encode([
            'status'    => 'success',
            'judul'     => $book['title'] ?? '',
            'pengarang' => isset($book['authors']) ? implode(', ', array_column($book['authors'], 'name')) : '',
            'penerbit'  => isset($book['publishers']) ? implode(', ', array_column($book['publishers'], 'name')) : '',
            'tahun'     => $book['publish_date'] ?? '',
            'cover'     => $cover,
            'source'    => 'Open Library'
        ]);
        exit;
    }
}

// ==========================================
// 3. API GOOGLE BOOKS (Cadangan Terakhir)
// ==========================================
$url_google = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . $kode;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_google);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['totalItems']) && $data['totalItems'] > 0) {
        $book = $data['items'][0]['volumeInfo'];
        $cover = $book['imageLinks']['thumbnail'] ?? '';
        if ($cover) $cover = str_replace('http://', 'https://', $cover);

        echo json_encode([
            'status'    => 'success',
            'judul'     => $book['title'] ?? '',
            'pengarang' => isset($book['authors']) ? implode(', ', $book['authors']) : '',
            'penerbit'  => $book['publisher'] ?? '',
            'tahun'     => isset($book['publishedDate']) ? substr($book['publishedDate'], 0, 4) : '',
            'cover'     => $cover,
            'source'    => 'Google Books'
        ]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Buku dengan ISBN ' . $kode . ' tidak ditemukan di internet. Silakan isi manual.']);