<?php

namespace App\Rules;

use App\Support\UrlSafety;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PublicHttpUrl implements ValidationRule
{
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    if (! is_string($value) || $value === '') {
      $fail('The :attribute must be a valid URL.');

      return;
    }

    if (! UrlSafety::isPublicHttpUrl($value)) {
      $fail('The :attribute must be a public HTTP/HTTPS URL (private networks are not allowed).');
    }
  }
}
