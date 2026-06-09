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
        $currency = Currency::where('code', $this->input('currency'))->where('is_active', true)->first();

        $amountRules = ['required', 'numeric', 'gt:0'];

        if ($currency) {
            $amountRules[] = 'gte:' . $currency->min_amount;

            if ($currency->max_amount !== null) {
                $amountRules[] = 'lte:' . $currency->max_amount;
            }
        }

        return [
            'currency'    => ['required', 'string', Rule::exists('currencies', 'code')->where('is_active', true)],
            'amount'      => $amountRules,
            'order_id'    => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expires_in'  => ['nullable', 'integer', 'min:5', 'max:1440'],
            'metadata'    => ['nullable', 'array'],
            'metadata.*'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
