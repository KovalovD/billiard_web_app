<template>
    <component :is="'style'" type="text/css">
        /* Bracket labels */
        .bracket-label {
        font-size: 14px;
        font-weight: 600;
        fill: #374151;
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
        stroke-width: 2 !important;
        stroke: rgba(255, 198, 109, 0.7) !important;
        }

        /* Lower bracket match styles */
        .lower-bracket-match {
        opacity: 0.9;
        }

        .match-group:hover .match-pending,
        .match-group:hover .match-ready,
        .match-group:hover .match-active,
        .match-group:hover .match-verification,
        .match-group:hover .match-completed {
        filter: brightness(0.95);
        }

        /* Player backgrounds */
        .player-bg {
        fill: transparent;
        }

        .player-winner {
        fill: #2b81fd;
        fill-opacity: 0.1;
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

        .walkover-text {
        font-size: 10px;
        font-weight: bold;
        fill: #78350f;
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

        /* In-progress status indicator with breathing animation */
        .status-in-progress {
        fill: #ef4444;
        animation: breathe 2s ease-in-out infinite;
        }

        @keyframes breathe {
        0%, 100% {
        opacity: 1;
        r: 4;
        }
        50% {
        opacity: 0.6;
        r: 5;
        }
        }

        .bracket-svg {
        font-family: system-ui, -apple-system, sans-serif;
        }
    </component>
</template>

<script lang="ts" setup>
// This component provides shared styles for bracket visualizations
</script>
