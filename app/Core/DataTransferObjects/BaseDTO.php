<?php

namespace App\Core\DataTransferObjects;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseDTO implements DataTransferObject
{
    final public function __construct(array $params = [])
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function fromRequest(FormRequest $request): static
    {
        return new static(
            self::prepareDataBeforeCreation(
                $request->validated(),
            ),
        );
    }

    public static function prepareDataBeforeCreation(array $array): array
    {
        return $array;
    }

    public static function fromArray(array $array): static
    {
        return new static(
            self::prepareDataBeforeCreation(
                $array,
            ),
        );
    }

    public function toArray(array $except = []): array
    {
        $properties = get_object_vars($this);

        foreach ($except as $property) {
            if (property_exists($this, $property)) {
                unset($properties[$property]);
            }
        }

        return $properties;
    }
}
