<?php

declare(strict_types=1);

/**
 * Master category list.
 * Used by index.php, shop.php, product.php, and the header search dropdown.
 * Replace the static array below with a DB query once the schema is set up.
 *
 * Keys:
 *   slug       → used in shop.php?cat=<slug>
 *   name       → display label
 *   description→ short blurb shown on the shop page
 *   bg         → Tailwind bg-color class for category cards
 *   icon       → SVG path `d` attribute (Heroicons outline 24px)
 */

define('BOOKBITS_CATEGORIES', [
    [
        'slug'        => 'productivity',
        'name'        => 'Productivity',
        'description' => 'Work smarter, achieve more, and master your time.',
        'bg'          => 'bg-sky-100',
        'icon'        => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    ],
    [
        'slug'        => 'finance',
        'name'        => 'Finance',
        'description' => 'Personal finance, investing, and wealth building.',
        'bg'          => 'bg-blue-100',
        'icon'        => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
    [
        'slug'        => 'faith',
        'name'        => 'Faith',
        'description' => 'Spiritual growth, theology, and devotionals.',
        'bg'          => 'bg-indigo-50',
        'icon'        => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    ],
    [
        'slug'        => 'leadership',
        'name'        => 'Leadership',
        'description' => 'Lead with clarity, purpose, and lasting impact.',
        'bg'          => 'bg-cyan-100',
        'icon'        => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
    ],
    [
        'slug'        => 'business',
        'name'        => 'Business',
        'description' => 'Strategy, entrepreneurship, and management.',
        'bg'          => 'bg-sky-50',
        'icon'        => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    ],
    [
        'slug'        => 'biography',
        'name'        => 'Biography',
        'description' => 'True stories of extraordinary lives.',
        'bg'          => 'bg-blue-50',
        'icon'        => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    ],
    [
        'slug'        => 'fiction',
        'name'        => 'Fiction',
        'description' => 'Novels, short stories, and literary fiction.',
        'bg'          => 'bg-indigo-100',
        'icon'        => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    ],
    [
        'slug'        => 'memoir',
        'name'        => 'Memoir',
        'description' => 'Personal narratives and first-hand accounts.',
        'bg'          => 'bg-sky-100',
        'icon'        => 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
    ],
    [
        'slug'        => 'children-books',
        'name'        => 'Children Books',
        'description' => 'Picture books, early readers, and middle grade.',
        'bg'          => 'bg-cyan-50',
        'icon'        => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    ],
    [
        'slug'        => 'history-politics',
        'name'        => 'History & Politics',
        'description' => 'Events, movements, and the forces that shaped our world.',
        'bg'          => 'bg-blue-100',
        'icon'        => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
    ],
]);

/**
 * Find a category by slug. Returns null if not found.
 *
 * @param  string                    $slug
 * @return array<string,string>|null
 */
function bookbits_category_by_slug(string $slug): ?array
{
    foreach (BOOKBITS_CATEGORIES as $cat) {
        if ($cat['slug'] === $slug) {
            return $cat;
        }
    }
    return null;
}
