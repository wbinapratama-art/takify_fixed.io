<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/src_database.php';
require_once __DIR__ . '/src_helper.php';
require_once __DIR__ . '/src_user_session.php';
require_once __DIR__ . '/src_task.php';
require_once __DIR__ . '/src_category.php';
require_once __DIR__ . '/src_backup.php';