<?php
require __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

if (!UserSession::check()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'reason' => 'unauthenticated']);
    exit;
}

$uid = UserSession::id();
Database::conn()
    ->prepare('UPDATE users SET last_seen_at = NOW() WHERE id = :id')
    ->execute(['id' => $uid]);

echo json_encode(['ok' => true, 'ts' => date('c')]);