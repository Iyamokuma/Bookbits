<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/money.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/payments.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/blogs.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

try {
    bb_cart_bootstrap();
} catch (Throwable $e) {
    // Keep public pages renderable while hosting database settings are being completed.
}
