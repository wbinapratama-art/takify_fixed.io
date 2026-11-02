<?php
function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $t = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(419);
        die('CSRF token tidak valid.');
    }
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function status_label(string $s): string
{
    return match ($s) {
        'belum' => 'Belum dimulai',
        'proses' => 'Sedang dikerjakan',
        'selesai' => 'Selesai',
        default => ucfirst($s),
    };
}

function prioritas_label(string $p): string
{
    return match ($p) {
        'rendah' => 'Rendah',
        'sedang' => 'Sedang',
        'tinggi' => 'Tinggi',
        default => ucfirst($p),
    };
}
