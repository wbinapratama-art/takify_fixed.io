<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();

$user = UserSession::user();
if (($user['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

$pdo = Database::conn();
$uid = UserSession::id();

$widStmt = $pdo->prepare('SELECT workspace_id FROM users WHERE id=:id');
$widStmt->execute(['id' => $uid]);
$wid = (int) $widStmt->fetchColumn();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    exit('ID backup tidak valid.');
}

$stmt = $pdo->prepare('SELECT file_name FROM backup_logs WHERE id=:id AND workspace_id=:w AND status="Sukses"');
$stmt->execute(['id' => $id, 'w' => $wid]);
$fileName = $stmt->fetchColumn();

if (!$fileName) {
    http_response_code(404);
    exit('File backup tidak ditemukan di database.');
}

$safeName = basename($fileName);
$filePath = __DIR__ . '/backups/' . $safeName;

if (!is_file($filePath)) {
    http_response_code(404);
    exit('File backup tidak ada di folder.');
}

// Paksa browser untuk mendownload sebagai file Word
header('Content-Description: File Transfer');
header('Content-Type: application/msword'); // MIME Type untuk Word
header('Content-Disposition: attachment; filename="' . $safeName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

if (ob_get_level()) {
    ob_end_clean();
}

readfile($filePath);
exit;