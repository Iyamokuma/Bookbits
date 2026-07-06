<?php

declare(strict_types=1);

/**
 * Instagram icon (single clean path — avoids duplicated SVG geometry).
 */
function bb_svg_instagram(string $class = 'h-5 w-5'): string
{
    $class = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');

    return '<svg class="' . $class . '" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
        . '<rect x="3" y="3" width="18" height="18" rx="5" stroke-width="2"/>'
        . '<circle cx="12" cy="12" r="4" stroke-width="2"/>'
        . '<circle cx="17.5" cy="6.5" r="1.25" fill="currentColor" stroke="none"/>'
        . '</svg>';
}
