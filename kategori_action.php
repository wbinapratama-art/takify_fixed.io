<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kategori.php');
verify_csrf();

$uid = UserSession::id();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        flash('error', 'Nama kategori wajib diisi.');
    } else {
        CategoryRepository::create($uid, [
            'nama'  => $nama,
            'warna' => $_POST['warna'] ?? '#3B82F6',
        ]);
        flash('success', 'Kategori ditambahkan.');
    }
} elseif ($action === 'delete') {
    CategoryRepository::delete((int)$_POST['id'], $uid);
    flash('success', 'Kategori dihapus.');
}

redirect('kategori.php');
