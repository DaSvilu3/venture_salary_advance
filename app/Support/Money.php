<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An immutable amount of Omani rials, held as integer baisa (1 OMR = 1000 baisa).
 */
final class Money implements JsonSerializable, Stringable
{
    public const BAISA_PER_OMR = 1000;

    public function __construct(public readonly int $baisa) {}

    public static function baisa(int $baisa): self
    {
        return new self($baisa);
    }

    /**
     * Create an amount from rials, e.g. 100, "100" or "2.500". Floats are rejected to avoid rounding errors.
     */
    public static function omr(int|string $omr): self
    {
        if (is_int($omr)) {
            return new self($omr * self::BAISA_PER_OMR);
        }

        if (preg_match('/^(-)?(\d+)(?:\.(\d{1,3}))?$/', trim($omr), $matches) !== 1) {
            throw new InvalidArgumentException("Invalid OMR amount [{$omr}].");
        }

        $baisa = (int) $matches[2] * self::BAISA_PER_OMR + (int) str_pad($matches[3] ?? '', 3, '0');

        return new self($matches[1] === '-' ? -$baisa : $baisa);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->baisa + $other->baisa);
    }

    public function minus(self $other): self
    {
        return new self($this->baisa - $other->baisa);
    }

    /**
     * Multiply by a ratio in basis points (10000 = 100%), rounding down to the baisa.
     */
    public function multiplyBasisPoints(int $basisPoints): self
    {
        return new self(intdiv($this->baisa * $basisPoints, 10000));
    }

    public function min(self $other): self
    {
        return $this->baisa <= $other->baisa ? $this : $other;
    }

    public function max(self $other): self
    {
        return $this->baisa >= $other->baisa ? $this : $other;
    }

    public function isZero(): bool
    {
        return $this->baisa === 0;
    }

    public function isPositive(): bool
    {
        return $this->baisa > 0;
    }

    public function isNegative(): bool
    {
        return $this->baisa < 0;
    }

    public function greaterThan(self $other): bool
    {
        return $this->baisa > $other->baisa;
    }

    public function lessThan(self $other): bool
    {
        return $this->baisa < $other->baisa;
    }

    public function equals(self $other): bool
    {
        return $this->baisa === $other->baisa;
    }

    /**
     * The amount in rials with three decimals, e.g. "100.500".
     */
    public function toOmrString(): string
    {
        $sign = $this->baisa < 0 ? '-' : '';
        $absolute = abs($this->baisa);

        return sprintf('%s%d.%03d', $sign, intdiv($absolute, self::BAISA_PER_OMR), $absolute % self::BAISA_PER_OMR);
    }

    /**
     * A display string with thousands separators, e.g. "OMR 1,250.000".
     */
    public function format(): string
    {
        return 'OMR '.number_format($this->baisa / self::BAISA_PER_OMR, 3);
    }

    public function jsonSerialize(): int
    {
        return $this->baisa;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
