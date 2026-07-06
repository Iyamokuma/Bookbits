<?php

declare(strict_types=1);

/** @psalm-var string */
$bbEdBase = BOOKBITS_BASE;
$bbEdShop = htmlspecialchars($bbEdBase . '/shop.php', ENT_QUOTES, 'UTF-8');
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">

<section class="bb-ed-banner" aria-label="Featured — The Open Page">
    <div class="bb-ed-banner__frame">
        <!-- Base layers -->
        <div class="bb-ed-banner__bg" aria-hidden="true"></div>
        <div class="bb-ed-banner__wash bb-ed-banner__wash--warm" aria-hidden="true"></div>
        <div class="bb-ed-banner__wash bb-ed-banner__wash--cool" aria-hidden="true"></div>
        <div class="bb-ed-banner__grain" aria-hidden="true"></div>
        <div class="bb-ed-banner__vignette" aria-hidden="true"></div>

        <!-- Floating book silhouettes (right) -->
        <div class="bb-ed-books" aria-hidden="true">
            <span class="bb-ed-book bb-ed-book--1" style="animation-delay:0s"></span>
            <span class="bb-ed-book bb-ed-book--2" style="animation-delay:-2.5s"></span>
            <span class="bb-ed-book bb-ed-book--3" style="animation-delay:-5s"></span>
            <span class="bb-ed-book bb-ed-book--4" style="animation-delay:-7.5s"></span>
        </div>

        <!-- Dust motes container (filled by JS) -->
        <div class="bb-ed-particles" id="bb-ed-particles" aria-hidden="true"></div>

        <div class="bb-ed-banner__inner">
            <p class="bb-ed-masthead">The Open Page</p>

            <svg class="bb-ed-rule" viewBox="0 0 400 4" preserveAspectRatio="none" aria-hidden="true">
                <line class="bb-ed-rule__line" x1="0" y1="2" x2="400" y2="2" />
            </svg>

            <h2 class="bb-ed-headline">Every Story Begins Here</h2>

            <p class="bb-ed-sub">Discover curated books, rare finds &amp; timeless reads — delivered to your door.</p>

            <div class="bb-ed-row">
                <a class="bb-ed-cta" href="<?= $bbEdShop ?>">
                    <span class="bb-ed-cta__label">Browse the Collection</span>
                    <span class="bb-ed-cta__shine" aria-hidden="true"></span>
                </a>
                <span class="bb-ed-badge">Free delivery on orders over <?= htmlspecialchars(BOOKBITS_CURRENCY_SYMBOL, ENT_QUOTES, 'UTF-8') ?>10,000</span>
            </div>
        </div>
    </div>
</section>

<style>
/* ─── Editorial banner — scoped (dark academia) ─── */
.bb-ed-banner {
    --bb-ed-navy: #0c1018;
    --bb-ed-charcoal: #141a24;
    --bb-ed-ink: #1a222e;
    --bb-ed-cream: #f4ead8;
    --bb-ed-cream-dim: #d9cbb3;
    --bb-ed-amber: #c9a227;
    --bb-ed-gold: #d4af37;
    --bb-ed-gold-dim: #8a7029;
    font-family: "EB Garamond", "Times New Roman", serif;
    padding: 1.25rem 1rem 1.5rem;
    max-width: 100%;
}

.bb-ed-banner__frame {
    position: relative;
    width: 100%;
    max-width: 1200px;
    height: 400px;
    margin: 0 auto;
    overflow: hidden;
    border: 1px solid rgba(212, 175, 55, 0.22);
    box-shadow:
        0 1px 0 rgba(255, 248, 235, 0.06) inset,
        0 24px 48px rgba(0, 0, 0, 0.35);
}

.bb-ed-banner__bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 120% 80% at 20% 40%, rgba(30, 38, 52, 0.9) 0%, transparent 55%),
        radial-gradient(ellipse 90% 70% at 85% 60%, rgba(20, 26, 36, 0.95) 0%, transparent 50%),
        linear-gradient(165deg, var(--bb-ed-navy) 0%, var(--bb-ed-charcoal) 45%, var(--bb-ed-ink) 100%);
    opacity: 0;
    animation: bb-ed-bg-in 1.1s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.bb-ed-banner__wash--warm {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 70% 50% at 15% 85%, rgba(201, 162, 39, 0.08) 0%, transparent 60%);
    pointer-events: none;
}

.bb-ed-banner__wash--cool {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 92% 12%, rgba(80, 100, 130, 0.12) 0%, transparent 35%);
    pointer-events: none;
}

.bb-ed-banner__grain {
    position: absolute;
    inset: 0;
    opacity: 0.055;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
    mix-blend-mode: overlay;
}

.bb-ed-banner__vignette {
    position: absolute;
    inset: 0;
    pointer-events: none;
    box-shadow: inset 0 0 120px rgba(0, 0, 0, 0.55);
}

.bb-ed-books {
    position: absolute;
    right: -2%;
    bottom: 0;
    width: 42%;
    height: 100%;
    pointer-events: none;
}

.bb-ed-book {
    position: absolute;
    display: block;
    border-radius: 2px 4px 4px 2px;
    background: linear-gradient(90deg, rgba(0, 0, 0, 0.5) 0%, rgba(40, 48, 62, 0.65) 8%, rgba(55, 65, 82, 0.5) 100%);
    box-shadow: -6px 8px 24px rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(212, 175, 55, 0.12);
    animation: bb-ed-float-book 14s ease-in-out infinite;
    transform: rotate(var(--r));
}

.bb-ed-book--1 { width: 56px; height: 168px; right: 18%; bottom: 12%; --r: 12deg; }
.bb-ed-book--2 { width: 48px; height: 198px; right: 28%; bottom: 8%; --r: -6deg; opacity: 0.85; }
.bb-ed-book--3 { width: 52px; height: 152px; right: 38%; bottom: 18%; --r: 18deg; opacity: 0.7; }
.bb-ed-book--4 { width: 44px; height: 176px; right: 10%; bottom: 22%; --r: -14deg; opacity: 0.6; }

.bb-ed-particles {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.bb-ed-particle {
    position: absolute;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(244, 234, 216, 0.95) 0%, rgba(201, 162, 39, 0.2) 100%);
    opacity: 0;
    animation: bb-ed-mote 12s linear infinite;
    box-shadow: 0 0 6px rgba(244, 234, 216, 0.35);
}

.bb-ed-banner__inner {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 2.5rem 3rem 2.5rem 3.25rem;
    max-width: 62%;
}

.bb-ed-masthead {
    font-family: "Playfair Display", Georgia, serif;
    font-size: 0.8125rem;
    font-weight: 500;
    letter-spacing: 0.38em;
    text-transform: uppercase;
    color: var(--bb-ed-amber);
    margin: 0 0 0.75rem;
    opacity: 0;
    animation: bb-ed-fade-up 0.9s ease forwards 0.2s;
}

.bb-ed-rule {
    width: 100%;
    max-width: 280px;
    height: 6px;
    margin: 0 0 1.25rem;
    overflow: visible;
}

.bb-ed-rule__line {
    stroke: var(--bb-ed-gold);
    stroke-width: 1.5;
    stroke-linecap: round;
    stroke-dasharray: 400;
    stroke-dashoffset: 400;
    filter: drop-shadow(0 0 4px rgba(212, 175, 55, 0.35));
    animation: bb-ed-draw-line 1.35s cubic-bezier(0.45, 0, 0.2, 1) forwards 0.95s;
}

.bb-ed-headline {
    font-family: "Playfair Display", Georgia, serif;
    font-weight: 600;
    font-size: clamp(1.85rem, 3.8vw, 2.75rem);
    line-height: 1.12;
    color: var(--bb-ed-cream);
    margin: 0 0 1rem;
    max-width: 14ch;
    letter-spacing: -0.02em;
    opacity: 0;
    filter: blur(14px);
    transform: translateY(18px);
    animation: bb-ed-headline-in 1.05s cubic-bezier(0.22, 1, 0.36, 1) forwards 0.42s;
    text-shadow: 0 2px 24px rgba(0, 0, 0, 0.45);
}

.bb-ed-sub {
    font-size: clamp(1rem, 1.5vw, 1.2rem);
    line-height: 1.55;
    color: var(--bb-ed-cream-dim);
    margin: 0 0 1.75rem;
    max-width: 32ch;
    font-weight: 400;
    opacity: 0;
    transform: translateY(22px);
    animation: bb-ed-sub-in 0.95s cubic-bezier(0.22, 1, 0.36, 1) forwards 0.78s;
}

.bb-ed-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem 1.25rem;
    opacity: 0;
    animation: bb-ed-fade-up 0.85s ease forwards 1.05s;
}

.bb-ed-cta {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.85rem 1.85rem;
    font-family: "Playfair Display", Georgia, serif;
    font-size: 0.95rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: var(--bb-ed-navy);
    text-decoration: none;
    background: linear-gradient(180deg, #e8d5a8 0%, var(--bb-ed-gold) 48%, var(--bb-ed-gold-dim) 100%);
    border: 1px solid rgba(212, 175, 55, 0.65);
    border-radius: 2px;
    overflow: hidden;
    box-shadow:
        0 2px 0 rgba(0, 0, 0, 0.2),
        0 8px 28px rgba(0, 0, 0, 0.35);
    animation: bb-ed-cta-pulse 3.2s ease-in-out infinite 1.4s;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.bb-ed-cta:hover {
    transform: translateY(-2px);
    box-shadow:
        0 2px 0 rgba(0, 0, 0, 0.2),
        0 12px 36px rgba(0, 0, 0, 0.4);
}

.bb-ed-cta:focus-visible {
    outline: 2px solid var(--bb-ed-cream);
    outline-offset: 3px;
}

.bb-ed-cta__label {
    position: relative;
    z-index: 1;
}

.bb-ed-cta__shine {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        transparent 0%,
        transparent 42%,
        rgba(255, 255, 255, 0.55) 50%,
        transparent 58%,
        transparent 100%
    );
    transform: translateX(-100%) skewX(-18deg);
    animation: bb-ed-shimmer 3.5s ease-in-out infinite 1.6s;
    pointer-events: none;
}

.bb-ed-badge {
    font-size: 0.8125rem;
    color: rgba(244, 234, 216, 0.72);
    border-left: 2px solid var(--bb-ed-gold-dim);
    padding-left: 0.85rem;
    line-height: 1.35;
    max-width: 200px;
}

@keyframes bb-ed-bg-in {
    to { opacity: 1; }
}

@keyframes bb-ed-fade-up {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes bb-ed-headline-in {
    to {
        opacity: 1;
        filter: blur(0);
        transform: translateY(0);
    }
}

@keyframes bb-ed-sub-in {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes bb-ed-draw-line {
    to { stroke-dashoffset: 0; }
}

@keyframes bb-ed-cta-pulse {
    0%, 100% { box-shadow: 0 2px 0 rgba(0, 0, 0, 0.2), 0 8px 28px rgba(0, 0, 0, 0.35), 0 0 0 0 rgba(212, 175, 55, 0.25); }
    50% { box-shadow: 0 2px 0 rgba(0, 0, 0, 0.2), 0 8px 32px rgba(0, 0, 0, 0.38), 0 0 28px 2px rgba(212, 175, 55, 0.12); }
}

@keyframes bb-ed-shimmer {
    0% { transform: translateX(-120%) skewX(-18deg); }
    45%, 100% { transform: translateX(220%) skewX(-18deg); }
}

@keyframes bb-ed-float-book {
    0%, 100% { transform: translateY(0) rotate(var(--r)); }
    50% { transform: translateY(-10px) rotate(calc(var(--r) + 2deg)); }
}

@keyframes bb-ed-mote {
    0% { opacity: 0; transform: translate(0, 0) scale(0.6); }
    8% { opacity: 0.85; }
    92% { opacity: 0.85; }
    100% { opacity: 0; transform: translate(var(--dx, 20px), var(--dy, -80px)) scale(0.3); }
}

@media (max-width: 900px) {
    .bb-ed-banner__frame { height: auto; min-height: 360px; }
    .bb-ed-banner__inner { max-width: 100%; padding: 2rem 1.5rem 2.5rem; }
    .bb-ed-books { opacity: 0.45; width: 50%; }
    .bb-ed-headline { max-width: none; }
}

@media (max-width: 520px) {
    .bb-ed-banner { padding: 0.75rem 0.5rem 0; }
    .bb-ed-banner__inner { padding: 1.5rem 1.1rem 2rem; }
    .bb-ed-masthead { letter-spacing: 0.22em; font-size: 0.7rem; }
}
</style>

<script>
(function () {
    var root = document.getElementById('bb-ed-particles');
    if (!root) return;
    var n = 22;
    for (var i = 0; i < n; i++) {
        var p = document.createElement('span');
        p.className = 'bb-ed-particle';
        p.style.left = (8 + Math.random() * 84) + '%';
        p.style.top = (15 + Math.random() * 70) + '%';
        p.style.setProperty('--dx', (Math.random() * 60 - 30) + 'px');
        p.style.setProperty('--dy', (-40 - Math.random() * 100) + 'px');
        p.style.animationDelay = (Math.random() * 10) + 's';
        p.style.animationDuration = (10 + Math.random() * 14) + 's';
        root.appendChild(p);
    }
})();
</script>
