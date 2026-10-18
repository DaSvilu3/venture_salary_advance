<?php

namespace App\Http\Controllers\Partner;

use App\Enums\AdvanceStatus;
use App\Http\Resources\AdvanceResource;
use App\Models\Advance;
use App\Models\Employer;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdvanceController extends PartnerPortalController
{
    public function index(Request $request): Response
    {
        $partner = $this->partner($request);
        $filters = $this->filters($request);

        return Inertia::render('partner/advances', [
            'advances' => AdvanceResource::collection(
                $this->filtered($partner, $filters)->with(['employee:id,name,staff_no', 'employer:id,name'])->paginate(25)->withQueryString(),
            ),
            'filters' => [
                'status' => $filters['status'] ?? '',
                'employer' => isset($filters['employer']) ? (string) $filters['employer'] : '',
            ],
            'employers' => Employer::query()
                ->whereIn('id', $this->advances($partner)->select('employer_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statuses' => array_column(AdvanceStatus::cases(), 'value'),
        ]);
    }

    /**
     * The same filtered list as a CSV file, for the partner's own reconciliation.
     */
    public function export(Request $request): StreamedResponse
    {
        $partner = $this->partner($request);
        $query = $this->filtered($partner, $this->filters($request))->with(['employee:id,name,staff_no', 'employer:id,name']);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ['reference', 'employer', 'staff_no', 'employee', 'amount_omr', 'fee_omr', 'total_due_omr', 'due_date', 'status', 'disbursed_at', 'collected_at', 'collection_attempts']);

            $query->chunk(500, function ($advances) use ($out): void {
                foreach ($advances as $advance) {
                    /** @var Advance $advance */
                    fputcsv($out, [
                        $advance->reference,
                        $advance->employer->name,
                        $advance->employee->staff_no,
                        $advance->employee->name,
                        $advance->amount_baisa->toOmrString(),
                        $advance->fee_baisa->toOmrString(),
                        $advance->total_due_baisa->toOmrString(),
                        $advance->due_date->toDateString(),
                        $advance->status->value,
                        $advance->disbursed_at?->toIso8601String(),
                        $advance->collected_at?->toIso8601String(),
                        $advance->collection_attempts,
                    ]);
                }
            });

            fclose($out);
        }, sprintf('advances-%s-%s.csv', strtolower($partner->code), now()->format('Ymd')), ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{status?: string, employer?: int}
     */
    private function filters(Request $request): array
    {
        /** @var array{status?: string, employer?: int} */
        return $request->validate([
            'status' => ['nullable', Rule::enum(AdvanceStatus::class)],
            'employer' => ['nullable', 'integer'],
        ]);
    }

    /**
     * @param  array{status?: string, employer?: int}  $filters
     * @return Builder<Advance>
     */
    private function filtered(Partner $partner, array $filters): Builder
    {
        return $this->advances($partner)
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['employer'] ?? null, fn ($query, int $employerId) => $query->where('employer_id', $employerId))
            ->latest('requested_at')
            ->latest('id');
    }
}
