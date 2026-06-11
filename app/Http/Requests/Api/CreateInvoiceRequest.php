<?php

namespace App\Http\Requests\Api;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization is handled by AuthenticateApiKey middleware
    }

    public function rules(): array
    {
        $code = strtoupper(trim((string) $this->input('currency')));
        $fiat = (array) config('crynova.fiat_currencies', []);
        $isFiat = in_array($code, $fiat, true);

        // currency may be a fiat code (priced in fiat, crypto chosen at checkout)
        // or an active crypto code (direct crypto invoice).
        $cryptoCodes = Currency::where('is_active', true)->pluck('code')->all();
        $allowed = array_values(array_unique(array_merge($fiat, $cryptoCodes)));

        $amountRules = ['required', 'numeric', 'gt:0'];

        if (! $isFiat) {
            $currency = Currency::where('code', $code)->where('is_active', true)->first();
            if ($currency) {
                $amountRules[] = 'gte:' . $currency->min_amount;
                if ($currency->max_amount !== null) {
                    $amountRules[] = 'lte:' . $currency->max_amount;
                }
            }
        }

        return [
            'currency'    => ['required', 'string', Rule::in($allowed)],
            'amount'      => $amountRules,
            'order_id'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expires_in'  => ['nullable', 'integer', 'min:5', 'max:1440'],
            'metadata'    => ['nullable', 'array'],
            'metadata.*'  => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper(trim((string) $this->input('currency')))]);
        }
    }
}
