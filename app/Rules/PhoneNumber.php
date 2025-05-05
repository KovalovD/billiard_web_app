<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Basic phone number validation
        // Allows formats like: +1234567890, 1234567890, 123-456-7890, (123) 456-7890
        if (!preg_match('/^(\+?\d{1,3}[-\s]?)?\(?(\d{3})\)?[-\s]?(\d{3})[-\s]?(\d{4})$/', $value)) {
            $fail('The :attribute must be a valid phone number format (e.g., +1234567890, 123-456-7890).');
        }
    }
}
