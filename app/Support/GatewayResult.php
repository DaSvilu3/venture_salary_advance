<?php

namespace App\Support;

/**
 * The outcome a bank or funder gateway reports for one request.
 */
final readonly class GatewayResult
{
    public function __construct(
        public bool $succeeded,
        public ?string $reference = null,
        public ?string $failureReason = null,
    ) {}

    public static function success(string $reference): self
    {
        return new self(true, $reference);
    }

    public static function failure(string $reason, ?string $reference = null): self
    {
        return new self(false, $reference, $reason);
    }
}
