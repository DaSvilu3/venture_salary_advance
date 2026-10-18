<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdvanceStatus;
use App\Enums\EmployerStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdvanceResource;
use App\Models\Advance;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\LedgerEntry;
use App\Support\Clock;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The whole advance book: what is out, what came back this month, and what needs attention.
     */
    public function __invoke(Clock $clock): Response
    {
        $monthStart = $clock->today()->startOfMonth();
        $monthEnd = $clock->today()->endOfMonth();

        $collectionsThisMonth = LedgerEntry::query()
            ->where('type', LedgerEntryType::Collection)
            ->whereBetween('occurred_at', [$monthStart, $monthEnd]);

        $attempts = (clone $collectionsThisMonth)->count();
        $failedAttempts = (clone $collectionsThisMonth)->where('status', LedgerEntryStatus::Failed)->count();

        return Inertia::render('admin/dashboard', [
            'book' => [
                'outstanding' => (int) Advance::query()->whereIn('status', AdvanceStatus::outstanding())->sum('total_due_baisa'),
                'outstanding_count' => Advance::query()->whereIn('status', AdvanceStatus::outstanding())->count(),
                'overdue' => (int) Advance::query()->where('status', AdvanceStatus::Overdue)->sum('total_due_baisa'),
                'overdue_count' => Advance::query()->where('status', AdvanceStatus::Overdue)->count(),
            ],
            'month' => [
                'label' => $monthStart->toDateString(),
                'disbursed' => (int) LedgerEntry::query()
                    ->where('type', LedgerEntryType::Disbursement)
                    ->where('status', LedgerEntryStatus::Succeeded)
                    ->whereBetween('occurred_at', [$monthStart, $monthEnd])
                    ->sum('amount_baisa'),
                'collected' => (int) (clone $collectionsThisMonth)->where('status', LedgerEntryStatus::Succeeded)->sum('amount_baisa'),
                'fees' => (int) Advance::query()
                    ->where('status', AdvanceStatus::Collected)
                    ->whereBetween('collected_at', [$monthStart, $monthEnd])
                    ->sum('fee_baisa'),
                'collection_failure_rate' => $attempts === 0 ? 0 : round($failedAttempts / $attempts * 100, 1),
            ],
            'network' => [
                'employers_active' => Employer::query()->where('status', EmployerStatus::Active)->count(),
                'employers_pending' => Employer::query()->where('status', EmployerStatus::Pending)->count(),
                'employees_enrolled' => Employee::query()->where('enrolment_status', EnrolmentStatus::Enrolled)->count(),
            ],
            'needsAttention' => AdvanceResource::collection(
                Advance::query()
                    ->with(['employee', 'employer'])
                    ->whereIn('status', [AdvanceStatus::Overdue, AdvanceStatus::CollectionFailed, AdvanceStatus::DisbursementFailed])
                    ->latest('updated_at')
                    ->limit(8)
                    ->get(),
            ),
        ]);
    }
}
