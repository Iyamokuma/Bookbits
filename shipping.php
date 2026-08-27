<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Shipping & Returns — Books, Bits & Co';
require __DIR__ . '/includes/header.php';

$email = 'hello@booksbitsandco.com';
$phoneDisplay = bb_store_phone_display() !== '' ? bb_store_phone_display() : '+234 906 003 1555';
$phoneTel = bb_store_phone_tel() !== '' ? bb_store_phone_tel() : '+2349060031555';$igUrl = defined('BOOKBITS_INSTAGRAM_URL') ? trim((string) BOOKBITS_INSTAGRAM_URL) : 'https://www.instagram.com/booksbitsandco';
?>

        <nav class="border-b border-slate-100 bg-white" aria-label="Breadcrumb">
            <div class="mx-auto flex max-w-3xl items-center gap-2 px-4 py-3 text-sm text-slate-600 sm:px-6">
                <a href="<?= htmlspecialchars(BOOKBITS_BASE . '/index.php', ENT_QUOTES, 'UTF-8') ?>" class="hover:text-brand">Home</a>
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="font-medium text-slate-900">Shipping &amp; Returns</span>
            </div>
        </nav>

        <article class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
            <header class="border-b border-slate-200 pb-8">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand">Help</p>
                <h1 class="mt-2 font-serif text-3xl font-black text-slate-900 sm:text-4xl">Shipping &amp; Returns</h1>
                <p class="mt-4 text-base leading-relaxed text-slate-600">
                    At Books, Bits &amp; Co., we are committed to getting your books to you safely, quickly, and in perfect condition. This policy explains how we ship orders, what to expect on delivery, and how to return items if needed.
                </p>
            </header>

            <div class="mt-10 space-y-10 text-slate-700">
                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">1. Overview</h2>
                    <p class="mt-3 leading-relaxed">
                        At Books, Bits &amp; Co., we are committed to getting your books to you safely, quickly, and in perfect condition. This policy explains how we ship orders, what to expect on delivery, and how to return items if needed.
                    </p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">2. Order Processing</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">2.1 Processing Time</h3>
                    <p class="mt-3 leading-relaxed">All orders are processed within 1–3 business days after payment is confirmed. Orders placed on weekends or public holidays will be processed the next business day.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">2.2 Order Confirmation</h3>
                    <p class="mt-3 leading-relaxed">You will receive an order confirmation via WhatsApp and/or email once payment is successful. Please keep this for your records.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">2.3 Pre-Orders &amp; Out-of-Stock Items</h3>
                    <p class="mt-3 leading-relaxed">If a book is temporarily out of stock after you order, we will notify you of the expected delivery date. You may choose to wait or receive a full refund.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">3. Shipping</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.1 Delivery Areas</h3>
                    <p class="mt-3 leading-relaxed">We deliver across Nigeria. Estimated delivery timelines:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li><strong>Lagos (Mainland &amp; Island):</strong> 1–3 business days</li>
                        <li><strong>Abuja, Port Harcourt, Ibadan, Kano, Enugu:</strong> 3–5 business days</li>
                        <li><strong>Other states and remote areas:</strong> 5–10 business days</li>
                    </ul>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.2 Shipping Fees</h3>
                    <p class="mt-3 leading-relaxed">Shipping fees are calculated at checkout based on your location and order weight.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.3 Packaging</h3>
                    <p class="mt-3 leading-relaxed">All books are wrapped protectively to prevent damage in transit — at no extra charge.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.4 Order Tracking</h3>
                    <p class="mt-3 leading-relaxed">A tracking number will be sent to you via WhatsApp or email once your order is dispatched.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">3.5 Failed Delivery Attempts</h3>
                    <p class="mt-3 leading-relaxed">Our courier will attempt delivery twice. If both attempts fail, the order is returned to us and you will be contacted. Redelivery may incur additional shipping charges.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">4. Returns Policy</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.1 Return Window</h3>
                    <p class="mt-3 leading-relaxed">You may return a book within 7 calendar days of receiving your order, provided the item meets our return conditions.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.2 Return Conditions</h3>
                    <p class="mt-3 leading-relaxed">To be eligible for a return, the book must:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>Be in its original, unread, undamaged condition — no writing, torn pages, broken spine, or visible wear</li>
                        <li>Be in its original or equivalent protective packaging</li>
                        <li>Be accompanied by proof of purchase (order number or receipt)</li>
                    </ul>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.3 Non-Returnable Items</h3>
                    <p class="mt-3 leading-relaxed">The following cannot be returned or refunded:</p>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li>Books that have been read, written in, or damaged after delivery</li>
                        <li>Pre-loved (second-hand) books — all sales final</li>
                        <li>E-books or digital products</li>
                        <li>Complete bundles where individual books have been separated or used</li>
                        <li>Items returned after the 7-day window</li>
                    </ul>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.4 How to Return an Item</h3>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 leading-relaxed">
                        <li>
                            Contact us within 7 days of delivery:
                            WhatsApp <a href="tel:<?= htmlspecialchars($phoneTel, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline"><?= htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8') ?></a>
                            or email <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="font-medium text-brand hover:underline"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></a>
                        </li>
                        <li>Provide your order number and reason for return</li>
                        <li>Send photos showing the condition of the book</li>
                        <li>Our team will review within 24–48 hours and respond with next steps</li>
                        <li>If approved, we will provide return shipping instructions</li>
                    </ol>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">4.5 Return Shipping Costs</h3>
                    <p class="mt-3 leading-relaxed">Customers bear return shipping costs unless the return is due to our error (wrong item sent, damaged in transit). We recommend using a tracked courier service.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">5. Refunds</h2>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">5.1 Refund Timeline</h3>
                    <p class="mt-3 leading-relaxed">Once we receive and inspect the returned item, your refund will be processed within 7 business days of approval.</p>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">5.2 Refund Method</h3>
                    <ul class="mt-3 list-disc space-y-2 pl-5 leading-relaxed">
                        <li><strong>Card payments:</strong> Refunded to the original card via Paystack</li>
                        <li><strong>Bank transfers:</strong> Refunded to your provided bank account</li>
                    </ul>
                    <h3 class="mt-5 text-base font-semibold text-slate-900">5.3 Defective or Wrong Items</h3>
                    <p class="mt-3 leading-relaxed">If you receive a damaged or incorrect book, please contact us within 48 hours of delivery with photos. We will arrange a free replacement or full refund.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">6. Exchanges</h2>
                    <p class="mt-3 leading-relaxed">We do not offer direct exchanges at this time. Please initiate a return and place a new order for the desired item.</p>
                </section>

                <section>
                    <h2 class="font-serif text-xl font-bold text-slate-900">7. Contact</h2>
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
                            <span class="font-semibold text-slate-900">Instagram:</span>
                            <?php if ($igUrl !== '') : ?>
                                <a href="<?= htmlspecialchars($igUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-brand hover:underline">@booksbitsandco</a>
                            <?php else : ?>
                                @booksbitsandco
                            <?php endif; ?>
                        </li>
                    </ul>
                    <p class="mt-4 leading-relaxed text-slate-600">Customer service is available Monday–Saturday, 9:00 AM – 6:00 PM (WAT).</p>
                </section>
            </div>
        </article>

<?php require __DIR__ . '/includes/footer.php'; ?>
