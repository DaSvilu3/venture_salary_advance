<?php

namespace Tests\Feature\Http\Admin;

use App\Domain\Advances\RequestAdvance;
use App\Enums\AdvanceStatus;
use App\Jobs\DisburseAdvance;
use App\Models\Advance;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\ProgramTerms;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdvanceApprovalTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-10 10:00'));
    }

    public function test_requests_wait_for_an_admin_when_the_employer_turns_auto_approve_off(): void
    {
        $employer = Employer::factory()->paydayOn(25)->create();
        ProgramTerms::factory()->create(['employer_id' => $employer->id, 'auto_approve' => false]);
        $employee = Employee::factory()->for($employer)->enrolled()->earning(Money::omr(800))->create();
        Queue::fake([DisburseAdvance::class]);

        $advance = app(RequestAdvance::class)->handle($employee, Money::omr(100), Disclosure::factory()->create(), $employee->user);

        $this->assertSame(AdvanceStatus::Requested, $advance->fresh()->status);
        $this->assertSame(['advance.requested'], $advance->auditLogs()->pluck('action')->all());
        Queue::assertNotPushed(DisburseAdvance::class);
    }

    public function test_admin_approval_moves_a_requested_advance_on_to_payout(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Requested)->create();
        Queue::fake([DisburseAdvance::class]);

        $this->actingAs($admin)->post(route('admin.advances.approve', $advance->reference));

        $this->assertSame(AdvanceStatus::Approved, $advance->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'advance.approved', 'actor_id' => $admin->id]);
        Queue::assertPushed(DisburseAdvance::class, fn (DisburseAdvance $job): bool => $job->advanceId === $advance->id);
    }

    public function test_cannot_approve_an_advance_that_is_not_awaiting_approval(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $advance = Advance::factory()->inStatus(AdvanceStatus::Disbursed)->create();
        Queue::fake([DisburseAdvance::class]);

        $this->actingAs($admin)
            ->post(route('admin.advances.approve', $advance->reference))
            ->assertSessionHasErrors('advance');

        $this->assertSame(AdvanceStatus::Disbursed, $advance->fresh()->status);
        Queue::assertNotPushed(DisburseAdvance::class);
    }
}
