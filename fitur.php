<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$pdo = Database::conn();
$uid = UserSession::id();
$user = UserSession::user();
$workspaceStmt = $pdo->prepare('SELECT workspace_id FROM users WHERE id=:id');
$workspaceStmt->execute(['id' => $uid]);
$wid = (int) $workspaceStmt->fetchColumn();
$view = $_GET['p'] ?? 'template';
$adminViews = ['pengguna', 'log', 'admin-masukan'];
if (in_array($view, $adminViews, true) && ($user['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('<!doctype html><html lang="en"><meta charset="utf-8"><title>Can\'t reach this page</title><body style="font-family:Arial,sans-serif;color:#222;margin:8vh auto;max-width:640px;padding:24px"><h1 style="font-size:28px">Hmmm… can\'t reach this page</h1><p><strong>took too long to respond</strong></p><p>Try:</p><ul><li>Checking the connection</li><li>Checking the proxy and the firewall</li></ul><p>ERR_CONNECTION_TIMED_OUT</p></body></html>');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'send-feedback') {
        $subject = trim($_POST['subject'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $type = $_POST['type'] ?? 'Saran';
        if ($subject !== '' && $content !== '' && in_array($type, ['Saran', 'Kendala', 'Lainnya'], true)) {
            $stmt = $pdo->prepare('INSERT INTO feedbacks(workspace_id,user_id,subject,type,content) VALUES(:w,:u,:s,:t,:c)');
            $stmt->execute(['w' => $wid, 'u' => $uid, 's' => $subject, 't' => $type, 'c' => $content]);
            flash('success', 'Masukan berhasil dikirim ke admin.');
        } else
            flash('error', 'Subjek dan isi masukan wajib diisi.');
        redirect('fitur.php?p=masukan');
    }
    if ($action === 'invite-member' && !in_array($user['role'] ?? '', ['Admin', 'Pengamat'], true)) {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'Anggota';
        if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Nama dan email yang valid wajib diisi.');
        } elseif (strlen($pass) < 12) {
            flash('error', 'Kata sandi sementara minimal 12 karakter.');
        } else {
            [$ok, $msg] = UserSession::createMember($uid, $nama, $email, $pass, $role);
            flash($ok ? 'success' : 'error', $ok ? 'Undangan dibuat untuk ' . $nama . '. Email: ' . $email . ' · Kata sandi sementara: ' . $pass : $msg);
        }
        redirect('fitur.php?p=anggota');
    }
    if ($action === 'save-preferences') {
        $sql = 'INSERT INTO user_preferences(user_id,app_notification,email_notification,daily_summary,default_reminder_minutes,quiet_hours_start,quiet_hours_end,timezone) VALUES(:u,:a,:e,:d,:m,:qs,:qe,:tz) ON DUPLICATE KEY UPDATE app_notification=VALUES(app_notification),email_notification=VALUES(email_notification),daily_summary=VALUES(daily_summary),default_reminder_minutes=VALUES(default_reminder_minutes),quiet_hours_start=VALUES(quiet_hours_start),quiet_hours_end=VALUES(quiet_hours_end),timezone=VALUES(timezone)';
        $pdo->prepare($sql)->execute(['u' => $uid, 'a' => isset($_POST['app_notification']) ? 1 : 0, 'e' => isset($_POST['email_notification']) ? 1 : 0, 'd' => isset($_POST['daily_summary']) ? 1 : 0, 'm' => (int) ($_POST['minutes'] ?? 30), 'qs' => ($_POST['quiet_start'] ?? '') ?: null, 'qe' => ($_POST['quiet_end'] ?? '') ?: null, 'tz' => 'Asia/Jakarta']);
        flash('success', 'Preferensi notifikasi berhasil disimpan.');
        redirect('fitur.php?p=reminder');
    }
    if ($action === 'reply-feedback' && ($user['role'] ?? '') === 'Admin') {
        $status = $_POST['status'] ?? 'Baru';
        if (!in_array($status, ['Baru', 'Diproses', 'Selesai'], true))
            $status = 'Baru';
        $pdo->prepare('UPDATE feedbacks SET status=:s,admin_response=:r WHERE id=:id')->execute(['s' => $status, 'r' => trim($_POST['response'] ?? '') ?: null, 'id' => (int) ($_POST['feedback_id'] ?? 0)]);
        flash('success', 'Status dan tanggapan masukan diperbarui.');
        redirect('fitur.php?p=admin-masukan&id=' . (int) ($_POST['feedback_id'] ?? 0));
    }
}
$names = ['template' => 'Pustaka template', 'reminder' => 'Reminder & notifikasi', 'masukan' => 'Masukan', 'anggota' => 'Anggota workspace', 'pengguna' => 'Pengguna & akses', 'log' => 'Log & backup', 'admin-masukan' => 'Kelola masukan'];
$page = $names[$view] ?? $names['template'];
$subtitle = match ($view) { 'template' => 'Bangun kebiasaan kerja yang konsisten dengan template siap pakai.', 'reminder' => 'Pengingat tepat waktu, tanpa mengganggu waktu istirahat.', 'masukan' => 'Punya ide atau menemukan kendala? Bantu kami membuat Takify lebih baik.', 'anggota' => 'Undang orang lain agar tugas bisa dikerjakan bersama.', 'pengguna' => 'Kelola user dan batas tanggung jawabnya.', 'log' => 'Pantau aktivitas workspace dan riwayat backup.', 'admin-masukan' => 'Baca masukan anggota, berikan tanggapan, dan pantau tindak lanjutnya.', default => ''};
$pageAction = match ($view) { 'anggota' => in_array($user['role'] ?? '', ['Admin', 'Pengamat'], true) ? null : ['href' => '#invite', 'label' => '＋ Undang anggota'], default => null};
require __DIR__ . '/header.php';
?>
<?php if ($view === 'template'):
    $s = $pdo->prepare('SELECT t.*,c.name category_name FROM templates t LEFT JOIN categories c ON c.id=t.category_id ORDER BY t.created_at DESC');
    $s->execute();
    $templates = $s->fetchAll(); ?>
    <div class="template-grid" id="template-list">
        <?php foreach ($templates as $t): ?>
            <article class="card template-card">
                <div class="template-icon">▤</div>
                <h3>
                    <?= e($t['title']) ?>
                </h3>
                <p class="muted">
                    <?= e($t['description'] ?: 'Template workspace untuk membantu tim memulai tugas.') ?>
                </p>
                <div class="template-meta"><span class="chip">
                        <?= e($t['category_name'] ?: 'Umum') ?>
                    </span><span>
                        <?= e($t['default_priority']) ?>
                    </span></div>
                <hr><a class="btn btn-primary" href="tugas_form.php?template=<?= (int) $t['id'] ?>">＋ Gunakan template</a>
            </article>
        <?php endforeach; ?>
        <?php if (!$templates): ?>
            <div class="card empty">Belum ada template. Template dapat ditambahkan oleh admin workspace.</div>
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
            <h3>Preferensi pribadi</h3><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden"
                name="action" value="save-preferences"><label class="check-label"><input type="checkbox"
                    name="app_notification" <?= !isset($prefs['app_notification']) || $prefs['app_notification'] ? 'checked' : '' ?>> Notifikasi aplikasi</label><label class="check-label"><input type="checkbox"
                    name="email_notification"
                    <?= !isset($prefs['email_notification']) || $prefs['email_notification'] ? 'checked' : '' ?>>
                Email</label><label class="check-label"><input type="checkbox" name="daily_summary"
                    <?= !empty($prefs['daily_summary']) ? 'checked' : '' ?>> Ringkasan harian</label><label>Pengingat
                default<select name="minutes">
                    <?php foreach ([10, 30, 60, 1440] as $m): ?>
                        <option value="<?= $m ?>" <?= (int) ($prefs['default_reminder_minutes'] ?? 30) === $m ? 'selected' : '' ?>>
                            <?= $m < 60 ? $m . ' menit' : ($m === 60 ? '1 jam' : '1 hari') ?> sebelum tenggat
                        </option>
                    <?php endforeach; ?>
                </select></label>
            <h3>Jam tenang</h3>
            <div class="form-row"><label>Mulai<input type="time" name="quiet_start"
                        value="<?= e($prefs['quiet_hours_start'] ?? '22:00') ?>"></label><label>Selesai<input type="time"
                        name="quiet_end" value="<?= e($prefs['quiet_hours_end'] ?? '07:00') ?>"></label></div><button
                class="btn btn-primary">Simpan preferensi</button>
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
            <p class="muted small">Masukan ini dikirim langsung kepada admin workspace.</p><input type="hidden" name="_csrf"
                value="<?= csrf_token() ?>"><input type="hidden" name="action" value="send-feedback"><label>Subjek *<input
                    name="subject" maxlength="150" required></label><label>Jenis masukan<select name="type">
                    <option>Saran</option>
                    <option>Kendala</option>
                    <option>Lainnya</option>
                </select></label><label>Isi masukan *<textarea name="content" rows="6" required></textarea></label><button
                class="btn btn-primary">Kirim masukan</button>
        </form>
        <section class="card">
            <h3>Riwayat masukan saya <small class="muted">
                    <?= count($feedbacks) ?> masukan
                </small></h3>
            <?php foreach ($feedbacks as $f): ?>
                <article class="feedback-item"><span class="badge status-<?= e(strtolower($f['status'])) ?>">
                        <?= e($f['status']) ?>
                    </span><small>
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
                <p class="muted">Belum ada masukan yang dikirim.</p>
            <?php endif; ?>
        </section>
    </div>
<?php elseif ($view === 'admin-masukan'):
    $s = $pdo->prepare('SELECT f.*,u.name,u.email FROM feedbacks f JOIN users u ON u.id=f.user_id ORDER BY f.created_at DESC');
    $s->execute();
    $feedbacks = $s->fetchAll();
    $selected = null;
    foreach ($feedbacks as $f)
        if ((int) $f['id'] === (int) ($_GET['id'] ?? ($feedbacks[0]['id'] ?? 0)))
            $selected = $f; ?>
    <section class="stats-grid">
        <?php foreach (['Baru', 'Diproses', 'Selesai'] as $state):
            $n = count(array_filter($feedbacks, fn($f) => $f['status'] === $state)); ?>
            <article class="stat-card">
                <div class="stat-label">
                    <?= e($state) ?>
                </div>
                <div class="stat-value">
                    <?= $n ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    <div class="detail-columns">
        <section class="card">
            <h3>Masukan diterima</h3>
            <?php foreach ($feedbacks as $f): ?><a
                    class="feedback-list-item <?= $selected && $selected['id'] === $f['id'] ? 'selected' : '' ?>"
                    href="fitur.php?p=admin-masukan&id=<?= (int) $f['id'] ?>"><small>
                        <?= e($f['type']) ?> ·
                        <?= e($f['status']) ?>
                    </small><b>
                        <?= e($f['subject']) ?>
                    </b><span>
                        <?= e($f['name']) ?>
                    </span></a>
            <?php endforeach; ?>
            <?php if (!$feedbacks): ?>
                <p class="muted">Belum ada masukan.</p>
            <?php endif; ?>
        </section>
        <?php if ($selected): ?>
            <form method="post" class="card form"><small>
                    <?= e($selected['type']) ?> ·
                    <?= e($selected['email']) ?>
                </small>
                <h2>
                    <?= e($selected['subject']) ?>
                </h2>
                <p>
                    <?= e($selected['content']) ?>
                </p>
                <?php if ($selected['admin_response']): ?>
                    <blockquote>
                        <?= e($selected['admin_response']) ?>
                    </blockquote>
                <?php endif; ?><input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action"
                    value="reply-feedback"><input type="hidden" name="feedback_id"
                    value="<?= (int) $selected['id'] ?>"><label>Tanggapan admin<textarea name="response"
                        rows="5"><?= e($selected['admin_response'] ?? '') ?></textarea></label><label>Ubah status<select
                        name="status">
                        <?php foreach (['Baru', 'Diproses', 'Selesai'] as $st): ?>
                            <option <?= $selected['status'] === $st ? 'selected' : '' ?>>
                                <?= $st ?>
                            </option>
                        <?php endforeach; ?>
                    </select></label><button class="btn btn-primary">Simpan tanggapan</button>
            </form>
        <?php endif; ?>
    </div>
<?php elseif ($view === 'anggota'):
    $s = $pdo->prepare("SELECT name,email,role,status,last_active_at,last_seen_at FROM users WHERE workspace_id=:w AND role<>'Admin' ORDER BY name");
    $s->execute(['w' => $wid]);
    $workspaceMembers = $s->fetchAll();
    $canInvite = !in_array($user['role'] ?? '', ['Admin', 'Pengamat'], true); ?>
    <?php if ($canInvite): ?>
        <section class="card form" id="invite">
            <h3>Undang anggota</h3>
            <p class="muted small">Akun dibuat di workspace ini dengan kata sandi sementara. Bagikan email dan kata sandinya ke orang yang diundang.</p>
            <form method="post" id="invite-form">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="invite-member">
                <div class="form-row">
                    <label>Nama lengkap *<input name="nama" maxlength="100" required placeholder="Nama pengguna"></label>
                    <label>Email *<input type="email" name="email" required placeholder="nama@email.com"></label>
                </div>
                <div class="form-row">
                    <label>Role<select name="role"><option>Anggota</option><option>Pengamat</option></select></label>
                    <label>Kata sandi sementara *<input name="password" minlength="12" required placeholder="Minimal 12 karakter"></label>
                </div>
                <button class="btn" type="button" id="gen-pass">Buat kata sandi otomatis</button>
                <button class="btn btn-primary" type="submit">Kirim undangan</button>
            </form>
        </section>
        <script>
            document.querySelector('.page-heading a[href="#invite"]')?.addEventListener('click', (e) => {
                e.preventDefault();
                document.getElementById('invite').scrollIntoView({ behavior: 'smooth', block: 'start' });
                document.querySelector('#invite-form input[name="nama"]').focus({ preventScroll: true });
            });
            document.getElementById('gen-pass').addEventListener('click', () => {
                const chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                const buf = new Uint32Array(14);
                crypto.getRandomValues(buf);
                document.querySelector('#invite-form input[name="password"]').value = [...buf].map((n) => chars[n % chars.length]).join('');
            });
        </script>
    <?php endif; ?>
    <section class="card" id="members">
        <h3>Anggota workspace <small class="muted"><?= count($workspaceMembers) ?> orang</small></h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr><th>Anggota</th><th>Role</th><th>Status</th><th>Aktivitas terakhir (WIB)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($workspaceMembers as $m):
                        $online = UserSession::isOnline($m['last_seen_at'] ?? null);
                        $last = $m['last_seen_at'] ?: $m['last_active_at']; ?>
                        <tr>
                            <td><b><?= e($m['name']) ?></b><small><?= e($m['email']) ?></small></td>
                            <td><?= e($m['role']) ?></td>
                            <td><span class="badge status-<?= $online ? 'aktif' : 'offline' ?>"><?= $online ? 'Online' : 'Tidak aktif' ?></span></td>
                            <td><?= $last ? date('d M · H:i', strtotime($last)) : 'Belum pernah' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted small">Semua anggota di workspace ini berbagi daftar tugas dan kategori yang sama.</p>
    </section>
<?php elseif ($view === 'pengguna'):
    $s = $pdo->prepare("SELECT name,email,role,status,last_active_at,last_seen_at FROM users WHERE role IN ('Anggota','Pengamat') ORDER BY name");
    $s->execute();
    $members = $s->fetchAll();
    $selectedMember = null;
    foreach ($members as $member) {
        if (($member['email'] ?? '') === ($_GET['email'] ?? '')) {
            $selectedMember = $member;
            break;
        }
    } ?>
    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-label">Total user</div>
            <div class="stat-value">
                <?= count($members) ?>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-label">User online</div>
            <div class="stat-value">
                <?= count(array_filter($members, fn($m) => UserSession::isOnline($m['last_seen_at'] ?? null))) ?>
            </div>
        </article>
        <article class="stat-card">
            <div class="stat-label">Tidak aktif</div>
            <div class="stat-value">
                <?= count(array_filter($members, fn($m) => !UserSession::isOnline($m['last_seen_at'] ?? null))) ?>
            </div>
        </article>
    </section>
    <section class="card" id="members">
        <h3>User</h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Aktivitas terakhir (WIB)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m):
                        $online = UserSession::isOnline($m['last_seen_at'] ?? null);
                        $last = $m['last_seen_at'] ?: $m['last_active_at']; ?>
                        <tr>
                            <td><b><a href="fitur.php?p=pengguna&amp;email=<?= rawurlencode($m['email']) ?>#member-detail">
                                        <?= e($m['name']) ?>
                                    </a></b><small>
                                    <?= e($m['email']) ?>
                                </small></td>
                            <td>
                                <?= e($m['role']) ?>
                            </td>
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
        <p class="muted small">Pilih nama user untuk melihat detail akun.</p>
    </section>
    <?php if ($selectedMember):
        $online = UserSession::isOnline($selectedMember['last_seen_at'] ?? null);
        $last = $selectedMember['last_seen_at'] ?: $selectedMember['last_active_at']; ?>
        <section class="card" id="member-detail">
            <h3>Detail akun:
                <?= e($selectedMember['name']) ?>
            </h3>
            <p><b>Email:</b>
                <?= e($selectedMember['email']) ?>
            </p>
            <p><b>Status:</b> <span class="badge status-<?= $online ? 'aktif' : 'offline' ?>">
                    <?= $online ? 'Online' : 'Tidak aktif' ?>
                </span></p>
            <p><b>Aktivitas terakhir:</b>
                <?= $last ? date('d M Y · H:i', strtotime($last)) : 'Belum pernah' ?>
            </p>
            <p><b>Kata sandi:</b> ********</p>
            <p class="muted small">Kata sandi disimpan secara aman dan tidak dapat ditampilkan. Jika lupa, atur ulang kata sandi
                akun.</p>
        </section>
    <?php endif; ?>
<?php elseif ($view === 'log'):
    $s = $pdo->prepare("SELECT l.*,u.name FROM activity_logs l JOIN users u ON u.id=l.user_id WHERE u.role IN ('Anggota','Pengamat') AND l.action_type='login' ORDER BY l.created_at DESC LIMIT 50");
    $s->execute();
    $logs = $s->fetchAll();
    $b = $pdo->prepare('SELECT * FROM backup_logs WHERE workspace_id=:w ORDER BY created_at DESC');
    $b->execute(['w' => $wid]);
    $backups = $b->fetchAll(); ?>
    <div class="alert alert-success">Riwayat login User tercatat di sini.</div>
    <section class="card">
        <h3>Login User</h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Waktu (WIB)</th>
                        <th>User</th>
                        <th>Peristiwa</th>
                        <th>Level</th>
                        <th>Referensi</th>
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
                            <td colspan="5" class="muted">Belum ada riwayat login User.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <section class="card" id="backup-list">
        <h3>Riwayat backup</h3>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Arsip</th>
                        <th>Ukuran</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $b): ?>
                        <tr>
                            <td>
                                <?= e($b['file_name']) ?>
                            </td>
                            <td>
                                <?= e($b['file_size']) ?>
                            </td>
                            <td>
                                <?= e($b['status']) ?>
                            </td>
                            <td>
                                <?= date('d M Y · H:i', strtotime($b['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="muted small">Daftar ini menampilkan metadata backup yang tercatat. Proses pembuatan arsip fisik belum
            dikonfigurasi.</p>
    </section>
<?php else: ?>
    <section class="card">
        <p>Halaman tidak ditemukan.</p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>