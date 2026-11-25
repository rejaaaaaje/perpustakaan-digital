<?php
$role    = $_SESSION['role'];
$current = basename($_SERVER['PHP_SELF']);
$nama    = $_SESSION['nama'] ?? 'User';
$initial = strtoupper(substr($nama, 0, 1));

function isActive($file) {
    global $current;
    return $current === $file ? 'active' : '';
}

$roleLabel = [
    'admin'   => 'Administrator',
    'petugas' => 'Petugas',
    'anggota' => 'Anggota'
][$role] ?? ucfirst($role);

// =========================================================
// CEK NOTIFIKASI PINJAMAN PENDING
// =========================================================
$notif_pending = 0;
if (($role === 'admin' || $role === 'petugas') && isset($conn)) {
    $query_notif = $conn->query("SELECT COUNT(*) as total FROM pinjaman WHERE status='pending'");
    if ($query_notif) {
        $notif_pending = $query_notif->fetch_assoc()['total'];
    }
}

// =========================================================
// CEK NOTIFIKASI KOMENTAR BARU
// =========================================================
$notif_komentar = 0;
if (($role === 'admin' || $role === 'petugas') && isset($conn)) {
    $query_komentar = $conn->query("SELECT COUNT(*) as total FROM komentar WHERE status_baca = 0");
    if ($query_komentar) {
        $notif_komentar = $query_komentar->fetch_assoc()['total'];
    }
}
?>

<style>
/* ============================================================
   SIDEBAR MODERN — RESPONSIVE (HIJAU)
   ============================================================ */
.sidebar-modern {
    width: 260px;
    background: linear-gradient(180deg, #0a3622 0%, #0f5132 50%, #0a3622 100%);
    background-size: 100% 200%;
    animation: sidebarGradient 20s ease infinite;
    color: #fff;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    box-shadow: 4px 0 24px rgba(5, 40, 25, 0.35);
    display: flex;
    flex-direction: column;
    transition: transform .3s ease;
}

.sidebar-modern::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(circle at 20% 15%, rgba(255,255,255,0.06) 1px, transparent 2px),
        radial-gradient(circle at 80% 40%, rgba(255,204,0,0.08) 1px, transparent 2px),
        radial-gradient(circle at 40% 70%, rgba(255,255,255,0.05) 1px, transparent 2px),
        radial-gradient(circle at 70% 90%, rgba(255,204,0,0.06) 1px, transparent 2px);
    background-size: 180px 180px, 220px 220px, 200px 200px, 240px 240px;
    pointer-events: none;
}

.sidebar-modern::after {
    content: "";
    position: absolute;
    top: -100px;
    right: -100px;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(255,204,0,0.12), transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    animation: sidebarPulse 8s ease-in-out infinite;
}

@keyframes sidebarGradient {
    0%, 100% { background-position: 0% 0%; }
    50%      { background-position: 0% 100%; }
}
@keyframes sidebarPulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50%      { transform: scale(1.15); opacity: 0.3; }
}
@keyframes dotPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%      { opacity: 0.5; transform: scale(1.4); }
}

/* ============================================================
   TYPEWRITER CURSOR (untuk nama sekolah di brand)
   ============================================================ */
.typing-cursor {
    display: inline-block;
    width: 2px;
    height: 1em;
    background-color: currentColor;
    margin-left: 2px;
    animation: blink 0.7s infinite;
    vertical-align: text-bottom;
}
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}

/* ============================================================
   STYLE NOTIFIKASI BADGE
   ============================================================ */
.notif-badge {
    margin-left: auto;
    background: #ef4444;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
    animation: pulseNotif 2s infinite;
}

@keyframes pulseNotif {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* ============================================================
   OVERLAY (muncul saat sidebar terbuka di HP)
   ============================================================ */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
    z-index: 999;
    opacity: 0;
    transition: opacity .3s ease;
}
.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

/* ============================================================
   TOMBOL TOGGLE SIDEBAR (muncul di HP)
   ============================================================ */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 1001;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #0f5132;
    color: #fff;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(15, 81, 50, 0.3);
    align-items: center;
    justify-content: center;
    font-size: 22px;
    transition: all .2s;
}
.sidebar-toggle:hover {
    background: #146c43;
    transform: scale(1.05);
}
.sidebar-toggle:active {
    transform: scale(0.95);
}

/* ============================================================
   BRAND / LOGO
   ============================================================ */
.sidebar-brand {
    padding: 20px 20px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    z-index: 2;
    flex-shrink: 0;
    background: linear-gradient(180deg, rgba(255,255,255,0.03) 0%, transparent 100%);
    min-height: 64px;
}

.brand-text {
    line-height: 1.2;
    min-width: 0;
    flex: 1;
    text-align: center;
}
.brand-text strong {
    color: #fff;
    font-size: 15px;
    font-weight: 800;
    letter-spacing: 0.3px;
    display: inline-block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}
.brand-text small {
    color: rgba(255, 204, 0, 0.9);
    font-size: 9.5px;
    letter-spacing: 1.3px;
    text-transform: uppercase;
    font-weight: 700;
    display: block;
    margin-top: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-close {
    display: none;
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border: none;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: all .2s;
    z-index: 3;
}
.sidebar-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: rotate(90deg);
}

/* ============================================================
   USER CARD
   ============================================================ */
.sidebar-user {
    padding: 16px;
    margin: 14px 14px 10px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 14px;
    position: relative;
    z-index: 2;
    backdrop-filter: blur(10px);
    transition: 0.3s;
}
.sidebar-user:hover {
    background: rgba(255, 204, 0, 0.08);
    border-color: rgba(255, 204, 0, 0.25);
}

.user-avatar {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25),
                0 0 0 2px rgba(255, 204, 0, 0.5);
    overflow: hidden;
    padding: 3px;
    transition: transform 0.3s;
}
.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}
.sidebar-user:hover .user-avatar {
    transform: scale(1.08) rotate(-3deg);
}

.user-info-mini {
    flex: 1;
    min-width: 0;
}
.user-info-mini .u-name {
    color: #fff;
    font-size: 13.5px;
    font-weight: 700;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.user-info-mini .u-role {
    color: rgba(255, 204, 0, 0.9);
    font-size: 10px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 5px;
    line-height: 1.2;
}
.user-info-mini .u-role::before {
    content: "";
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #10b981;
    box-shadow: 0 0 6px #10b981;
    animation: dotPulse 2s ease-in-out infinite;
    flex-shrink: 0;
}

/* ============================================================
   MENU
   ============================================================ */
.menu-section {
    padding: 8px 14px 4px;
    position: relative;
    z-index: 2;
}
.menu-label {
    font-size: 10px;
    font-weight: 800;
    color: rgba(255, 204, 0, 0.7);
    text-transform: uppercase;
    letter-spacing: 1.6px;
    padding: 8px 12px 6px;
    margin-top: 4px;
}
.menu-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.menu-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    color: rgba(255, 255, 255, 0.82);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 600;
    border-radius: 10px;
    transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.menu-link::before {
    content: "";
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 0;
    background: #ffcc00;
    border-radius: 0 4px 4px 0;
    transition: 0.25s;
}
.menu-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    transform: translateX(4px);
}
.menu-link:hover::before { height: 60%; }
.menu-link:hover .menu-icon {
    color: #ffcc00;
    transform: scale(1.1);
}

.menu-link.active {
    background: linear-gradient(90deg, rgba(255, 204, 0, 0.18), rgba(255, 204, 0, 0.03));
    color: #fff;
    font-weight: 700;
}
.menu-link.active::before {
    height: 70%;
    background: #ffcc00;
    box-shadow: 0 0 12px rgba(255, 204, 0, 0.6);
}
.menu-link.active .menu-icon { color: #ffcc00; }
.menu-link.active::after {
    content: "";
    position: absolute;
    right: 14px;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #ffcc00;
    box-shadow: 0 0 10px #ffcc00;
    animation: dotPulse 2s ease-in-out infinite;
}

.menu-link.active:has(.notif-badge)::after {
    display: none;
}

.menu-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: rgba(255, 255, 255, 0.65);
    transition: 0.25s;
}
.menu-icon svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* ============================================================
   LOGOUT
   ============================================================ */
.menu-section-logout {
    margin-top: auto;
    padding: 20px 14px 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    position: relative;
    z-index: 2;
    background: rgba(10, 54, 34, 0.9);
    backdrop-filter: blur(10px);
}
.logout-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    color: #fca5a5;
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 700;
    border-radius: 10px;
    transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    position: relative;
    overflow: hidden;
}
.logout-link:hover {
    background: rgba(239, 68, 68, 0.3);
    border-color: rgba(239, 68, 68, 0.6);
    color: #fff;
    transform: translateY(-2px);
}
.logout-link .menu-icon {
    color: #ef4444;
}
.logout-link:hover .menu-icon {
    color: #fff;
    transform: scale(1.1);
}

/* ============================================================
   SCROLLBAR
   ============================================================ */
.sidebar-modern::-webkit-scrollbar { width: 6px; }
.sidebar-modern::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1); }
.sidebar-modern::-webkit-scrollbar-thumb {
    background: rgba(255, 204, 0, 0.3);
    border-radius: 3px;
}
.sidebar-modern::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 204, 0, 0.5);
}

.sidebar { display: none !important; }

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 1024px) and (min-width: 769px) {
    .sidebar-modern {
        width: 240px;
    }
}

@media (max-width: 900px) {
    .sidebar-modern {
        transform: translateX(-100%);
        width: 280px;
        box-shadow: 4px 0 24px rgba(5, 40, 25, 0.4);
    }
    .sidebar-modern.open {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: flex;
    }

    .sidebar-close {
        display: flex;
    }

    .sidebar-modern::after { display: none; }

    body {
        margin-left: 0 !important;
    }

    .app {
        max-width: 100vw !important;
    }

    .main,
    .content,
    .topbar,
    .dash-wrap,
    .container {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .content,
    .dash-wrap {
        padding-top: 70px !important;
    }

    .topbar {
        padding-top: 70px !important;
        padding-left: 16px !important;
        padding-right: 16px !important;
    }
}

@media (max-width: 480px) {
    .sidebar-modern {
        width: 260px;
    }
    .sidebar-toggle {
        width: 40px;
        height: 40px;
        font-size: 20px;
        top: 10px;
        left: 10px;
    }
}
</style>

<!-- TOMBOL TOGGLE SIDEBAR -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu">
    <i class="bi bi-list"></i>
</button>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<aside class="sidebar-modern" id="sidebarModern">

    <button class="sidebar-close" id="sidebarClose" aria-label="Tutup menu">
        <i class="bi bi-x-lg"></i>
    </button>

    <!-- BRAND — dengan typewriter -->
    <div class="sidebar-brand">
        <div class="brand-text">
            <strong class="typing-text-sidebar"></strong>
        </div>
    </div>

    <!-- USER CARD -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])): ?>
                <img src="<?= base_url('assets/img/profil/'.$_SESSION['foto']) ?>" alt="Foto Profil" style="object-fit: cover; border-radius: 50%;">
            <?php else: ?>
                <img src="<?= base_url('assets/img/foto.png') ?>" alt="Logo SMK As-Syafi'iyah">
            <?php endif; ?>
        </div>
        <div class="user-info-mini">
            <div class="u-name"><?= htmlspecialchars($nama) ?></div>
            <div class="u-role"><?= htmlspecialchars($roleLabel) ?></div>
        </div>
    </div>

    <!-- MENU ADMIN -->
    <?php if ($role === 'admin'): ?>

        <div class="menu-section">
            <div class="menu-label">Utama</div>
            <div class="menu-list">
                <a href="<?= base_url('admin/dashboard.php') ?>" class="menu-link <?= isActive('dashboard.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    Dashboard
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Kelola Data</div>
            <div class="menu-list">
                <a href="<?= base_url('admin/buku.php') ?>" class="menu-link <?= isActive('buku.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </span>
                    Buku
                </a>
                <a href="<?= base_url('admin/kategori.php') ?>" class="menu-link <?= isActive('kategori.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    </span>
                    Kategori
                </a>
                <a href="<?= base_url('admin/user.php') ?>" class="menu-link <?= isActive('user.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </span>
                    User
                </a>
                <a href="<?= base_url('admin/komentar.php') ?>" class="menu-link <?= isActive('komentar.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    Komentar
                    <?php if ($notif_komentar > 0): ?>
                        <span class="notif-badge"><?= $notif_komentar ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Pinjaman</div>
            <div class="menu-list">
                <a href="<?= base_url('admin/pinjaman.php') ?>" class="menu-link <?= isActive('pinjaman.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </span>
                    Pinjaman
                    <?php if ($notif_pending > 0): ?>
                        <span class="notif-badge"><?= $notif_pending ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Komunikasi</div>
            <div class="menu-list">
                <a href="<?= base_url('admin/pengumuman.php') ?>" class="menu-link <?= isActive('pengumuman.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                    </span>
                    Pengumuman
                </a>
                <a href="<?= base_url('admin/pesan.php') ?>" class="menu-link <?= isActive('pesan.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    Kirim Pesan
                </a>
            </div>
        </div>

    <?php elseif ($role === 'petugas'): ?>

        <div class="menu-section">
            <div class="menu-label">Utama</div>
            <div class="menu-list">
                <a href="<?= base_url('petugas/dashboard.php') ?>" class="menu-link <?= isActive('dashboard.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    Dashboard
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Pekerjaan</div>
            <div class="menu-list">
                <a href="<?= base_url('petugas/pinjaman.php') ?>" class="menu-link <?= isActive('pinjaman.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </span>
                    Pinjaman
                    <?php if ($notif_pending > 0): ?>
                        <span class="notif-badge"><?= $notif_pending ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= base_url('petugas/buku.php') ?>" class="menu-link <?= isActive('buku.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </span>
                    Daftar Buku
                </a>
                <a href="<?= base_url('petugas/komentar.php') ?>" class="menu-link <?= isActive('komentar.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    Komentar
                    <?php if ($notif_komentar > 0): ?>
                        <span class="notif-badge"><?= $notif_komentar ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Komunikasi</div>
            <div class="menu-list">
                <a href="<?= base_url('petugas/pengumuman.php') ?>" class="menu-link <?= isActive('pengumuman.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                    </span>
                    Pengumuman
                </a>
                <a href="<?= base_url('petugas/pesan.php') ?>" class="menu-link <?= isActive('pesan.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </span>
                    Kirim Pesan
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Laporan</div>
            <div class="menu-list">
                <a href="<?= base_url('petugas/laporan.php') ?>" class="menu-link <?= isActive('laporan.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </span>
                    Rekapan Laporan
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="menu-section">
            <div class="menu-label">Utama</div>
            <div class="menu-list">
                <a href="<?= base_url('anggota/dashboard.php') ?>" class="menu-link <?= isActive('dashboard.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    Dashboard
                </a>
            </div>
        </div>

        <div class="menu-section">
            <div class="menu-label">Perpustakaan</div>
            <div class="menu-list">
                <a href="<?= base_url('anggota/katalog.php') ?>" class="menu-link <?= isActive('katalog.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </span>
                    Cari Buku
                </a>
                <a href="<?= base_url('anggota/riwayat.php') ?>" class="menu-link <?= isActive('riwayat.php') ?>">
                    <span class="menu-icon">
                        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </span>
                    Riwayat Pinjam
                </a>
            </div>
        </div>

    <?php endif; ?>

    <!-- LOGOUT -->
    <div class="menu-section-logout">
        <a href="<?= base_url('auth/logout.php') ?>" class="logout-link" onclick="return confirm('Yakin ingin keluar?')">
            <span class="menu-icon">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </span>
            Logout
        </a>
    </div>

</aside>

<script>
(function() {
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('sidebarModern');
    const close   = document.getElementById('sidebarClose');

    if (!toggle || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', openSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
    if (close) close.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });

    window.addEventListener('resize', function() {
        if (window.innerWidth > 900) closeSidebar();
    });

    sidebar.querySelectorAll('.menu-link, .logout-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 900) {
                setTimeout(closeSidebar, 150);
            }
        });
    });
})();

// ==========================================
// FITUR EFEK KETIK (TYPEWRITER LOOP) — SIDEBAR BRAND
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const el = document.querySelector('.typing-text-sidebar');
    if (!el) return;

    const texts = ["SMK As-Syafi'iyah 01 Jakarta", "Perpustakaan Digital"];
    let count = 0;
    let currentText = '';
    let isDeleting = false;
    let speed = 100;
    let deletingSpeed = 50;
    let delayBetweenTexts = 2000;

    function type() {
        if (isDeleting) {
            currentText = texts[count].substring(0, currentText.length - 1);
            speed = deletingSpeed;
        } else {
            currentText = texts[count].substring(0, currentText.length + 1);
            speed = 100;
        }

        el.innerHTML = currentText + '<span class="typing-cursor"></span>';

        if (!isDeleting && currentText === texts[count]) {
            isDeleting = true;
            speed = delayBetweenTexts;
        } else if (isDeleting && currentText === '') {
            isDeleting = false;
            count = (count + 1) % texts.length;
            speed = 500;
        }

        setTimeout(type, speed);
    }

    setTimeout(type, 800);
});
</script>