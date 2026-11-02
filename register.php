<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partial_focus.php';

if (UserSession::check())
    redirect((UserSession::user()['role'] ?? '') === 'Admin' ? 'fitur.php?p=pengguna' : 'tugas.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if ($nama === '' || $email === '' || $pass === '') {
        $error = 'Semua kolomnya diisi dulu, ya.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format emailnya kayaknya belum benar. Cek lagi, ya.';
    } elseif (strlen($pass) < 12) {
        $error = 'Kata sandi minimal 12 karakter, ya.';
    } elseif ($pass !== $pass2) {
        $error = 'Ulangi kata sandinya belum cocok. Coba cek lagi, ya.';
    } else {
        [$ok, $msg] = UserSession::register($nama, $email, $pass);
        if ($ok) {
            flash('success', 'Akunmu berhasil dibuat! Yuk, masuk.');
            redirect('login.php');
        } else
            $error = $msg;
    }
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Registrasi — Takify</title>
    <link rel="icon" type="image/svg+xml" href="assets/takify-bell.svg">
    <link rel="stylesheet" href="style.css">
</head>

<body class="account-page">
    <main class="account-card account-layout account-layout-register">
        <aside class="account-panel">
            <a class="account-brand" href="index.php" aria-label="Takify">
                <img class="account-brand-icon" src="takify-bell.svg" alt="">
                <span>Takify</span>
            </a>
            <div class="account-panel-copy">
                <span class="account-panel-kicker">MULAI DARI SINI</span>
                <h2>Atur tugasmu.<br>Beresin satu-satu.</h2>
                <p>Biar semua rencana dan tugasmu lebih gampang diatur.</p>
            </div>
            <div class="auth-preview">
                <div class="auth-preview-head"><b>Fokus hari ini</b><span><?= date('l, d M') ?></span></div>
                <?php render_focus_slider(); ?>
                <div class="preview-reminder">♧ &nbsp; Pengingat di waktu yang tepat</div>
            </div>
            <div class="account-panel-note"><span class="account-live-dot"></span> Semua tugas, nggak berantakan lagi.
            </div>
        </aside>

        <section class="account-content">
            <div class="account-heading">
                <p class="account-eyebrow">BIKIN AKUN BARU</p>
                <h1>Yuk, gabung!</h1>
                <p class="muted">Isi data ini dulu, habis itu kamu bisa mulai.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="form">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <label for="nama">Nama lengkap <input id="nama" name="nama" value="<?= e($_POST['nama'] ?? '') ?>"
                        autocomplete="name" maxlength="100" placeholder="Nama kamu" required></label>
                <label for="email">Email <input id="email" type="email" name="email"
                        value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" placeholder="nama@email.com"
                        required></label>
                <label for="password">Kata sandi <input id="password" type="password" name="password"
                        autocomplete="new-password" minlength="12" placeholder="Minimal 12 karakter" required></label>
                <label for="password2">Ulangi kata sandi <input id="password2" type="password" name="password2"
                        autocomplete="new-password" minlength="12" placeholder="Masukkan ulang kata sandi"
                        required></label>
                <button class="btn btn-primary btn-block account-submit">Buat akun</button>
            </form>

            <p class="account-footer">Sudah punya akun? <a href="login.php">Masuk aja</a></p>
        </section>
    </main>
    <script>
        document.querySelectorAll('a[href="login.php"]').forEach(link => link.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.add('auth-page-leaving');
            setTimeout(() => { window.location.href = link.href; }, 180);
        }));
        document.querySelector('.account-content form')?.addEventListener('submit', event => {
            if (!event.target.reportValidity()) return;
            event.preventDefault();
            document.body.classList.add('auth-page-leaving');
            setTimeout(() => event.target.submit(), 180);
        });
    </script>
</body>

</html>