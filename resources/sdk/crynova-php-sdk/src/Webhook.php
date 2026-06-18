<?php

namespace Crynova;

/**
 * Verifies incoming Crynova webhooks.
 *
 * Crynova signs every webhook with HMAC-SHA256 over the raw request body using
 * your project's webhook secret. The signature is sent in the `X-Crynova-Sig`
 * header as `sha256=<hex>`; the event name is in `X-Crynova-Event`.
 *
 * <code>
 * $payload   = file_get_contents('php://input');
 * $signature = $_SERVER['HTTP_X_CRYNOVA_SIG'] ?? '';
 *
 * if (! \Crynova\Webhook::isValid($payload, $signature, $webhookSecret)) {
 *     http_response_code(400);
 *     exit('Invalid signature');
 * }
 *
 * $event = json_decode($payload, true);
 * // $event['event'] => invoice.paid | invoice.expired | …
 * </code>
 */
class Webhook
{
    /**
     * Constant-time verification of a webhook signature.
     *
     * @param string $payload   Raw, unmodified request body.
     * @param string $signature Value of the X-Crynova-Sig header (with or without the "sha256=" prefix).
     * @param string $secret    Your project's webhook secret.
     */
    public static function isValid(string $payload, string $signature, string $secret): bool
    {
        if ($secret === '' || $signature === '') {
            return false;
        }

        $provided = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $provided);
    }

    /**
     * Verify and decode a webhook in one step.
     *
     * @return array Decoded event payload.
     * @throws CrynovaException if the signature is invalid or the body is not JSON.
     */
    public static function parse(string $payload, string $signature, string $secret): array
    {
        if (! self::isValid($payload, $signature, $secret)) {
            throw new CrynovaException('Invalid webhook signature.', 400);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            throw new CrynovaException('Webhook body is not valid JSON.', 400);
        }

        return $data;
    }
}
