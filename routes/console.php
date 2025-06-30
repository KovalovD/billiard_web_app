<?php

use App\Console\Commands\GenerateSitemap;

Schedule::command(GenerateSitemap::class)->dailyAt('03:00');
