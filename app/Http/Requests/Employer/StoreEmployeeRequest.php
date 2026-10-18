<?php

namespace App\Http\Requests\Employer;

use App\Domain\Roster\RosterRowRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return RosterRowRules::for($this->user()->employer);
    }
}
