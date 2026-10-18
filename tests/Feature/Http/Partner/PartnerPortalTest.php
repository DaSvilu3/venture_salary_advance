<?php

namespace Tests\Feature\Http\Partner;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PartnerPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_bank_viewer_sees_only_advances_its_bank_handles(): void
    {
        $employer = Employer::factory()->create();
        $bankViewer = User::factory()->partnerViewer()->create(['partner_id' => $employer->bank_partner_id]);
        $own = Advance::factory()->for(Employee::factory()->for($employer)->enrolled())->create();
        Advance::factory()->create();

        $this->actingAs($bankViewer)
            ->get(route('partner.advances.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('partner/advances')
                ->has('advances.data', 1)
                ->where('advances.data.0.reference', $own->reference));
    }

    public function test_funder_viewer_sees_advances_it_funds_across_banks(): void
    {
        $first = Employer::factory()->create();
        $second = Employer::factory()->create(['funder_partner_id' => $first->funder_partner_id]);
        $funderViewer = User::factory()->partnerViewer()->create(['partner_id' => $first->funder_partner_id]);
        Advance::factory()->for(Employee::factory()->for($first)->enrolled())->create();
        Advance::factory()->for(Employee::factory()->for($second)->enrolled())->create();
        Advance::factory()->create();

        $this->actingAs($funderViewer)
            ->get(route('partner.advances.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('advances.data', 2));
    }

    public function test_dashboard_reports_the_book_and_exposure_by_employer(): void
    {
        $employer = Employer::factory()->create(['name' => 'Al Noor Trading LLC']);
        $viewer = User::factory()->partnerViewer()->create(['partner_id' => $employer->bank_partner_id]);
        $employee = Employee::factory()->for($employer)->enrolled()->create();
        Advance::factory()->for($employee)->forAmount(Money::omr(100))->create();
        Advance::factory()->for($employee)->forAmount(Money::omr(50))->inStatus(AdvanceStatus::Overdue)->create(['due_date' => now()->subDays(10)]);

        $this->actingAs($viewer)
            ->get(route('partner.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('partner/dashboard')
                ->where('book.outstanding', 155_000)
                ->where('book.overdue', 52_500)
                ->where('ageing.8_30', 52_500)
                ->where('exposure.0.employer', 'Al Noor Trading LLC')
                ->where('exposure.0.advances', 2)
                ->has('trend', 6));
    }

    public function test_csv_export_contains_only_the_partners_filtered_advances(): void
    {
        $employer = Employer::factory()->create();
        $viewer = User::factory()->partnerViewer()->create(['partner_id' => $employer->bank_partner_id]);
        $employee = Employee::factory()->for($employer)->enrolled()->create(['staff_no' => 'AN-7']);
        $overdue = Advance::factory()->for($employee)->forAmount(Money::omr(50))->inStatus(AdvanceStatus::Overdue)->create();
        Advance::factory()->for($employee)->create();
        Advance::factory()->inStatus(AdvanceStatus::Overdue)->create();

        $csv = $this->actingAs($viewer)
            ->get(route('partner.advances.export', ['status' => 'overdue']))
            ->assertOk()
            ->assertDownload()
            ->streamedContent();

        $lines = array_values(array_filter(explode("\n", $csv)));
        $this->assertCount(2, $lines);
        $this->assertStringStartsWith('reference,employer,staff_no', $lines[0]);
        $this->assertStringContainsString("{$overdue->reference},", $lines[1]);
        $this->assertStringContainsString(',AN-7,', $lines[1]);
        $this->assertStringContainsString(',52.500,', $lines[1]);
    }

    public function test_viewer_without_a_partner_is_forbidden(): void
    {
        $this->actingAs(User::factory()->partnerViewer()->create())
            ->get(route('partner.dashboard'))
            ->assertForbidden();
    }
}
