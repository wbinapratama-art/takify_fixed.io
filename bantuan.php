<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$uid = UserSession::id();
$pdo = Database::conn();

// Cek Role User
$user = UserSession::user();
$isAdmin = ($user['role'] ?? '') === 'Admin';

$widStmt = $pdo->prepare('SELECT workspace_id FROM users WHERE id=:id');
$widStmt->execute(['id' => $uid]);
$wid = (int) $widStmt->fetchColumn();

// Proses form komentar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send-comment') {
    verify_csrf();
    $subject = trim($_POST['subject'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = $_POST['type'] ?? 'Saran';
    $rating = (int) ($_POST['rating'] ?? 5); // Tangkap nilai rating

    if ($subject !== '' && $content !== '') {
        $stmt = $pdo->prepare('INSERT INTO feedbacks(workspace_id,user_id,subject,type,content,rating) VALUES(:w,:u,:s,:t,:c,:r)');
        $stmt->execute(['w' => $wid, 'u' => $uid, 's' => $subject, 't' => $type, 'c' => $content, 'r' => $rating]);

        log_activity($wid, $uid, 'send-feedback', 'Mengirim ulasan: ' . $subject);

        flash('success', 'Berhasil! Ulasan Anda sudah masuk ke Admin. Terima kasih!');
        redirect('bantuan.php?tab=komentar');
    } else {
        flash('error', 'Subjek dan isi ulasan tidak boleh kosong.');
    }
}

$stmt = $pdo->prepare('SELECT * FROM feedbacks WHERE workspace_id=:w AND user_id=:u ORDER BY created_at DESC');
$stmt->execute(['w' => $wid, 'u' => $uid]);
$myFeedbacks = $stmt->fetchAll();

$page = 'Pusat Bantuan';
$subtitle = 'Panduan penggunaan, FAQ, dan ruang komentar untuk Anda.';
$pageAction = null;
$activeTab = $_GET['tab'] ?? 'panduan';

require __DIR__ . '/header.php';
?>

<style>
    .faq-accordion {
        border: 1px solid var(--border);
        border-radius: 8px;
        background: #fff;
        margin-bottom: 12px;
        overflow: hidden;
        transition: 0.2s;
    }

    .faq-accordion[open] {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(91, 85, 231, 0.08);
    }

    .faq-accordion summary {
        padding: 16px 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--text);
    }

    .faq-accordion summary::-webkit-details-marker {
        display: none;
    }

    .faq-accordion summary::after {
        content: '+';
        font-size: 20px;
        color: var(--muted);
        font-weight: 400;
    }

    .faq-accordion[open] summary::after {
        content: '−';
        color: var(--primary);
    }

    .faq-content {
        padding: 0 20px 16px 20px;
        color: var(--muted);
        font-size: 13px;
        line-height: 1.6;
        border-top: 1px dashed var(--border);
        margin-top: 4px;
        padding-top: 12px;
    }

    .search-faq-wrapper {
        position: relative;
        margin-bottom: 24px;
    }

    .search-faq-wrapper input {
        width: 100%;
        padding: 14px 16px 14px 40px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
        outline: none;
        transition: 0.2s;
    }

    .search-faq-wrapper input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(91, 85, 231, 0.1);
    }

    .search-faq-wrapper svg {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 18px;
        height: 18px;
        fill: var(--muted);
    }

    /* Gaya Ulasan Google Play */
    .review-card {
        display: flex;
        gap: 16px;
        padding-bottom: 24px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 24px;
    }

    .avatar-initial {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        color: #fff;
        display: grid;
        place-items: center;
        font-weight: 600;
        font-size: 18px;
        flex-shrink: 0;
        overflow: hidden;
    }

    /* Gaya Bintang Rating Interaktif */
    .star-rating {
        display: flex;
        gap: 8px;
        font-size: 32px;
        cursor: pointer;
        color: #d1d5db;
    }

    .star-rating .star:hover,
    .star-rating .star.active {
        color: #01875f;
    }
</style>

<div class="card" style="padding: 0; overflow: hidden;">
    <div class="filter-tabs" style="padding: 0 20px; background: #f9fafb; border-bottom: 1px solid var(--border);">
        <a href="bantuan.php?tab=panduan" class="tab <?= $activeTab === 'panduan' ? 'active' : '' ?>"
            style="text-decoration: none;">Panduan & FAQ</a>
        <a href="bantuan.php?tab=komentar" class="tab <?= $activeTab === 'komentar' ? 'active' : '' ?>"
            style="text-decoration: none;">Komentar</a>
    </div>

    <div style="padding: 24px;">
        <?php if ($activeTab === 'panduan'): ?>
            <div style="max-width: 800px;">
                <div class="search-faq-wrapper">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                    </svg>
                    <input type="text" id="faq-search" placeholder="Cari panduan atau pertanyaan..." onkeyup="filterFAQ()">
                </div>

                <!-- Kategori: Manajemen Tugas -->
                <h4
                    style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); margin-bottom: 12px; margin-top: 24px;">
                    Manajemen Tugas</h4>
                <div class="faq-group">
                    <details class="faq-accordion" open>
                        <summary>Bagaimana cara menambah tugas baru?</summary>
                        <div class="faq-content">Buka menu <b>Tugas saya</b> atau <b>Dashboard</b>, lalu klik tombol ungu "+
                            Tambah tugas" di pojok kanan atas. Anda juga bisa menggunakan tombol pintas yang tersedia di
                            halaman tersebut.</div>
                    </details>
                    <details class="faq-accordion">
                        <summary>Apa bedanya Prioritas Sedang dan Tinggi?</summary>
                        <div class="faq-content">Prioritas Tinggi (merah) harus segera diselesaikan. Sedang (oranye) bisa
                            dikerjakan minggu ini, dan Rendah (abu-abu) dikerjakan jika ada waktu luang.</div>
                    </details>
                    <details class="faq-accordion">
                        <summary>Apakah saya bisa mengatur sub-tugas?</summary>
                        <div class="faq-content">Tentu! Buka detail tugas Anda, lalu gulir ke bagian <b>Sub-tugas</b>. Anda
                            bisa menambahkan langkah-langkah kecil (sub-tugas) dan mencentangnya satu per satu. Progress
                            tugas utama akan otomatis menyesuaikan dengan sub-tugas yang selesai.</div>
                    </details>
                </div>

                <!-- Kategori: Fitur Website -->
                <h4
                    style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); margin-bottom: 12px; margin-top: 24px;">
                    Fitur Website & Akun</h4>
                <div class="faq-group">
                    <details class="faq-accordion">
                        <summary>Bagaimana cara mengubah foto profil saya?</summary>
                        <div class="faq-content">Buka halaman <b>Profil</b> melalui menu di pojok kiri bawah. Di sana, Anda
                            bisa mengklik tombol <b>Select Image</b> untuk mengunggah foto baru. Pastikan ukuran file
                            maksimal 1 MB dengan format JPEG atau PNG.</div>
                    </details>
                    <details class="faq-accordion">
                        <summary>Apa fungsi menu "Pustaka Template"?</summary>
                        <div class="faq-content">Menu <b>Pustaka Template</b> berisi daftar template tugas yang sudah
                            disiapkan (misalnya untuk rapat mingguan atau review sprint). Anda bisa langsung menggunakan
                            template ini untuk membuat tugas baru tanpa harus mengetik ulang detailnya.</div>
                    </details>
                    <details class="faq-accordion">
                        <summary>Bagaimana cara mengatur pengingat (Reminder)?</summary>
                        <div class="faq-content">Saat membuat atau mengedit tugas, Anda bisa memilih opsi pengingat (misal:
                            30 menit sebelum tenggat). Anda juga bisa mengatur preferensi notifikasi default di menu
                            <b>Reminder</b>.</div>
                    </details>
                </div>

                <!-- Kategori: Khusus Admin (Hanya muncul untuk Admin) -->
                <?php if ($isAdmin): ?>
                    <h4
                        style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted); margin-bottom: 12px; margin-top: 24px;">
                        Fitur Admin</h4>
                    <div class="faq-group">
                        <details class="faq-accordion">
                            <summary>Bagaimana cara mengelola anggota tim?</summary>
                            <div class="faq-content">Buka menu <b>Pengguna & Akses</b>. Anda bisa mengundang anggota baru,
                                mengubah role mereka (Anggota/Pengamat), atau mereset password mereka jika diperlukan.</div>
                        </details>
                        <details class="faq-accordion">
                            <summary>Bagaimana cara membackup data workspace?</summary>
                            <div class="faq-content">Buka menu <b>Log & Backup</b>. Klik tombol "Buat backup sekarang". Sistem
                                akan membuat file Word (.doc) berisi data workspace Anda yang bisa diunduh kapan saja.</div>
                        </details>
                        <details class="faq-accordion">
                            <summary>Bagaimana cara membalas ulasan user?</summary>
                            <div class="faq-content">Buka menu <b>Kelola Masukan</b>. Pilih ulasan yang ingin dibalas di kolom
                                kiri, lalu tulis tanggapan Anda di panel kanan dan ubah statusnya (Baru -> Diproses -> Selesai).
                            </div>
                        </details>
                    </div>
                <?php endif; ?>

            </div>

        <?php elseif ($activeTab === 'komentar'): ?>
            <div class="detail-columns">

                <!-- Form Kirim Ulasan -->
                <form method="post" class="form"
                    style="background: #f9f9fb; padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
                    <h3 style="margin-top: 0;">Tulis Ulasan</h3>
                    <p class="muted small" style="margin-bottom: 16px;">Beri tahu kami tentang pengalaman Anda menggunakan
                        Takify.</p>

                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="send-comment">

                    <!-- Input Rating Tersembunyi -->
                    <input type="hidden" name="rating" id="input-rating" value="5">

                    <label style="display: block; margin-bottom: 8px;">Rating Bintang</label>
                    <div class="star-rating" id="star-container">
                        <span class="star active" data-value="1">★</span>
                        <span class="star active" data-value="2">★</span>
                        <span class="star active" data-value="3">★</span>
                        <span class="star active" data-value="4">★</span>
                        <span class="star active" data-value="5">★</span>
                    </div>

                    <div class="form-row" style="margin-top: 12px;">
                        <label>Subjek / Topik *
                            <input type="text" name="subject" required placeholder="Contoh: Aplikasi sangat bagus">
                        </label>
                        <label>Kategori
                            <select name="type">
                                <option value="Saran">Masukan Fitur</option>
                                <option value="Kendala">Laporan Bug</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </label>
                    </div>

                    <label>Ulasan Anda *
                        <textarea name="content" rows="5" required
                            placeholder="Ceritakan pengalaman Anda atau kendala yang ditemui..."></textarea>
                    </label>

                    <button class="btn btn-primary"
                        style="margin-top: 10px; background: #01875f; border-color: #01875f;">Kirim Ulasan</button>
                </form>

                <!-- Riwayat Ulasan -->
                <section class="card" style="margin-bottom: 0;">
                    <h3 style="margin-top: 0;">Ulasan Anda</h3>

                    <div style="max-height: 500px; overflow-y: auto; padding-right: 5px;">
                        <?php foreach ($myFeedbacks as $f):
                            $starCount = (int) ($f['rating'] ?? 5);
                            $initial = strtoupper(substr(UserSession::user()['nama'], 0, 1));
                            $bgColor = '#1976d2';
                            ?>
                            <div class="review-card">
                                <!-- Avatar (Gambar jika ada, huruf jika tidak ada) -->
                                <div class="avatar-initial" style="background: <?= $bgColor ?>;">
                                    <?php if (!empty(UserSession::user()['avatar']) && file_exists(__DIR__ . '/' . UserSession::user()['avatar'])): ?>
                                        <img src="<?= e(UserSession::user()['avatar']) ?>" alt="Avatar"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?= e($initial) ?>
                                    <?php endif; ?>
                                </div>

                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                        <div style="font-weight: 500; font-size: 14px;">
                                            <?= e(UserSession::user()['nama']) ?>
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
                                    </div>
                                    <div style="font-weight: 600; font-size: 13px; margin-bottom: 4px;">
                                        <?= e($f['subject']) ?>
                                    </div>
                                    <div style="font-size: 14px; color: #333; line-height: 1.6;">
                                        <?= nl2br(e($f['content'])) ?>
                                    </div>

                                    <?php if ($f['admin_response']): ?>
                                        <div
                                            style="margin-top: 12px; background: #f0fdf4; padding: 12px; border-radius: 6px; border-left: 3px solid #22c55e;">
                                            <b style="font-size: 11px; color: #166534;">Balasan Admin:</b>
                                            <p style="font-size: 13px; margin: 4px 0 0 0; color: #166534;">
                                                <?= nl2br(e($f['admin_response'])) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (!$myFeedbacks): ?>
                            <div style="text-align: center; padding: 30px 10px;">
                                <p class="muted" style="font-size: 13px;">Anda belum menulis ulasan.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Script untuk bintang interaktif
    const stars = document.querySelectorAll('.star-rating .star');
    const inputRating = document.getElementById('input-rating');

    stars.forEach(star => {
        star.addEventListener('click', function () {
            const value = this.getAttribute('data-value');
            inputRating.value = value;

            // Reset semua bintang
            stars.forEach(s => s.classList.remove('active'));

            // Nyalakan bintang sampai yang diklik
            for (let i = 0; i < value; i++) {
                stars[i].classList.add('active');
            }
        });
    });

    // Fungsi Pencarian FAQ
    function filterFAQ() {
        let input = document.getElementById('faq-search').value.toLowerCase();
        let groups = document.querySelectorAll('.faq-group');
        groups.forEach(group => {
            let details = group.querySelectorAll('details');
            let hasVisible = false;
            details.forEach(detail => {
                let text = detail.querySelector('summary').textContent.toLowerCase() + detail.querySelector('.faq-content').textContent.toLowerCase();
                if (text.includes(input)) {
                    detail.style.display = 'block';
                    hasVisible = true;
                } else {
                    detail.style.display = 'none';
                }
            });
            let title = group.previousElementSibling;
            if (title && title.tagName === 'H4') {
                title.style.display = hasVisible ? 'block' : 'none';
            }
        });
    }
</script>

<?php require __DIR__ . '/footer.php'; ?>