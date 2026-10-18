<?php

namespace Tests\Feature\Http\Admin;

use App\Models\Employer;
use App\Models\ProgramTerms;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProgramTermsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @var array<string, string|int>
     */
    private const TERMS = [
        'limit_ratio_percent' => '25',
        'max_limit' => '400',
        'min_advance' => '20',
        'flat_fee' => '1.750',
        'min_tenure_months' => 6,
        'cutoff_days_before_payday' => 2,
        'max_draws_per_cycle' => 2,
    ];

    public function test_saves_platform_terms_in_basis_points_and_baisa(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)->put(route('admin.terms.update'), self::TERMS)->assertRedirect(route('admin.terms.edit'));

        $terms = ProgramTerms::platformDefaults();
        $this->assertTrue($terms->exists);
        $this->assertSame(2500, $terms->limit_ratio_bps);
        $this->assertSame(400_000, $terms->max_limit_baisa->baisa);
        $this->assertSame(1_750, $terms->flat_fee_baisa->baisa);
        $this->assertSame(6, $terms->min_tenure_months);
    }

    public function test_employer_override_applies_only_to_that_employer_until_removed(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();
        $other = Employer::factory()->create();

        $this->actingAs($admin)->put(route('admin.employers.terms.update', $employer), self::TERMS);

        $this->assertSame(2500, ProgramTerms::forEmployer($employer)->limit_ratio_bps);
        $this->assertSame(1875, ProgramTerms::forEmployer($other)->limit_ratio_bps);

        $this->actingAs($admin)->delete(route('admin.employers.terms.destroy', $employer));

        $this->assertSame(1875, ProgramTerms::forEmployer($employer->fresh())->limit_ratio_bps);
    }

    public function test_minimum_advance_cannot_exceed_the_maximum_limit(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->put(route('admin.terms.update'), [...self::TERMS, 'min_advance' => '500'])
            ->assertSessionHasErrors('min_advance');
    }
}
