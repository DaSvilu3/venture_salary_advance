<?php

namespace App\Http\Requests\Employer;

use App\Domain\Roster\RosterRowRules;
use App\Enums\EmploymentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Only staff on the HR user's own roster can be edited.
     */
    public function authorize(): bool
    {
        return $this->user()->employer?->employees()->whereKey($this->route('employee'))->exists() ?? false;
    }

    /**
     * Report other employers' staff as not found rather than forbidden, so their existence is not revealed.
     */
    protected function failedAuthorization(): never
    {
        throw new NotFoundHttpException;
    }

    /**
     * Staff number and civil ID identify the person and are not edited here.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...Arr::except(RosterRowRules::for($this->user()->employer), ['staff_no', 'civil_id']),
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
        ];
    }
}
