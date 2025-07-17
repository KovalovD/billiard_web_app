// Components/Tournament/BracketStyles.vue
<template>
    <component :is="'style'" type="text/css">
        /* Bracket labels */
        .bracket-label {
        font-size: 14px;
        font-weight: 600;
        fill: #374151;
        }

        /* Mobile label adjustments */
        @media (max-width: 640px) {
        .bracket-label {
        font-size: 12px;
        }
        }

        /* Match styles */
        .match-pending {
        fill: #ffffff;
        stroke: #d1d5db;
        stroke-width: 1;
        }

        .match-ready {
        fill: rgba(230, 255, 237, 1);
        stroke: #10b981;
        stroke-width: 1.5;
        }

        .match-active {
        fill: #fee2e2;
        stroke: #ef4444;
        stroke-width: 2;
        }

        .match-verification {
        fill: #f3e8ff;
        stroke: #9333ea;
        stroke-width: 1.5;
        }

        .match-completed {
        fill: rgba(219, 234, 254, 0.2);
        stroke: #3b82f6;
        stroke-width: 1;
        }

        /* Current user match highlight */
        .user-match {
        stroke-width: 3 !important;
        stroke: #f59e0b !important;
        filter: drop-shadow(0 2px 4px rgba(245, 158, 11, 0.3));
        }

        /* Lower bracket match styles */
        .lower-bracket-match {
        opacity: 0.9;
        }

        /* Connector lines */
        .connector-line {
        stroke: #9ca3af;
        stroke-width: 2;
        }

        .connector-line-lower {
        stroke: #a78bfa;
        stroke-dasharray: 4 2;
        }

        /* Group separator (upper vs lower) */
        .bracket-separator {
        stroke: #9ca3af;
        stroke-width: 2;
        stroke-dasharray: 6 4;
        }

        /* Vertical round separators */
        .round-separator {
        stroke: #9ca3af;
        stroke-width: 2;
        stroke-dasharray: 6 4;
        opacity: 0.5;
        }

        @media (max-width: 640px) {
        .connector-line,
        .connector-line-lower,
        .bracket-separator,
        .round-separator {
        stroke-width: 1.5;
        }

        .bracket-separator,
        .round-separator {
        stroke-dasharray: 4 3;
        }
        }

        /* In-progress status indicator with breathing animation */
        .status-in-progress {
        fill: #ef4444;
        animation: breathe 2s ease-in-out infinite;
        }

        @keyframes breathe {
        0%,
        100% {
        opacity: 1;
        r: 4;
        }
        50% {
        opacity: 0.6;
        r: 5;
        }
        }

        /* Mobile status indicator */
        @media (max-width: 640px) {
        .status-in-progress {
        r: 3;
        }

        @keyframes breathe {
        0%,
        100% {
        opacity: 1;
        r: 3;
        }
        50% {
        opacity: 0.6;
        r: 4;
        }
        }
        }

        /* Player backgrounds */
        .player-bg {
        fill: transparent;
        }

        .player-winner {
        fill: #2b81fd;
        fill-opacity: 0.15;
        }

        /* Text styles */
        .match-number {
        font-size: 11px;
        fill: #6b7280;
        }

        .player-name {
        font-size: 13px;
        font-weight: 500;
        fill: #111827;
        }

        .player-score {
        font-size: 14px;
        font-weight: 600;
        fill: #111827;
        text-anchor: end;
        }

        /* Mobile text adjustments */
        @media (max-width: 640px) {
        .match-number {
        font-size: 9px;
        }

        .player-name {
        font-size: 11px;
        }

        .player-score {
        font-size: 12px;
        }

        .walkover-text {
        font-size: 8px;
        }
        }

        .walkover-text {
        font-size: 10px;
        font-weight: bold;
        fill: #78350f;
        }

        .bracket-svg {
        font-family: system-ui, -apple-system, sans-serif;
        }

        /* Touch-friendly tap targets */
        .match-group {
        cursor: pointer;
        }

        /* Larger touch targets on mobile */
        @media (max-width: 640px) {
        .match-group rect {
        stroke-width: 2;
        }
        }

        /* Prevent selection on touch devices */
        .bracket-svg {
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        }
    </component>
</template>

<script lang="ts" setup>
// No script needed – purely shared styles
</script>
