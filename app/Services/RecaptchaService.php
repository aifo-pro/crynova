<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecaptchaService
{
    public function enabled(): bool
    {
        return (bool) Setting::get('recaptcha_enabled', false);
    }

    public function siteKey(): string
    {
        return trim((string) Setting::get('recaptcha_site_key', ''));
    }

    public function verify(Request $request, string $action): void
    {
        if (! $this->enabled()) {
            return;
        }

        $secret = trim((string) Setting::get('recaptcha_secret_key', ''));
        $token = trim((string) $request->input('recaptcha_token', ''));

        if ($secret === '' || $token === '') {
            $this->fail();
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification failed: '.$e->getMessage());
            $this->fail();
        }

        $payload = $response->json() ?: [];
        $score = (float) ($payload['score'] ?? 0);
        $minScore = max(0, min(1, (float) Setting::get('recaptcha_min_score', 0.5)));
        $responseAction = (string) ($payload['action'] ?? '');

        if (! ($payload['success'] ?? false)) {
            $this->fail();
        }

        if ($score < $minScore) {
            $this->fail();
        }

        if ($responseAction !== '' && $responseAction !== $action) {
            $this->fail();
        }
    }

    private function fail(): never
    {
        throw ValidationException::withMessages([
            'recaptcha_token' => 'Перевірка reCAPTCHA не пройдена. Спробуйте ще раз.',
        ]);
    }
}
