<?php
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/partial_focus.php';

if (UserSession::check())
    redirect((UserSession::user()['role'] ?? '') === 'Admin' ? 'fitur.php?p=pengguna' : 'tugas.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    if ($email === '' || $pass === '') {
        $error = 'Email dan kata sandi jangan sampai kosong, ya.';
    } elseif (UserSession::attempt($email, $pass)) {
        flash('success', 'Hai, ' . UserSession::user()['nama'] . '! Senang kamu balik lagi.');
        redirect((UserSession::user()['role'] ?? '') === 'Admin' ? 'fitur.php?p=pengguna' : 'tugas.php');
    } else {
        $error = 'Email atau kata sandinya kurang pas. Coba cek lagi, ya.';
    }
}
$flashes = get_flash();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk — Takify</title>
    <link rel="icon" type="image/svg+xml" href="takify-bell.svg">
    <link rel="stylesheet" href="style.css">
</head>

<body class="account-page">
    <main class="account-card account-layout">
        <aside class="account-panel">
            <a class="account-brand" href="index.php" aria-label="Takify">
                <img class="account-brand-icon" src="takify-bell.svg" alt="">
                <span>Takify</span>
            </a>
            <div class="account-panel-copy">
                <span class="account-panel-kicker">TEMPAT KAMU BERESIN TUGAS</span>
                <h2>Fokus dulu.<br>Tugas beres satu-satu.</h2>
                <p>Atur tugasmu dengan gampang, lalu fokus ke hal yang paling penting.</p>
            </div>
            <div class="auth-preview">
                <div class="auth-preview-head"><b>Fokus hari ini</b><span>
                        <?= date('l, d M') ?>
                    </span></div>
                <?php render_focus_slider(); ?>
                <div class="preview-reminder">♧ &nbsp; Pengingat di waktu yang tepat</div>
            </div>
            <div class="account-panel-note"><span class="account-live-dot"></span> Pelan-pelan, yang penting beres.
            </div>
        </aside>

        <section class="account-content">
            <div class="account-heading">
                <p class="account-eyebrow">WELCOME BACK!</p>
                <h1>Masuk</h1>
                <p class="muted">Yuk, masuk lagi dan lanjut beresin tugasmu.</p>
            </div>

            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= $f['type'] === 'error' ? 'error' : 'success' ?>" role="status">
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>

            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="form">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <label for="email">Email
                    <input id="email" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>"
                        autocomplete="email" placeholder="nama@email.com" required autofocus>
                </label>
                <label for="password">Kata sandi
                    <input id="password" type="password" name="password" autocomplete="current-password"
                        placeholder="Ketik kata sandimu" required>
                </label>
                <button class="btn btn-primary btn-block account-submit">Masuk</button>
            </form>

            <p class="account-footer">Lupa kata sandi? Chat admin, ya.</p>
            <p class="account-footer">Belum punya akun? <a href="register.php">Yuk, daftar</a></p>
        </section>
    </main>
    <script>
        document.querySelectorAll('a[href="register.php"]').forEach(link => link.addEventListener('click', event => {
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