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
$page = 'Detail tugas';
$subtitle = $task['judul'];
$pageAction = ['href' => 'tugas_form.php?id=' . (int) $task['id'], 'label' => 'Edit tugas'];
require __DIR__ . '/header.php'; ?>
<p class="detail-back"><a href="tugas.php">← Semua tugas</a><span class="badge badge-<?= e($task['prioritas']) ?>">
        <?= e(prioritas_label($task['prioritas'])) ?>
    </span><span class="chip">
        <?= e($task['kategori_nama'] ?: 'Tanpa kategori') ?>
    </span><span class="status-pill">
        <?= e(status_label($task['status'])) ?>
    </span></p>
<h2 class="detail-title">
    <?= e($task['judul']) ?>
</h2>
<div class="detail-columns">
    <section>
        <article class="card">
            <h3>Ringkasan</h3>
            <p>
                <?= nl2br(e($task['deskripsi'] ?: 'Belum ada deskripsi.')) ?>
            </p>
            <div class="detail-progress">Progress keseluruhan <b>
                    <?= (int) $task['progress'] ?>%
                </b>
                <div class="progress"><span style="width:<?= (int) $task['progress'] ?>%"></span></div>
            </div>
            <hr>
            <h3>Sub-tugas <small class="muted">
                    <?= count(array_filter($subtasks, fn($s) => $s['is_completed'])) ?> dari
                    <?= count($subtasks) ?> selesai
                </small></h3>
            <?php foreach ($subtasks as $s): ?>
                <p>☐
                    <?= e($s['title']) ?>
                </p>
            <?php endforeach; ?>
            <?php if (!$subtasks): ?>
                <p class="muted">Belum ada sub-tugas.</p>
            <?php endif; ?>
        </article>
        <article class="card">
            <h3>Lampiran</h3>
            <?php foreach ($files as $f): ?>
                <div class="simple-row">
                    <div><b>
                            <?= e($f['file_name']) ?>
                        </b><small>
                            <?= e($f['file_type'] ?? 'File') ?> ·
                            <?= number_format((int) $f['file_size'] / 1000, 1) ?> MB
                        </small></div><a href="<?= e($f['file_path']) ?>" download>Unduh</a>
                </div>
            <?php endforeach; ?>
            <?php if (!$files): ?>
                <p class="muted">Belum ada lampiran.</p>
            <?php endif; ?>
        </article>
        <article class="card">
            <h3>Diskusi</h3>
            <?php foreach ($comments as $c): ?>
                <div class="comment"><b>
                        <?= e($c['name']) ?>
                    </b><time>
                        <?= date('d M Y · H:i', strtotime($c['created_at'])) ?>
                    </time>
                    <p>
                        <?= e($c['comment']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <?php if (!$comments): ?>
                <p class="muted">Belum ada komentar.</p>
            <?php endif; ?>
        </article>
    </section>
    <aside class="card task-detail-aside">
        <h3>Detail tugas</h3>
        <p><span>Penanggung jawab</span><b>
                <?= e(UserSession::user()['nama']) ?>
            </b></p>
        <p><span>Tenggat</span><b>
                <?= $task['deadline'] ? date('d M Y · H:i', strtotime($task['deadline'])) : 'Tanpa tenggat' ?>
            </b></p>
        <p><span>Prioritas</span><b>
                <?= e(prioritas_label($task['prioritas'])) ?>
            </b></p>
        <p><span>Status</span><b>
                <?= e(status_label($task['status'])) ?>
            </b></p>
        <form method="post" action="tugas_action.php"><input type="hidden" name="_csrf"
                value="<?= csrf_token() ?>"><input type="hidden" name="action" value="toggle"><input type="hidden"
                name="id" value="<?= (int) $task['id'] ?>"><button class="btn btn-primary btn-block">Tandai
                selesai</button></form>
    </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>