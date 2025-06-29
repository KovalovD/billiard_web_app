<?php

return [
    /*
     * These options will be passed to the underlying `Spatie\Sitemap\Sitemap` instance.
     */
    'options' => [
        'use_cache'            => false,
        'cache_key'            => 'sitemap',
        'cache_duration'       => 60 * 24, // 24 hours
        'escaping'             => true,
        'use_limit_size'       => false,
        'max_urls_per_sitemap' => 50000,
        'generate_index'       => true,
    ],
];
