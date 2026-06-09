<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\IpUtils;

class ApiIpListService
{
    public const SETTING_KEY = 'api_ips_json';

    public function defaultJson(): string
    {
        return json_encode(['list' => ''], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function rawJson(): string
    {
        $raw = (string) Setting::get(self::SETTING_KEY, $this->defaultJson());

        return trim($raw) !== '' ? $raw : $this->defaultJson();
    }

    public function payload(): array
    {
        return $this->decode($this->rawJson());
    }

    public function allowedIps(): array
    {
        return $this->extractList($this->payload()['list'] ?? '');
    }

    public function allows(?string $ip): bool
    {
        $allowed = $this->allowedIps();

        if ($allowed === []) {
            return true;
        }

        return $ip !== null && IpUtils::checkIp($ip, $allowed);
    }

    public function validateAndNormalize(string $json): string
    {
        $payload = $this->decode($json);

        if (! array_key_exists('list', $payload)) {
            throw ValidationException::withMessages([
                self::SETTING_KEY => 'JSON має містити ключ "list".',
            ]);
        }

        $ips = $this->extractList($payload['list']);

        foreach ($ips as $ip) {
            if (! $this->isValidIpOrCidr($ip)) {
                throw ValidationException::withMessages([
                    self::SETTING_KEY => "Некоректний IP або CIDR: {$ip}",
                ]);
            }
        }

        return json_encode(
            ['list' => implode(', ', $ips)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );
    }

    private function decode(string $json): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValidationException::withMessages([
                self::SETTING_KEY => 'Введіть валідний JSON. '.$e->getMessage(),
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                self::SETTING_KEY => 'JSON має бути обʼєктом.',
            ]);
        }

        return $payload;
    }

    private function extractList(mixed $value): array
    {
        if (is_string($value)) {
            $items = preg_split('/[\s,;]+/', $value) ?: [];
        } elseif (is_array($value)) {
            $items = $value;
        } else {
            throw ValidationException::withMessages([
                self::SETTING_KEY => 'Ключ "list" має бути рядком або масивом IP.',
            ]);
        }

        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function isValidIpOrCidr(string $value): bool
    {
        if (! str_contains($value, '/')) {
            return filter_var($value, FILTER_VALIDATE_IP) !== false;
        }

        [$ip, $prefix] = array_pad(explode('/', $value, 2), 2, null);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false || ! ctype_digit((string) $prefix)) {
            return false;
        }

        $maxPrefix = str_contains($ip, ':') ? 128 : 32;
        $prefix = (int) $prefix;

        return $prefix >= 0 && $prefix <= $maxPrefix;
    }
}
