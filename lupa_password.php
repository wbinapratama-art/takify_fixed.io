<?php
require __DIR__ . '/bootstrap.php';

if (UserSession::check()) {
    redirect('dashboard.php');
}

$pdo = Database::conn();
$error = '';
$step = $_POST['step'] ?? '1';
$email = strtolower(trim($_POST['email'] ?? ''));

// Waktu dibikin 5 menit (300 detik)
define('RESET_TTL', 300);
define('RESET_MAX_TRY', 5);

function generateToken($length = 6)
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    for ($i = 0; $i < $length; $i++) {
        $token .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $token;
}

// Tangani permintaan AJAX untuk kode baru (saat timer habis)
if (isset($_GET['refresh_token']) && isset($_SESSION['reset_email'])) {
    if (!empty($_SESSION['reset_verified'])) {
        http_response_code(403);
        echo json_encode(['error' => 'already_verified']);
        exit;
    }
    $new_token = generateToken(6);
    $_SESSION['reset_token'] = $new_token;
    $_SESSION['reset_token_time'] = time();
    $_SESSION['reset_try'] = 0;
    echo json_encode(['token' => $new_token]);
    exit;
}

// LANGKAH 1: Cek Email & Buat Kode Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '1') {
    verify_csrf();
    if ($email === '') {
        $error = 'Email wajib diisi.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e AND status = 'Aktif'");
        $stmt->execute(['e' => $email]);
        if ($stmt->fetch()) {
            $token = generateToken(6);
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_token_time'] = time();
            $_SESSION['reset_try'] = 0;
            $step = '2';
        } else {
            $error = 'Email tidak ditemukan atau akun sedang tidak aktif.';
        }
    }
}

// LANGKAH 2: Verifikasi Kode
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '2' && isset($_POST['verify_code'])) {
    verify_csrf();
    $input_code = trim($_POST['verify_code'] ?? '');
    $sessionEmail = $_SESSION['reset_email'] ?? '';

    if ($sessionEmail === '' || $email !== $sessionEmail) {
        $error = 'Sesi verifikasi tidak valid. Silakan ulangi dari awal.';
        $step = '1';
        unset($_SESSION['reset_token'], $_SESSION['reset_token_time'], $_SESSION['reset_email']);
    } else {
        $token_time = $_SESSION['reset_token_time'] ?? 0;
        $_SESSION['reset_try'] = ($_SESSION['reset_try'] ?? 0) + 1;

        if ($_SESSION['reset_try'] > RESET_MAX_TRY) {
            $error = 'Terlalu banyak percobaan. Silakan minta kode baru.';
            unset($_SESSION['reset_token'], $_SESSION['reset_token_time']);
        } elseif (time() - $token_time > RESET_TTL) {
            $error = 'Kode kedaluwarsa. Silakan minta kode baru.';
            unset($_SESSION['reset_token'], $_SESSION['reset_token_time']);
        } elseif (!hash_equals($_SESSION['reset_token'] ?? '', $input_code)) {
            $error = 'Kode verifikasi salah. Sisa percobaan: ' . (RESET_MAX_TRY - $_SESSION['reset_try']);
        } else {
            $_SESSION['reset_verified'] = true;
            unset($_SESSION['reset_token'], $_SESSION['reset_token_time'], $_SESSION['reset_try']);
            $step = '3';
        }
    }
}

// LANGKAH 3: Simpan Password Baru (DENGAN VALIDASI KETAT)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === '3' && isset($_POST['new_password'])) {
    verify_csrf();
    $sessionEmail = $_SESSION['reset_email'] ?? '';

    if (empty($_SESSION['reset_verified']) || $sessionEmail === '' || $email !== $sessionEmail) {
        $error = 'Sesi tidak valid. Silakan ulangi dari awal.';
        $step = '1';
        unset($_SESSION['reset_verified'], $_SESSION['reset_email']);
    } else {
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        $commonPasswords = ['password', '123456', '12345678', 'qwerty', 'admin', 'kucing', 'anjing', 'sayang', 'bismillah', 'rahasia'];
        $isCommon = false;
        foreach ($commonPasswords as $cp) {
            if (stripos($new_pass, $cp) !== false) {
                $isCommon = true;
                break;
            }
        }

        if (strlen($new_pass) < 12) {
            $error = 'Kata sandi minimal 12 karakter.';
        } elseif ($isCommon) {
            $error = 'Kata sandi terlalu mudah ditebak. Jangan gunakan kata umum.';
        } elseif ($new_pass !== $confirm_pass) {
            $error = 'Konfirmasi kata sandi tidak cocok.';
        } else {
            $hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = :p WHERE email = :e")
                ->execute(['p' => $hash, 'e' => $sessionEmail]);

            unset($_SESSION['reset_email'], $_SESSION['reset_verified']);
            flash('success', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru Anda.');
            redirect('login.php?email=' . urlencode($sessionEmail));
        }
    }
}

$time_left = RESET_TTL;
if ($step === '2' && isset($_SESSION['reset_token_time'])) {
    $time_left = RESET_TTL - (time() - $_SESSION['reset_token_time']);
    if ($time_left < 0)
        $time_left = 0;
}

$flashes = get_flash();
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lupa Kata Sandi — Takify</title>
    <link rel="icon" type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='14' fill='%235b55e7'/%3E%3Cpath d='M32 12a2 2 0 0 1 2 2v1.2c5 1.1 9 5.6 9 11.1v9l3 4v1.5H18V39.3l3-4v-9c0-5.5 4-10 9-11.1V14a2 2 0 0 1 2-2z' fill='white'/%3E%3Cpath d='M27 43a5 5 0 0 0 10 0z' fill='white'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="style.css">
    <style>
        .reset-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            background: #fff;
        }

        .reset-panel {
            background: linear-gradient(150deg, #5b55e7 0%, #4a44d1 100%);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
        }

        .reset-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 80px;
            text-decoration: none;
            color: #fff;
        }

        .reset-brand-icon {
            width: 44px;
            height: 44px;
            background: #fff;
            border-radius: 12px;
            display: grid;
            place-items: center;
        }

        .reset-brand-icon svg {
            width: 24px;
            height: 24px;
            fill: #5b55e7;
        }

        .reset-brand-text {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .reset-kicker {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #c7c4ff;
            margin-bottom: 16px;
            text-transform: uppercase;
        }

        .reset-title {
            font-size: 48px;
            font-weight: 700;
            line-height: 1.15;
            margin: 0 0 24px 0;
            letter-spacing: -1px;
        }

        .reset-desc {
            font-size: 16px;
            color: #e0e7ff;
            line-height: 1.6;
            max-width: 400px;
        }

        .reset-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 10%;
            background: #fff;
        }

        .reset-form-title {
            font-size: 28px;
            font-weight: 600;
            color: #222;
            margin: 0 0 8px 0;
        }

        .reset-form-subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 32px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #5b55e7;
            box-shadow: 0 0 0 3px rgba(91, 85, 231, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
            display: grid;
            place-items: center;
        }

        .toggle-password:hover {
            color: #5b55e7;
        }

        .toggle-password svg {
            width: 22px;
            height: 22px;
            fill: currentColor;
        }

        .strength-text {
            font-size: 12px;
            color: #777;
            line-height: 1.5;
            margin-top: -10px;
            margin-bottom: 20px;
        }

        .strength-text b {
            color: #444;
        }

        .btn-submit {
            width: 100%;
            background: #5b55e7;
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #4a44d1;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
            text-decoration: none;
        }

        .back-link:hover {
            color: #5b55e7;
        }

        .alert-info {
            padding: 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }

        .alert-info b {
            font-size: 24px;
            letter-spacing: 4px;
            display: block;
            margin-top: 8px;
            transition: opacity 0.3s ease;
        }

        .timer-text {
            font-size: 14px;
            color: #4b5563;
            display: block;
            text-align: center;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .timer-text span {
            font-family: monospace;
            font-weight: 700;
            color: #111827;
        }

        @media (max-width: 850px) {
            .reset-layout {
                grid-template-columns: 1fr;
            }

            .reset-panel {
                padding: 40px;
                text-align: center;
                align-items: center;
            }

            .reset-brand {
                margin-bottom: 40px;
            }

            .reset-title {
                font-size: 32px;
            }

            .reset-content {
                padding: 40px 24px;
            }
        }
    </style>
</head>

<body>
    <div class="reset-layout">
        <div class="reset-panel">
            <a href="index.php" class="reset-brand">
                <div class="reset-brand-icon">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z" />
                    </svg>
                </div>
                <span class="reset-brand-text">Takify</span>
            </a>
            <div>
                <div class="reset-kicker">AMANKAN AKUNMU</div>
                <h1 class="reset-title">Jangan panik.<br>Atur ulang sandimu.</h1>
                <p class="reset-desc">Kami akan memverifikasi identitasmu melalui email sebelum kamu bisa mengganti
                    sandi.</p>
            </div>
        </div>

        <div class="reset-content">
            <div>
                <?php if ($step === '1'): ?>
                    <h2 class="reset-form-title">Lupa Kata Sandi</h2>
                    <p class="reset-form-subtitle">Masukkan email terdaftar Anda untuk melanjutkan.</p>
                <?php elseif ($step === '2'): ?>
                    <h2 class="reset-form-title">Verifikasi Email</h2>
                    <p class="reset-form-subtitle">Kami telah mengirimkan kode verifikasi ke email Anda. (Simulasi: Kode
                        ditampilkan di bawah).</p>
                <?php else: ?>
                    <h2 class="reset-form-title">Buat Sandi Baru</h2>
                    <p class="reset-form-subtitle">Silakan buat kata sandi baru untuk akun Anda.</p>
                <?php endif; ?>

                <?php foreach ($flashes as $f): ?>
                    <div
                        style="margin-bottom: 20px; padding: 12px; border-radius: 8px; font-size: 14px; background: <?= $f['type'] === 'error' ? '#fee2e2' : '#dcfce7' ?>; color: <?= $f['type'] === 'error' ? '#991b1b' : '#166534' ?>;">
                        <?= e($f['msg']) ?>
                    </div>
                <?php endforeach; ?>
                <?php if ($error): ?>
                    <div
                        style="margin-bottom: 20px; padding: 12px; border-radius: 8px; font-size: 14px; background: #fee2e2; color: #991b1b;">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === '2'): ?>
                    <div class="alert-info">
                        <span>Kode Verifikasi Email Anda:</span>
                        <b id="code-display">
                            <?= e($_SESSION['reset_token']) ?>
                        </b>
                    </div>
                    <span class="timer-text">Sisa waktu: <span id="timer">05:00</span></span>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="email" value="<?= e($email) ?>">

                    <?php if ($step === '1'): ?>
                        <input type="hidden" name="step" value="1">
                        <div class="form-group">
                            <input type="email" name="email" class="form-control" value="<?= e($email) ?>"
                                placeholder="Alamat Email Terdaftar" required autofocus>
                        </div>
                        <button type="submit" class="btn-submit">Kirim Kode Verifikasi</button>
                    <?php elseif ($step === '2'): ?>
                        <input type="hidden" name="step" value="2">
                        <div class="form-group">
                            <input type="text" name="verify_code" class="form-control"
                                placeholder="Masukkan 6 karakter kode di atas" required autofocus maxlength="6"
                                style="text-align:center; letter-spacing:2px; font-weight:bold;">
                        </div>
                        <button type="submit" class="btn-submit">Verifikasi</button>
                    <?php else: ?>
                        <input type="hidden" name="step" value="3">
                        <div class="form-group password-wrapper">
                            <input type="password" name="new_password" id="pass1" class="form-control" required
                                minlength="12" placeholder="New password" autofocus>
                            <span class="toggle-password" onclick="togglePass('pass1', this)">
                                <svg viewBox="0 0 24 24">
                                    <path
                                        d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                </svg>
                            </span>
                        </div>
                        <div class="strength-text">
                            <b>Password strength:</b><br>
                            Use at least 12 characters. Don't use a password from another site, or something too obvious
                            like your pet's name.
                        </div>
                        <div class="form-group password-wrapper">
                            <input type="password" name="confirm_password" id="pass2" class="form-control" required
                                minlength="12" placeholder="Confirm new password">
                            <span class="toggle-password" onclick="togglePass('pass2', this)">
                                <svg viewBox="0 0 24 24">
                                    <path
                                        d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                </svg>
                            </span>
                        </div>
                        <button type="submit" class="btn-submit">Change password</button>
                    <?php endif; ?>
                    <a href="login.php" class="back-link">← Kembali ke Login</a>
                </form>
            </div>
        </div>
    </div>

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

        <?php if ($step === '2'): ?>
                document.addEventListener('DOMContentLoaded', () => {
                    // 300000 ms = 5 menit
                    let timeLeft = <?= (int) $time_left ?> * 1000;
                    const timerEl = document.getElementById('timer');
                    const codeEl = document.getElementById('code-display');
                    let timerInterval;

                    function formatTime(ms) {
                        if (ms < 0) ms = 0;
                        let totalSeconds = Math.floor(ms / 1000);
                        let minutes = Math.floor(totalSeconds / 60);
                        let seconds = totalSeconds % 60;
                        return String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                    }

                    timerEl.textContent = formatTime(timeLeft);

                    function startTimer() {
                        clearInterval(timerInterval);
                        timerInterval = setInterval(() => {
                            timeLeft -= 1000;
                            if (timeLeft >= 0) timerEl.textContent = formatTime(timeLeft);
                            if (timeLeft <= 0) {
                                clearInterval(timerInterval);
                                timerEl.textContent = "Memuat...";

                                // Minta token baru ke server via AJAX
                                fetch('lupa_password.php?refresh_token=1')
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.token) {
                                            codeEl.style.opacity = 0;
                                            setTimeout(() => {
                                                codeEl.textContent = data.token;
                                                codeEl.style.opacity = 1;
                                            }, 300);
                                            // Reset timer ke 5 menit lagi
                                            timeLeft = 300 * 1000;
                                            timerEl.textContent = formatTime(timeLeft);
                                            startTimer();
                                        } else {
                                            timerEl.textContent = "Gagal memuat kode.";
                                        }
                                    }).catch(() => {
                                        timerEl.textContent = "Gagal memuat kode.";
                                    });
                            }
                        }, 1000);
                    }
                    startTimer();
                });
        <?php endif; ?>
    </script>
</body>

</html>