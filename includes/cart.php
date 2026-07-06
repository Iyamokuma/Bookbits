<?php

declare(strict_types=1);

const BB_COVER_PAPERBACK = 'paperback';
const BB_COVER_HARDCOVER = 'hardcover';

/**
 * @return array<string,true>
 */
function bb_table_column_map(string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $map  = [];
    try {
        $st = db()->query('SHOW COLUMNS FROM `' . $safe . '`');
        if ($st !== false) {
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                if (isset($row['Field']) && $row['Field'] !== '') {
                    $map[(string) $row['Field']] = true;
                }
            }
        }
    } catch (Throwable $e) {
        $map = [];
    }
    $cache[$table] = $map;

    return $map;
}

function bb_table_has_column(string $table, string $column): bool
{
    return isset(bb_table_column_map($table)[$column]);
}

/** @return array{0:int,1:?string} */
function bb_cart_decode_key(string $key): array
{
    $parts = explode(':', $key, 2);
    $bookId = (int) ($parts[0] ?? 0);
    $coverType = $parts[1] ?? '';
    if ($coverType !== BB_COVER_PAPERBACK && $coverType !== BB_COVER_HARDCOVER) {
        $coverType = null;
    }

    return [$bookId, $coverType];
}

function bb_cart_encode_key(int $bookId, ?string $coverType): string
{
    if ($coverType === BB_COVER_PAPERBACK || $coverType === BB_COVER_HARDCOVER) {
        return $bookId . ':' . $coverType;
    }

    return (string) $bookId;
}

/**
 * @param array<string,mixed> $book
 */
function bb_book_supports_cover_options(array $book): bool
{
    if ((int) ($book['has_cover_options'] ?? 0) === 1) {
        return true;
    }
    $p = isset($book['paperback_price']) && $book['paperback_price'] !== null && $book['paperback_price'] !== ''
        ? (float) $book['paperback_price'] : 0.0;
    $h = isset($book['hardcover_price']) && $book['hardcover_price'] !== null && $book['hardcover_price'] !== ''
        ? (float) $book['hardcover_price'] : 0.0;

    return $p > 0 && $h > 0 && abs($p - $h) > 0.0001;
}

/**
 * @param array<string,mixed> $book
 */
function bb_book_validate_cover_type(array $book, ?string $coverType): bool
{
    if (!bb_book_supports_cover_options($book)) {
        return $coverType === null || $coverType === '';
    }

    return $coverType === BB_COVER_PAPERBACK || $coverType === BB_COVER_HARDCOVER;
}

/**
 * @param array<string,mixed> $book
 */
function bb_book_cover_price(array $book, ?string $coverType): float
{
    if (bb_book_supports_cover_options($book)) {
        if ($coverType === BB_COVER_HARDCOVER) {
            return (float) $book['hardcover_price'];
        }

        return (float) $book['paperback_price'];
    }

    return $book['sale_price'] !== null ? (float) $book['sale_price'] : (float) $book['price'];
}

function bb_cart_session_key(): string
{
    return 'bb_cart';
}

/** @return array<string,int> cart_key => qty */
function bb_cart_get_raw(): array
{
    $k = bb_cart_session_key();
    if (!isset($_SESSION[$k]) || !is_array($_SESSION[$k])) {
        $_SESSION[$k] = [];
    }

    $out = [];
    foreach ($_SESSION[$k] as $key => $qty) {
        $cartKey = (string) $key;
        if ($cartKey === '') {
            continue;
        }
        $out[$cartKey] = max(0, (int) $qty);
    }

    return array_filter($out, fn (int $q) => $q > 0);
}

function bb_cart_set_raw(array $items): void
{
    $_SESSION[bb_cart_session_key()] = $items;
}

function bb_cart_bootstrap(): void
{
    if (!isset($_SESSION[bb_cart_session_key()]) || !is_array($_SESSION[bb_cart_session_key()])) {
        $_SESSION[bb_cart_session_key()] = [];
    }

    $u = bb_current_user();
    if ($u === null) {
        return;
    }

    // New session with empty cart: pull saved cart from DB for logged-in users
    if (bb_cart_total_qty() === 0) {
        bb_cart_load_from_database($u['id']);
    }
}

function bb_cart_total_qty(): int
{
    return array_sum(bb_cart_get_raw());
}

function bb_cart_stable_session_id(int $userId): string
{
    return 'u' . $userId;
}

function bb_cart_load_from_database(int $userId): void
{
    $pdo = db();
    $hasCoverType = bb_table_has_column('cart_items', 'cover_type');
    $sql = $hasCoverType
        ? 'SELECT book_id, cover_type, qty FROM cart_items WHERE user_id = ?'
        : 'SELECT book_id, NULL AS cover_type, qty FROM cart_items WHERE user_id = ?';
    $st  = $pdo->prepare($sql);
    $st->execute([$userId]);
    $rows = $st->fetchAll();
    $map  = [];
    foreach ($rows as $r) {
        $bookId = (int) $r['book_id'];
        $coverType = $r['cover_type'] !== null ? (string) $r['cover_type'] : null;
        $map[bb_cart_encode_key($bookId, $coverType)] = (int) $r['qty'];
    }
    bb_cart_set_raw($map);
}

/**
 * Persist current session cart for logged-in user.
 */
function bb_cart_sync_to_database(int $userId): void
{
    $pdo = db();
    $sid = bb_cart_stable_session_id($userId);
    $hasCoverType = bb_table_has_column('cart_items', 'cover_type');
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
        $items = bb_cart_get_raw();
        if ($items !== []) {
            $ins = $hasCoverType
                ? $pdo->prepare('INSERT INTO cart_items (user_id, session_id, book_id, cover_type, qty) VALUES (?, ?, ?, ?, ?)')
                : $pdo->prepare('INSERT INTO cart_items (user_id, session_id, book_id, qty) VALUES (?, ?, ?, ?)');
            foreach ($items as $key => $qty) {
                [$bookId, $coverType] = bb_cart_decode_key((string) $key);
                if ($bookId < 1) {
                    continue;
                }
                if ($hasCoverType) {
                    $ins->execute([$userId, $sid, $bookId, $coverType, $qty]);
                } else {
                    $ins->execute([$userId, $sid, $bookId, $qty]);
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function bb_cart_clear_for_user(int $userId): void
{
    db()->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
    $u = bb_current_user();
    if ($u !== null && (int) $u['id'] === $userId) {
        bb_cart_set_raw([]);
    }
}

/**
 * Merge guest session cart into the user's DB-backed cart (sum quantities), then persist.
 */
function bb_cart_merge_guest_into_user(int $userId): void
{
    $guest = bb_cart_get_raw();
    bb_cart_load_from_database($userId);
    $existing = bb_cart_get_raw();
    foreach ($guest as $key => $qty) {
        $existing[$key] = ($existing[$key] ?? 0) + $qty;
    }
    bb_cart_set_raw($existing);
    bb_cart_sync_to_database($userId);
}

function bb_book_exists_active(int $bookId): bool
{
    $st = db()->prepare('SELECT 1 FROM books WHERE id = ? AND is_active = 1 LIMIT 1');
    $st->execute([$bookId]);

    return (bool) $st->fetchColumn();
}

function bb_book_stock(int $bookId): int
{
    $st = db()->prepare('SELECT stock_qty FROM books WHERE id = ? AND is_active = 1 LIMIT 1');
    $st->execute([$bookId]);
    $v = $st->fetchColumn();

    return $v !== false ? (int) $v : 0;
}

/**
 * @return array<string,mixed>|null
 */
function bb_fetch_book_cart_data(int $bookId): ?array
{
    $hasBookCoverOptions = bb_table_has_column('books', 'has_cover_options')
        && bb_table_has_column('books', 'paperback_price')
        && bb_table_has_column('books', 'hardcover_price');
    $sql = $hasBookCoverOptions
        ? 'SELECT id, title, is_active, stock_qty, price, sale_price, has_cover_options, paperback_price, hardcover_price
           FROM books
           WHERE id = ? AND is_active = 1 LIMIT 1'
        : 'SELECT id, title, is_active, stock_qty, price, sale_price, 0 AS has_cover_options, NULL AS paperback_price, NULL AS hardcover_price
           FROM books
           WHERE id = ? AND is_active = 1 LIMIT 1';
    $st = db()->prepare($sql);
    $st->execute([$bookId]);
    $row = $st->fetch();

    return $row !== false ? $row : null;
}

/**
 * @return array{ok:bool,message:string}
 */
function bb_cart_add(int $bookId, int $qty, ?string $coverType = null): array
{
    if ($qty < 1) {
        $qty = 1;
    }
    $book = bb_fetch_book_cart_data($bookId);
    if ($book === null) {
        return ['ok' => false, 'message' => 'This book is not available.'];
    }
    $coverType = $coverType !== null ? trim($coverType) : null;
    if ($coverType === '') {
        $coverType = null;
    }
    if (!bb_book_validate_cover_type($book, $coverType)) {
        return ['ok' => false, 'message' => 'Please select a valid cover type.'];
    }
    if (bb_book_supports_cover_options($book)) {
        $paper = (float) ($book['paperback_price'] ?? 0);
        $hard  = (float) ($book['hardcover_price'] ?? 0);
        if ($paper <= 0 || $hard <= 0) {
            return ['ok' => false, 'message' => 'Cover prices are not configured for this book.'];
        }
    }

    $stock = bb_book_stock($bookId);
    $cart  = bb_cart_get_raw();
    $key   = bb_cart_encode_key($bookId, $coverType);
    $cur   = $cart[$key] ?? 0;
    if ($cur + $qty > $stock) {
        return ['ok' => false, 'message' => 'Not enough copies in stock.'];
    }

    $cart[$key] = $cur + $qty;
    bb_cart_set_raw($cart);

    $u = bb_current_user();
    if ($u !== null) {
        bb_cart_sync_to_database($u['id']);
    }

    return ['ok' => true, 'message' => 'Added to cart'];
}

/**
 * @return array{ok:bool,message:string}
 */
function bb_cart_set_qty(int $bookId, int $qty, ?string $coverType = null): array
{
    if ($qty < 1) {
        return bb_cart_remove($bookId, $coverType);
    }
    $book = bb_fetch_book_cart_data($bookId);
    if ($book === null) {
        return ['ok' => false, 'message' => 'This book is not available.'];
    }
    $coverType = $coverType !== null ? trim($coverType) : null;
    if ($coverType === '') {
        $coverType = null;
    }
    if (!bb_book_validate_cover_type($book, $coverType)) {
        return ['ok' => false, 'message' => 'Please select a valid cover type.'];
    }
    $stock = bb_book_stock($bookId);
    if ($qty > $stock) {
        $qty = $stock;
    }
    $cart = bb_cart_get_raw();
    $key = bb_cart_encode_key($bookId, $coverType);
    $cart[$key] = $qty;
    bb_cart_set_raw($cart);
    $u = bb_current_user();
    if ($u !== null) {
        bb_cart_sync_to_database($u['id']);
    }

    return ['ok' => true, 'message' => 'Cart updated'];
}

/**
 * @return array{ok:bool,message:string}
 */
function bb_cart_remove(int $bookId, ?string $coverType = null): array
{
    $cart = bb_cart_get_raw();
    $key = bb_cart_encode_key($bookId, $coverType);
    unset($cart[$key]);
    bb_cart_set_raw($cart);
    $u = bb_current_user();
    if ($u !== null) {
        bb_cart_sync_to_database($u['id']);
    }

    return ['ok' => true, 'message' => 'Removed'];
}

/**
 * @return list<array<string,mixed>>
 */
function bb_cart_lines_with_books(): array
{
    $cart = bb_cart_get_raw();
    if ($cart === []) {
        return [];
    }
    $ids = [];
    foreach (array_keys($cart) as $key) {
        [$bookId, ] = bb_cart_decode_key((string) $key);
        if ($bookId > 0) {
            $ids[$bookId] = true;
        }
    }
    $ids = array_keys($ids);
    if ($ids === []) {
        return [];
    }
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT b.*, c.slug AS cat_slug, c.name AS cat_name
            FROM books b
            INNER JOIN categories c ON c.id = b.category_id
            WHERE b.id IN ($in) AND b.is_active = 1";
    $st = db()->prepare($sql);
    $st->execute($ids);
    $rows = $st->fetchAll();
    $out = [];
    $booksById = [];
    foreach ($rows as $row) {
        $booksById[(int) $row['id']] = $row;
    }
    foreach ($cart as $key => $qty) {
        [$bookId, $coverType] = bb_cart_decode_key((string) $key);
        if ($bookId < 1 || !isset($booksById[$bookId])) {
            continue;
        }
        $book = $booksById[$bookId];
        if (!bb_book_validate_cover_type($book, $coverType)) {
            continue;
        }
        $out[] = [
            'key'        => (string) $key,
            'book'       => $book,
            'cover_type' => $coverType,
            'qty'        => $qty,
        ];
    }

    return $out;
}

function bb_cart_subtotal(array $lines): float
{
    $sum = 0.0;
    foreach ($lines as $line) {
        $b   = $line['book'];
        $qty = (int) $line['qty'];
        $coverType = $line['cover_type'] ?? null;
        $price = bb_book_cover_price($b, is_string($coverType) ? $coverType : null);
        $sum += $price * $qty;
    }

    return round($sum, 2);
}
