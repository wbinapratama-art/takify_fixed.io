<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/.installed')) {
    http_response_code(403);
    exit('<h1>403</h1><p>Instalasi sudah selesai. Hapus <code>install.php</code> dan <code>schema.sql</code>.</p>');
}

$cfg = require __DIR__ . '/config.php';
$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};charset={$cfg['charset']}";

try {
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $check = $pdo->query("SHOW DATABASES LIKE '{$cfg['database']}'")->fetch();
    if ($check) {
        try {
            $hasUsers = $pdo->query("SELECT COUNT(*) FROM `{$cfg['database']}`.users")->fetchColumn();
            if ((int) $hasUsers > 0) {
                exit('<h1>⚠️ Sudah terinstall</h1><p>Database sudah memiliki user. Hapus file install.php untuk keamanan.</p>');
            }
        } catch (Throwable $e) {
            // tabel belum ada, lanjut install
        }
    }

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
    $pdo->exec("USE `{$cfg['database']}`");

    $pdo->exec("INSERT INTO workspaces (name) VALUES ('Demo')");
    $wid = (int) $pdo->lastInsertId();

    $adminHash = password_hash('Admin123456789!', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (workspace_id,name,email,password_hash,role,status) VALUES (?,?,?,?,'Admin','Aktif')");
    $stmt->execute([$wid, 'Administrator', 'admin@takify.test', $adminHash]);
    $adminId = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)")->execute([$adminId]);

    $userHash = password_hash('User123456789!', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (workspace_id,name,email,password_hash,role,status) VALUES (?,?,?,?,'Anggota','Aktif')")
        ->execute([$wid, 'Budi Demo', 'user@takify.test', $userHash]);
    $userId = (int) $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)")->execute([$userId]);

    $pdo->prepare("INSERT INTO categories (workspace_id,name,color,description,created_by) VALUES (?,?,?,?,?)")
        ->execute([$wid, 'Produk', '#5b55e7', 'Riset & pengembangan produk', $adminId]);
    $catId = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO templates (workspace_id,title,description,category_id,default_priority) VALUES (?,?,?,?,'Sedang')")
        ->execute([$wid, 'Rapat mingguan tim', 'Agenda + notulen + action item', $catId]);
    $pdo->prepare("INSERT INTO templates (workspace_id,title,description,category_id,default_priority) VALUES (?,?,?,?,'Tinggi')")
        ->execute([$wid, 'Review sprint', 'Evaluasi capaian dan blocker', $catId]);

    $pdo->prepare("INSERT INTO tasks (workspace_id,category_id,title,description,due_date,priority,status,progress,created_by,assigned_to) VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $wid,
            $catId,
            'Riset kompetitor Q4',
            'Bandingkan 5 produk sejenis',
            date('Y-m-d H:i:s', strtotime('+3 days')),
            'Sedang',
            'Sedang dikerjakan',
            40,
            $userId,
            $userId,
        ]);

    $pdo->prepare("INSERT INTO feedbacks (workspace_id,user_id,subject,type,content) VALUES (?,?,?,?,?)")
        ->execute([$wid, $userId, 'Tambah fitur dark mode', 'Saran', 'Biar mata nggak capek malam hari.']);

    $pdo->prepare("INSERT INTO activity_logs (workspace_id,user_id,action_type,description) VALUES (?,?,?,?)")
        ->execute([$wid, $adminId, 'system', 'Instalasi selesai']);

    @file_put_contents(__DIR__ . '/.installed', date('c'));

    echo '<!doctype html><html><body style="font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px">';
    echo '<h1 style="color:#38a169">✅ Instalasi Sukses</h1>';
    echo '<div style="background:#f0fff4;border:1px solid #c6f6d5;padding:16px;border-radius:8px;margin:20px 0">';
    echo '<p><b>Admin:</b> admin@takify.test / <code>Admin123456789!</code></p>';
    echo '<p><b>User :</b> user@takify.test / <code>User123456789!</code></p>';
    echo '</div>';
    echo '<p style="color:#e53e3e"><b>⚠️ PENTING:</b> Hapus file <code>install.php</code> dan <code>schema.sql</code> sekarang!</p>';
    echo '<p><a href="login.php" style="background:#5b55e7;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px">→ Ke Login</a></p>';
    echo '</body></html>';
} catch (Throwable $e) {
    echo '<h1> メ Gagal</h1><pre style="background:#fff5f5;padding:16px;border-radius:8px;overflow:auto">'
        . htmlspecialchars($e->getMessage()) . '</pre>';
}