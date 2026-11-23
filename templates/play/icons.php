<?php
/**
 * The icons of the game page, drawn once as SVG symbols and used by id:
 *   <svg class="icon"><use href="#icon-flip"/></svg>
 *
 * Our own simple line drawings on a 24 x 24 grid (no icon library, A16).
 * They use "currentColor", so each icon takes the colour of its button.
 * To add an icon, add a <symbol> with a new id here.
 *
 * Variables: none.
 */
?>
<svg class="icon-sprite" aria-hidden="true" focusable="false">
    <!-- Flip the board: an arrow up and an arrow down -->
    <symbol id="icon-flip" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M7 20V4M3 8l4-4 4 4M17 4v16M13 16l4 4 4-4"/>
    </symbol>
    <!-- Planning board: four squares of a chessboard -->
    <symbol id="icon-planning" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="18" height="18"/>
        <rect x="3" y="3" width="9" height="9" fill="currentColor"/>
        <rect x="12" y="12" width="9" height="9" fill="currentColor"/>
    </symbol>
    <!-- Replay: a circular arrow -->
    <symbol id="icon-replay" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 12a9 9 0 1 0 2.6-6.4L3 8M3 3v5h5"/>
    </symbol>
    <!-- Download: an arrow down in a circle -->
    <symbol id="icon-download" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="9"/>
        <path d="M12 7v9M8 12l4 4 4-4"/>
    </symbol>
    <!-- Replay bar: |<  <<  <  ||  >  >  >>  >| -->
    <symbol id="icon-first" viewBox="0 0 24 24" fill="currentColor">
        <rect x="5" y="5" width="2.5" height="14"/>
        <path d="M19 5v14l-10-7z"/>
    </symbol>
    <symbol id="icon-rewind" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 5v14L3 12zM21 5v14l-9-7z"/>
    </symbol>
    <symbol id="icon-step-back" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 5l-7 7 7 7"/>
    </symbol>
    <symbol id="icon-pause" viewBox="0 0 24 24" fill="currentColor">
        <rect x="6" y="5" width="4" height="14"/>
        <rect x="14" y="5" width="4" height="14"/>
    </symbol>
    <symbol id="icon-play" viewBox="0 0 24 24" fill="currentColor">
        <path d="M7 5v14l12-7z"/>
    </symbol>
    <symbol id="icon-step-forward" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 5l7 7-7 7"/>
    </symbol>
    <symbol id="icon-fast-forward" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 5v14l9-7zM12 5v14l9-7z"/>
    </symbol>
    <symbol id="icon-last" viewBox="0 0 24 24" fill="currentColor">
        <path d="M5 5v14l10-7z"/>
        <rect x="16.5" y="5" width="2.5" height="14"/>
    </symbol>
    <!-- Close (leave replay) -->
    <symbol id="icon-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
        <path d="M6 6l12 12M18 6L6 18"/>
    </symbol>
    <!-- Game buttons: abort (a crossed circle), resign (a flag) -->
    <symbol id="icon-abort" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="12" cy="12" r="9"/>
        <path d="M9 9l6 6M15 9l-6 6"/>
    </symbol>
    <symbol id="icon-flag" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 21V4M5 4h12l-2.5 4L17 12H5"/>
    </symbol>
</svg>
