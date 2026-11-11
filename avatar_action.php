<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profil.php');
}

verify_csrf();
$uid = UserSession::id();
$pdo = Database::conn();

$file = $_FILES['avatar'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    flash('error', 'Gagal upload. Pastikan Anda memilih file.');
    redirect('profil.php');
}

if ($file['size'] > 1 * 1024 * 1024) {
    flash('error', 'Ukuran file maksimal 1 MB.');
    redirect('profil.php');
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    flash('error', 'Format file harus JPEG atau PNG.');
    redirect('profil.php');
}

// Cek ekstensi asli juga (cegah double extension)
$origExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($origExt, ['jpg', 'jpeg', 'png'], true)) {
    flash('error', 'Ekstensi file tidak diizinkan.');
    redirect('profil.php');
}

$ext = $allowed[$mime];
$dir = __DIR__ . '/uploads/avatars';

if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}

// Blokir eksekusi PHP di folder upload
$htaccess = $dir . '/.htaccess';
if (!is_file($htaccess)) {
    @file_put_contents($htaccess, "php_flag engine off\nOptions -ExecCGI\nAddType text/plain .php .phtml .php3 .php4 .php5\n");
}

$storedName = 'avatar_user' . $uid . '_' . time() . '.' . $ext;
$dest = $dir . '/' . $storedName;
$dbPath = 'uploads/avatars/' . $storedName;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
    $stmt->execute(['id' => $uid]);
    $oldAvatar = $stmt->fetchColumn();

    if ($oldAvatar && str_starts_with($oldAvatar, 'uploads/avatars/') && file_exists(__DIR__ . '/' . $oldAvatar)) {
        @unlink(__DIR__ . '/' . $oldAvatar);
    }

    $pdo->prepare("UPDATE users SET avatar=:a WHERE id=:id")
        ->execute(['a' => $dbPath, 'id' => $uid]);

    $_SESSION['user']['avatar'] = $dbPath;

    $widStmt = $pdo->prepare('SELECT workspace_id FROM users WHERE id=:id');
    $widStmt->execute(['id' => $uid]);
    $wid = (int) $widStmt->fetchColumn();

    log_activity($wid, $uid, 'profile-update', 'Mengubah foto profil');
    flash('success', 'Foto profil berhasil diperbarui.');
} else {
    flash('error', 'Gagal menyimpan file. Cek izin folder uploads.');
}

redirect('profil.php');