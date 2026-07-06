-- Sync default admin row; dashboard password is enforced in config/app.php (BOOKBITS_ADMIN_*).
--
-- Usage:
--   mysql -u root -p bookbits < database/fix-admin-password.sql
--
-- Sign in: /Bookbits/admin/login.php — same email/password as BOOKBITS_ADMIN_EMAIL / BOOKBITS_ADMIN_PASSWORD

USE `bookbits`;

UPDATE `users`
SET
    `password` = 'Admin@1234',
    `role`     = 'admin'
WHERE `email` = 'admin@bookbits.com';

INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`)
VALUES (
    'Admin',
    'admin@bookbits.com',
    'Admin@1234',
    'admin'
);
