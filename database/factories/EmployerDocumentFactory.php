<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Employer;
use App\Models\EmployerDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployerDocument>
 */
class EmployerDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(2).'.pdf';

        return [
            'employer_id' => Employer::factory(),
            'type' => DocumentType::CommercialRegistration,
            'original_name' => $name,
            'path' => 'employer-documents/'.fake()->uuid().'.pdf',
            'mime' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 4_000_000),
            'status' => DocumentStatus::Pending,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_note' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Approved,
            'reviewed_by' => User::factory()->platformAdmin(),
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $note = 'The scan is unreadable.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentStatus::Rejected,
            'reviewed_by' => User::factory()->platformAdmin(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }
}
