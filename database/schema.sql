-- ============================================================
-- Bookbits — MySQL Schema
-- Compatible with MySQL 5.7+ / MariaDB 10.4+
-- Run once:  mysql -u root -p bookbits < database/schema.sql
--
-- Existing database from an older schema?
--   CREATE TABLE IF NOT EXISTS does not add new columns. Use the
--   "UPGRADE — existing database only" section at the end of this file
--   (uncomment those lines in phpMyAdmin, or run them one at a time).
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `bookbits`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `bookbits`;

-- ------------------------------------------------------------
-- 1. USERS
--    Covers both customers and admins via the `role` column.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(120)    NOT NULL,
    `email`             VARCHAR(191)    NOT NULL,
    `password`          VARCHAR(255)    NOT NULL COMMENT 'bcrypt hash for customers; default admin uses config/app.php',
    `role`              ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `email_verified_at` TIMESTAMP       NULL DEFAULT NULL,
    `remember_token`    VARCHAR(100)    NULL DEFAULT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. CATEGORIES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100)    NOT NULL,
    `slug`        VARCHAR(100)    NOT NULL COMMENT 'URL-safe identifier used in shop.php?cat=<slug>',
    `description` TEXT            NULL,
    `icon`        TEXT            NULL     COMMENT 'SVG path d= attribute (Heroicons outline)',
    `bg_color`    VARCHAR(60)     NULL     COMMENT 'Tailwind bg-* class for card backgrounds',
    `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. BOOKS  (products)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `books` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `category_id`    INT UNSIGNED     NOT NULL,
    `title`          VARCHAR(255)     NOT NULL,
    `author`         VARCHAR(180)     NOT NULL,
    `isbn`           VARCHAR(20)      NULL COMMENT 'ISBN-10 or ISBN-13; optional — omit or NULL if unknown',
    `description`    TEXT             NULL,
    `price`          DECIMAL(10,2)    NOT NULL,
    `sale_price`     DECIMAL(10,2)    NULL     COMMENT 'NULL means no active sale',
    `has_cover_options` TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'If 1, customer must select paperback or hardcover',
    `paperback_price`   DECIMAL(10,2) NULL     COMMENT 'Required when has_cover_options = 1',
    `hardcover_price`   DECIMAL(10,2) NULL     COMMENT 'Required when has_cover_options = 1',
    `stock_qty`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `cover_image`    VARCHAR(255)     NULL     COMMENT 'path relative to /assets/img/covers/ OR a full URL',
    `pages`          SMALLINT UNSIGNED NULL,
    `publisher`      VARCHAR(150)     NULL,
    `published_year` YEAR             NULL,
    `language`       VARCHAR(60)      NOT NULL DEFAULT 'English',
    `is_featured`    TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Show in featured / hero sections',
    `is_deal`        TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Show in Daily Deals section',
    `is_new_arrival` TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Show in New Arrivals section',
    `is_book_bundle` TINYINT(1)       NOT NULL DEFAULT 0 COMMENT 'Show in Book Bundles section',
    `is_active`      TINYINT(1)       NOT NULL DEFAULT 1,
    `created_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_books_isbn` (`isbn`),
    KEY `idx_books_category` (`category_id`),
    KEY `idx_books_featured` (`is_featured`),
    KEY `idx_books_deal`     (`is_deal`),
    KEY `idx_books_new_arrival` (`is_new_arrival`),
    KEY `idx_books_book_bundle` (`is_book_bundle`),
    CONSTRAINT `fk_books_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. CART ITEMS
--    session_id is always set (supports guest carts).
--    user_id is set after login; used to merge carts.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cart_items` (
    `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED     NULL DEFAULT NULL,
    `session_id` VARCHAR(128)     NOT NULL,
    `book_id`    INT UNSIGNED     NOT NULL,
    `cover_type` ENUM('paperback','hardcover') NULL DEFAULT NULL,
    `qty`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cart_session_book_cover` (`session_id`, `book_id`, `cover_type`),
    KEY `idx_cart_user` (`user_id`),
    CONSTRAINT `fk_cart_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cart_book`
        FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. ORDERS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED  NOT NULL,
    `status`           ENUM('pending','processing','shipped','delivered','cancelled','refunded')
                                     NOT NULL DEFAULT 'pending',
    `subtotal`         DECIMAL(10,2) NOT NULL,
    `discount`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `shipping_fee`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total`            DECIMAL(10,2) NOT NULL,
    `shipping_name`    VARCHAR(180)  NULL,
    `shipping_phone`   VARCHAR(40)   NULL,
    `shipping_address` TEXT          NULL,
    `shipping_city`    VARCHAR(100)  NULL,
    `shipping_state`   VARCHAR(100)  NULL,
    `shipping_zip`     VARCHAR(20)   NULL,
    `shipping_country` VARCHAR(100)  NOT NULL DEFAULT 'NG',
    `notes`            TEXT          NULL,
    `payment_confirmed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Set when payment gateway confirms success',
    `checkout_snapshot`    JSON      NULL DEFAULT NULL COMMENT 'Frozen shipping + line items JSON at payment success',
    `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_orders_user`   (`user_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_payment_confirmed` (`payment_confirmed_at`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. ORDER ITEMS
--    Snapshots title + author + price at purchase time so
--    the history stays correct even if a book is later edited.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `order_id`   INT UNSIGNED  NOT NULL,
    `book_id`    INT UNSIGNED  NOT NULL,
    `title`      VARCHAR(255)  NOT NULL COMMENT 'Snapshot at purchase time',
    `author`     VARCHAR(180)  NOT NULL COMMENT 'Snapshot at purchase time',
    `cover_type` ENUM('paperback','hardcover') NULL DEFAULT NULL COMMENT 'Selected cover for this order line',
    `qty`        TINYINT UNSIGNED NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL COMMENT 'Price charged (may be sale_price)',
    `subtotal`   DECIMAL(10,2) NOT NULL COMMENT 'qty × unit_price',
    PRIMARY KEY (`id`),
    KEY `idx_oi_order` (`order_id`),
    KEY `idx_oi_book`  (`book_id`),
    CONSTRAINT `fk_oi_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_oi_book`
        FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. PAYMENTS
--    One payment row per order (1-to-1 with orders).
--    gateway_response stores raw JSON from the payment gateway.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `order_id`          INT UNSIGNED  NOT NULL,
    `method`            ENUM('card','paypal','bank_transfer','cash_on_delivery','paystack','klump','korapayment')
                                      NOT NULL,
    `status`            ENUM('pending','completed','failed','refunded')
                                      NOT NULL DEFAULT 'pending',
    `amount`            DECIMAL(10,2) NOT NULL,
    `currency`          VARCHAR(3)    NOT NULL DEFAULT 'NGN',
    `transaction_ref`   VARCHAR(255)  NULL COMMENT 'Gateway transaction / reference ID',
    `gateway_response`  JSON          NULL,
    `paid_at`           TIMESTAMP     NULL DEFAULT NULL,
    `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), 
    UNIQUE KEY `uq_payments_order` (`order_id`),
    KEY `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 8. BLOG POSTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blogs` (
    `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(255)   NOT NULL,
    `slug`         VARCHAR(255)   NOT NULL,
    `excerpt`      TEXT           NULL,
    `body`         MEDIUMTEXT     NOT NULL,
    `cover_image`  VARCHAR(255)   NULL,
    `is_active`    TINYINT(1)     NOT NULL DEFAULT 1,
    `published_at` DATETIME       NULL,
    `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_blogs_slug` (`slug`),
    KEY `idx_blogs_active_published` (`is_active`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 9. WISHLIST
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlist` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `book_id`    INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_wishlist_user_book` (`user_id`, `book_id`),
    CONSTRAINT `fk_wl_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_wl_book`
        FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA — Categories
-- ============================================================
INSERT INTO `categories`
    (`name`, `slug`, `description`, `bg_color`, `sort_order`)
VALUES
    ('Productivity',      'productivity',     'Work smarter, achieve more, and master your time.',              'bg-sky-100',    1),
    ('Finance',           'finance',          'Personal finance, investing, and wealth building.',              'bg-blue-100',   2),
    ('Faith',             'faith',            'Spiritual growth, theology, and devotionals.',                   'bg-indigo-50',  3),
    ('Leadership',        'leadership',       'Lead with clarity, purpose, and lasting impact.',                'bg-cyan-100',   4),
    ('Business',          'business',         'Strategy, entrepreneurship, and management.',                    'bg-sky-50',     5),
    ('Biography',         'biography',        'True stories of extraordinary lives.',                          'bg-blue-50',    6),
    ('Fiction',           'fiction',          'Novels, short stories, and literary fiction.',                   'bg-indigo-100', 7),
    ('Memoir',            'memoir',           'Personal narratives and first-hand accounts.',                   'bg-sky-100',    8),
    ('Children Books',    'children-books',   'Picture books, early readers, and middle grade.',                'bg-cyan-50',    9),
    ('History & Politics','history-politics', 'Events, movements, and the forces that shaped our world.',      'bg-blue-100',  10)
ON DUPLICATE KEY UPDATE
    `name`        = VALUES(`name`),
    `description` = VALUES(`description`),
    `bg_color`    = VALUES(`bg_color`),
    `sort_order`  = VALUES(`sort_order`);

-- ============================================================
-- SEED DATA — Default admin user (sign-in password is hardcoded in config/app.php)
--   BOOKBITS_ADMIN_EMAIL / BOOKBITS_ADMIN_PASSWORD
--   The `password` column here is informational only for that account.
-- ============================================================
INSERT INTO `users` (`name`, `email`, `password`, `role`)
VALUES (
    'Admin',
    'admin@bookbits.com',
    'Admin@1234',
    'admin'
)
ON DUPLICATE KEY UPDATE
    `name`     = VALUES(`name`),
    `password` = VALUES(`password`),
    `role`     = VALUES(`role`);

-- ============================================================
-- UPGRADE — existing database only
--
-- If your `bookbits` database was created before cover-type pricing,
-- the tables above already exist without the new columns. Importing
-- this full file will not alter them. Run the statements below instead.
--
-- NOTE: you asked to run the whole schema.sql, so this section is now
-- uncommented. If your DB is already updated, you may see
-- Duplicate column / Can't DROP / Duplicate key errors — in that case
-- you can stop the import and ignore the already-applied changes.
-- ============================================================

USE `bookbits`;

-- Make upgrades safe/idempotent so importing the whole file doesn't fail
-- on databases that were already upgraded.

-- -------------------------
-- books: cover type fields
-- -------------------------
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'has_cover_options');
SET @sql := IF(@has = 0,
    'ALTER TABLE `books` ADD COLUMN `has_cover_options` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''If 1, customer must select paperback or hardcover'' AFTER `sale_price`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @pb := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'paperback_price');
SET @sql := IF(@pb = 0,
    'ALTER TABLE `books` ADD COLUMN `paperback_price` DECIMAL(10,2) NULL COMMENT ''Required when has_cover_options = 1'' AFTER `has_cover_options`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @hc := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'hardcover_price');
SET @sql := IF(@hc = 0,
    'ALTER TABLE `books` ADD COLUMN `hardcover_price` DECIMAL(10,2) NULL COMMENT ''Required when has_cover_options = 1'' AFTER `paperback_price`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- books: homepage flags
-- -------------------------
SET @na := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'is_new_arrival');
SET @sql := IF(@na = 0,
    'ALTER TABLE `books` ADD COLUMN `is_new_arrival` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Show in New Arrivals section'' AFTER `is_deal`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @bb := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND COLUMN_NAME = 'is_book_bundle');
SET @sql := IF(@bb = 0,
    'ALTER TABLE `books` ADD COLUMN `is_book_bundle` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Show in Book Bundles section'' AFTER `is_new_arrival`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idxna := (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND INDEX_NAME = 'idx_books_new_arrival');
SET @sql := IF(@idxna = 0,
    'ALTER TABLE `books` ADD KEY `idx_books_new_arrival` (`is_new_arrival`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idxbb := (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'books' AND INDEX_NAME = 'idx_books_book_bundle');
SET @sql := IF(@idxbb = 0,
    'ALTER TABLE `books` ADD KEY `idx_books_book_bundle` (`is_book_bundle`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- cart_items: cover_type
-- -------------------------
SET @ct := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cart_items' AND COLUMN_NAME = 'cover_type');
SET @sql := IF(@ct = 0,
    'ALTER TABLE `cart_items` ADD COLUMN `cover_type` ENUM(''paperback'',''hardcover'') NULL DEFAULT NULL AFTER `book_id`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @oldIdx := (SELECT COUNT(*) FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cart_items' AND INDEX_NAME = 'uq_cart_session_book');
SET @sql := IF(@oldIdx > 0,
    'ALTER TABLE `cart_items` DROP INDEX `uq_cart_session_book`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @newIdx := (SELECT COUNT(*) FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cart_items' AND INDEX_NAME = 'uq_cart_session_book_cover');
SET @sql := IF(@newIdx = 0,
    'ALTER TABLE `cart_items` ADD UNIQUE KEY `uq_cart_session_book_cover` (`session_id`, `book_id`, `cover_type`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- order_items: cover_type
-- -------------------------
SET @oit := (SELECT COUNT(*) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'order_items' AND COLUMN_NAME = 'cover_type');
SET @sql := IF(@oit = 0,
    'ALTER TABLE `order_items` ADD COLUMN `cover_type` ENUM(''paperback'',''hardcover'') NULL DEFAULT NULL COMMENT ''Selected cover for this order line'' AFTER `author`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- orders: shipping_phone
-- -------------------------
SET @sp := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'shipping_phone');
SET @sql := IF(@sp = 0,
    'ALTER TABLE `orders` ADD COLUMN `shipping_phone` VARCHAR(40) NULL AFTER `shipping_name`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- orders: payment confirmation + checkout snapshot (admin / fulfillment)
-- -------------------------
SET @pca := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_confirmed_at');
SET @sql := IF(@pca = 0,
    'ALTER TABLE `orders` ADD COLUMN `payment_confirmed_at` TIMESTAMP NULL DEFAULT NULL COMMENT ''Set when payment gateway confirms success'' AFTER `notes`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @csn := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'checkout_snapshot');
SET @sql := IF(@csn = 0,
    'ALTER TABLE `orders` ADD COLUMN `checkout_snapshot` JSON NULL DEFAULT NULL COMMENT ''Frozen shipping + line items at payment success'' AFTER `payment_confirmed_at`',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idxpc := (SELECT COUNT(*) FROM information_schema.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND INDEX_NAME = 'idx_orders_payment_confirmed');
SET @sql := IF(@idxpc = 0,
    'ALTER TABLE `orders` ADD KEY `idx_orders_payment_confirmed` (`payment_confirmed_at`)',
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- payments: method enum expansion
-- -------------------------
-- This should be safe even if already applied.
SET @sql := 'ALTER TABLE `payments` MODIFY COLUMN `method` ENUM(''card'',''paypal'',''bank_transfer'',''cash_on_delivery'',''paystack'',''klump'',''korapayment'') NOT NULL';
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -------------------------
-- blogs: create + missing columns
-- -------------------------
CREATE TABLE IF NOT EXISTS `blogs` (
    `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(255)   NOT NULL,
    `slug`         VARCHAR(255)   NOT NULL,
    `excerpt`      TEXT           NULL,
    `body`         MEDIUMTEXT     NOT NULL,
    `cover_image`  VARCHAR(255)   NULL,
    `is_active`    TINYINT(1)     NOT NULL DEFAULT 1,
    `published_at` DATETIME       NULL,
    `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_blogs_slug` (`slug`),
    KEY `idx_blogs_active_published` (`is_active`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @ex := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blogs' AND COLUMN_NAME = 'excerpt');
SET @sql := IF(@ex = 0, 'ALTER TABLE `blogs` ADD COLUMN `excerpt` TEXT NULL AFTER `slug`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @bd := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blogs' AND COLUMN_NAME = 'body');
SET @sql := IF(@bd = 0, 'ALTER TABLE `blogs` ADD COLUMN `body` MEDIUMTEXT NOT NULL AFTER `excerpt`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ci := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blogs' AND COLUMN_NAME = 'cover_image');
SET @sql := IF(@ci = 0, 'ALTER TABLE `blogs` ADD COLUMN `cover_image` VARCHAR(255) NULL AFTER `body`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ia := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blogs' AND COLUMN_NAME = 'is_active');
SET @sql := IF(@ia = 0, 'ALTER TABLE `blogs` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `cover_image`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @pa := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blogs' AND COLUMN_NAME = 'published_at');
SET @sql := IF(@pa = 0, 'ALTER TABLE `blogs` ADD COLUMN `published_at` DATETIME NULL AFTER `is_active`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ua := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blogs' AND COLUMN_NAME = 'updated_at');
SET @sql := IF(@ua = 0, 'ALTER TABLE `blogs` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
