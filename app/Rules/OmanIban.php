<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * An Omani IBAN: "OM", two check digits, a three-digit bank code and a 16-character account number,
 * with a valid ISO 13616 mod-97 checksum. Spaces are ignored.
 */
class OmanIban implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $iban = self::normalize((string) $value);

        if (preg_match('/^OM\d{2}\d{3}[A-Z0-9]{16}$/', $iban) !== 1 || ! self::hasValidChecksum($iban)) {
            $fail(__('Enter a valid Omani IBAN, for example OM60 0180 0000 1234 5678 901.'));
        }
    }

    public static function normalize(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/', '', $iban) ?? '');
    }

    private static function hasValidChecksum(string $iban): bool
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);
        $numeric = preg_replace_callback('/[A-Z]/', fn (array $letter): string => (string) (ord($letter[0]) - 55), $rearranged) ?? '';

        $remainder = 0;

        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }
}
