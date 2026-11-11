<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$uid = UserSession::id();
$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$tugas = $isEdit ? TaskRepository::find($id, $uid) : null;
if ($isEdit && !$tugas)
    redirect('tugas.php');

$formValues = $tugas ?? [];
$kategori = CategoryRepository::all($uid);

$page = $isEdit ? 'Edit tugas' : 'Tambah tugas';
$subtitle = 'Rencanakan pekerjaan berikutnya dengan detail yang tepat.';
$pageAction = null;
require __DIR__ . '/header.php';
?>
<form method="post" action="tugas_action.php" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="card form" style="margin-bottom: 0;">
        <h3 style="margin-bottom: 16px;">Informasi tugas</h3>

        <label>Judul tugas *
            <input name="judul" value="<?= e($formValues['judul'] ?? '') ?>" required maxlength="200"
                placeholder="Contoh: Review desain landing page">
        </label>

        <label>Deskripsi
            <textarea name="deskripsi" rows="4"
                placeholder="Tambahkan konteks atau detail tugas..."><?= e($formValues['deskripsi'] ?? '') ?></textarea>
        </label>

        <div class="form-row">
            <label>Kategori
                <select name="kategori_id">
                    <option value="">Tanpa kategori</option>
                    <?php foreach ($kategori as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= ($formValues['kategori_id'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                            <?= e($k['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Penanggung jawab
                <input value="<?= e(UserSession::user()['nama']) ?>" disabled style="background: var(--bg);">
            </label>
        </div>

        <div class="form-row">
            <label>Prioritas
                <select name="prioritas">
                    <?php foreach (['rendah', 'sedang', 'tinggi'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($formValues['prioritas'] ?? 'sedang') === $p ? 'selected' : '' ?>>
                            <?= e(prioritas_label($p)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Progress (%)
                <input type="number" name="progress" min="0" max="100" value="<?= e($formValues['progress'] ?? 0) ?>">
            </label>
        </div>

        <label>Status
            <select name="status">
                <?php foreach (['belum', 'proses', 'selesai'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($formValues['status'] ?? 'belum') === $s ? 'selected' : '' ?>>
                        <?= e(status_label($s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <h3 style="margin-top: 16px; margin-bottom: 16px; border-top: 1px solid var(--border); padding-top: 24px;">
            Jadwal & pengulangan</h3>

        <label>Tanggal dan waktu tenggat *
            <input type="datetime-local" name="deadline" required
                value="<?= e(!empty($tugas['deadline']) ? date('Y-m-d\TH:i', strtotime($tugas['deadline'])) : date('Y-m-d\TH:i', strtotime('+1 day'))) ?>">
        </label>

        <input type="hidden" name="is_recurring" value="0">
        <div style="display: flex; align-items: center; gap: 8px; margin: 8px 0;">
            <input type="checkbox" name="is_recurring" value="1" id="recur" <?= !empty($tugas['is_recurring']) ? 'checked' : '' ?> style="width:auto;">
            <label for="recur" style="font-weight: normal; flex-direction: row;">Jadikan tugas berulang</label>
        </div>

        <label>Ulangi setiap
            <select name="recurrence_rule">
                <option value="daily">Setiap hari</option>
                <option value="weekly" selected>Setiap minggu</option>
                <option value="monthly">Setiap bulan</option>
            </select>
        </label>
    </div>

    <aside style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card form">
            <h3>Reminder</h3>
            <p style="font-size: 13px; color: var(--muted); margin-bottom: 8px;">Pengingat dijadwalkan sebelum tenggat.
            </p>
            <label>Pengingat pertama
                <select name="reminder_minutes">
                    <option value="10">10 menit sebelumnya</option>
                    <option value="30" selected>30 menit sebelumnya</option>
                    <option value="60">1 jam sebelumnya</option>
                    <option value="1440">1 hari sebelumnya</option>
                </select>
            </label>
            <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="reminder_email" id="rem_email" style="width:auto;">
                <label for="rem_email" style="font-weight: normal; flex-direction: row;">Kirim salinan email</label>
            </div>
        </div>

        <div class="card">
            <h3>Ringkasan</h3>
            <p style="font-size: 13px; color: var(--muted); margin-bottom: 24px;">Tugas akan tersimpan dan dapat
                diperbarui kapan saja.</p>
            <button class="btn btn-primary" style="width: 100%; margin-bottom: 12px;">
                <?= $isEdit ? 'Simpan perubahan' : '✓ Simpan tugas' ?>
            </button>
            <a class="btn" href="tugas.php" style="width: 100%;">Batal</a>
        </div>
    </aside>
</form>
<?php require __DIR__ . '/footer.php'; ?>