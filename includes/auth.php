<?php

declare(strict_types=1);

/**
 * @return array{id:int,name:string,email:string,role:string}|null
 */
function bb_current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => (string) ($_SESSION['user_name'] ?? ''),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'role'  => (string) ($_SESSION['user_role'] ?? 'customer'),
    ];
}

function bb_is_admin(): bool
{
    $u = bb_current_user();

    return $u !== null && $u['role'] === 'admin';
}

/**
 * @param array<string,mixed> $row users table row
 */
function bb_login_user(array $row): void
{
    $_SESSION['user_id']    = (int) $row['id'];
    $_SESSION['user_name']  = (string) $row['name'];
    $_SESSION['user_email'] = (string) $row['email'];
    $_SESSION['user_role']  = (string) $row['role'];
}

function bb_logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role']);
}

function bb_require_login(string $redirectTo = '/login.php'): void
{
    if (bb_current_user() === null) {
        $q = $_SERVER['QUERY_STRING'] ?? '';
        $dest = $_SERVER['REQUEST_URI'] ?? '/';
        $url = BOOKBITS_BASE . $redirectTo . '?redirect=' . rawurlencode($dest !== '' ? $dest . ($q !== '' ? '?' . $q : '') : $dest);
        header('Location: ' . $url);
        exit;
    }
}

function bb_require_admin(): void
{
    if (bb_is_admin()) {
        return;
    }

    $req  = $_SERVER['REQUEST_URI'] ?? '';
    $path = $req !== '' ? (string) parse_url($req, PHP_URL_PATH) : '';
    $base = rtrim(BOOKBITS_BASE, '/');
    $rel  = 'admin/index.php';
    if ($path !== '' && strpos($path, $base . '/') === 0) {
        $rel = ltrim(substr($path, strlen($base)), '/');
    }

    $_SESSION['admin_login_error'] = 'Please sign in with an administrator account to continue.';
    header('Location: ' . BOOKBITS_BASE . '/admin/login.php?redirect=' . rawurlencode($rel));
    exit;
}
