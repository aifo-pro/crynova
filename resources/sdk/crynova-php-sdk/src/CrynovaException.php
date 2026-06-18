<?php

namespace Crynova;

/**
 * Thrown when the Crynova API returns an error response or the request fails.
 */
class CrynovaException extends \RuntimeException
{
    /** Decoded JSON error body returned by the API (if any). */
    public array $response;

    public function __construct(string $message, int $httpStatus = 0, array $response = [])
    {
        parent::__construct($message, $httpStatus);
        $this->response = $response;
    }

    /** HTTP status code returned by the API (0 for transport-level failures). */
    public function getHttpStatus(): int
    {
        return $this->getCode();
    }
}
