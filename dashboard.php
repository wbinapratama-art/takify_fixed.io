<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$uid = UserSession::id();
$stats = TaskRepository::stats($uid);
$tasks = TaskRepository::all($uid);
$today = array_values(array_filter($tasks, fn($t) => $t['status'] !== 'selesai' && date('Y-m-d', strtotime($t['deadline'])) <= date('Y-m-d')));
$isAdmin = (UserSession::user()['role'] ?? '') === 'Admin';
$page = 'Dashboard';
$subtitle = $isAdmin
    ? 'Yo, Admin! Cek progres tim, biar kerjaan nggak ada yang kelewat.'
    : 'Gaskeun! Pilih satu tugas, beresin, lanjut lagi.';
$pageAction = ['href' => 'tugas_form.php', 'label' => '＋ Tambah tugas'];
require __DIR__ . '/header.php';
?>
<section class="stats-grid">
    <?php foreach (($isAdmin
        ? [['Lagi dikerjain tim', (int) $stats['total'] - (int) $stats['selesai'], 'accent-blue'], ['Kelarin bareng', (int) $stats['selesai'], 'accent-green'], ['Deadline hari ini', count($today), 'accent-blue'], ['Waktunya gas', (int) $stats['terlambat'], 'accent-red']]
        : [['Lagi kamu kerjain', (int) $stats['total'] - (int) $stats['selesai'], 'accent-blue'], ['Udah kelar', (int) $stats['selesai'], 'accent-green'], ['Deadline hari ini', count($today), 'accent-blue'], ['Jangan ditunda', (int) $stats['terlambat'], 'accent-red']]) as [$label, $value, $color]): ?>
        <article class="stat-card <?= $color ?>">
            <div class="stat-label">
                <?= e($label) ?>
            </div>
            <div class="stat-value">
                <?= $value ?>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<div class="dashboard-columns">
    <section class="card">
        <div class="panel-head">
            <h3>
                <?= $isAdmin ? 'Cek kerjaan tim hari ini' : 'Gas tugas hari ini' ?>
            </h3><a href="tugas.php">
                <?= $isAdmin ? 'Intip semua tugas →' : 'Lihat semua tugas →' ?>
            </a>
        </div>
        <?php $shown = array_slice(array_values(array_filter($tasks, fn($t) => $t['status'] !== 'selesai')), 0, 5);
        if (!$shown): ?>
            <p class="muted">
                <?= $isAdmin ? 'Wih, kerjaan tim udah kelar semua. Keren!' : 'Wih, tugasmu udah kelar semua. Keren!' ?>
            </p>
        <?php endif; ?>
        <?php foreach ($shown as $t): ?><a class="dashboard-task" href="tugas_detail.php?id=<?= (int) $t['id'] ?>"><span
                    class="check-static">○</span>
                <div class="task-main"><strong>
                        <?= e($t['judul']) ?>
                    </strong>
                    <div class="muted small">
                        <?= e($t['kategori_nama'] ?: 'Tanpa kategori') ?> ·
                        <?= $t['deadline'] ? date('d M, H:i', strtotime($t['deadline'])) : 'Tanpa tenggat' ?>
                    </div>
                    <div class="progress"><span style="width:<?= (int) $t['progress'] ?>%"></span></div>
                </div><span class="badge badge-<?= e($t['prioritas']) ?>">
                    <?= e(prioritas_label($t['prioritas'])) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </section>
    <aside class="card weekly-card">
        <h3>
            <?= $isAdmin ? 'Performa tim' : 'Progress kamu' ?>
        </h3>
        <p><strong>
                <?= (int) $stats['selesai'] ?>
            </strong> tugas selesai</p>
        <div class="muted small">
            <?= $isAdmin ? 'Jaga tim tetap kompak, target beres bareng.' : 'Santai, cicil aja. Lama-lama kelar juga.' ?>
        </div>
        <div class="weekly-bars"><i style="height:32%"></i><i style="height:55%"></i><i style="height:41%"></i><i
                style="height:75%"></i><i style="height:25%"></i><i style="height:12%"></i><i style="height:8%"></i>
        </div>
        <div class="week-labels">
            <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
        </div><a href="fitur.php?p=reminder">
            <?= $isAdmin ? 'Kepo reminder tim →' : 'Cek reminder kamu →' ?>
        </a>
    </aside>
</div>
<?php require __DIR__ . '/footer.php'; ?>