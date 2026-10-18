<?php

namespace Tests\Unit\Support;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{int|string, int}>
     */
    public static function omrInputs(): array
    {
        return [
            'whole rials as int' => [100, 100_000],
            'whole rials as string' => ['100', 100_000],
            'three decimals' => ['2.500', 2_500],
            'one decimal pads to baisa' => ['2.5', 2_500],
            'two decimals pads to baisa' => ['0.05', 50],
            'negative amount' => ['-1.250', -1_250],
        ];
    }

    #[DataProvider('omrInputs')]
    public function test_parses_rials_into_exact_baisa(int|string $omr, int $expectedBaisa): void
    {
        $this->assertSame($expectedBaisa, Money::omr($omr)->baisa);
    }

    #[TestWith(['1.2345'])]
    #[TestWith(['abc'])]
    #[TestWith(['1,000'])]
    #[TestWith([''])]
    public function test_rejects_malformed_rial_strings(string $omr): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::omr($omr);
    }

    public function test_multiplying_by_basis_points_rounds_down_to_the_baisa(): void
    {
        $salary = Money::omr(800);

        $this->assertSame(150_000, $salary->multiplyBasisPoints(1875)->baisa);
        $this->assertSame(333, Money::baisa(1_000)->multiplyBasisPoints(3333)->baisa);
    }

    public function test_arithmetic_returns_new_instances(): void
    {
        $limit = Money::omr(150);
        $remaining = $limit->minus(Money::omr(100));

        $this->assertSame(150_000, $limit->baisa);
        $this->assertSame(50_000, $remaining->baisa);
        $this->assertSame(52_500, $remaining->plus(Money::omr('2.500'))->baisa);
    }

    public function test_min_and_max_pick_the_smaller_and_larger_amount(): void
    {
        $small = Money::omr(10);
        $large = Money::omr(500);

        $this->assertSame($small, $small->min($large));
        $this->assertSame($large, $small->max($large));
    }

    public function test_formats_with_three_decimals_and_separators(): void
    {
        $this->assertSame('1250.500', Money::baisa(1_250_500)->toOmrString());
        $this->assertSame('-0.005', Money::baisa(-5)->toOmrString());
        $this->assertSame('OMR 1,250.500', Money::baisa(1_250_500)->format());
    }

    public function test_serializes_to_integer_baisa_in_json(): void
    {
        $this->assertSame('{"amount":2500}', json_encode(['amount' => Money::omr('2.500')]));
    }
}
