<?php

namespace App\Http\Requests\Employee;

use App\Rules\OmanIban;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'iban' => ['required', 'string', new OmanIban],
        ];
    }
}
