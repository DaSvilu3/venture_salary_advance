<?php

namespace App\Domain\Roster;

use App\Models\Employer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Validation for one roster entry, shared by the add-employee form and CSV import.
 */
class RosterRowRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function for(Employer $employer, ?int $ignoreEmployeeId = null): array
    {
        return [
            'staff_no' => ['required', 'string', 'max:50', Rule::unique('employees')->where('employer_id', $employer->id)->ignore($ignoreEmployeeId)],
            'name' => ['required', 'string', 'max:255'],
            'civil_id' => ['required', 'digits_between:8,10', Rule::unique('employees')->where('employer_id', $employer->id)->ignore($ignoreEmployeeId)],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'net_salary' => ['required', 'decimal:0,3', 'min:1', 'max:100000'],
            'employment_start_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
