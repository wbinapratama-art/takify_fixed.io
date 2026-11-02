<?php
require_once __DIR__ . '/src_database.php';

class UserSession
{
    public static function attempt(string $email, string $password): bool
    {
        $pdo = Database::conn();
        $email = strtolower(trim($email));

        $stmt = $pdo->prepare("SELECT u.*, w.name AS workspace_name FROM users u JOIN workspaces w ON w.id=u.workspace_id WHERE u.email = :e AND u.status = 'Aktif' LIMIT 1");
        $stmt->execute(['e' => $email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'nama' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'workspace' => $user['workspace_name'],
        ];
        Database::conn()->prepare("UPDATE users SET last_active_at = NOW(), last_seen_at = NOW(), status = 'Aktif' WHERE id = :id")
            ->execute(['id' => $user['id']]);
        Database::conn()->prepare("INSERT INTO activity_logs (workspace_id, user_id, action_type, description) VALUES (:wid,:uid,'login','Login berhasil')")
            ->execute(['wid' => $user['workspace_id'], 'uid' => $user['id']]);
        // Ingat user terakhir yang login di browser ini (dipakai untuk "Fokus hari ini" di halaman login/register)
        setcookie('takify_last_user', (string) $user['id'], [
            'expires' => time() + 60 * 60 * 24 * 365,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    public static function logout(): void
    {
        if (self::check()) {
            $uid = self::id();
            Database::conn()->prepare('UPDATE users SET last_active_at = NOW(), last_seen_at = NULL WHERE id = :id')
                ->execute(['id' => $uid]);
        }
        unset($_SESSION['user']);
        session_destroy();
    }

    public static function register(string $nama, string $email, string $password): array
    {
        $pdo = Database::conn();
        $email = strtolower(trim($email));

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :e");
        $stmt->execute(['e' => $email]);
        if ($stmt->fetch()) {
            return [false, 'Email ini sudah punya akun. Coba masuk aja.'];
        }

        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO workspaces (name) VALUES (:n)")->execute(['n' => 'Workspace ' . $nama]);
            $workspaceId = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO users (workspace_id, name, email, password_hash, role, status) VALUES (:w,:n,:e,:p,'Anggota','Aktif')")
                ->execute(['w' => $workspaceId, 'n' => $nama, 'e' => $email, 'p' => $hash]);
            $uid = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (:u)")->execute(['u' => $uid]);
            $pdo->commit();
            return [true, 'Akun berhasil dibuat.'];
        } catch (Throwable $e) {
            $pdo->rollBack();
            return [false, 'Akun belum bisa dibuat. Coba lagi sebentar, ya.'];
        }
    }

    /** Mengundang anggota ke workspace milik pengundang, dengan kata sandi sementara. */
    public static function createMember(int $inviterId, string $nama, string $email, string $password, string $role): array
    {
        $pdo = Database::conn();
        $email = strtolower(trim($email));
        if (!in_array($role, ['Anggota', 'Pengamat'], true)) {
            $role = 'Anggota';
        }

        $stmt = $pdo->prepare('SELECT workspace_id FROM users WHERE id = :id');
        $stmt->execute(['id' => $inviterId]);
        $workspaceId = (int) $stmt->fetchColumn();
        if ($workspaceId <= 0) {
            return [false, 'Workspace tidak ditemukan.'];
        }

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $stmt->execute(['e' => $email]);
        if ($stmt->fetch()) {
            return [false, 'Email ini sudah terdaftar.'];
        }

        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO users (workspace_id, name, email, password_hash, role, status) VALUES (:w,:n,:e,:p,:r,'Aktif')")
                ->execute(['w' => $workspaceId, 'n' => $nama, 'e' => $email, 'p' => $hash, 'r' => $role]);
            $uid = (int) $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO user_preferences (user_id) VALUES (:u)')->execute(['u' => $uid]);
            $pdo->prepare("INSERT INTO activity_logs (workspace_id, user_id, action_type, description, reference_id) VALUES (:w,:u,'invite',:d,:r)")
                ->execute(['w' => $workspaceId, 'u' => $inviterId, 'd' => 'Mengundang ' . $nama . ' (' . $role . ')', 'r' => (string) $uid]);
            $pdo->commit();
            return [true, 'Akun dibuat.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return [false, 'Undangan belum bisa dibuat. Coba lagi sebentar, ya.'];
        }
    }

    public static function isOnline(?string $lastSeenAt, int $thresholdSeconds = 90): bool
    {
        if ($lastSeenAt === null || $lastSeenAt === '') {
            return false;
        }
        return (time() - strtotime($lastSeenAt)) <= $thresholdSeconds;
    }
}