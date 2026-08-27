    </main>

    <?php
    require_once __DIR__ . '/icons.php';
    $bbFooterBase = BOOKBITS_BASE;
    $bbWaHref = bb_whatsapp_chat_url();
    $bbFbFooter = defined('BOOKBITS_FACEBOOK_URL') ? trim((string) BOOKBITS_FACEBOOK_URL) : '';
    $bbIgFooter = defined('BOOKBITS_INSTAGRAM_URL') ? trim((string) BOOKBITS_INSTAGRAM_URL) : '';
    ?>

    <footer class="border-t border-slate-200 bg-slate-900 text-slate-300">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-14">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4 lg:gap-12">
                <div class="sm:col-span-2 lg:col-span-1">
                    <p class="font-serif text-lg font-bold tracking-tight text-white">Bookbits</p>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-400">Curated books delivered with care. Discover fiction, non-fiction, children’s reads, and more.</p>
                    <?php if ($bbFbFooter !== '' || $bbIgFooter !== '') : ?>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <?php if ($bbFbFooter !== '') : ?>
                                <a href="<?= htmlspecialchars($bbFbFooter, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white" aria-label="Facebook">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
                                    Facebook
                                </a>
                            <?php endif; ?>
                            <?php if ($bbIgFooter !== '') : ?>
                                <a href="<?= htmlspecialchars($bbIgFooter, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium text-slate-300 transition hover:text-white" aria-label="Instagram">
                                    <?= bb_svg_instagram('h-5 w-5') ?>
                                    Instagram
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($bbWaHref !== '') : ?>
                        <a href="<?= htmlspecialchars($bbWaHref, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-emerald-400 transition hover:text-emerald-300">
                            <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            Message us on WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Shop</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">All books</a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/stationery.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Stationery</a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/blog.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Blog</a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/cart.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Cart</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Account</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/login.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Sign in</a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/register.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Help</h2>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li><a href="tel:<?= htmlspecialchars(bb_store_phone_tel(), ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white"><?= htmlspecialchars(bb_store_phone_display(), ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/shipping.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Shipping &amp; returns</a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/privacy.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Privacy policy</a></li>
                        <li><a href="<?= htmlspecialchars($bbFooterBase . '/terms.php', ENT_QUOTES, 'UTF-8') ?>" class="text-slate-300 transition hover:text-white">Terms of use</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800 pt-8 sm:flex-row">
                <p class="text-center text-sm text-slate-500 sm:text-left">&copy; <?= date('Y') ?> Bookbits. All rights reserved.</p>
                <nav class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-500" aria-label="Footer shortcuts">
                    <a href="<?= htmlspecialchars($bbFooterBase . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="transition hover:text-slate-300">Home</a>
                    <a href="<?= htmlspecialchars($bbFooterBase . '/shop.php', ENT_QUOTES, 'UTF-8') ?>" class="transition hover:text-slate-300">Shop</a>
                    <a href="<?= htmlspecialchars($bbFooterBase . '/blog.php', ENT_QUOTES, 'UTF-8') ?>" class="transition hover:text-slate-300">Blog</a>
                </nav>
            </div>
        </div>
    </footer>

    <?php if ($bbWaHref !== '') : ?>
        <a href="<?= htmlspecialchars($bbWaHref, ENT_QUOTES, 'UTF-8') ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="fixed bottom-5 right-5 z-[60] flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-emerald-900/30 ring-2 ring-white transition hover:scale-105 hover:bg-[#20BD5A] focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2"
           aria-label="Chat with us on WhatsApp">
            <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>
    <?php endif; ?>

    <script>
    (function () {
        var wrap = document.getElementById('bb-profile-wrap');
        var btn = document.getElementById('bb-profile-trigger');
        var panel = document.getElementById('bb-profile-panel');
        if (!wrap || !btn || !panel) return;

        function isOpen() {
            return !panel.classList.contains('hidden');
        }
        function openMenu() {
            panel.classList.remove('hidden');
            btn.setAttribute('aria-expanded', 'true');
        }
        function closeMenu() {
            panel.classList.add('hidden');
            btn.setAttribute('aria-expanded', 'false');
        }
        function toggleMenu() {
            if (isOpen()) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });

        document.addEventListener('click', function (e) {
            var t = e.target;
            if (t && !wrap.contains(t)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        wrap.querySelectorAll('.bb-profile-cancel').forEach(function (el) {
            el.addEventListener('click', function () {
                closeMenu();
            });
        });
    })();
    </script>
</body>
</html>
