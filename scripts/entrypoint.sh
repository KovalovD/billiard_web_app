#!/usr/bin/env bash
set -euo pipefail

# 1. Публічні файли вже в /public; Nginx їх віддає сам.
# 2. Стартуємо PHP-сервер (можна замінити на Octane, якщо потрібно).
php artisan serve --host 0.0.0.0 --port "${PORT:-8080}"
