<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    redirect('tugas.php');
verify_csrf();

$uid = UserSession::id();
$action = $_POST['action'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

switch ($action) {
    case 'toggle':
        TaskRepository::toggleStatus($id, $uid);
        flash('success', 'Status tugas diperbarui.');
        redirect('tugas.php');

    case 'delete':
        TaskRepository::delete($id, $uid);
        flash('success', 'Tugas dihapus.');
        redirect('tugas.php');

    case 'save':
        $data = [
            'judul' => trim($_POST['judul'] ?? ''),
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
            'kategori_id' => (int) ($_POST['kategori_id'] ?? 0),
            'deadline' => $_POST['deadline'] ?? '',
            'prioritas' => $_POST['prioritas'] ?? 'sedang',
            'status' => $_POST['status'] ?? 'belum',
            'progress' => (int) ($_POST['progress'] ?? 0),
            'is_recurring' => isset($_POST['is_recurring']),
            'recurrence_rule' => $_POST['recurrence_rule'] ?? 'weekly',
            'reminder_minutes' => (int) ($_POST['reminder_minutes'] ?? 30),
            'reminder_email' => isset($_POST['reminder_email']),
        ];
        if ($data['judul'] === '' || $data['deadline'] === '') {
            flash('error', 'Judul & deadline wajib diisi.');
            redirect($id ? "tugas_form.php?id=$id" : 'tugas_form.php');
        }
        if ($id > 0) {
            TaskRepository::update($id, $uid, $data);
            flash('success', 'Tugas diperbarui.');
        } else {
            TaskRepository::create($uid, $data);
            flash('success', 'Tugas ditambahkan.');
        }
        redirect('tugas.php');
}

redirect('tugas.php');
