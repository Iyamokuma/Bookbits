<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/books_db.php';
bb_require_admin();
require_once __DIR__ . '/_layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$edit = $id > 0 ? db()->prepare('SELECT * FROM books WHERE id = ?') : null;
if ($edit) {
    $edit->execute([$id]);
    $book = $edit->fetch();
    if ($book === false) {
        http_response_code(404);
        exit('Book not found');
    }
} else {
    $book = null;
}

$coverOptionsDbReady = bb_table_has_column('books', 'has_cover_options')
    && bb_table_has_column('books', 'paperback_price')
    && bb_table_has_column('books', 'hardcover_price');
$newArrivalDbReady = bb_table_has_column('books', 'is_new_arrival');
$bookBundleDbReady = bb_table_has_column('books', 'is_book_bundle');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userWantsCoverOptions = isset($_POST['has_cover_options']);
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title      = trim((string) ($_POST['title'] ?? ''));
    $author     = trim((string) ($_POST['author'] ?? ''));
    $isbn       = trim((string) ($_POST['isbn'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $price      = (float) str_replace(',', '', (string) ($_POST['price'] ?? '0'));
    $saleRaw    = trim((string) ($_POST['sale_price'] ?? ''));
    $salePrice  = $saleRaw === '' ? null : (float) str_replace(',', '', $saleRaw);
    $stock      = (int) ($_POST['stock_qty'] ?? 0);
    $cover      = trim((string) ($_POST['cover_image'] ?? ''));
    $publisher  = trim((string) ($_POST['publisher'] ?? ''));
    $hasCoverOptions = $coverOptionsDbReady && $userWantsCoverOptions ? 1 : 0;
    $paperbackRaw = trim((string) ($_POST['paperback_price'] ?? ''));
    $hardcoverRaw = trim((string) ($_POST['hardcover_price'] ?? ''));
    $paperbackPrice = $paperbackRaw === '' ? null : (float) str_replace(',', '', $paperbackRaw);
    $hardcoverPrice = $hardcoverRaw === '' ? null : (float) str_replace(',', '', $hardcoverRaw);
    $pages      = ($_POST['pages'] ?? '') === '' ? null : (int) $_POST['pages'];
    $language   = trim((string) ($_POST['language'] ?? 'English'));
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isDeal     = isset($_POST['is_deal']) ? 1 : 0;
    $isNewArrival = $newArrivalDbReady && isset($_POST['is_new_arrival']) ? 1 : 0;
    $isBookBundle = $bookBundleDbReady && isset($_POST['is_book_bundle']) ? 1 : 0;
    $isActive   = isset($_POST['is_active']) ? 1 : 0;

    $upload  = bb_book_cover_upload();
    $saveErr = null;
    $isbnDb  = null;

    if (!$upload['ok']) {
        $saveErr = $upload['message'];
    } elseif ($userWantsCoverOptions && !$coverOptionsDbReady) {
        $saveErr = 'Cover options need new database columns. In phpMyAdmin, select your Bookbits database and run the upgrade statements at the bottom of database/schema.sql (section “UPGRADE — existing database only” — remove the leading comment dashes from each SQL line, or run them one at a time). Then save this product again.';
    } elseif ($categoryId < 1 || $title === '' || $author === '' || $price <= 0) {
        $saveErr = 'Please fill category, title, author, and a valid price.';
    } elseif ($hasCoverOptions && ($paperbackPrice === null || $hardcoverPrice === null)) {
        $saveErr = 'When cover options are enabled, enter both paperback and hardcover prices.';
    } elseif ($hasCoverOptions && (($paperbackPrice ?? 0) <= 0 || ($hardcoverPrice ?? 0) <= 0)) {
        $saveErr = 'Paperback and hardcover prices must be greater than zero.';
    } elseif ($hasCoverOptions && (float) $paperbackPrice === (float) $hardcoverPrice) {
        $saveErr = 'Paperback and hardcover prices must be different.';
    } elseif ($salePrice !== null && $salePrice < 0) {
        $saveErr = 'Sale price cannot be negative.';
    } elseif ($salePrice !== null && $salePrice > $price) {
        $saveErr = 'Sale price cannot be greater than regular price.';
    } elseif ($pages !== null && $pages < 0) {
        $saveErr = 'Pages cannot be negative.';
    } else {
        $isbnDb = $isbn === '' ? null : $isbn;
        $catOk = db()->prepare('SELECT 1 FROM categories WHERE id = ? AND is_active = 1 LIMIT 1');
        $catOk->execute([$categoryId]);
        if ($catOk->fetch() === false) {
            $saveErr = 'Please choose a valid category.';
        } elseif ($isbnDb !== null) {
            $chk = db()->prepare('SELECT id FROM books WHERE isbn = ? AND id <> ? LIMIT 1');
            $chk->execute([$isbnDb, $id]);
            if ($chk->fetch() !== false) {
                $saveErr = 'That ISBN is already in use by another product.';
            }
        }
    }

    if ($saveErr === null) {
        $descDb = $description === '' ? null : $description;
        $pubDb = $publisher === '' ? null : $publisher;
        $paperDb = $hasCoverOptions ? (float) $paperbackPrice : null;
        $hardDb  = $hasCoverOptions ? (float) $hardcoverPrice : null;
        if ($hasCoverOptions) {
            // Keep legacy `price` populated for compatibility with existing pages.
            $price = (float) $paperDb;
            $salePrice = null;
        }

        $coverDb = null;
        if ($upload['path'] !== null) {
            if ($book !== null) {
                bb_delete_stored_cover_file(isset($book['cover_image']) ? (string) $book['cover_image'] : null);
            }
            $coverDb = $upload['path'];
        } elseif ($cover !== '') {
            $coverDb = $cover;
        } elseif ($book !== null && isset($book['cover_image']) && (string) $book['cover_image'] !== '') {
            $coverDb = (string) $book['cover_image'];
        }

        try {
            if ($book !== null) {
                if ($coverOptionsDbReady) {
                    if ($newArrivalDbReady && $bookBundleDbReady) {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, has_cover_options=?, paperback_price=?, hardcover_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_new_arrival=?, is_book_bundle=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isNewArrival, $isBookBundle, $isActive, $id,
                        ]);
                    } elseif ($newArrivalDbReady) {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, has_cover_options=?, paperback_price=?, hardcover_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_new_arrival=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isNewArrival, $isActive, $id,
                        ]);
                    } elseif ($bookBundleDbReady) {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, has_cover_options=?, paperback_price=?, hardcover_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_book_bundle=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isBookBundle, $isActive, $id,
                        ]);
                    } else {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, has_cover_options=?, paperback_price=?, hardcover_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isActive, $id,
                        ]);
                    }
                } else {
                    if ($newArrivalDbReady && $bookBundleDbReady) {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_new_arrival=?, is_book_bundle=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isNewArrival, $isBookBundle, $isActive, $id,
                        ]);
                    } elseif ($newArrivalDbReady) {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_new_arrival=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isNewArrival, $isActive, $id,
                        ]);
                    } elseif ($bookBundleDbReady) {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_book_bundle=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isBookBundle, $isActive, $id,
                        ]);
                    } else {
                        $up = db()->prepare(
                            'UPDATE books SET category_id=?, title=?, author=?, isbn=?, description=?, price=?, sale_price=?, stock_qty=?, cover_image=?, pages=?, publisher=?, language=?, is_featured=?, is_deal=?, is_active=? WHERE id=?'
                        );
                        $up->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $pubDb, $language, $isFeatured, $isDeal, $isActive, $id,
                        ]);
                    }
                }
                $_SESSION['admin_flash'] = 'Product updated.';
            } else {
                if ($coverOptionsDbReady) {
                    if ($newArrivalDbReady && $bookBundleDbReady) {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, has_cover_options, paperback_price, hardcover_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_new_arrival, is_book_bundle, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isNewArrival, $isBookBundle, $isActive,
                        ]);
                    } elseif ($newArrivalDbReady) {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, has_cover_options, paperback_price, hardcover_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_new_arrival, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isNewArrival, $isActive,
                        ]);
                    } elseif ($bookBundleDbReady) {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, has_cover_options, paperback_price, hardcover_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_book_bundle, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isBookBundle, $isActive,
                        ]);
                    } else {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, has_cover_options, paperback_price, hardcover_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $hasCoverOptions, $paperDb, $hardDb, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isActive,
                        ]);
                    }
                } else {
                    if ($newArrivalDbReady && $bookBundleDbReady) {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_new_arrival, is_book_bundle, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isNewArrival, $isBookBundle, $isActive,
                        ]);
                    } elseif ($newArrivalDbReady) {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_new_arrival, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isNewArrival, $isActive,
                        ]);
                    } elseif ($bookBundleDbReady) {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_book_bundle, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isBookBundle, $isActive,
                        ]);
                    } else {
                        $ins = db()->prepare(
                            'INSERT INTO books (category_id, title, author, isbn, description, price, sale_price, stock_qty, cover_image, pages, language, is_featured, is_deal, is_active)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $ins->execute([
                            $categoryId, $title, $author, $isbnDb, $descDb, $price, $salePrice, $stock, $coverDb,
                            $pages, $language, $isFeatured, $isDeal, $isActive,
                        ]);
                    }
                }
                $_SESSION['admin_flash'] = 'Product created.';
            }
        } catch (Throwable $e) {
            if ($upload['path'] !== null) {
                bb_delete_stored_cover_file($upload['path']);
            }
            $msg = $e->getMessage();
            if (strpos($msg, '1062') !== false || stripos($msg, 'Duplicate') !== false) {
                $saveErr = 'That ISBN is already in use by another product.';
            } elseif (strpos($msg, '1452') !== false || stripos($msg, 'foreign key') !== false) {
                $saveErr = 'Invalid category — refresh the page and try again.';
            } else {
                // Include DB error detail in admin UI for faster troubleshooting on local/dev.
                $saveErr = 'Could not save product: ' . $msg;
            }
        }

        if ($saveErr === null) {
            header('Location: ' . BOOKBITS_BASE . '/admin/products.php');
            exit;
        }
    }

    if ($saveErr !== null) {
        $_SESSION['admin_flash']       = $saveErr;
        $_SESSION['admin_flash_error'] = true;
    }
}

$cats = db()->query('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

$pageTitle = $book ? 'Edit product' : 'Add product';
bb_admin_header($book ? 'Admin — Edit product' : 'Admin — Add product', 'products');

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$flashIsError = !empty($_SESSION['admin_flash_error']);
unset($_SESSION['admin_flash_error']);
?>

        <?php if ($flash !== '') : ?>
            <div class="mb-6 rounded-xl border px-4 py-3 text-sm <?= $flashIsError ? 'border-red-200 bg-red-50 text-red-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?>"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!$coverOptionsDbReady) : ?>
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <strong>Cover options are not active in the database yet.</strong>
                Open phpMyAdmin, select your Bookbits database, go to the SQL tab, and run the file
                <code class="rounded bg-white px-1 py-0.5 text-xs">database/schema.sql</code>
                (section <strong>UPGRADE — existing database only</strong> at the end — uncomment those SQL lines in phpMyAdmin and run them). After that, saving a product with “Enable cover options” will show paperback/hardcover on the shop and product pages.
            </div>
        <?php endif; ?>
        <?php if (!$newArrivalDbReady || !$bookBundleDbReady) : ?>
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <strong>Homepage flags are not fully active in the database yet.</strong>
                Run the latest statements at the end of <code class="rounded bg-white px-1 py-0.5 text-xs">database/schema.sql</code> to add
                <code class="rounded bg-white px-1 py-0.5 text-xs">is_new_arrival</code> and
                <code class="rounded bg-white px-1 py-0.5 text-xs">is_book_bundle</code>.
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-slate-900"><?= $book ? 'Edit product' : 'Add product' ?></h1>
            <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/admin/products.php', ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-slate-600 hover:text-brand">← Back to list</a>
        </div>

        <form method="post" enctype="multipart/form-data" class="mt-8 max-w-3xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Category *</label>
                    <select name="category_id" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <?php foreach ($cats as $c) :
                            $cid = (int) $c['id'];
                            $sel = $book && (int) $book['category_id'] === $cid ? ' selected' : '';
                        ?>
                            <option value="<?= $cid ?>"<?= $sel ?>><?= htmlspecialchars((string) $c['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Title *</label>
                    <input type="text" name="title" required value="<?= $book ? htmlspecialchars((string) $book['title'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Author *</label>
                    <input type="text" name="author" required value="<?= $book ? htmlspecialchars((string) $book['author'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Price (<?= htmlspecialchars(BOOKBITS_CURRENCY_CODE, ENT_QUOTES, 'UTF-8') ?>) *</label>
                    <input type="number" step="0.01" min="0" name="price" required value="<?= $book ? htmlspecialchars((string) $book['price'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Sale price (optional)</label>
                    <input type="number" step="0.01" min="0" name="sale_price" value="<?= $book && $book['sale_price'] !== null ? htmlspecialchars((string) $book['sale_price'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Leave empty if not on sale">
                </div>
                <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input type="checkbox" name="has_cover_options" value="1" <?= $book && (int) ($book['has_cover_options'] ?? 0) === 1 ? ' checked' : '' ?>>
                        Enable cover options (Soft Paperback + Hardcover)
                    </label>
                    <p class="mt-1 text-xs text-slate-500">If enabled, customer must choose cover type before adding to cart.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-700">Soft paperback price</label>
                            <input type="number" step="0.01" min="0" name="paperback_price" value="<?= $book && $book['paperback_price'] !== null ? htmlspecialchars((string) $book['paperback_price'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="e.g. 9500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700">Hardcover price</label>
                            <input type="number" step="0.01" min="0" name="hardcover_price" value="<?= $book && $book['hardcover_price'] !== null ? htmlspecialchars((string) $book['hardcover_price'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="e.g. 14500">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Stock quantity *</label>
                    <input type="number" min="0" name="stock_qty" required value="<?= $book ? (int) $book['stock_qty'] : '0' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">ISBN <span class="font-normal text-slate-500">(optional)</span></label>
                    <input type="text" name="isbn" value="<?= $book && $book['isbn'] ? htmlspecialchars((string) $book['isbn'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="Leave blank if unknown" autocomplete="off">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Cover image</label>
                    <p class="text-xs text-slate-500">Upload a file (JPEG, PNG, WebP, or GIF, max 5MB). A new upload replaces the current cover.</p>
                    <input type="file" name="cover_upload" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-dark">
                    <?php if ($book && !empty($book['cover_image'])) : ?>
                        <p class="mt-2 text-xs text-slate-600">Current: <code class="rounded bg-slate-100 px-1"><?= htmlspecialchars((string) $book['cover_image'], ENT_QUOTES, 'UTF-8') ?></code></p>
                    <?php endif; ?>
                    <p class="mt-3 text-xs font-medium text-slate-600">Or use an external URL / legacy filename (optional)</p>
                    <input type="text" name="cover_image" value="<?= $book && $book['cover_image'] && !str_starts_with((string) $book['cover_image'], 'uploads/') ? htmlspecialchars((string) $book['cover_image'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" placeholder="https://… or filename in /assets/img/covers/">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"><?= $book && $book['description'] ? htmlspecialchars((string) $book['description'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Pages</label>
                    <input type="number" name="pages" min="0" value="<?= $book && $book['pages'] !== null ? (int) $book['pages'] : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Publisher</label>
                    <input type="text" name="publisher" value="<?= $book && $book['publisher'] !== null ? htmlspecialchars((string) $book['publisher'], ENT_QUOTES, 'UTF-8') : '' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Language</label>
                    <input type="text" name="language" value="<?= $book ? htmlspecialchars((string) $book['language'], ENT_QUOTES, 'UTF-8') : 'English' ?>" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="flex flex-wrap gap-4 sm:col-span-2">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" <?= $book && (int) $book['is_featured'] ? ' checked' : '' ?>> Featured</label>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_deal" value="1" <?= $book && (int) $book['is_deal'] ? ' checked' : '' ?>> Daily deal</label>
                    <?php if ($newArrivalDbReady) : ?>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_new_arrival" value="1" <?= $book && (int) ($book['is_new_arrival'] ?? 0) ? ' checked' : '' ?>> New arrival</label>
                    <?php endif; ?>
                    <?php if ($bookBundleDbReady) : ?>
                        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_book_bundle" value="1" <?= $book && (int) ($book['is_book_bundle'] ?? 0) ? ' checked' : '' ?>> Book bundle</label>
                    <?php endif; ?>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?= !$book || (int) $book['is_active'] ? ' checked' : '' ?>> Active (visible in shop)</label>
                </div>
            </div>
            <button type="submit" class="rounded-xl bg-brand px-6 py-3 text-sm font-bold text-white shadow hover:bg-brand-dark"><?= $book ? 'Save changes' : 'Create product' ?></button>
        </form>

<?php bb_admin_footer(); ?>
