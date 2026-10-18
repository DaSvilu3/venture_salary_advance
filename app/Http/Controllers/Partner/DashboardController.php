<?php

namespace App\Http\Controllers\Partner;

use App\Enums\AdvanceStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Support\Clock;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends PartnerPortalController
{
    private const TREND_MONTHS = 6;

    public function __invoke(Request $request, Clock $clock): Response
    {
        $partner = $this->partner($request);
        $today = $clock->today();
        $advances = fn () => $this->advances($partner);

        $unpaid = $advances()
            ->whereIn('status', [AdvanceStatus::CollectionFailed, AdvanceStatus::Overdue])
            ->get(['id', 'due_date', 'total_due_baisa']);

        $ageing = ['1_7' => 0, '8_30' => 0, '31_plus' => 0];

        foreach ($unpaid as $advance) {
            $daysLate = (int) $advance->due_date->diffInDays($today);
            $bucket = match (true) {
                $daysLate <= 7 => '1_7',
                $daysLate <= 30 => '8_30',
                default => '31_plus',
            };
            $ageing[$bucket] += $advance->total_due_baisa->baisa;
        }

        return Inertia::render('partner/dashboard', [
            'partner' => ['name' => $partner->name, 'type' => $partner->type->value],
            'book' => [
                'outstanding' => (int) $advances()->whereIn('status', AdvanceStatus::outstanding())->sum('total_due_baisa'),
                'outstanding_count' => $advances()->whereIn('status', AdvanceStatus::outstanding())->count(),
                'overdue' => (int) $advances()->where('status', AdvanceStatus::Overdue)->sum('total_due_baisa'),
                'collected_total' => (int) $advances()->where('status', AdvanceStatus::Collected)->sum('total_due_baisa'),
                'written_off' => (int) $advances()->where('status', AdvanceStatus::WrittenOff)->sum('total_due_baisa'),
            ],
            'ageing' => $ageing,
            'exposure' => $advances()
                ->whereIn('advances.status', AdvanceStatus::outstanding())
                ->join('employers', 'employers.id', '=', 'advances.employer_id')
                ->selectRaw('employers.id as employer_id, employers.name as employer, count(*) as advances, sum(advances.total_due_baisa) as outstanding')
                ->selectRaw("sum(case when advances.status = 'overdue' then advances.total_due_baisa else 0 end) as overdue")
                ->groupBy('employers.id', 'employers.name')
                ->orderByDesc('outstanding')
                ->toBase()
                ->get()
                ->map(fn (object $row): array => [
                    'employer_id' => (int) $row->employer_id,
                    'employer' => (string) $row->employer,
                    'advances' => (int) $row->advances,
                    'outstanding' => (int) $row->outstanding,
                    'overdue' => (int) $row->overdue,
                ]),
            'trend' => $this->trend($request, $clock),
        ]);
    }

    /**
     * Paid out and collected per month for the last six months, oldest first.
     *
     * @return list<array{month: string, disbursed: int, collected: int}>
     */
    private function trend(Request $request, Clock $clock): array
    {
        $partner = $this->partner($request);
        $firstMonth = $clock->today()->startOfMonth()->subMonthsNoOverflow(self::TREND_MONTHS - 1);

        $months = [];

        for ($offset = 0; $offset < self::TREND_MONTHS; $offset++) {
            $months[$firstMonth->addMonthsNoOverflow($offset)->format('Y-m')] = ['disbursed' => 0, 'collected' => 0];
        }

        LedgerEntry::query()
            ->whereIn('advance_id', $this->advances($partner)->select('id'))
            ->where('status', LedgerEntryStatus::Succeeded)
            ->whereIn('type', [LedgerEntryType::Disbursement, LedgerEntryType::Collection])
            ->where('occurred_at', '>=', $firstMonth)
            ->get(['type', 'amount_baisa', 'occurred_at'])
            ->each(function (LedgerEntry $entry) use (&$months): void {
                $month = $entry->occurred_at->format('Y-m');

                if (isset($months[$month])) {
                    $key = $entry->type === LedgerEntryType::Disbursement ? 'disbursed' : 'collected';
                    $months[$month][$key] += $entry->amount_baisa->baisa;
                }
            });

        return array_map(
            fn (string $month, array $totals): array => ['month' => $month, ...$totals],
            array_keys($months),
            array_values($months),
        );
    }
}
