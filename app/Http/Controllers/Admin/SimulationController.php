<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Advances\QueueDueCollections;
use App\Domain\Payroll\PayCycleCalendar;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Employer;
use App\Services\Fake\FakeScenarios;
use App\Support\Clock;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Demo controls: move the clock, run payday collections, and force gateway failures.
 */
class SimulationController extends Controller
{
    public function show(Clock $clock, FakeScenarios $scenarios, PayCycleCalendar $calendar): Response
    {
        $failures = $scenarios->all();
        $names = Employee::query()->whereIn('id', array_column($failures, 'employee_id'))->pluck('name', 'id');

        return Inertia::render('admin/simulation', [
            'demoClock' => [
                'today' => $clock->today()->toDateString(),
                'real_today' => CarbonImmutable::today()->toDateString(),
                'is_simulated' => $clock->isSimulated(),
            ],
            'employers' => Employer::query()->orderBy('name')->get()->map(fn (Employer $employer): array => [
                'id' => $employer->id,
                'name' => $employer->name,
                'next_payday' => $calendar->paydayOnOrAfter($employer, $clock->today())->toDateString(),
            ]),
            'employees' => Employee::query()->whereNotNull('user_id')->orderBy('name')->get(['id', 'name', 'staff_no']),
            'operations' => FakeScenarios::OPERATIONS,
            'failures' => array_map(fn (array $failure): array => [
                ...$failure,
                'employee' => $names[$failure['employee_id']] ?? '#'.$failure['employee_id'],
            ], $failures),
        ]);
    }

    public function updateClock(Request $request, Clock $clock): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date']]);
        $clock->travelTo(CarbonImmutable::parse($data['date']));
        AuditLog::record('simulation.clock_moved', null, $request->user(), ['date' => $data['date']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Demo clock set to :date.', ['date' => $data['date']])]);

        return to_route('admin.simulation.show');
    }

    public function resetClock(Request $request, Clock $clock): RedirectResponse
    {
        $clock->reset();
        AuditLog::record('simulation.clock_reset', null, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Demo clock is back to the real date.')]);

        return to_route('admin.simulation.show');
    }

    public function runCollections(Request $request, QueueDueCollections $collections): RedirectResponse
    {
        $result = $collections->handle();
        AuditLog::record('simulation.collections_run', null, $request->user(), $result);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':queued collections sent to the bank and :closed pay cycles closed.', [
            'queued' => $result['queued'],
            'closed' => $result['closed'],
        ])]);

        return to_route('admin.simulation.show');
    }

    public function storeFailure(Request $request, FakeScenarios $scenarios): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'operation' => ['required', Rule::in(FakeScenarios::OPERATIONS)],
            'times' => ['required', 'integer', 'between:1,5'],
        ]);

        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();
        $scenarios->failNext($data['operation'], $employee, (int) $data['times']);
        AuditLog::record('simulation.failure_forced', $employee, $request->user(), $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('The next :operation for :name will fail.', ['operation' => $data['operation'], 'name' => $employee->name])]);

        return to_route('admin.simulation.show');
    }

    public function clearFailures(FakeScenarios $scenarios): RedirectResponse
    {
        $scenarios->clearAll();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Forced failures cleared.')]);

        return to_route('admin.simulation.show');
    }
}
