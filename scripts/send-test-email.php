<?php

declare(strict_types=1);

/**
 * One-off Resend delivery check — run via CLI only.
 * Usage: php scripts/send-test-email.php
 */
require_once __DIR__ . '/../includes/init.php';

$to = 'timichris45@gmail.com';
$subject = 'Bookbits email test — ' . BOOKBITS_STORE_NAME;
$html = bb_email_shell(
    'Email test',
    '<p style="margin:0;font-size:15px;color:#334155;">Hi,</p>'
    . '<p style="margin:12px 0 0;font-size:15px;color:#334155;line-height:1.55;">This is a test email from your Bookbits store via Resend '
    . '(from <strong>' . htmlspecialchars(BOOKBITS_MAIL_FROM_EMAIL, ENT_QUOTES, 'UTF-8') . '</strong>).</p>'
    . '<p style="margin:12px 0 0;font-size:14px;color:#64748b;">If you received this, customer order emails are working.</p>'
);
$plain = "Bookbits email test from " . BOOKBITS_MAIL_FROM_EMAIL . "\nIf you got this, Resend is working.\n";

$ok = bb_send_email($to, $subject, $html, $plain);
echo $ok ? "OK: sent to {$to}\n" : ('FAIL: ' . bb_mail_last_error() . "\n");
exit($ok ? 0 : 1);
