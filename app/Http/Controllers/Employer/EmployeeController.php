<?php

namespace App\Http\Controllers\Employer;

use App\Domain\Roster\AddEmployee;
use App\Enums\EmploymentStatus;
use App\Enums\EnrolmentStatus;
use App\Http\Requests\Employer\StoreEmployeeRequest;
use App\Http\Requests\Employer\UpdateEmployeeRequest;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Support\Clock;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends EmployerPortalController
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(EnrolmentStatus::class)],
        ]);

        $employees = $this->employer($request)->employees()
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('staff_no', 'like', "%{$search}%"),
            ))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('enrolment_status', $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Employee $employee): array => $this->row($employee));

        return Inertia::render('employer/employees/index', [
            'employees' => $employees,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'importResult' => $request->session()->get('import_result'),
        ]);
    }

    public function store(StoreEmployeeRequest $request, AddEmployee $addEmployee): RedirectResponse
    {
        $employee = $addEmployee->handle($this->employer($request), $request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was added and invited.', ['name' => $employee->name])]);

        return to_route('employer.employees.show', $employee->id);
    }

    public function show(Request $request, int $employee): Response
    {
        $employee = $this->rosterEmployee($request, $employee);

        return Inertia::render('employer/employees/show', [
            'employee' => [
                ...$this->row($employee),
                'phone' => $employee->phone,
                'civil_id' => $employee->maskedCivilId(),
                'employment_start_date' => $employee->employment_start_date->toDateString(),
                'salary_verified' => $employee->salary_verified_at !== null,
                'has_mandate' => $employee->activeMandate !== null,
            ],
            'history' => AuditLog::query()
                ->whereMorphedTo('subject', $employee)
                ->with('actor:id,name')
                ->latest('id')
                ->limit(20)
                ->get()
                ->map(fn (AuditLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor?->name,
                    'at' => $log->created_at?->toIso8601String(),
                ]),
            'employmentStatuses' => array_column(EmploymentStatus::cases(), 'value'),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, int $employee, Clock $clock): RedirectResponse
    {
        $employee = $this->rosterEmployee($request, $employee);
        $data = $request->validated();
        $salary = Money::omr((string) $data['net_salary']);

        $employee->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'employment_start_date' => $data['employment_start_date'],
            'employment_status' => $data['employment_status'],
            'net_salary_baisa' => $salary,
        ]);

        if ($employee->isDirty('net_salary_baisa')) {
            $employee->salary_verified_at = $clock->now();
        }

        $changed = array_keys($employee->getDirty());
        $employee->save();

        if ($changed !== []) {
            AuditLog::record('employee.updated', $employee, $request->user(), ['changed' => $changed]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Employee details saved.')]);

        return to_route('employer.employees.show', $employee->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'staff_no' => $employee->staff_no,
            'email' => $employee->email,
            'net_salary' => $employee->net_salary_baisa,
            'employment_status' => $employee->employment_status->value,
            'enrolment_status' => $employee->enrolment_status->value,
            'has_account' => $employee->user_id !== null,
            'invited_at' => $employee->invited_at?->toIso8601String(),
            'invitation_url' => $employee->user_id === null && $employee->invitation_token !== null
                ? route('invitation.show', $employee->invitation_token)
                : null,
        ];
    }
}
