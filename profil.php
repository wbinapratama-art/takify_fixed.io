<?php
require __DIR__ . '/bootstrap.php';
UserSession::requireLogin();
$u = UserSession::user();
$uid = UserSession::id();
$pdo = Database::conn();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $uid]);
$userData = $stmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update-profile') {
    verify_csrf();

    $nama = trim($_POST['nama'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $gender = in_array($_POST['gender'] ?? '', ['Male', 'Female', 'Other'], true) ? $_POST['gender'] : null;
    $dob = $_POST['dob'] ?? '';
    $dob = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ? $dob : null;

    if ($nama === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Nama dan Email yang valid wajib diisi.';
    } elseif ($password !== '' && strlen($password) < 12) {
        $error = 'Kata sandi baru minimal 12 karakter.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email=:e AND id<>:id");
        $check->execute(['e' => $email, 'id' => $uid]);
        if ($check->fetch()) {
            $error = 'Email sudah digunakan akun lain.';
        } else {
            if ($password !== '') {
                $upd = $pdo->prepare("UPDATE users SET name=:n, email=:e, password_hash=:p, phone=:ph, gender=:g, dob=:d WHERE id=:id");
                $upd->execute(['n' => $nama, 'e' => $email, 'p' => password_hash($password, PASSWORD_BCRYPT), 'ph' => $phone ?: null, 'g' => $gender, 'd' => $dob, 'id' => $uid]);
            } else {
                $upd = $pdo->prepare("UPDATE users SET name=:n, email=:e, phone=:ph, gender=:g, dob=:d WHERE id=:id");
                $upd->execute(['n' => $nama, 'e' => $email, 'ph' => $phone ?: null, 'g' => $gender, 'd' => $dob, 'id' => $uid]);
            }
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            flash('success', 'Profil berhasil diperbarui.');
            redirect('profil.php');
        }
    }
}

$stmt->execute(['id' => $uid]);
$userData = $stmt->fetch();
$_SESSION['user']['avatar'] = $userData['avatar'] ?? null;

$extras = [
    'phone' => $userData['phone'] ?? '',
    'gender' => $userData['gender'] ?? '',
    'dob' => $userData['dob'] ?? '',
];

$email = $userData['email'];
$atPos = strpos($email, '@');
$maskedEmail = $atPos !== false ? substr($email, 0, 2) . str_repeat('*', max(3, $atPos - 2)) . substr($email, $atPos) : $email;

$page = 'My Profile';
$subtitle = 'Manage and protect your account';
require __DIR__ . '/header.php';
?>

<style>
    .profile-container {
        display: flex;
        gap: 40px;
        background: #fff;
        padding: 40px;
        border-radius: 8px;
        border: 1px solid var(--border);
        max-width: 1100px;
    }

    .profile-left {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-group {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }

    .form-group>label {
        width: 130px;
        text-align: right;
        font-size: 13px;
        color: #555;
        margin-top: 10px;
    }

    .form-control-wrapper {
        flex: 1;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        color: #333;
        outline: none;
    }

    .form-control:focus {
        border-color: var(--primary);
    }

    .form-control:disabled {
        background: #f9f9f9;
        color: #888;
        cursor: not-allowed;
    }

    .form-text {
        font-size: 12px;
        color: #888;
        margin-top: 6px;
    }

    .link-change {
        color: #0056b3;
        text-decoration: underline;
        cursor: pointer;
        font-size: 13px;
        margin-left: 10px;
    }

    .radio-group {
        display: flex;
        gap: 20px;
        margin-top: 8px;
    }

    .radio-group label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        cursor: pointer;
        color: #333;
        font-weight: normal;
    }

    .radio-group input[type="radio"] {
        width: 16px;
        height: 16px;
        accent-color: #555;
        cursor: pointer;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 10px;
        margin-left: 150px;
    }

    .btn-save {
        background: var(--primary);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        transition: background 0.2s;
    }

    .btn-save:hover {
        background: var(--primary-hover);
    }

    .profile-right {
        width: 220px;
        border-left: 1px solid #eee;
        padding-left: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: #5b6cb8;
        color: #fff;
        display: grid;
        place-items: center;
        font-size: 50px;
        font-weight: normal;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .btn-select-img {
        background: #fff;
        border: 1px solid #ddd;
        padding: 10px 24px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        color: #333;
        margin-bottom: 15px;
        transition: 0.2s;
    }

    .btn-select-img:hover {
        background: #f9f9f9;
    }

    .file-info {
        text-align: center;
        font-size: 12px;
        color: #888;
        line-height: 1.6;
    }
</style>

<div class="profile-container">
    <form method="post" class="profile-left">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update-profile">

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-left: 150px;"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label>Username</label>
            <div class="form-control-wrapper">
                <input type="text" class="form-control" value="<?= e(explode('@', $userData['email'])[0]) ?>" disabled>
                <div class="form-text">Username can only be changed once.</div>
            </div>
        </div>

        <div class="form-group">
            <label>Name</label>
            <div class="form-control-wrapper">
                <input type="text" name="nama" class="form-control" value="<?= e($userData['name']) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Email</label>
            <div class="form-control-wrapper" style="display: flex; align-items: center; padding: 5px 0;">
                <span style="font-size: 14px; color: #333;" id="email-text"><?= e($maskedEmail) ?></span>
                <a href="#" class="link-change"
                    onclick="document.getElementById('real-email-input').style.display='block'; document.getElementById('email-text').style.display='none'; this.style.display='none'; return false;">Change</a>
            </div>
            <input type="email" name="email" id="real-email-input" class="form-control"
                style="display: none; margin-top: 10px;" value="<?= e($userData['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <div class="form-control-wrapper" style="display: flex; align-items: center; padding: 5px 0;">
                <?php if (!empty($extras['phone'])): ?>
                    <span style="font-size: 14px; color: #333;"
                        id="phone-text"><?= e(substr($extras['phone'], 0, 4)) ?>*******</span>
                    <a href="#" class="link-change"
                        onclick="document.getElementById('real-phone-input').style.display='block'; document.getElementById('phone-text').style.display='none'; this.style.display='none'; return false;">Change</a>
                <?php else: ?>
                    <a href="#" class="link-change"
                        onclick="document.getElementById('real-phone-input').style.display='block'; this.style.display='none'; return false;"
                        style="margin-left: 0;">Add</a>
                <?php endif; ?>
            </div>
            <input type="text" name="phone" id="real-phone-input" class="form-control"
                style="display: none; margin-top: 10px;" value="<?= e($extras['phone']) ?>"
                placeholder="Masukkan nomor HP">
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="form-control-wrapper" style="display: flex; align-items: center; padding: 5px 0;">
                <span style="font-size: 14px; color: #333;" id="pass-text">********</span>
                <a href="#" class="link-change"
                    onclick="document.getElementById('real-pass-input').style.display='block'; document.getElementById('pass-text').style.display='none'; this.style.display='none'; return false;">Change</a>
            </div>
            <input type="password" name="password" id="real-pass-input" class="form-control"
                style="display: none; margin-top: 10px;" placeholder="Minimal 12 karakter" minlength="12">
        </div>

        <div class="form-group">
            <label>Gender</label>
            <div class="form-control-wrapper radio-group">
                <label><input type="radio" name="gender" value="Male" <?= $extras['gender'] === 'Male' ? 'checked' : '' ?>>
                    Male</label>
                <label><input type="radio" name="gender" value="Female" <?= $extras['gender'] === 'Female' ? 'checked' : '' ?>> Female</label>
                <label><input type="radio" name="gender" value="Other" <?= $extras['gender'] === 'Other' ? 'checked' : '' ?>> Other</label>
            </div>
        </div>

        <div class="form-group">
            <label>Date of birth</label>
            <div class="form-control-wrapper" style="padding: 8px 0;">
                <input type="date" name="dob" value="<?= e($extras['dob']) ?>"
                    style="border: none; background: transparent; color: #333; font-size: 14px; outline: none; width: 150px;">
            </div>
        </div>

        <div class="action-buttons">
            <button type="submit" class="btn-save">Save</button>
            <a href="logout.php" class="btn-logout"
                style="background:#fff; color:#d32f2f; border:1px solid #d32f2f; padding:11px 30px; border-radius:4px; text-decoration:none; font-size:14px;">Logout</a>
        </div>
    </form>

    <div class="profile-right">
        <div class="profile-avatar">
            <?php if (!empty($userData['avatar']) && file_exists(__DIR__ . '/' . $userData['avatar'])): ?>
                <img src="<?= e($userData['avatar']) ?>?t=<?= time() ?>" alt="Avatar">
            <?php else: ?>
                <?= strtoupper(substr($userData['name'], 0, 1)) ?>
            <?php endif; ?>
        </div>

        <form method="post" action="avatar_action.php" enctype="multipart/form-data" id="avatar-form">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="file" name="avatar" id="avatar-input" accept=".jpg,.jpeg,.png" style="display: none;"
                onchange="previewAvatar(this);">
            <button type="button" class="btn-select-img"
                onclick="document.getElementById('avatar-input').click();">Select Image</button>
            <button type="submit" class="btn btn-primary btn-sm" id="avatar-submit"
                style="display:none; margin-top:8px;">
                Simpan foto
            </button>
        </form>

        <div class="file-info">
            File size: maximum 1 MB<br>
            File extension: .JPEG, .PNG
        </div>
    </div>
</div>

<script>
    function previewAvatar(input) {
        if (!input.files || !input.files[0]) return;
        const f = input.files[0];
        if (f.size > 1024 * 1024) { alert('Maksimal 1 MB.'); input.value = ''; return; }
        const btn = document.getElementById('avatar-submit');
        btn.style.display = 'inline-flex';
        const reader = new FileReader();
        reader.onload = e => {
            const av = document.querySelector('.profile-avatar');
            av.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(f);
    }
</script>

<?php require __DIR__ . '/footer.php'; ?>