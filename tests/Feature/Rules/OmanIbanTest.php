<?php

namespace Tests\Feature\Rules;

use App\Rules\OmanIban;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OmanIbanTest extends TestCase
{
    #[TestWith(['OM600180000012345678901'])]
    #[TestWith(['om60 0180 0000 1234 5678 901'])]
    #[TestWith(['OM880180000098765432109'])]
    public function test_accepts_valid_omani_ibans_with_or_without_spaces(string $iban): void
    {
        $this->assertTrue(Validator::make(['iban' => $iban], ['iban' => new OmanIban])->passes());
    }

    #[TestWith(['OM610180000012345678901'], 'wrong check digits')]
    #[TestWith(['GB82WEST12345698765432'], 'another country')]
    #[TestWith(['OM6001800000123456789'], 'too short')]
    public function test_rejects_invalid_ibans(string $iban): void
    {
        $validator = Validator::make(['iban' => $iban], ['iban' => new OmanIban]);

        $this->assertSame(
            ['Enter a valid Omani IBAN, for example OM60 0180 0000 1234 5678 901.'],
            $validator->errors()->get('iban'),
        );
    }

    public function test_normalizes_spacing_and_case(): void
    {
        $this->assertSame('OM600180000012345678901', OmanIban::normalize(' om60 0180 0000 1234 5678 901 '));
    }
}
