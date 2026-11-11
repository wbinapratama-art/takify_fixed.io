<?php
$u = UserSession::user();
$active = basename($_SERVER['PHP_SELF']);
$isAdmin = ($u['role'] ?? '') === 'Admin';
$flash = get_flash();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>
        <?= e($page ?? 'Takify') ?> — Takify
    </title>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%235b55e7'/%3E%3Cpath d='M32 12a2 2 0 0 1 2 2v1.2c5 1.1 9 5.6 9 11.1v9l3 4v1.5H18V39.3l3-4v-9c0-5.5 4-10 9-11.1V14a2 2 0 0 1 2-2z' fill='white'/%3E%3Cpath d='M27 43a5 5 0 0 0 10 0z' fill='white'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="style.css">
</head>

<body class="app <?= $isAdmin ? 'role-admin' : 'role-user' ?>">
    <aside class="sidebar">
        <a class="logo" href="<?= $isAdmin ? 'fitur.php?p=pengguna' : 'dashboard.php' ?>">
            <div class="logo-mark">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
                    <path
                        d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
                </svg>
            </div>
            <span>Takify</span>
        </a>

        <div class="workspace-switch workspace-switch-admin">
            <strong style="display:block;width:100%;text-align:center">Menu</strong>
        </div>

        <?php if (!$isAdmin): ?>
            <!-- MENU UNTUK USER BIASA -->
            <div class="nav-group-title">USER</div>
            <nav>
                <a href="dashboard.php" class="nav-item <?= $active === 'dashboard.php' ? 'active' : '' ?>">
                    <span class="ico">▦</span> Dashboard
                </a>
                <a href="tugas.php" class="nav-item <?= $active === 'tugas.php' ? 'active' : '' ?>">
                    <span class="ico">✓</span> Tugas saya
                </a>
                <a href="kategori.php" class="nav-item <?= $active === 'kategori.php' ? 'active' : '' ?>">
                    <span class="ico">▱</span> Kategori
                </a>
                <a href="fitur.php?p=template"
                    class="nav-item <?= ($active === 'fitur.php' && ($_GET['p'] ?? '') === 'template') ? 'active' : '' ?>">
                    <span class="ico">▤</span> Pustaka Template
                </a>
                <a href="fitur.php?p=reminder"
                    class="nav-item <?= ($active === 'fitur.php' && ($_GET['p'] ?? '') === 'reminder') ? 'active' : '' ?>">
                    <span class="ico">♧</span> Reminder
                </a>
                <a href="bantuan.php" class="nav-item <?= $active === 'bantuan.php' ? 'active' : '' ?>">
                    <span class="ico">?</span> Bantuan
                </a>
            </nav>
        <?php else: ?>
            <!-- MENU UNTUK ADMIN -->
            <div class="nav-group-title">ADMIN</div>
            <nav>
                <a href="fitur.php?p=pengguna"
                    class="nav-item <?= ($active === 'fitur.php' && ($_GET['p'] ?? '') === 'pengguna') ? 'active' : '' ?>">
                    <span class="ico">♙</span> Pengguna & akses
                </a>
                <a href="fitur.php?p=template"
                    class="nav-item <?= ($active === 'fitur.php' && ($_GET['p'] ?? '') === 'template') ? 'active' : '' ?>">
                    <span class="ico">▤</span> Pustaka Template
                </a>
                <a href="fitur.php?p=log"
                    class="nav-item <?= ($active === 'fitur.php' && ($_GET['p'] ?? '') === 'log') ? 'active' : '' ?>">
                    <span class="ico">▥</span> Log & backup
                </a>
                <a href="fitur.php?p=admin-masukan"
                    class="nav-item <?= ($active === 'fitur.php' && ($_GET['p'] ?? '') === 'admin-masukan') ? 'active' : '' ?>">
                    <span class="ico">▣</span> Kelola masukan
                </a>
            </nav>
        <?php endif; ?>

        <div class="sidebar-note">
            <b>
                <?= $isAdmin ? '✦ Admin sigap, tim makin mantap!' : '✦ Gas pelan-pelan, tugas kelar!' ?>
            </b>
            <span>
                <?= $isAdmin ? 'Pantau aktivitas tim, biar semua tetap on track.' : 'Atur tugasmu biar nggak numpuk.' ?>
            </span>
        </div>

        <div class="sidebar-footer">
            <a href="profil.php" class="user-chip" style="text-decoration: none; color: inherit;">
                <div class="avatar" style="overflow: hidden;">
                    <?php if (!empty($u['avatar']) && file_exists(__DIR__ . '/' . $u['avatar'])): ?>
                        <img src="<?= e($u['avatar']) ?>" alt="Avatar"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="name">
                        <?= e($u['nama']) ?>
                    </div>
                </div>
            </a>
        </div>
    </aside>

    <main class="main">
        <header class="app-topbar">
            <div class="crumb">
                <strong>
                    <?= e($page ?? '') ?>
                </strong>
            </div>
            <div class="topbar-tools">
                <form action="tugas.php" method="get">
                    <input class="global-search" name="q" value="<?= e($_GET['q'] ?? '') ?>"
                        placeholder="⌕  Cari tugas, kategori, atau anggota">
                </form>
                <span>Asia/Jakarta · WIB</span>
            </div>
        </header>
        <div class="page-content">
            <div class="page-heading">
                <div>
                    <h1>
                        <?= e($page ?? '') ?>
                    </h1>
                    <p>
                        <?= e($subtitle ?? 'Prioritaskan yang penting. Selesaikan satu per satu.') ?>
                    </p>
                </div>
                <?php if (!empty($pageAction)): ?>
                    <a href="<?= e($pageAction['href']) ?>" class="btn btn-primary">
                        <?= e($pageAction['label']) ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php foreach ($flash as $f): ?>
                <div class="alert alert-<?= $f['type'] === 'error' ? 'error' : 'success' ?>">
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>