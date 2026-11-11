<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
if ((UserSession::user()['role'] ?? '') === 'Admin')
    redirect('fitur.php?p=pengguna');
$uid = UserSession::id();
$status = $_GET['status'] ?? 'semua';
$q = trim($_GET['q'] ?? '');
$tasks = TaskRepository::all($uid, $status, $q ?: null);
$all = TaskRepository::all($uid);
$overdue = array_filter(
    $all,
    fn($t) =>
        $t['status'] !== 'selesai'
        && !empty($t['deadline'])
        && strtotime($t['deadline']) < time()
);
$page = 'Tugas saya';
$subtitle = 'Prioritaskan yang penting. Selesaikan satu per satu.';
$pageAction = ['href' => 'tugas_form.php', 'label' => '＋ Tambah tugas'];
require __DIR__ . '/header.php';
?>
<div class="toolbar">
    <form class="filter-tabs" method="get">
        <?php foreach (['semua' => 'Semua tugas', 'belum' => 'Belum dimulai', 'proses' => 'Sedang dikerjakan', 'selesai' => 'Selesai'] as $k => $v): ?><button
                name="status" value="<?= $k ?>" class="tab <?= $status === $k ? 'active' : '' ?>">
                <?= e($v) ?>
            </button>
        <?php endforeach; ?><input class="search" name="q" value="<?= e($q) ?>" placeholder="Cari tugas...">
    </form>
</div>
<?php if ($overdue): ?>
    <div class="alert alert-warning"><b>
            <?= count($overdue) ?> tugas melewati tenggat
        </b><br>Perbarui status atau atur ulang tenggat agar rencana tetap sinkron.</div>
<?php endif; ?>
<section class="card task-table-card">
    <div class="task-table-summary">
        <?= count($tasks) ?> tugas ·
        <?= count(array_filter($all, fn($t) => $t['status'] !== 'selesai')) ?> aktif,
        <?= count(array_filter($all, fn($t) => $t['status'] === 'selesai')) ?> selesai
    </div>
    <?php if (!$tasks): ?>
        <div class="empty">
            <div class="empty-icon">▤</div>
            <p>Belum ada tugas untuk filter ini. <a href="tugas_form.php">Tambah tugas</a>.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="task-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Nama tugas</th>
                        <th>Kategori</th>
                        <th>Prioritas</th>
                        <th>Progress</th>
                        <th>Tenggat (WIB)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td>
                                <form method="post" action="tugas_action.php"><input type="hidden" name="_csrf"
                                        value="<?= csrf_token() ?>"><input type="hidden" name="action" value="toggle"><input
                                        type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button
                                        class="check <?= $t['status'] === 'selesai' ? 'checked' : '' ?>"
                                        aria-label="Ubah status"></button></form>
                            </td>
                            <td><a class="task-name" href="tugas_detail.php?id=<?= (int) $t['id'] ?>">
                                    <?= e($t['judul']) ?>
                                </a><small>
                                    <?= e($t['deskripsi'] ?: 'Tugas') ?>
                                </small></td>
                            <td>
                                <?= e($t['kategori_nama'] ?: '—') ?>
                            </td>
                            <td><span class="badge badge-<?= e($t['prioritas']) ?>">
                                    <?= e(prioritas_label($t['prioritas'])) ?>
                                </span></td>
                            <td>
                                <div class="table-progress">
                                    <div class="progress"><span style="width:<?= (int) $t['progress'] ?>%"></span></div>
                                    <?= (int) $t['progress'] ?>%
                                </div>
                            </td>
                            <td>
                                <?= $t['deadline'] ? date('d M · H:i', strtotime($t['deadline'])) : '—' ?>
                            </td>
                            <td class="task-actions"><a href="tugas_form.php?id=<?= (int) $t['id'] ?>">Edit</a>
                                <form method="post" action="tugas_action.php" onsubmit="return confirm('Hapus tugas ini?')">
                                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>"><input type="hidden"
                                        name="action" value="delete"><input type="hidden" name="id"
                                        value="<?= (int) $t['id'] ?>"><button class="text-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/footer.php'; ?>