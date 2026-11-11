<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$uid = UserSession::id();
$stats = TaskRepository::stats($uid);
$tasks = TaskRepository::all($uid);
$today = array_values(array_filter($tasks, fn($t) => $t['status'] !== 'selesai' && !empty($t['deadline']) && date('Y-m-d', strtotime($t['deadline'])) <= date('Y-m-d')));

$dayName = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'][date('l')] ?? date('l');
$page = 'Dashboard';
$subtitle = $dayName . ', ' . date('d F Y') . ' · Mari buat hari ini lebih berarti.';
$pageAction = ['href' => 'tugas_form.php', 'label' => 'Tambah tugas'];
require __DIR__ . '/header.php';
?>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-label">Tugas aktif</div>
        <div class="stat-value">
            <?= (int) $stats['total'] - (int) $stats['selesai'] ?>
        </div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Selesai minggu ini</div>
        <div class="stat-value">
            <?= (int) $stats['selesai'] ?>
        </div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Tenggat hari ini</div>
        <div class="stat-value">
            <?= count($today) ?>
        </div>
    </article>
    <article class="stat-card">
        <div class="stat-label">Terlambat</div>
        <div class="stat-value" style="color: var(--danger);">
            <?= (int) $stats['terlambat'] ?>
        </div>
    </article>
</section>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <div class="card" style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 16px; font-weight: 600;">Prioritas hari ini</h3>
                <a href="tugas.php" style="font-size: 13px; color: var(--primary);">Lihat semua tugas →</a>
            </div>

            <?php
            $shown = array_slice(array_values(array_filter($tasks, fn($t) => $t['status'] !== 'selesai')), 0, 3);
            foreach ($shown as $t): ?>
                <a href="tugas_detail.php?id=<?= (int) $t['id'] ?>"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--border); color: inherit;">
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <div class="check"></div>
                        <div>
                            <div style="font-weight: 600; font-size: 14px;">
                                <?= e($t['judul']) ?>
                            </div>
                            <div style="font-size: 12px; color: var(--muted);">
                                <?= e($t['kategori_nama'] ?: 'Tanpa kategori') ?>
                            </div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <span class="badge badge-<?= e($t['prioritas']) ?>">
                            <?= e(prioritas_label($t['prioritas'])) ?>
                        </span>
                        <div class="progress"><span style="width:<?= (int) $t['progress'] ?>%"></span></div>
                        <span style="font-size: 12px; width: 30px; text-align: right;">
                            <?= (int) $t['progress'] ?>%
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
            <?php if (!$shown): ?>
                <p style="color: var(--muted); font-size: 13px;">Tidak ada tugas aktif.</p>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <?php
        $totalTarget = max(1, (int) $stats['total']);
        $pct = min(100, (int) round(((int) $stats['selesai'] / $totalTarget) * 100));
        $sisa = max(0, $totalTarget - (int) $stats['selesai']);
        ?>
        <div class="card" style="background: var(--primary); color: #fff; border: none;">
            <div style="font-size: 13px; font-weight: 500; margin-bottom: 24px;">Target minggu ini</div>
            <div style="font-size: 36px; font-weight: 700; margin-bottom: 8px;">
                <?= (int) $stats['selesai'] ?> <span style="font-size: 18px; opacity: 0.6;">/
                    <?= $totalTarget ?>
                </span>
            </div>
            <p style="font-size: 13px; opacity: 0.8; margin-bottom: 24px; line-height: 1.5;">
                <?= $sisa > 0
                    ? $sisa . ' tugas lagi menuju target. Kamu sudah ' . $pct . '% di perjalanan!'
                    : 'Target tercapai! Pertahankan momentumnya.' ?>
            </p>
            <div style="background: rgba(255,255,255,0.2); height: 8px; border-radius: 4px; margin-bottom: 24px;">
                <div style="background: #fff; width: <?= $pct ?>%; height: 100%; border-radius: 4px;"></div>
            </div>
            <a href="tugas.php" style="color: #fff; font-size: 13px; font-weight: 500;">Ubah target →</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>