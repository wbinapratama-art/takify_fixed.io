<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$pdo = Database::conn();
$uid = UserSession::id();
$user = UserSession::user();
$widStmt = $pdo->prepare('SELECT workspace_id FROM users WHERE id=:id');
$widStmt->execute(['id' => $uid]);
$wid = (int) $widStmt->fetchColumn();

$view = $_GET['p'] ?? 'template';
$adminViews = ['pengguna', 'log', 'admin-masukan'];
if (in_array($view, $adminViews, true) && ($user['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Akses ditolak.');
}

// ============================================================
// POST HANDLERS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create-template' && ($user['role'] ?? '') === 'Admin') {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $catId = (int) ($_POST['category_id'] ?? 0) ?: null;
        $priority = in_array($_POST['priority'] ?? '', ['Rendah', 'Sedang', 'Tinggi']) ? $_POST['priority'] : 'Sedang';
        if ($title !== '') {
            $pdo->prepare('INSERT INTO templates (workspace_id,title,description,category_id,default_priority,created_by) VALUES (:w,:t,:d,:c,:p,:u)')
                ->execute(['w' => $wid, 't' => $title, 'd' => $desc ?: null, 'c' => $catId, 'p' => $priority, 'u' => $uid]);
            flash('success', 'Template berhasil ditambahkan.');
        } else {
            flash('error', 'Judul template wajib diisi.');
        }
        redirect('fitur.php?p=template');
    }

    if ($action === 'delete-template' && ($user['role'] ?? '') === 'Admin') {
        $tplId = (int) ($_POST['template_id'] ?? 0);
        $pdo->prepare('DELETE FROM templates WHERE id=:id')->execute(['id' => $tplId]);
        flash('success', 'Template dihapus.');
        redirect('fitur.php?p=template');
    }

    if ($action === 'send-feedback') {
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'Saran';
        if ($subject !== '' && $content !== '') {
            $stmt = $pdo->prepare('INSERT INTO feedbacks(workspace_id,user_id,subject,type,content) VALUES(:w,:u,:s,:t,:c)');
            $stmt->execute(['w' => $wid, 'u' => $uid, 's' => $subject, 't' => $type, 'c' => $content]);
            flash('success', 'Masukan berhasil dikirim.');
        }
        redirect('fitur.php?p=masukan');
    }

    if ($action === 'invite-member' && ($user['role'] ?? '') === 'Admin') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'Anggota';
        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Nama dan email valid wajib diisi.');
        } elseif (strlen($pass) < 12) {
            flash('error', 'Kata sandi minimal 12 karakter.');
        } else {
            [$ok, $msg] = UserSession::createMember($uid, $nama, $email, $pass, $role);
            flash($ok ? 'success' : 'error', $ok ? 'Undangan dibuat.' : $msg);
        }
        redirect('fitur.php?p=anggota');
    }

    if ($action === 'save-preferences') {
        $sql = 'INSERT INTO user_preferences(user_id,app_notification,email_notification,daily_summary,default_reminder_minutes,quiet_hours_start,quiet_hours_end,timezone) VALUES(:u,:a,:e,:d,:m,:qs,:qe,:tz) ON DUPLICATE KEY UPDATE app_notification=VALUES(app_notification),email_notification=VALUES(email_notification),daily_summary=VALUES(daily_summary),default_reminder_minutes=VALUES(default_reminder_minutes),quiet_hours_start=VALUES(quiet_hours_start),quiet_hours_end=VALUES(quiet_hours_end),timezone=VALUES(timezone)';
        $pdo->prepare($sql)->execute([
            'u' => $uid,
            'a' => isset($_POST['app_notification']) ? 1 : 0,
            'e' => isset($_POST['email_notification']) ? 1 : 0,
            'd' => isset($_POST['daily_summary']) ? 1 : 0,
            'm' => (int) ($_POST['minutes'] ?? 30),
            'qs' => ($_POST['quiet_start'] ?? '') ?: null,
            'qe' => ($_POST['quiet_end'] ?? '') ?: null,
            'tz' => 'Asia/Jakarta',
        ]);
        flash('success', 'Preferensi disimpan.');
        redirect('fitur.php?p=reminder');
    }

    if ($action === 'reply-feedback' && ($user['role'] ?? '') === 'Admin') {
        $status = $_POST['status'] ?? 'Baru';
        $fbId = (int) ($_POST['feedback_id'] ?? 0);
        $pdo->prepare('UPDATE feedbacks SET status=:s,admin_response=:r WHERE id=:id')
            ->execute(['s' => $status, 'r' => trim($_POST['response'] ?? '') ?: null, 'id' => $fbId]);
        flash('success', 'Tanggapan disimpan.');
        redirect('fitur.php?p=admin-masukan&id=' . $fbId);
    }

    if ($action === 'delete-feedback' && ($user['role'] ?? '') === 'Admin') {
        $fbId = (int) ($_POST['feedback_id'] ?? 0);
        $pdo->prepare('DELETE FROM feedbacks WHERE id=:id')->execute(['id' => $fbId]);
        flash('success', 'Masukan dihapus.');
        redirect('fitur.php?p=admin-masukan');
    }

    if ($action === 'update-user' && ($user['role'] ?? '') === 'Admin') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId === $uid) {
            flash('error', 'Tidak bisa mengubah role sendiri.');
        } else {
            $newRole = in_array($_POST['role'] ?? '', ['Anggota', 'Pengamat']) ? $_POST['role'] : 'Anggota';
            $newStatus = in_array($_POST['status'] ?? '', ['Aktif', 'Nonaktif']) ? $_POST['status'] : 'Aktif';
            $pdo->prepare('UPDATE users SET role=:r, status=:s WHERE id=:id AND role<>"Admin"')
                ->execute(['r' => $newRole, 's' => $newStatus, 'id' => $targetId]);
            flash('success', 'Data user diperbarui.');
        }
        redirect('fitur.php?p=pengguna');
    }

    if ($action === 'reset-password' && ($user['role'] ?? '') === 'Admin') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $newPass = (string) ($_POST['new_password'] ?? '');
        if (strlen($newPass) >= 12) {
            $pdo->prepare('UPDATE users SET password_hash=:p WHERE id=:id AND role<>"Admin"')
                ->execute(['p' => password_hash($newPass, PASSWORD_BCRYPT), 'id' => $targetId]);
            flash('success', 'Password direset.');
        } else {
            flash('error', 'Minimal 12 karakter.');
        }
        redirect('fitur.php?p=pengguna');
    }

    if ($action === 'delete-user' && ($user['role'] ?? '') === 'Admin') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId !== $uid) {
            $pdo->prepare('DELETE FROM users WHERE id=:id AND role<>"Admin"')->execute(['id' => $targetId]);
            flash('success', 'User dihapus.');
        }
        redirect('fitur.php?p=pengguna');
    }

    if ($action === 'create-backup' && ($user['role'] ?? '') === 'Admin') {
        try {
            $result = create_backup($wid);
            $pdo->prepare('INSERT INTO backup_logs (workspace_id,file_name,file_size,status) VALUES (:w,:f,:s,"Sukses")')
                ->execute(['w' => $wid, 'f' => $result['filename'], 's' => $result['size']]);
            flash('success', 'Backup berhasil.');
        } catch (Throwable $e) {
            flash('error', 'Backup gagal: ' . $e->getMessage());
        }
        redirect('fitur.php?p=log');
    }

    if ($action === 'delete-backup' && ($user['role'] ?? '') === 'Admin') {
        $backupId = (int) ($_POST['backup_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT file_name FROM backup_logs WHERE id=:id AND workspace_id=:w');
        $stmt->execute(['id' => $backupId, 'w' => $wid]);
        if ($file = $stmt->fetchColumn()) {
            @unlink(__DIR__ . '/backups/' . basename($file));
            $pdo->prepare('DELETE FROM backup_logs WHERE id=:id AND workspace_id=:w')->execute(['id' => $backupId, 'w' => $wid]);
            flash('success', 'Backup dihapus.');
        }
        redirect('fitur.php?p=log');
    }
}

// ============================================================
// VIEW DATA
// ============================================================
$names = ['template' => 'Pustaka template', 'reminder' => 'Reminder & notifikasi', 'masukan' => 'Masukan', 'anggota' => 'Anggota', 'pengguna' => 'Pengguna & akses', 'log' => 'Log & backup', 'admin-masukan' => 'Kelola masukan'];
$page = $names[$view] ?? $names['template'];
$subtitle = match ($view) {
    'template' => 'Bangun kebiasaan kerja yang konsisten dengan template siap pakai.',
    'reminder' => 'Pengingat tepat waktu, tanpa mengganggu waktu istirahat.',
    'masukan' => 'Punya ide atau menemukan kendala? Bantu kami membuat Takify lebih baik.',
    'anggota' => 'Undang orang lain agar tugas bisa dikerjakan bersama.',
    'pengguna' => 'Kelola user dan batas tanggung jawabnya.',
    'log' => 'Pantau aktivitas dan riwayat backup.',
    'admin-masukan' => 'Baca ulasan anggota, berikan tanggapan, dan pantau tindak lanjutnya.',
    default => ''
};
$pageAction = match ($view) {
    'anggota' => ($user['role'] ?? '') === 'Admin' ? ['href' => '#invite', 'label' => '＋ Undang anggota'] : null,
    'template' => ($user['role'] ?? '') === 'Admin' ? ['href' => '#form-template', 'label' => '＋ Tambah template'] : null,
    default => null
};
require __DIR__ . '/header.php';
?>

<?php if ($view === 'template'):
    // Filter workspace DIHAPUS agar semua template terlihat
    $s = $pdo->prepare('SELECT t.*,c.name category_name FROM templates t LEFT JOIN categories c ON c.id=t.category_id ORDER BY t.created_at DESC');
    $s->execute();
    $templates = $s->fetchAll();
    ?>
    <?php if (($user['role'] ?? '') === 'Admin'): ?>
        <section class="card form" id="form-template" style="margin-bottom: 24px;">
            <h3>Tambah Template Baru</h3>
            <form method="post">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="create-template">
                <div class="form-row">
                    <label>Judul *<input name="title" required maxlength="200"></label>
                    <label>Prioritas<select name="priority">
                            <option>Rendah</option>
                            <option selected>Sedang</option>
                            <option>Tinggi</option>
                        </select></label>
                </div>
                <label>Deskripsi<textarea name="description" rows="2"></textarea></label>
                <div class="form-row">
                    <label>Kategori
                        <select name="category_id">
                            <option value="">Tanpa Kategori</option>
                            <?php
                            // Filter workspace DIHAPUS agar semua kategori muncul di dropdown
                            $catStmt = $pdo->prepare('SELECT id,name FROM categories ORDER BY name');
                            $catStmt->execute();
                            foreach ($catStmt->fetchAll() as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    <?= e($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div style="display:flex; align-items:flex-end;"><button class="btn btn-primary"
                            style="width:100%;">Simpan</button></div>
                </div>
            </form>
        </section>
    <?php endif; ?>
    <div class="template-grid" id="template-list">
        <?php foreach ($templates as $t): ?>
            <article class="card template-card" style="display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div class="template-icon">▤</div>
                    <?php if (($user['role'] ?? '') === 'Admin'): ?>
                        <form method="post" onsubmit="return confirm('Hapus template ini?')" style="margin:0;">
                            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                                value="delete-template"><input type="hidden" name="template_id" value="<?= (int) $t['id'] ?>">
                            <button class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    <?php endif; ?>
                </div>
                <h3 style="margin-top: 12px;">
                    <?= e($t['title']) ?>
                </h3>
                <p class="muted" style="flex: 1;">
                    <?= e($t['description'] ?: 'Template tugas.') ?>
                </p>
                <div class="template-meta" style="margin-bottom: 12px;"><span class="chip">
                        <?= e($t['category_name'] ?: 'Umum') ?>
                    </span><span>
                        <?= e($t['default_priority']) ?>
                    </span></div>
                <a class="btn btn-primary" style="width: 100%;" href="tugas_form.php?template=<?= (int) $t['id'] ?>">＋
                    Gunakan</a>
            </article>
        <?php endforeach; ?>
        <?php if (!$templates): ?>
            <div class="card empty" style="grid-column: 1 / -1;">Belum ada template.</div>
        <?php endif; ?>
    </div>

<?php elseif ($view === 'reminder'):
    $s = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id=:u');
    $s->execute(['u' => $uid]);
    $prefs = $s->fetch() ?: [];
    $s = $pdo->prepare('SELECT r.*,t.title FROM reminders r LEFT JOIN tasks t ON t.id=r.task_id WHERE r.user_id=:u ORDER BY r.remind_at DESC LIMIT 8');
    $s->execute(['u' => $uid]);
    $reminders = $s->fetchAll(); ?>
    <div class="detail-columns">
        <form method="post" class="card form">
            <h3>Preferensi pribadi</h3>
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                value="save-preferences">
            <label class="check-label"><input type="checkbox" name="app_notification" <?= !isset($prefs['app_notification']) || $prefs['app_notification'] ? 'checked' : '' ?>> Notifikasi aplikasi</label>
            <label class="check-label"><input type="checkbox" name="email_notification"
                    <?= !isset($prefs['email_notification']) || $prefs['email_notification'] ? 'checked' : '' ?>>
                Email</label>
            <label class="check-label"><input type="checkbox" name="daily_summary" <?= !empty($prefs['daily_summary']) ? 'checked' : '' ?>> Ringkasan harian</label>
            <label>Pengingat default
                <select name="minutes">
                    <?php foreach ([10, 30, 60, 1440] as $m): ?>
                        <option value="<?= $m ?>" <?= (int) ($prefs['default_reminder_minutes'] ?? 30) === $m ? 'selected' : '' ?>>
                            <?= $m < 60 ? $m . ' menit' : ($m === 60 ? '1 jam' : '1 hari') ?> sebelum tenggat
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <h3>Jam tenang</h3>
            <div class="form-row">
                <label>Mulai<input type="time" name="quiet_start"
                        value="<?= e($prefs['quiet_hours_start'] ?? '22:00') ?>"></label>
                <label>Selesai<input type="time" name="quiet_end"
                        value="<?= e($prefs['quiet_hours_end'] ?? '07:00') ?>"></label>
            </div>
            <button class="btn btn-primary">Simpan preferensi</button>
        </form>
        <section class="card">
            <h3>Reminder terjadwal</h3>
            <?php foreach ($reminders as $r): ?>
                <div class="simple-row">
                    <div><b>
                            <?= e($r['title'] ?? 'Pengingat umum') ?>
                        </b><small>
                            <?= date('d M Y · H:i', strtotime($r['remind_at'])) ?> ·
                            <?= e($r['channel']) ?>
                        </small></div><span class="badge badge-sedang">
                        <?= e($r['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
            <?php if (!$reminders): ?>
                <p class="muted">Belum ada reminder terjadwal.</p>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($view === 'masukan'):
    $s = $pdo->prepare('SELECT * FROM feedbacks WHERE workspace_id=:w AND user_id=:u ORDER BY created_at DESC');
    $s->execute(['w' => $wid, 'u' => $uid]);
    $feedbacks = $s->fetchAll(); ?>
    <div class="detail-columns">
        <form method="post" class="card form">
            <h3>Sampaikan masukan</h3>
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                value="send-feedback">
            <label>Subjek *<input name="subject" maxlength="150" required></label>
            <label>Jenis masukan<select name="type">
                    <option>Saran</option>
                    <option>Kendala</option>
                    <option>Lainnya</option>
                </select></label>
            <label>Isi masukan *<textarea name="content" rows="6" required></textarea></label>
            <button class="btn btn-primary">Kirim masukan</button>
        </form>
        <section class="card">
            <h3>Riwayat masukan saya</h3>
            <?php foreach ($feedbacks as $f): ?>
                <article class="feedback-item">
                    <span class="badge status-<?= e(strtolower($f['status'])) ?>">
                        <?= e($f['status']) ?>
                    </span>
                    <small>
                        <?= e($f['type']) ?> ·
                        <?= date('d M Y · H:i', strtotime($f['created_at'])) ?>
                    </small>
                    <h4>
                        <?= e($f['subject']) ?>
                    </h4>
                    <p>
                        <?= e($f['content']) ?>
                    </p>
                    <?php if ($f['admin_response']): ?>
                        <blockquote><b>Tanggapan admin</b>
                            <p>
                                <?= e($f['admin_response']) ?>
                            </p>
                        </blockquote>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
            <?php if (!$feedbacks): ?>
                <p class="muted">Belum ada masukan.</p>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($view === 'admin-masukan'):
    $filterStatus = $_GET['status'] ?? '';

    $fbSql = 'SELECT f.*,u.name,u.email,u.avatar FROM feedbacks f JOIN users u ON u.id=f.user_id WHERE 1=1';
    $fbParams = [];
    if (in_array($filterStatus, ['Baru', 'Diproses', 'Selesai'], true)) {
        $fbSql .= ' AND f.status=:s';
        $fbParams['s'] = $filterStatus;
    }
    $fbSql .= ' ORDER BY f.created_at DESC';
    $s = $pdo->prepare($fbSql);
    $s->execute($fbParams);
    $feedbacks = $s->fetchAll();

    $allStmt = $pdo->prepare('SELECT status, rating FROM feedbacks');
    $allStmt->execute();
    $allFeedbacks = $allStmt->fetchAll();

    $totalRating = 0;
    $countRating = 0;
    $starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($allFeedbacks as $af) {
        $r = (int) ($af['rating'] ?? 5);
        $totalRating += $r;
        $countRating++;
        if ($r >= 1 && $r <= 5)
            $starCounts[$r]++;
    }
    $avgRating = $countRating > 0 ? round($totalRating / $countRating, 1) : 0;
    $avgDisplay = number_format($avgRating, 1, ',', '.');

    $selected = null;
    $selId = (int) ($_GET['id'] ?? ($feedbacks[0]['id'] ?? 0));
    foreach ($feedbacks as $f)
        if ((int) $f['id'] === $selId)
            $selected = $f;
    ?>
    <div class="grid-2" style="grid-template-columns: minmax(0, 1.8fr) minmax(300px, 1fr); align-items: start; gap: 24px;">
        <div>
            <!-- Ringkasan Rating Google Play Style -->
            <div style="display: flex; gap: 40px; align-items: center; margin-bottom: 30px;">
                <div>
                    <div style="font-size: 56px; font-weight: 500; color: #1f1f1f; line-height: 1;">
                        <?= $avgDisplay ?>
                    </div>
                    <div style="color: #01875f; font-size: 18px; letter-spacing: 2px; margin: 8px 0;">
                        <?php
                        $fullStars = floor($avgRating);
                        $halfStar = ($avgRating - $fullStars) >= 0.5;
                        echo str_repeat('★', $fullStars);
                        if ($halfStar)
                            echo '⯪';
                        echo str_repeat('☆', 5 - $fullStars - ($halfStar ? 1 : 0));
                        ?>
                    </div>
                    <div style="font-size: 13px; color: var(--muted);">
                        <?= count($allFeedbacks) ?> ulasan
                    </div>
                </div>
                <div style="flex: 1; max-width: 400px; display: flex; flex-direction: column; gap: 6px;">
                    <?php foreach ([5, 4, 3, 2, 1] as $star):
                        $pct = $countRating > 0 ? round(($starCounts[$star] / $countRating) * 100) : 0;
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--muted);">
                            <span style="width: 10px;">
                                <?= $star ?>
                            </span>
                            <div style="flex: 1; height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                                <div style="width: <?= $pct ?>%; height: 100%; background: #01875f;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-tabs" style="margin-bottom: 20px; border-bottom: none;">
                <a href="fitur.php?p=admin-masukan" class="tab <?= $filterStatus === '' ? 'active' : '' ?>"
                    style="text-decoration: none;">Semua</a>
                <?php foreach (['Baru', 'Diproses', 'Selesai'] as $state): ?>
                    <a href="fitur.php?p=admin-masukan&amp;status=<?= $state ?>"
                        class="tab <?= $filterStatus === $state ? 'active' : '' ?>" style="text-decoration: none;">
                        <?= $state ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; flex-direction: column; gap: 24px;">
                <?php foreach ($feedbacks as $f):
                    $starCount = (int) ($f['rating'] ?? 5);
                    $initial = strtoupper(substr($f['name'], 0, 1));
                    $bgColors = ['#1976d2', '#e64a19', '#388e3c', '#7b1fa2', '#fbc02d'];
                    $bgColor = $bgColors[ord($initial) % 5];
                    ?>
                    <div style="display: flex; gap: 16px; padding-bottom: 24px; border-bottom: 1px solid var(--border);">
                        <!-- Avatar (Gambar jika ada, huruf jika tidak ada) -->
                        <div
                            style="width: 40px; height: 40px; border-radius: 50%; background: <?= $bgColor ?>; color: #fff; display: grid; place-items: center; font-weight: 600; font-size: 18px; flex-shrink: 0; overflow: hidden;">
                            <?php if (!empty($f['avatar']) && file_exists(__DIR__ . '/' . $f['avatar'])): ?>
                                <img src="<?= e($f['avatar']) ?>" alt="Avatar"
                                    style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <?= e($initial) ?>
                            <?php endif; ?>
                        </div>

                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="font-weight: 500; font-size: 14px; color: var(--text);">
                                    <?= e($f['name']) ?>
                                </div>
                                <div style="position: relative;">
                                    <button type="button" onclick="toggleMenu(<?= (int) $f['id'] ?>)"
                                        style="background: none; border: none; cursor: pointer; color: var(--muted); font-size: 20px; line-height: 1; padding: 0 5px;">⋮</button>
                                    <div id="menu-<?= (int) $f['id'] ?>"
                                        style="display: none; position: absolute; right: 0; top: 25px; background: #fff; border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 10; min-width: 120px;">
                                        <button type="button" onclick="toggleMenu(<?= (int) $f['id'] ?>)"
                                            style="display: block; width: 100%; text-align: left; padding: 10px 16px; background: none; border: none; border-bottom: 1px solid var(--border); cursor: pointer; font-size: 13px; color: var(--text);">Tutup</button>
                                        <button type="button" onclick="hapusUlasan(<?= (int) $f['id'] ?>)"
                                            style="display: block; width: 100%; text-align: left; padding: 10px 16px; background: none; border: none; cursor: pointer; font-size: 13px; color: var(--danger);">Hapus</button>
                                    </div>
                                </div>
                            </div>
                            <div
                                style="display: flex; align-items: center; gap: 8px; margin: 4px 0 8px 0; font-size: 12px; color: var(--muted);">
                                <span style="color: #01875f; letter-spacing: 1px;">
                                    <?= str_repeat('★', $starCount) . str_repeat('☆', 5 - $starCount) ?>
                                </span>
                                <span>
                                    <?= date('d F Y', strtotime($f['created_at'])) ?>
                                </span>
                                <span class="badge status-<?= strtolower($f['status']) ?>"
                                    style="font-size: 10px; padding: 2px 6px;">
                                    <?= e($f['status']) ?>
                                </span>
                            </div>
                            <div style="font-size: 14px; color: #333; line-height: 1.6; white-space: pre-wrap;">
                                <?= nl2br(e($f['content'])) ?>
                            </div>
                            <?php if ($f['admin_response']): ?>
                                <div
                                    style="margin-top: 12px; background: #f0fdf4; padding: 12px; border-radius: 6px; border-left: 3px solid #22c55e;">
                                    <b style="font-size: 11px; color: #166534;">Balasan Anda:</b>
                                    <p style="font-size: 13px; margin: 4px 0 0 0; color: #166534;">
                                        <?= nl2br(e($f['admin_response'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$feedbacks): ?>
                    <p class="muted" style="text-align: center; padding: 40px 0;">Belum ada ulasan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Panel Balasan Admin -->
        <?php if ($selected): ?>
            <div style="position: sticky; top: 90px;">
                <form method="post" class="card form" style="margin-bottom: 0; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <h3 style="margin-top: 0; margin-bottom: 8px;">Balas Ulasan</h3>
                    <p class="muted small" style="margin-top: 0; margin-bottom: 16px;">Membalas: <b>
                            <?= e($selected['name']) ?>
                        </b></p>
                    <div
                        style="background: #f9f9fb; padding: 12px; border-radius: 6px; font-size: 13px; color: #555; margin-bottom: 16px; border: 1px solid var(--border);">
                        <b>
                            <?= e($selected['subject']) ?>
                        </b><br><span style="font-size: 11px; color: #888;">(
                            <?= e($selected['type']) ?>)
                        </span>
                        <p style="margin: 8px 0 0 0;">
                            <?= nl2br(e($selected['content'])) ?>
                        </p>
                    </div>
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                        value="reply-feedback"><input type="hidden" name="feedback_id" value="<?= (int) $selected['id'] ?>">
                    <label>Tanggapan Admin<textarea name="response"
                            rows="5"><?= e($selected['admin_response'] ?? '') ?></textarea></label>
                    <label>Ubah status
                        <select name="status">
                            <?php foreach (['Baru', 'Diproses', 'Selesai'] as $st): ?>
                                <option <?= $selected['status'] === $st ? 'selected' : '' ?>>
                                    <?= $st ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn btn-primary" style="margin-top: 8px;">Simpan Tanggapan</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleMenu(id) {
            const menu = document.getElementById('menu-' + id);
            document.querySelectorAll('[id^="menu-"]').forEach(el => {
                if (el.id !== 'menu-' + id) el.style.display = 'none';
            });
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }

        function hapusUlasan(id) {
            if (confirm('Apakah Anda yakin ingin menghapus ulasan ini secara permanen?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'fitur.php?p=admin-masukan';

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden'; csrfInput.name = '_csrf'; csrfInput.value = '<?= csrf_token() ?>';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden'; actionInput.name = 'action'; actionInput.value = 'delete-feedback';

                const idInput = document.createElement('input');
                idInput.type = 'hidden'; idInput.name = 'feedback_id'; idInput.value = id;

                form.appendChild(csrfInput); form.appendChild(actionInput); form.appendChild(idInput);
                document.body.appendChild(form); form.submit();
            }
        }

        document.addEventListener('click', function (event) {
            if (!event.target.closest('div[style*="position: relative"]')) {
                document.querySelectorAll('[id^="menu-"]').forEach(el => el.style.display = 'none');
            }
        });
    </script>

<?php elseif ($view === 'anggota'):
    $s = $pdo->prepare("SELECT name,email,role,status,last_active_at,last_seen_at,avatar FROM users WHERE role<>'Admin' ORDER BY name");
    $s->execute();
    $workspaceMembers = $s->fetchAll();
    ?>
    <?php if (($user['role'] ?? '') === 'Admin'): ?>
        <section class="card form" id="invite">
            <h3>Undang anggota</h3>
            <form method="post" id="invite-form">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                    value="invite-member">
                <div class="form-row"><label>Nama lengkap *<input name="nama" maxlength="100" required></label><label>Email
                        *<input type="email" name="email" required></label></div>
                <div class="form-row"><label>Role<select name="role">
                            <option>Anggota</option>
                            <option>Pengamat</option>
                        </select></label><label>Kata sandi sementara *<input name="password" minlength="12" required></label>
                </div>
                <button class="btn" type="button" id="gen-pass">Buat kata sandi otomatis</button>
                <button class="btn btn-primary" type="submit">Kirim undangan</button>
            </form>
        </section>
        <script>
            document.getElementById('gen-pass')?.addEventListener('click', () => {
                const chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                const buf = new Uint32Array(14); crypto.getRandomValues(buf);
                document.querySelector('#invite-form input[name="password"]').value = [...buf].map((n) => chars[n % chars.length]).join('');
            });
        </script>
    <?php endif; ?>
    <section class="card" id="members">
        <h3>Anggota <small class="muted">
                <?= count($workspaceMembers) ?> orang
            </small></h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aktivitas terakhir (WIB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workspaceMembers as $m):
                        $online = UserSession::isOnline($m['last_seen_at'] ?? null);
                        $last = $m['last_seen_at'] ?: $m['last_active_at']; ?>
                        <tr>
                            <td style="display:flex; align-items:center; gap:10px;">
                                <div class="avatar"
                                    style="width:28px; height:28px; font-size:11px; overflow:hidden; flex-shrink:0;">
                                    <?php if (!empty($m['avatar']) && file_exists(__DIR__ . '/' . $m['avatar'])): ?><img
                                            src="<?= e($m['avatar']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div><b>
                                        <?= e($m['name']) ?>
                                    </b><small>
                                        <?= e($m['email']) ?>
                                    </small></div>
                            </td>
                            <td><span class="badge">
                                    <?= e($m['role']) ?>
                                </span></td>
                            <td><span class="badge status-<?= $online ? 'aktif' : 'offline' ?>">
                                    <?= $online ? 'Online' : 'Tidak aktif' ?>
                                </span></td>
                            <td>
                                <?= $last ? date('d M · H:i', strtotime($last)) : 'Belum pernah' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php elseif ($view === 'pengguna'):
    $s = $pdo->prepare("SELECT id,name,email,role,status,last_active_at,last_seen_at,avatar FROM users WHERE role<>'Admin' ORDER BY name");
    $s->execute();
    $members = $s->fetchAll();

    $selectedMember = null;
    $selEmail = $_GET['email'] ?? '';
    foreach ($members as $member) {
        if ($member['email'] === $selEmail) {
            $selectedMember = $member;
            break;
        }
    }
    ?>
    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-label">Total Anggota</div>
            <div class="stat-value">
                <?= count($members) ?>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-label">Sedang Online</div>
            <div class="stat-value" style="color: var(--success);">
                <?= count(array_filter($members, fn($m) => UserSession::isOnline($m['last_seen_at'] ?? null))) ?>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-label">Akun Dinonaktifkan</div>
            <div class="stat-value" style="color: var(--danger);">
                <?= count(array_filter($members, fn($m) => $m['status'] === 'Nonaktif')) ?>
            </div>
        </article>
    </section>
    <section class="card" id="members">
        <h3>Daftar Anggota</h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Status Pantauan</th>
                        <th>Aktivitas terakhir</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m):
                        $online = UserSession::isOnline($m['last_seen_at'] ?? null);
                        $last = $m['last_seen_at'] ?: $m['last_active_at'];
                        $displayStatus = $m['status'] === 'Nonaktif' ? 'Nonaktif' : ($online ? 'Online' : 'Offline');
                        $badgeClass = $m['status'] === 'Nonaktif' ? 'offline' : ($online ? 'aktif' : 'offline'); ?>
                        <tr>
                            <td style="display:flex; align-items:center; gap:10px;">
                                <div class="avatar"
                                    style="width:32px; height:32px; font-size:12px; overflow:hidden; flex-shrink:0;">
                                    <?php if (!empty($m['avatar']) && file_exists(__DIR__ . '/' . $m['avatar'])): ?><img
                                            src="<?= e($m['avatar']) ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div><b><a href="fitur.php?p=pengguna&amp;email=<?= rawurlencode($m['email']) ?>#member-detail">
                                            <?= e($m['name']) ?>
                                        </a></b><small>
                                        <?= e($m['email']) ?>
                                    </small></div>
                            </td>
                            <td><span class="badge">
                                    <?= e($m['role']) ?>
                                </span></td>
                            <td><span class="badge status-<?= $badgeClass ?>">
                                    <?= $displayStatus ?>
                                </span></td>
                            <td>
                                <?= $last ? date('d M · H:i', strtotime($last)) : 'Belum pernah' ?>
                            </td>
                            <td><button type="button" class="btn btn-sm"
                                    onclick="ubahPassword(<?= (int) $m['id'] ?>, '<?= e($m['name']) ?>')">Ubah</button></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$members): ?>
                        <tr>
                            <td colspan="5" class="muted" style="text-align: center; padding: 20px;">Belum ada
                                anggota.<br><br><a href="fitur.php?p=anggota#invite" class="btn btn-primary btn-sm">＋ Undang
                                    Anggota</a></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <form id="form-reset-pass" method="post" style="display: none;"><input type="hidden" name="_csrf"
            value="<?= csrf_token() ?>"><input type="hidden" name="action" value="reset-password"><input type="hidden"
            name="user_id" id="reset-user-id" value=""><input type="hidden" name="new_password" id="reset-new-password"
            value=""></form>
    <script>
        function ubahPassword(userId, userName) {
            let newPass = prompt("Password baru (Minimal 12 karakter):");
            if (newPass !== null) {
                if (newPass.length < 12) { alert("Minimal 12 karakter!"); return; }
                document.getElementById('reset-user-id').value = userId; document.getElementById('reset-new-password').value = newPass; document.getElementById('form-reset-pass').submit();
            }
        }
    </script>
    <?php if ($selectedMember): ?>
        <section class="card" id="member-detail" style="margin-top: 24px;">
            <h3>Kelola akun:
                <?= e($selectedMember['name']) ?>
            </h3>
            <div class="grid-2" style="margin-top:14px">
                <form method="post" class="form">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                        value="update-user"><input type="hidden" name="user_id" value="<?= (int) $selectedMember['id'] ?>">
                    <h4>Edit role & status akun</h4>
                    <label>Role<select name="role">
                            <option value="Anggota" <?= $selectedMember['role'] === 'Anggota' ? 'selected' : '' ?>>Anggota</option>
                            <option value="Pengamat" <?= $selectedMember['role'] === 'Pengamat' ? 'selected' : '' ?>>Pengamat
                            </option>
                        </select></label>
                    <label>Status Akun<select name="status">
                            <option value="Aktif" <?= $selectedMember['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= $selectedMember['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif
                            </option>
                        </select></label>
                    <button class="btn btn-primary">Simpan</button>
                </form>
                <form method="post" class="form">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                        value="reset-password"><input type="hidden" name="user_id" value="<?= (int) $selectedMember['id'] ?>">
                    <h4>Reset password manual</h4>
                    <label>Kata sandi baru *<input type="text" name="new_password" minlength="12" required
                            placeholder="Min 12 karakter"></label>
                    <button class="btn">Reset password</button>
                </form>
            </div>
            <hr>
            <form method="post" onsubmit="return confirm('Hapus user ini permanen?')"><input type="hidden" name="_csrf"
                    value="<?= csrf_token() ?>"><input type="hidden" name="action" value="delete-user"><input type="hidden"
                    name="user_id" value="<?= (int) $selectedMember['id'] ?>"><button class="btn btn-danger">🗑 Hapus user
                    permanen</button></form>
        </section>
    <?php endif; ?>

<?php elseif ($view === 'log'):
    $filterAction = $_GET['action_type'] ?? '';
    $filterUser = (int) ($_GET['user_id'] ?? 0);
    $logSql = "SELECT l.*, u.name FROM activity_logs l JOIN users u ON u.id=l.user_id WHERE 1=1";
    $logParams = [];
    if ($filterAction !== '') {
        $logSql .= " AND l.action_type=:a";
        $logParams['a'] = $filterAction;
    }
    if ($filterUser > 0) {
        $logSql .= " AND l.user_id=:u";
        $logParams['u'] = $filterUser;
    }
    $logSql .= " ORDER BY l.created_at DESC LIMIT 100";
    $s = $pdo->prepare($logSql);
    $s->execute($logParams);
    $logs = $s->fetchAll();

    $atypeStmt = $pdo->prepare('SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type');
    $atypeStmt->execute();
    $actionTypes = $atypeStmt->fetchAll(PDO::FETCH_COLUMN);
    $usrStmt = $pdo->prepare('SELECT id, name FROM users ORDER BY name');
    $usrStmt->execute();
    $allUsers = $usrStmt->fetchAll();

    $b = $pdo->prepare('SELECT * FROM backup_logs WHERE workspace_id=:w ORDER BY created_at DESC');
    $b->execute(['w' => $wid]);
    $backups = $b->fetchAll(); ?>
    <section class="card">
        <div class="panel-head">
            <h3>Activity log</h3>
            <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="hidden" name="p" value="log">
                <select name="action_type" class="search" onchange="this.form.submit()">
                    <option value="">Semua aksi</option>
                    <?php foreach ($actionTypes as $at): ?>
                        <option value="<?= e($at) ?>" <?= $filterAction === $at ? 'selected' : '' ?>>
                            <?= e($at) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="user_id" class="search" onchange="this.form.submit()">
                    <option value="0">Semua user</option>
                    <?php foreach ($allUsers as $usr): ?>
                        <option value="<?= (int) $usr['id'] ?>" <?= $filterUser === (int) $usr['id'] ? 'selected' : '' ?>>
                            <?= e($usr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Waktu (WIB)</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Peristiwa</th>
                        <th>Level</th>
                        <th>Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td>
                                <?= date('d M · H:i:s', strtotime($l['created_at'])) ?>
                            </td>
                            <td>
                                <?= e($l['name']) ?>
                            </td>
                            <td><span class="chip">
                                    <?= e($l['action_type']) ?>
                                </span></td>
                            <td>
                                <?= e($l['description']) ?>
                            </td>
                            <td><span class="badge">
                                    <?= e($l['level']) ?>
                                </span></td>
                            <td>
                                <?= e($l['reference_id'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$logs): ?>
                        <tr>
                            <td colspan="6" class="muted">Belum ada aktivitas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card" id="backup-list">
        <div class="panel-head">
            <h3>Backup</h3>
            <form method="post"><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden"
                    name="action" value="create-backup"><button class="btn btn-primary btn-sm">＋ Buat backup
                    sekarang</button></form>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Arsip</th>
                        <th>Ukuran</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $bk): ?>
                        <tr>
                            <td>
                                <?= e($bk['file_name']) ?>
                            </td>
                            <td>
                                <?= e($bk['file_size']) ?>
                            </td>
                            <td><span
                                    class="badge status-<?= $bk['status'] === 'Sukses' ? 'aktif' : ($bk['status'] === 'Gagal' ? 'offline' : 'baru') ?>">
                                    <?= e($bk['status']) ?>
                                </span></td>
                            <td>
                                <?= date('d M Y · H:i', strtotime($bk['created_at'])) ?>
                            </td>
                            <td class="task-actions">
                                <?php if ($bk['status'] === 'Sukses' && is_file(__DIR__ . '/backups/' . basename($bk['file_name']))): ?><a
                                        href="admin_download_backup.php?id=<?= (int) $bk['id'] ?>">Unduh</a>
                                <?php endif; ?>
                                <form method="post" onsubmit="return confirm('Hapus backup ini?')" style="display:inline"><input
                                        type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden"
                                        name="action" value="delete-backup"><input type="hidden" name="backup_id"
                                        value="<?= (int) $bk['id'] ?>"><button class="text-danger">Hapus</button></form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$backups): ?>
                        <tr>
                            <td colspan="5" class="muted">Belum ada backup.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="muted small">Backup berisi data workspace dalam format Word.</p>
    </section>
<?php else: ?>
    <section class="card">
        <p>Halaman tidak ditemukan.</p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>