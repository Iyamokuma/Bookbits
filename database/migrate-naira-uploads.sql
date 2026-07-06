-- Optional migration for existing databases: Naira defaults + cover upload path support (no structural change).
-- New installs: use current schema.sql instead.

USE `bookbits`;

ALTER TABLE `orders`
    MODIFY `shipping_country` VARCHAR(100) NOT NULL DEFAULT 'NG';

ALTER TABLE `payments`
    MODIFY `currency` VARCHAR(3) NOT NULL DEFAULT 'NGN';
