<?php
$u = UserSession::user();
$active = basename($_SERVER['PHP_SELF']);
$isAdmin = ($u['role'] ?? '') === 'Admin';
$nav = $isAdmin
    ? ['Admin' => [['fitur.php?p=pengguna', 'Pengguna & akses', '♙'], ['fitur.php?p=log', 'Log & backup', '▤'], ['fitur.php?p=admin-masukan', 'Kelola masukan', '▣']]]
    : ['User' => [['dashboard.php', 'Dashboard', '▦'], ['tugas.php', 'Tugas saya', '✓'], ['kategori.php', 'Kategori', '▱'], ['fitur.php?p=template', 'Template', '▤'], ['fitur.php?p=reminder', 'Reminder', '♧'], ['fitur.php?p=anggota', 'Anggota', '♙'], ['fitur.php?p=masukan', 'Masukan', '▱']]];
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
    <link rel="icon" type="image/svg+xml" href="takify-bell.svg">
    <link rel="stylesheet" href="style.css">
</head>

<body class="app <?= $isAdmin ? 'role-admin' : 'role-user' ?>">
    <aside class="sidebar">
        <a class="logo" href="<?= $isAdmin ? 'fitur.php?p=pengguna' : 'dashboard.php' ?>">
            <img class="logo-mark" src="takify-bell.svg" alt="">
            <span>Takify</span>
        </a>
        <div class="workspace-switch workspace-switch-admin"><strong style="display:block;width:100%;text-align:center">
                <?= $isAdmin ? 'Admin' : 'User' ?>
            </strong></div>
        <?php foreach ($nav as $group => $items): ?>
            <div class="nav-group-title">
                <?= e($group) ?>
            </div>
            <nav>
                <?php foreach ($items as [$file, $label, $icon]):
                    $sectionMap = ['Template' => 'template', 'Reminder' => 'reminder', 'Anggota' => 'anggota', 'Masukan' => 'masukan', 'Pengguna & akses' => 'pengguna', 'Log & backup' => 'log', 'Kelola masukan' => 'admin-masukan'];
                    $section = $sectionMap[$label] ?? '';
                    $isActive = ($active === $file) || ($active === 'fitur.php' && $section !== '' && ($_GET['p'] ?? '') === $section); ?><a
                        href="<?= e($file) ?>" class="nav-item <?= $isActive ? 'active' : '' ?>"><span class="ico">
                            <?= e($icon) ?>
                        </span>
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endforeach; ?>
        <div class="sidebar-note"><b>
                <?= $isAdmin ? '✦ Admin sigap, tim makin mantap!' : '✦ Gas pelan-pelan, tugas kelar!' ?>
            </b><span>
                <?= $isAdmin ? 'Pantau aktivitas tim, biar semua tetap on track.' : 'Atur tugasmu biar nggak numpuk.' ?>
            </span></div>
        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="avatar">
                    <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                </div>
                <div>
                    <div class="name">
                        <?= e($u['nama']) ?>
                    </div>
                    <div class="muted small">
                        <?= $isAdmin ? 'Admin' : 'User' ?>
                    </div>
                </div>
            </div><a class="logout-link" href="logout.php">Keluar dari akun</a>
        </div>
    </aside>
    <main class="main">
        <header class="app-topbar">
            <div class="crumb"><span>
                    <?= $isAdmin ? 'Admin' : 'User' ?>
                </span><b>›</b><strong>
                    <?= e($page ?? '') ?>
                </strong></div>
            <div class="topbar-tools">
                <form action="tugas.php" method="get"><input class="global-search" name="q"
                        value="<?= e($_GET['q'] ?? '') ?>" placeholder="⌕  Cari tugas, kategori, atau anggota"></form>
                <span>Asia/Jakarta · WIB</span><span class="top-avatar">
                    <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                </span>
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
                <?php if (!empty($pageAction)): ?><a href="<?= e($pageAction['href']) ?>" class="btn btn-primary">
                        <?= e($pageAction['label']) ?>
                    </a>
                <?php elseif (!in_array($active, ['tugas_form.php', 'fitur.php'], true)): ?><a href="tugas_form.php"
                        class="btn btn-primary">＋ Tambah tugas</a>
                <?php endif; ?>
            </div>
            <?php foreach ($flash as $f): ?>
                <div class="alert alert-<?= $f['type'] === 'error' ? 'error' : 'success' ?>">
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>