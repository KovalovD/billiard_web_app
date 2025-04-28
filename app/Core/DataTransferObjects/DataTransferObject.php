<?php

namespace App\Core\DataTransferObjects;

use Illuminate\Foundation\Http\FormRequest;

interface DataTransferObject
{
    public static function fromRequest(FormRequest $request): self;

    public static function fromArray(array $array): self;

    public static function prepareDataBeforeCreation(array $array): array;

    public function toArray(array $except = []): array;
}
