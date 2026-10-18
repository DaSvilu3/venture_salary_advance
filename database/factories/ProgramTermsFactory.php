<?php

namespace Database\Factories;

use App\Models\ProgramTerms;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramTerms>
 */
class ProgramTermsFactory extends Factory
{
    /**
     * Platform-wide terms with the configured defaults.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employer_id' => null,
            ...config('salary_access.default_terms'),
        ];
    }
}
