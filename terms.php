<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Terms of Use — Books, Bits & Co';
require __DIR__ . '/includes/header.php';

$email = 'hello@booksbitsandco.com';
$phoneDisplay = bb_store_phone_display() !== '' ? bb_store_phone_display() : '+234 906 003 1555';
$phoneTel = bb_store_phone_tel() !== '' ? bb_store_phone_tel() : '+2349060031555';?>

        <nav class="border-b border-slate-100 bg-white" aria-label="Breadcrumb">
            <div class="mx-auto flex max-w-3xl items-center gap-2 px-4 py-3 text-sm text-slate-600 sm:px-6">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-brand">Home</a>
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="font-medium text-slate-900">Terms of Use</span>
            </div>
        </nav>

        <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
            <header class="border-b border-slate-200 pb-8">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand">Legal</p>
                <h1 class="mt-2 font-serif text-3xl font-black text-slate-900 sm:text-4xl">Terms of Use</h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    Please read these terms carefully before using Books, Bits &amp; Co. They apply together with our
                    <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/privacy.php', ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline">Privacy Policy</a>.
                </p>
            </header>

            <div class="mt-10 space-y-10 text-slate-700">
                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">1. Acceptance of Terms</h2>
                    <p class="mt-3 leading-relaxed">
                        By browsing our website or placing an order, you confirm that you are at least 18 years old (or have parental consent), and that you agree to these Terms of Use and our
                        <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/privacy.php', ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline">Privacy Policy</a>.
                    </p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">2. About Us</h2>
                    <p class="mt-3 leading-relaxed">
                        Books, Bits &amp; Co. is a Nigerian online bookstore dedicated to making quality books accessible and rebuilding the culture of reading. We sell physical books and curated bundles. We are not a publisher and do not claim ownership of the books we sell.
                    </p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">3. Products &amp; Pricing</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.1 Product Descriptions</h3>
                    <p class="mt-3 leading-relaxed">We strive to describe all books accurately. Images are illustrative; cover designs may vary by edition. Hardcover and softcover pricing is listed separately where both formats are available.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.2 Pricing</h3>
                    <p class="mt-3 leading-relaxed">All prices are in Nigerian Naira (₦) and may change without prior notice. Delivery fees are shown separately at checkout. The price at checkout is final.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.3 Availability</h3>
                    <p class="mt-3 leading-relaxed">If a book you ordered is out of stock post-payment, we will contact you immediately to offer a refund or alternative.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">4. Orders &amp; Payments</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.1 Placing an Order</h3>
                    <p class="mt-3 leading-relaxed">Completing checkout constitutes a binding offer to purchase. We reserve the right to cancel any order, in which case a full refund will be issued.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.2 Payment</h3>
                    <p class="mt-3 leading-relaxed">Payments are processed securely by Paystack. We accept debit/credit cards, bank transfers, and USSD. We do not store card details.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.3 Order Confirmation</h3>
                    <p class="mt-3 leading-relaxed">Your order is confirmed once payment is successfully processed. A confirmation is sent via WhatsApp and/or email.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">5. Intellectual Property</h2>
                    <p class="mt-3 leading-relaxed">All website content — including the Books, Bits &amp; Co. name, logo, blog articles, and design — belongs to Books, Bits &amp; Co. and may not be reproduced or distributed without written permission.</p>
                    <p class="mt-3 leading-relaxed">Books sold are the intellectual property of their authors and publishers. Purchasing a book grants personal use only; it does not permit reproduction, resale, or distribution.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">6. User Conduct</h2>
                    <p class="mt-3 leading-relaxed">When using our website, you agree not to:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>Provide false information during checkout</li>
                        <li>Use the website for unlawful or fraudulent purposes</li>
                        <li>Attempt to access restricted areas of the website</li>
                        <li>Resell our books commercially without written authorisation</li>
                    </ul>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">7. Book Club &amp; Newsletter</h2>
                    <p class="mt-3 leading-relaxed">Participation in our Book Club and newsletter is voluntary. By subscribing, you agree to receive emails about new arrivals, promotions, and reading content. You may unsubscribe at any time by replying STOP or using the unsubscribe link in any email.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">8. Disclaimer</h2>
                    <p class="mt-3 leading-relaxed">Books, Bits &amp; Co. provides this website and services &ldquo;as is&rdquo; without warranties of any kind. We do not guarantee uninterrupted access to the site or that product information is always error-free.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">9. Limitation of Liability</h2>
                    <p class="mt-3 leading-relaxed">To the extent permitted by Nigerian law, Books, Bits &amp; Co. shall not be liable for indirect, incidental, or consequential damages arising from the use of our website or products.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">10. Governing Law</h2>
                    <p class="mt-3 leading-relaxed">These terms are governed by the laws of the Federal Republic of Nigeria.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">11. Changes to These Terms</h2>
                    <p class="mt-3 leading-relaxed">We may update these terms at any time. Changes take effect upon posting. Continued use of the site constitutes acceptance.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">12. Contact</h2>
                    <ul class="mt-3 space-y-2 leading-relaxed">
                        <li>
                            <span class="font-semibold text-slate-900">Email:</span>
                            <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="text-brand hover:underline"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                        <li>
                            <span class="font-semibold text-slate-900">WhatsApp:</span>
                            <a href="tel:<?= htmlspecialchars($phoneTel, ENT_QUOTES, 'UTF-8') ?>" class="text-brand hover:underline"><?= htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                    </ul>
                </section>
            </div>
        </article>

<?php require __DIR__ . '/includes/footer.php'; ?>
