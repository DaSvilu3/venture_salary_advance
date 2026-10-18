<?php

namespace Tests\Feature\Http\Employer;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_updates_the_payday(): void
    {
        $hr = User::factory()->employerAdmin()->for(Employer::factory()->paydayOn(25))->create();

        $this->actingAs($hr)
            ->put(route('employer.settings.update'), ['payday_day' => 28])
            ->assertRedirect(route('employer.settings.edit'));

        $this->assertSame(28, $hr->employer->fresh()->payday_day);
    }

    public function test_rejects_a_payday_outside_the_month(): void
    {
        $hr = User::factory()->employerAdmin()->for(Employer::factory()->paydayOn(25))->create();

        $this->actingAs($hr)
            ->put(route('employer.settings.update'), ['payday_day' => 32])
            ->assertSessionHasErrors('payday_day');

        $this->assertSame(25, $hr->employer->fresh()->payday_day);
    }
}
