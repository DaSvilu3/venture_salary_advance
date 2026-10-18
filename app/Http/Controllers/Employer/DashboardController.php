<?php

namespace App\Http\Controllers\Employer;

use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Enums\EnrolmentStatus;
use App\Models\Employee;
use App\Support\Clock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends EmployerPortalController
{
    /**
     * Enrolment and usage for HR. Only totals are shown: individual advances stay private to staff.
     */
    public function __invoke(Request $request, Clock $clock, PayCycleCalendar $calendar): Response
    {
        $employer = $this->employer($request);
        $payday = $calendar->paydayOnOrAfter($employer, $clock->today());

        $enrolment = $employer->employees()
            ->selectRaw('enrolment_status, count(*) as total')
            ->groupBy('enrolment_status')
            ->pluck('total', 'enrolment_status');

        $thisCycle = $employer->advances()
            ->whereIn('status', AdvanceStatus::drawnAgainstLimit())
            ->whereHas('payCycle', fn ($cycle) => $cycle->whereDate('payday', $payday));

        $outstanding = $employer->advances()->whereIn('status', AdvanceStatus::outstanding());

        return Inertia::render('employer/dashboard', [
            'employer' => ['name' => $employer->name, 'status' => $employer->status->value],
            'payday' => $payday->toDateString(),
            'enrolment' => collect(EnrolmentStatus::cases())
                ->mapWithKeys(fn (EnrolmentStatus $status): array => [$status->value => (int) ($enrolment[$status->value] ?? 0)]),
            'cycle' => [
                'drawn' => (int) (clone $thisCycle)->sum('amount_baisa'),
                'advances' => (clone $thisCycle)->count(),
                'employees_using' => (clone $thisCycle)->distinct()->count('employee_id'),
            ],
            'collections' => [
                'outstanding' => (int) (clone $outstanding)->sum('total_due_baisa'),
                'employees' => (clone $outstanding)->distinct()->count('employee_id'),
                'overdue' => $employer->advances()->where('status', AdvanceStatus::Overdue)->count(),
            ],
            'pendingApprovals' => $employer->employees()
                ->where('enrolment_status', EnrolmentStatus::PendingApproval)
                ->orderBy('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'staff_no' => $employee->staff_no,
                    'salary_verified' => $employee->salary_verified_at !== null,
                ]),
        ]);
    }
}
