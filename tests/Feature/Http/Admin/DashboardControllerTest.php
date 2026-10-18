<?php

namespace Tests\Feature\Http\Admin;

use App\Enums\AdvanceStatus;
use App\Models\Advance;
use App\Models\User;
use App\Support\Money;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sums_the_outstanding_book_and_lists_advances_needing_attention(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        Advance::factory()->forAmount(Money::omr(100))->create();
        Advance::factory()->forAmount(Money::omr(50))->inStatus(AdvanceStatus::Overdue)->create();
        Advance::factory()->forAmount(Money::omr(70))->inStatus(AdvanceStatus::Collected)->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/dashboard')
                ->where('book.outstanding', 155_000)
                ->where('book.overdue', 52_500)
                ->where('book.overdue_count', 1)
                ->has('needsAttention.data', 1));
    }
}
