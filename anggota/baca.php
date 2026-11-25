<?php
require_once '../config/database.php';
cekLogin();
if ($_SESSION['role'] !== 'anggota') { redirect('../index.php'); }

$buku_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* Ambil data buku */
$q_buku = $conn->query("SELECT * FROM buku WHERE id = $buku_id");
if (!$q_buku || $q_buku->num_rows === 0) {
    flash('error', 'Buku tidak ditemukan!');
    redirect('katalog.php');
}
$buku = $q_buku->fetch_assoc();

/* Ambil nama file PDF dari database */
$file_pdf = $buku['file_pdf'] ?? $buku['pdf'] ?? $buku['file'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Membaca: <?= htmlspecialchars($buku['judul']) ?> - Perpustakaan Digital</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <!-- jQuery & Turn.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js"></script>
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #121212; color: #fff; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        
        .reader-nav {
            height: 50px; background: #1e1e1e;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 0 16px;
            display: flex; justify-content: space-between; align-items: center;
            flex-shrink: 0; z-index: 100;
        }
        .reader-title { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 40%; }
        
        .reader-nav-actions { display: flex; gap: 6px; align-items: center; }
        
        .btn-icon {
            background: rgba(255, 255, 255, 0.1); color: #fff;
            width: 34px; height: 34px; border-radius: 6px; border: none; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; font-size: 16px;
            transition: 0.2s;
        }
        .btn-icon:hover { background: rgba(255, 255, 255, 0.2); }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.1); color: #fff;
            padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            transition: 0.2s; white-space: nowrap; margin-left: 6px;
        }
        .btn-back:hover { background: rgba(255, 255, 255, 0.2); }

        /* MENGUBAH CONTAINER MENJADI FULL SCREEN */
        .reader-container {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: #1a1a1a; position: relative; overflow: hidden; 
            padding: 0; /* Padding dihilangkan */
            width: 100%;
            height: 100%;
        }

        #flipbook-wrapper {
            display: flex; justify-content: center; align-items: center; 
            width: 100%; height: 100%;
            transition: transform 0.3s ease;
            transform-origin: center center;
        }
        
        #flipbook {
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            background: #fff;
        }
        #flipbook .page {
            background-color: #fdfdfd;
            color: #333;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #flipbook .page canvas {
            width: 100% !important;
            height: 100% !important;
            object-fit: contain;
        }
        
        .reader-actions {
            position: absolute;
            bottom: 15px; /* Tombol navigasi menempel di bawah */
            display: flex; gap: 12px; align-items: center; justify-content: center;
            z-index: 10;
            background: rgba(18, 18, 18, 0.85); /* Background gelap transparan agar teks PDF tidak tertutup */
            padding: 8px 20px;
            border-radius: 50px;
            backdrop-filter: blur(4px);
        }
        .nav-btn {
            background: #0f5132; color: #fff; border: none; padding: 8px 16px;
            border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .nav-btn:hover { background: #146c43; }
        .nav-btn:disabled { background: #4b5563; cursor: not-allowed; }
        
        /* Input Go To Page */
        .page-input-wrap { display: flex; align-items: center; gap: 5px; font-size: 13px; color: #cbd5e1; font-weight: 600; }
        .page-input-wrap input {
            width: 50px; padding: 6px; text-align: center;
            border: 1px solid #334155; border-radius: 6px;
            background: #0f172a; color: #fff; font-size: 13px; font-weight: 600;
        }
        .page-input-wrap input:focus { outline: none; border-color: #0f5132; }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute; inset: 0; background: #1a1a1a; z-index: 50;
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid rgba(255,255,255,0.1);
            border-top-color: #0f5132; border-radius: 50%; animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .reader-nav { padding: 0 10px; }
            .reader-title { font-size: 12px; max-width: 35%; }
            .btn-icon { width: 30px; height: 30px; font-size: 14px; }
            .btn-back { padding: 6px 10px; font-size: 11px; }
            .reader-actions { bottom: 10px; padding: 6px 16px; }
        }
    </style>
</head>
<body>

    <div class="reader-nav">
        <div class="reader-title">
            <i class="bi bi-book-half"></i> <?= htmlspecialchars($buku['judul']) ?>
        </div>
        
        <div class="reader-nav-actions">
            <button class="btn-icon" id="zoom-out" title="Perkecil"><i class="bi bi-zoom-out"></i></button>
            <button class="btn-icon" id="zoom-reset" title="Ukuran Normal"><i class="bi bi-aspect-ratio"></i></button>
            <button class="btn-icon" id="zoom-in" title="Perbesar"><i class="bi bi-zoom-in"></i></button>
            
            <button class="btn-icon" id="btn-fullscreen" title="Layar Penuh"><i class="bi bi-arrows-fullscreen"></i></button>
            
            <a href="katalog.php?id=<?= $buku_id ?>" class="btn-back">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="reader-container">
        <?php if (!empty($file_pdf) && file_exists('../assets/file_buku/' . $file_pdf)): ?>
            
            <div class="loading-overlay" id="loading">
                <div class="spinner"></div>
                <div style="font-size: 14px; color: #9ca3af;">Memuat dokumen...</div>
            </div>

            <div id="flipbook-wrapper">
                <div id="flipbook">
                    <!-- Halaman dirender otomatis -->
                </div>
            </div>

            <div class="reader-actions">
                <button class="nav-btn" id="prev" disabled><i class="bi bi-chevron-left"></i> Sblm</button>
                
                <div class="page-input-wrap">
                    Hal. <input type="number" id="page-input" value="1" min="1"> / <span id="total-pages">-</span>
                </div>

                <button class="nav-btn" id="next">Nxt <i class="bi bi-chevron-right"></i></button>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #9ca3af; padding: 20px;">
                <i class="bi bi-exclamation-circle" style="font-size: 48px; color: #ef4444; display: block; margin-bottom: 12px;"></i>
                <h3>File PDF Buku Belum Tersedia</h3>
                <p style="font-size: 14px; margin-top: 6px;">Admin belum meng-upload file PDF untuk buku ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if (!empty($file_pdf)): ?>
        const pdfUrl = '../assets/file_buku/<?= htmlspecialchars($file_pdf) ?>';
        
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

        let pdfDoc = null;
        let currentPage = 1;
        const flipbook = $('#flipbook');
        const pageInput = $('#page-input');
        const totalPagesSpan = $('#total-pages');

        // Variabel Zoom CSS
        let currentScale = 1;
        const scaleStep = 0.2;
        const maxScale = 3;
        const minScale = 0.5;
        
        const renderScale = 1.5; // Skala HD

        async function renderPage(pageNum) {
            const pageDiv = flipbook.find(`.page[data-page="${pageNum}"]`);
            
            if (pageDiv.find('canvas').length > 0) return;

            try {
                const page = await pdfDoc.getPage(pageNum);
                const viewport = page.getViewport({ scale: renderScale });
                
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                await page.render({ canvasContext: context, viewport: viewport }).promise;
                pageDiv.append(canvas);
                
            } catch (err) {
                console.error("Gagal render halaman " + pageNum, err);
            }
        }

        async function initPDF() {
            try {
                const loadingTask = pdfjsLib.getDocument(pdfUrl);
                pdfDoc = await loadingTask.promise;
                const numPages = pdfDoc.numPages;

                // PERBAIKAN TYPO: Menghilangkan tanda minus di depan angka
                totalPagesSpan.text(numPages);
                pageInput.attr('max', numPages);

                for (let i = 1; i <= numPages; i++) {
                    $(`<div class="page" data-page="${i}"></div>`).appendTo(flipbook);
                }

                await renderPage(1);
                if(window.innerWidth > 768 && numPages > 1) {
                    await renderPage(2);
                }

                $('#loading').fadeOut(300);
                initFlipbook(numPages);
            } catch (err) {
                console.error(err);
                $('#loading').html('<div style="color:#ef4444;">Gagal memuat PDF.</div>');
            }
        }

        function initFlipbook(numPages) {
            const wrapper = $('#flipbook-wrapper');
            
            // UKURAN DIPERBESAR: Menggunakan lebar/tinggi container penuh
            const containerWidth = wrapper.width();
            const containerHeight = wrapper.height() - 60; // Kurangi sedikit untuk ruang tombol navigasi bawah
            const isMobile = window.innerWidth <= 768;

            let width, height, displayMode;
            if (isMobile) {
                displayMode = 'single';
                width = containerWidth; // Full width di HP
                height = containerHeight;
            } else {
                displayMode = 'double';
                width = containerWidth; // Full width di Laptop
                height = containerHeight;
            }

            flipbook.turn({
                width: width,
                height: height,
                display: displayMode,
                autoCenter: true,
                gradients: true,
                elevation: 50,
                when: {
                    turned: async function(e, page) {
                        currentPage = page;
                        pageInput.val(page);
                        
                        $('#prev').prop('disabled', page <= 1);
                        $('#next').prop('disabled', page >= numPages);

                        await renderPage(page);
                        if (displayMode === 'double' && page + 1 <= numPages) {
                            await renderPage(page + 1);
                        }
                    }
                }
            });

            function goToPage(targetPage) {
                if (targetPage < 1) targetPage = 1;
                if (targetPage > numPages) targetPage = numPages;
                flipbook.turn('page', targetPage);
            }

            $('#next').click(function() { 
                goToPage(currentPage + (isMobile ? 1 : 2)); 
            });

            $('#prev').click(function() { 
                goToPage(currentPage - (isMobile ? 1 : 2)); 
            });

            pageInput.on('change', function() {
                let val = parseInt($(this).val());
                if (!isNaN(val)) goToPage(val);
            });

            pageInput.on('keypress', function(e) {
                if (e.which === 13) { $(this).trigger('change'); $(this).blur(); }
            });
        }

        // FITUR ZOOM
        $('#zoom-in').click(function() {
            if (currentScale < maxScale) {
                currentScale += scaleStep;
                $('#flipbook-wrapper').css('transform', 'scale(' + currentScale + ')');
            }
        });

        $('#zoom-out').click(function() {
            if (currentScale > minScale) {
                currentScale -= scaleStep;
                $('#flipbook-wrapper').css('transform', 'scale(' + currentScale + ')');
            }
        });

        $('#zoom-reset').click(function() {
            currentScale = 1;
            $('#flipbook-wrapper').css('transform', 'scale(1)');
        });

        // FITUR FULLSCREEN
        $('#btn-fullscreen').click(function() {
            let elem = document.documentElement;
            if (!document.fullscreenElement) {
                if (elem.requestFullscreen) elem.requestFullscreen();
                else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
                else if (elem.msRequestFullscreen) elem.msRequestFullscreen();
                $(this).html('<i class="bi bi-fullscreen-exit"></i>');
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
                else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
                else if (document.msExitFullscreen) document.msExitFullscreen();
                $(this).html('<i class="bi bi-arrows-fullscreen"></i>');
            }
        });

        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement) {
                $('#btn-fullscreen').html('<i class="bi bi-arrows-fullscreen"></i>');
            }
        });

        $(document).ready(function() {
            initPDF();
        });
        <?php endif; ?>
    </script>
</body>
</html>