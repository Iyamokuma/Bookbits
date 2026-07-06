<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

bb_logout_user();

header('Location: ' . BOOKBITS_BASE . '/index.php');
exit;
