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

// Tangkap email dari URL (jika habis ganti password)
$prefill_email = $_POST['email'] ?? ($_GET['email'] ?? '');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Masuk — Takify</title>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%235b55e7'/%3E%3Cpath d='M32 12a2 2 0 0 1 2 2v1.2c5 1.1 9 5.6 9 11.1v9l3 4v1.5H18V39.3l3-4v-9c0-5.5 4-10 9-11.1V14a2 2 0 0 1 2-2z' fill='white'/%3E%3Cpath d='M27 43a5 5 0 0 0 10 0z' fill='white'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="style.css">
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .password-wrapper input {
            padding-right: 40px !important;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0a7b8;
            display: grid;
            place-items: center;
            transition: color 0.2s;
            margin-top: 11px;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .toggle-password svg {
            width: 20px !important;
            height: 20px !important;
            fill: currentColor;
        }
    </style>
</head>

<body class="account-page">
    <main class="account-card account-layout">
        <aside class="account-panel">
            <a class="account-brand" href="index.php" aria-label="Takify"
                style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <div
                    style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; min-width: 36px; min-height: 36px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex-shrink: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#5b55e7"
                        style="width: 22px; height: 22px; min-width: 22px; min-height: 22px; max-width: 22px; max-height: 22px; flex-shrink: 0;">
                        <path
                            d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
                    </svg>
                </div>
                <span style="font-size: 22px; font-weight: 700; color: #fff; letter-spacing: -0.5px;">Takify</span>
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

            <!-- TAMBAHAN: autocomplete="off" mencegah browser mengisi password otomatis -->
            <form method="post" class="form" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                <label for="email">Email
                    <input id="email" type="email" name="email" value="<?= e($prefill_email) ?>" autocomplete="email"
                        placeholder="nama@email.com" required autofocus>
                </label>

                <label for="password" class="password-wrapper">Kata sandi
                    <!-- Ubah autocomplete menjadi new-password -->
                    <input id="password" type="password" name="password" autocomplete="new-password"
                        placeholder="Ketik kata sandimu" required>
                    <span class="toggle-password" onclick="togglePass('password', this)">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                        </svg>
                    </span>
                </label>

                <button class="btn btn-primary btn-block account-submit">Masuk</button>
            </form>

            <p class="account-footer">
                Lupa kata sandi? <a href="lupa_password.php"
                    style="font-weight: 600; color: var(--primary); text-decoration: underline;">Ubah di sini</a>.
            </p>
            <p class="account-footer">Belum punya akun? <a href="register.php">Yuk, daftar</a></p>
        </section>
    </main>

    <script>
        function togglePass(inputId, iconElement) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
            } else {
                input.type = 'password';
                iconElement.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
            }
        }

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