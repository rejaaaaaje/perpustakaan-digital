<?php
if (!function_exists('base_url')) {
    require_once __DIR__ . '/../config/database.php';
}

$currentLang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'id');
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id','en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $currentLang = $_GET['lang'];
}

$ext = [
    'perpus' => 'https://pustaka.ut.ac.id',
    'blog'   => 'https://pustaka.ut.ac.id/blog',
    'jdih'   => 'https://jdih.ut.ac.id',
    'abakus' => 'https://abakus.ut.ac.id',
    'faq'    => 'https://pustaka.ut.ac.id/faq',
];
?>
<style>
.top-strip {
    background: #ffcc00;
    padding: 6px 0;
    font-size: 12px;
    border-bottom: 1px solid #e6b800;
    position: relative;
    z-index: 11;
}
.top-strip-inner {
    max-width: 1300px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.top-strip-group {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}
.top-strip a {
    color: #333;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.25s ease;
    white-space: nowrap;
}
.top-strip a:hover {
    background: rgba(0, 61, 122, 0.12);
    transform: translateY(-1px);
}
.top-strip a .ts-icon {
    font-size: 13px;
    line-height: 1;
    display: inline-flex;
    align-items: center;
}
.top-strip a.flag {
    font-weight: 700;
    padding: 3px 10px;
    border: 1.5px solid transparent;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 11px;
}
.top-strip a.flag:hover {
    border-color: rgba(0, 61, 122, 0.3);
}
.top-strip a.flag.lang-active {
    background: #003d7a;
    color: #fff !important;
    border-color: #003d7a;
    box-shadow: 0 2px 8px rgba(0, 61, 122, 0.3);
}

/* ============================================================
   RESPONSIVE — HP
   ============================================================ */
@media (max-width: 700px) {
    .top-strip { padding: 5px 0; font-size: 11px; }
    .top-strip-inner {
        padding: 0 12px;
        justify-content: center;
        gap: 4px;
    }
    .top-strip-group {
        justify-content: center;
    }
    .top-strip a {
        padding: 3px 6px;
        font-size: 11px;
    }
    /* Sembunyikan icon di HP sangat kecil */
    .top-strip a .ts-icon {
        display: none;
    }
    /* Khusus lang flag, tetap tampil */
    .top-strip a.flag .ts-icon {
        display: inline-flex;
    }
}

@media (max-width: 380px) {
    /* Sembunyikan beberapa link di HP sangat kecil */
    .top-strip a[data-hide-small="true"] {
        display: none;
    }
}
</style>
