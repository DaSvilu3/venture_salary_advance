<?php

namespace App\Http\Resources;

use App\Models\Advance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Advance
 */
class AdvanceResource extends JsonResource
{
    /**
     * Amounts are integer baisa; dates are ISO 8601.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'amount' => $this->amount_baisa,
            'fee' => $this->fee_baisa,
            'total_due' => $this->total_due_baisa,
            'due_date' => $this->due_date->toDateString(),
            'requested_at' => $this->requested_at->toIso8601String(),
            'disbursed_at' => $this->disbursed_at?->toIso8601String(),
            'collected_at' => $this->collected_at?->toIso8601String(),
            'collection_attempts' => $this->collection_attempts,
            'next_collection_attempt_on' => $this->next_collection_attempt_on?->toDateString(),
            'failure_reason' => $this->failure_reason,
            'employee' => $this->whenLoaded('employee', fn (): array => [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'staff_no' => $this->employee->staff_no,
            ]),
            'employer' => $this->whenLoaded('employer', fn (): array => [
                'id' => $this->employer->id,
                'name' => $this->employer->name,
            ]),
        ];
    }
}
