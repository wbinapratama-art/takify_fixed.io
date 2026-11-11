<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('kategori.php');
}
verify_csrf();

$uid = UserSession::id();
$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        flash('error', 'Nama kategori wajib diisi.');
    } else {
        CategoryRepository::create($uid, [
            'nama' => $nama,
            'warna' => $_POST['warna'] ?? '#5b55e7',
        ]);
        flash('success', 'Kategori ditambahkan.');
    }
} elseif ($action === 'update') {
    $id = (int) ($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    if ($id <= 0 || $nama === '') {
        flash('error', 'Nama kategori wajib diisi.');
    } else {
        CategoryRepository::update($id, $uid, [
            'nama' => $nama,
            'warna' => $_POST['warna'] ?? '#5b55e7',
            'deskripsi' => trim($_POST['deskripsi'] ?? '') ?: null,
        ]);
        flash('success', 'Kategori diperbarui.');
    }
} elseif ($action === 'delete') {
    CategoryRepository::delete((int) $_POST['id'], $uid);
    flash('success', 'Kategori dihapus.');
}

redirect('kategori.php');