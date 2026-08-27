<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Privacy Policy — Books, Bits & Co';
require __DIR__ . '/includes/header.php';

$email = 'hello@booksbitsandco.com';
$phoneDisplay = bb_store_phone_display() !== '' ? bb_store_phone_display() : '+234 906 003 1555';
$phoneTel = bb_store_phone_tel() !== '' ? bb_store_phone_tel() : '+2349060031555';
$siteUrl = 'https://booksandbits.com.ng';
?>

        <nav class="border-b border-slate-100 bg-white" aria-label="Breadcrumb">
            <div class="mx-auto flex max-w-3xl items-center gap-2 px-4 py-3 text-sm text-slate-600 sm:px-6">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-brand">Home</a>
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="font-medium text-slate-900">Privacy Policy</span>
            </div>
        </nav>

        <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
            <header class="border-b border-slate-200 pb-8">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand">Legal</p>
                <h1 class="mt-2 font-serif text-3xl font-black text-slate-900 sm:text-4xl">Privacy Policy</h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    At Books, Bits &amp; Co., your privacy matters. This Privacy Policy explains what personal information we collect, how we use it, and how we protect it when you use our website (<a href="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline"><?= htmlspecialchars(parse_url($siteUrl, PHP_URL_HOST) ?: 'booksandbits.com.ng', ENT_QUOTES, 'UTF-8') ?></a>).
                </p>
            </header>

            <div class="prose-policy mt-10 space-y-10 text-slate-700">
                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">1. Who We Are</h2>
                    <p class="mt-3 leading-relaxed">
                        Books, Bits &amp; Co. is an online bookstore based in Nigeria, operating at booksandbits.com.ng. You can reach us at
                        <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                        or
                        <a href="tel:<?= htmlspecialchars($phoneTel, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline"><?= htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8') ?></a>.
                    </p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">2. Information We Collect</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">2.1 Information You Provide</h3>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>Full name and contact details (email, phone number)</li>
                        <li>Delivery address</li>
                        <li>Payment information (handled securely by Paystack — we never store card details)</li>
                        <li>Messages sent via our contact form or WhatsApp</li>
                        <li>Newsletter subscription details (name and email)</li>
                    </ul>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">2.2 Information Collected Automatically</h3>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>IP address and browser type</li>
                        <li>Pages visited and time on site</li>
                        <li>Device type (mobile, desktop, tablet)</li>
                    </ul>
                    <p class="mt-3 leading-relaxed">This data is collected via cookies and analytics tools (e.g., Google Analytics) in aggregated, non-identifying form.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">3. How We Use Your Information</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>To process and fulfil your orders</li>
                        <li>To send order confirmations and delivery updates</li>
                        <li>To respond to enquiries and provide customer support</li>
                        <li>To send newsletters and promotions (only if you subscribed)</li>
                        <li>To improve our website and product offerings</li>
                        <li>To comply with legal obligations</li>
                    </ul>
                    <p class="mt-3 leading-relaxed">We will never use your data for purposes beyond those listed without your explicit consent.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">4. How We Share Your Information</h2>
                    <p class="mt-3 leading-relaxed">We do not sell or rent your personal data. We share it only with:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li><strong>Paystack</strong> — to process payments</li>
                        <li><strong>Delivery/courier partners</strong> — name, phone, and address only</li>
                        <li><strong>Email platforms</strong> — to send newsletters and order updates</li>
                    </ul>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">5. Cookies</h2>
                    <p class="mt-3 leading-relaxed">We use cookies to remember your cart, understand site usage, and improve performance. You may disable cookies in your browser settings, though some features may be affected.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">6. Data Retention</h2>
                    <p class="mt-3 leading-relaxed">We retain personal data only as long as necessary. Order records are kept for a minimum of 5 years for legal and accounting purposes. Newsletter subscriber data is deleted within 30 days of unsubscription.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">7. Your Rights</h2>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>Access the personal information we hold about you</li>
                        <li>Request correction of inaccurate data</li>
                        <li>Request deletion of your data (subject to legal requirements)</li>
                        <li>Withdraw marketing consent at any time</li>
                    </ul>
                    <p class="mt-3 leading-relaxed">
                        To exercise your rights, contact us at
                        <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>.
                        We will respond within 7 business days.
                    </p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">8. Security</h2>
                    <p class="mt-3 leading-relaxed">We implement appropriate technical and organisational security measures to protect your data. All payment transactions are encrypted via Paystack. However, no internet transmission is 100% secure.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">9. Children&rsquo;s Privacy</h2>
                    <p class="mt-3 leading-relaxed">Our website is not directed at children under 13. We do not knowingly collect data from children. If you believe a child has shared their data with us, please contact us immediately.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">10. Third-Party Links</h2>
                    <p class="mt-3 leading-relaxed">Our website may link to third-party platforms (e.g., social media). We are not responsible for their privacy practices and encourage you to review their own policies.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">11. Changes to This Policy</h2>
                    <p class="mt-3 leading-relaxed">We may update this policy periodically. Changes will be posted with a new effective date. Continued use of our website constitutes acceptance of the updated policy.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">12. Contact Us</h2>
                    <ul class="mt-3 space-y-2 leading-relaxed">
                        <li>
                            <span class="font-semibold text-slate-900">Email:</span>
                            <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="text-brand hover:underline"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                        <li>
                            <span class="font-semibold text-slate-900">WhatsApp:</span>
                            <a href="tel:<?= htmlspecialchars($phoneTel, ENT_QUOTES, 'UTF-8') ?>" class="text-brand hover:underline"><?= htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                        <li>
                            <span class="font-semibold text-slate-900">Website:</span>
                            <a href="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-brand hover:underline" rel="noopener noreferrer"><?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                    </ul>
                </section>

                <p class="border-t border-slate-200 pt-8 font-serif text-base italic text-slate-600">
                    Books, Bits &amp; Co. — Committed to your privacy and your reading journey.
                </p>
            </div>
        </article>

<?php require __DIR__ . '/includes/footer.php'; ?>
