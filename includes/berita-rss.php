<?php
function getBeritaRSS($limit = 4) {
    // Folder untuk menyimpan cache berita
    $cache_dir  = __DIR__ . '/cache/';
    $cache_file = $cache_dir . 'berita.json';
    
    // Buat folder cache jika belum ada
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    // 1. Cek apakah cache masih berlaku (misal: 15 menit = 900 detik)
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 900) {
        $data = file_get_contents($cache_file);
        return json_decode($data, true);
    }

    // 2. Jika cache habis, ambil berita baru dari ANTARA News
    $url = 'https://www.antaranews.com/rss/terkini.xml';
    
    // Gunakan @ untuk menekan warning jika internet mati
    $rss = @simplexml_load_file($url);
    
    // Jika gagal ambil data, pakai cache lama (kalau ada)
    if ($rss === false) {
        if (file_exists($cache_file)) {
            return json_decode(file_get_contents($cache_file), true);
        }
        return []; 
    }

    $berita = [];
    $count = 0;
    
    foreach ($rss->channel->item as $item) {
        if ($count >= $limit) break;
        
        // ANTARA biasanya menyertakan gambar di dalam tag <enclosure>
        $gambar = '';
        if (isset($item->enclosure)) {
            $gambar = (string) $item->enclosure['url'];
        } else {
            // Alternatif: ambil gambar pertama dari deskripsi jika ada
            preg_match('/<img.+src=[\'"](?P<src>.+?)[\'"].*>/i', (string)$item->description, $image);
            if (!empty($image['src'])) $gambar = $image['src'];
        }

        $berita[] = [
            'judul'     => (string) $item->title,
            'link'      => (string) $item->link,
            'tanggal'   => date('d M Y H:i', strtotime((string) $item->pubDate)),
            'ringkasan' => strip_tags((string) $item->description),
            'gambar'    => $gambar,
            'kategori'  => 'Berita Terkini',
            'sumber'    => 'ANTARA News'
        ];
        $count++;
    }
    
    // 3. Simpan hasilnya ke file cache
    file_put_contents($cache_file, json_encode($berita));
    
    return $berita;
}
?>