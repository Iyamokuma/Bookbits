<?php

declare(strict_types=1);

function bb_safe_redirect_target(?string $target, string $defaultPath = 'index.php'): string
{
    $base    = rtrim(BOOKBITS_BASE, '/');
    $default = $base . '/' . ltrim($defaultPath, '/');

    if ($target === null) {
        return $default;
    }

    $t = trim($target);
    if ($t === '') {
        return $default;
    }

    if (strpos($t, '://') !== false || strpos($t, '..') !== false || strpos($t, "\n") !== false) {
        return $default;
    }

    // Same-site absolute path (e.g. /Bookbits/checkout.php)
    if ($t[0] === '/' && strpos($t, $base . '/') === 0) {
        return $t;
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\.\/?=&]+$/', $t)) {
        return $default;
    }

    return $base . '/' . ltrim($t, '/');
}
