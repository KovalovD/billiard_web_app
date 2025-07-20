<?php

namespace App\Core\Traits;

use Storage;

trait HasFiles
{
    public function getPicture(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return Storage::disk('r2')->url($path);
    }
}
