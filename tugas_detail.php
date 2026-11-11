<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$task = TaskRepository::find((int) ($_GET['id'] ?? 0), UserSession::id());
if (!$task)
    redirect('tugas.php');
$pdo = Database::conn();

$sub = $pdo->prepare('SELECT * FROM subtasks WHERE task_id=:id ORDER BY id');
$sub->execute(['id' => $task['id']]);
$subtasks = $sub->fetchAll();

$comments = $pdo->prepare('SELECT c.comment,c.created_at,u.name FROM task_comments c JOIN users u ON u.id=c.user_id WHERE c.task_id=:id ORDER BY c.created_at');
$comments->execute(['id' => $task['id']]);
$comments = $comments->fetchAll();

$files = $pdo->prepare('SELECT file_name,file_path,file_size,file_type FROM attachments WHERE task_id=:id ORDER BY created_at');
$files->execute(['id' => $task['id']]);
$files = $files->fetchAll();

$doneSub = count(array_filter($subtasks, fn($s) => (int) $s['is_completed'] === 1));

$page = 'Detail tugas';
$subtitle = $task['judul'];
$pageAction = ['href' => 'tugas_form.php?id=' . (int) $task['id'], 'label' => 'Edit tugas'];
require __DIR__ . '/header.php';
?>

<div style="display: flex; gap: 8px; margin-bottom: 16px;">
    <span class="badge" style="background: var(--primary-soft); color: var(--primary);">
        <?= e($task['kategori_nama'] ?: 'Umum') ?>
    </span>
    <span class="badge badge-<?= e($task['prioritas']) ?>">
        <?= e(prioritas_label($task['prioritas'])) ?>
    </span>
    <span class="badge" style="background: var(--bg);">
        <?= e(status_label($task['status'])) ?>
    </span>
</div>

<h2 style="font-size: 32px; font-weight: 600; margin-bottom: 8px;">
    <?= e($task['judul']) ?>
</h2>
<p style="color: var(--muted); font-size: 13px; margin-bottom: 32px;">Dibuat oleh
    <?= e(UserSession::user()['nama']) ?> ·
    <?= date('d M Y', strtotime($task['created_at'])) ?>
</p>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- Ringkasan -->
        <div class="card">
            <h3 style="margin-bottom: 16px;">Ringkasan</h3>
            <p style="font-size: 14px; color: var(--text-soft); line-height: 1.6; margin-bottom: 24px;">
                <?= nl2br(e($task['deskripsi'] ?: 'Belum ada deskripsi.')) ?>
            </p>

            <div
                style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 8px;">
                <span>Progress keseluruhan</span>
                <span>
                    <?= (int) $task['progress'] ?>%
                </span>
            </div>
            <div class="progress" style="width: 100%; height: 8px;"><span
                    style="width:<?= (int) $task['progress'] ?>%"></span></div>

            <div style="border-top: 1px solid var(--border); margin: 24px 0;"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3>Sub-tugas</h3>
                <span style="font-size: 12px; color: var(--muted);">
                    <?= $doneSub ?> dari
                    <?= count($subtasks) ?> selesai
                </span>
            </div>
            <?php foreach ($subtasks as $s): ?>
                <form method="post" action="subtask_action.php"
                    style="display:flex;align-items:center;gap:12px;padding:10px 0;">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button type="submit" class="check <?= (int) $s['is_completed'] === 1 ? 'checked' : '' ?>"></button>
                    <span
                        style="font-size: 14px; <?= (int) $s['is_completed'] === 1 ? 'text-decoration:line-through;color:var(--muted);' : '' ?>">
                        <?= e($s['title']) ?>
                    </span>
                </form>
            <?php endforeach; ?>
            <?php if (!$subtasks): ?>
                <p style="color: var(--muted); font-size: 13px;">Belum ada sub-tugas.</p>
            <?php endif; ?>

            <form method="post" action="subtask_action.php" style="display:flex;gap:8px;margin-top:16px">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                <input name="title" placeholder="+ Tambah sub-tugas" required
                    style="flex:1;padding:10px;border:1px dashed var(--border);border-radius:8px;background:var(--bg);font-size:13px;">
            </form>
        </div>

        <!-- Lampiran -->
        <div class="card">
            <h3 style="margin-bottom: 16px;">Lampiran</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <?php foreach ($files as $f): ?>
                    <div
                        style="border: 1px solid var(--border); border-radius: 8px; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; font-size: 13px;">
                                <?= e($f['file_name']) ?>
                            </div>
                            <div style="font-size: 11px; color: var(--muted);">
                                <?= number_format((int) $f['file_size'] / 1000, 1) ?> KB
                            </div>
                        </div>
                        <a href="<?= e($f['file_path']) ?>" download style="color: var(--primary); font-size: 18px;">⬇</a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!$files): ?>
                <p style="color: var(--muted); font-size: 13px;">Belum ada lampiran.</p>
            <?php endif; ?>

            <form method="post" action="lampiran_action.php" enctype="multipart/form-data"
                style="margin-top: 16px; border: 1px dashed var(--border); padding: 16px; border-radius: 8px; text-align: center; background: var(--bg);">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                <input type="file" name="file" required accept=".jpg,.jpeg,.png,.pdf,.txt"
                    style="font-size: 13px; margin-bottom: 8px;">
                <button class="btn btn-sm" style="display: block; margin: 0 auto;">Upload file</button>
            </form>
        </div>

        <!-- Diskusi -->
        <div class="card">
            <h3 style="margin-bottom: 16px;">Diskusi</h3>
            <?php foreach ($comments as $c): ?>
                <div style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <b style="font-size: 13px;">
                            <?= e($c['name']) ?>
                        </b>
                        <span style="font-size: 11px; color: var(--muted);">
                            <?= date('d M · H:i', strtotime($c['created_at'])) ?>
                        </span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-soft);">
                        <?= nl2br(e($c['comment'])) ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <?php if (!$comments): ?>
                <p style="color: var(--muted); font-size: 13px; margin-bottom: 16px;">Belum ada komentar.</p>
            <?php endif; ?>

            <form method="post" action="komentar_action.php"
                style="margin-top: 16px; border-top: 1px solid var(--border); padding-top: 16px;">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                <textarea name="comment" rows="3" required placeholder="Tulis komentar..."
                    style="width:100%;padding:12px;border:1px solid var(--border);border-radius:8px;font-family:inherit;font-size:13px;margin-bottom:12px;"></textarea>
                <button class="btn btn-primary" style="float: right;">Kirim komentar</button>
                <div style="clear: both;"></div>
            </form>
        </div>
    </div>

    <!-- Sidebar Kanan -->
    <aside style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card">
            <h3 style="margin-bottom: 16px;">Detail tugas</h3>
            <div style="display: flex; flex-direction: column; gap: 16px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Penanggung jawab</span>
                    <b>
                        <?= e(UserSession::user()['nama']) ?>
                    </b>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Tenggat</span>
                    <b>
                        <?= $task['deadline'] ? date('d M Y · H:i', strtotime($task['deadline'])) : '—' ?>
                    </b>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Prioritas</span>
                    <b>
                        <?= e(prioritas_label($task['prioritas'])) ?>
                    </b>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--muted);">Status</span>
                    <b>
                        <?= e(status_label($task['status'])) ?>
                    </b>
                </div>
            </div>

            <form method="post" action="tugas_action.php" style="margin-top: 24px;">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                <button class="btn btn-primary" style="width: 100%;">
                    <?= $task['status'] === 'selesai' ? 'Buka kembali tugas' : 'Tandai selesai' ?>
                </button>
            </form>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/footer.php'; ?>