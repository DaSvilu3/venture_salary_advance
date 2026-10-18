<?php

namespace Database\Seeders;

use App\Domain\Payroll\PayCycleCalendar;
use App\Enums\AdvanceStatus;
use App\Enums\EnrolmentStatus;
use App\Enums\LedgerEntryStatus;
use App\Enums\LedgerEntryType;
use App\Models\Advance;
use App\Models\Disclosure;
use App\Models\Employee;
use App\Models\Employer;
use App\Models\Partner;
use App\Models\ProgramTerms;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Fictional demo data for walking through every portal. All accounts use the password "password".
 */
class DemoSeeder extends Seeder
{
    /**
     * Omani first and family names for the fictional rosters.
     */
    private const FIRST_NAMES = [
        'Salim', 'Aisha', 'Khalid', 'Maryam', 'Hamed', 'Fatma', 'Said', 'Zainab', 'Yousuf', 'Noor',
        'Ahmed', 'Shamsa', 'Hilal', 'Muna', 'Talal', 'Asma', 'Badar', 'Huda', 'Nasser', 'Rahma',
    ];

    private const FAMILY_NAMES = [
        'Al Harthy', 'Al Balushi', 'Al Rawahi', 'Al Hinai', 'Al Busaidi', 'Al Kindi', 'Al Maskari', 'Al Siyabi',
        'Al Lawati', 'Al Shukaili', 'Al Abri', 'Al Riyami',
    ];

    public function __construct(private readonly PayCycleCalendar $calendar) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProgramTerms::factory()->create();

        $disclosure = Disclosure::factory()->create([
            'version' => '2026.1',
            'body_en' => "This is a salary advance, not a loan product sold by AWJ. The amount you choose and the fixed service fee shown are collected in full from your bank account by direct debit on your next payday.\n\nThe advance is paid and collected by our licensed bank partner and funded by our approved funding partner. If a collection fails we will retry up to two more times; while an advance is unpaid you cannot take a new one.\n\nYou can see every advance and its status in the app at any time.",
            'body_ar' => "هذه سلفة على الراتب وليست منتجاً تمويلياً تبيعه أوج. يُحصَّل المبلغ الذي تختاره ورسوم الخدمة الثابتة الموضّحة كاملةً من حسابك المصرفي عبر الخصم المباشر في يوم راتبك القادم.\n\nيدفع البنك الشريك المرخَّص السلفة ويحصّلها، ويموّلها شريك التمويل المعتمد. إذا تعذّر التحصيل نعيد المحاولة مرتين إضافيتين كحد أقصى، ولا يمكنك أخذ سلفة جديدة ما دامت سلفة سابقة غير مسددة.\n\nيمكنك الاطلاع على كل سلفة وحالتها في التطبيق في أي وقت.",
            'published_at' => now()->subMonths(2),
        ]);

        $bank = Partner::factory()->bank()->create([
            'name' => 'Demo National Bank',
            'code' => 'DNB',
            'brand_color' => '#0f766e',
        ]);

        $funder = Partner::factory()->funder()->create([
            'name' => 'Demo Capital Partners',
            'code' => 'DCP',
            'brand_color' => '#4338ca',
        ]);

        $alNoor = Employer::factory()->paydayOn(25)->create([
            'name' => 'Al Noor Trading LLC',
            'cr_number' => '1234567',
            'bank_partner_id' => $bank->id,
            'funder_partner_id' => $funder->id,
        ]);

        $majan = Employer::factory()->paydayOn(28)->create([
            'name' => 'Majan Logistics SAOC',
            'cr_number' => '1345678',
            'bank_partner_id' => $bank->id,
            'funder_partner_id' => $funder->id,
        ]);

        Employer::factory()->pending()->create([
            'name' => 'Ras Al Hadd Hospitality LLC',
            'cr_number' => '1456789',
            'bank_partner_id' => $bank->id,
            'funder_partner_id' => $funder->id,
        ]);

        User::factory()->platformAdmin()->create(['name' => 'Platform Admin', 'email' => 'admin@demo.test']);
        User::factory()->employerAdmin()->create(['name' => 'Huda Al Balushi', 'email' => 'hr@alnoor.demo.test', 'employer_id' => $alNoor->id]);
        User::factory()->employerAdmin()->create(['name' => 'Badar Al Kindi', 'email' => 'hr@majan.demo.test', 'employer_id' => $majan->id]);
        User::factory()->partnerViewer()->create(['name' => 'Bank Viewer', 'email' => 'bank@demo.test', 'partner_id' => $bank->id]);
        User::factory()->partnerViewer()->create(['name' => 'Funder Viewer', 'email' => 'funder@demo.test', 'partner_id' => $funder->id]);

        $salim = Employee::factory()->for($alNoor)->enrolled()->earning(Money::omr(800))->create([
            'name' => 'Salim Al Harthy',
            'email' => 'salim@alnoor.demo.test',
            'staff_no' => 'AN-1001',
            'civil_id' => '10234567',
            'employment_start_date' => now()->subYears(3),
        ]);
        $salim->user->update(['name' => $salim->name, 'email' => $salim->email]);

        Employee::factory()->for($alNoor)->pendingApproval()->earning(Money::omr(650))->create([
            'name' => 'Aisha Al Rawahi',
            'email' => 'aisha@alnoor.demo.test',
            'staff_no' => 'AN-1002',
        ])->user->update(['name' => 'Aisha Al Rawahi', 'email' => 'aisha@alnoor.demo.test']);

        Employee::factory()->for($alNoor)->earning(Money::omr(720))->create([
            'name' => 'Khalid Al Hinai',
            'email' => 'khalid@alnoor.demo.test',
            'staff_no' => 'AN-1003',
            'civil_id' => '10348061',
            'invitation_token' => 'demo-invite-khalid',
        ]);

        $this->seedRoster($alNoor, 'AN', 24, $disclosure);
        $this->seedRoster($majan, 'MJ', 14, $disclosure);
    }

    /**
     * A roster of enrolled, invited and departing staff with one cycle of advance history.
     */
    private function seedRoster(Employer $employer, string $prefix, int $count, Disclosure $disclosure): void
    {
        $today = CarbonImmutable::now();
        $currentPayday = $this->calendar->paydayOnOrAfter($employer, $today);
        $previousPayday = $this->calendar->paydayOnOrAfter($employer, $currentPayday->subMonthNoOverflow()->startOfMonth());
        $currentCycle = $this->calendar->cycleEndingOn($employer, $currentPayday);
        $previousCycle = $this->calendar->cycleEndingOn($employer, $previousPayday);

        foreach (range(1, $count) as $index) {
            $name = self::FIRST_NAMES[($index * 7) % count(self::FIRST_NAMES)].' '.self::FAMILY_NAMES[($index * 5) % count(self::FAMILY_NAMES)];
            $salary = Money::omr(350 + ($index * 37) % 1400);
            $attributes = ['name' => $name, 'staff_no' => sprintf('%s-%04d', $prefix, 2000 + $index)];

            $factory = Employee::factory()->for($employer)->earning($salary);

            $employee = match (true) {
                $index % 9 === 0 => $factory->create($attributes),
                $index % 11 === 0 => $factory->pendingApproval()->create($attributes),
                $index % 13 === 0 => $factory->enrolled()->onNotice()->create($attributes),
                default => $factory->enrolled()->create($attributes),
            };

            if ($employee->enrolment_status !== EnrolmentStatus::Enrolled) {
                continue;
            }

            if ($index % 2 === 0) {
                $this->historicAdvance($employee, $previousCycle->id, $previousPayday, $salary, $disclosure, $index % 20 === 4
                    ? AdvanceStatus::Overdue
                    : AdvanceStatus::Collected);
            }

            if ($index % 3 === 0 && $index % 20 !== 4) {
                $this->historicAdvance($employee, $currentCycle->id, $currentPayday, $salary, $disclosure, AdvanceStatus::Disbursed);
            }
        }
    }

    private function historicAdvance(
        Employee $employee,
        int $payCycleId,
        CarbonImmutable $payday,
        Money $salary,
        Disclosure $disclosure,
        AdvanceStatus $status,
    ): void {
        $amount = Money::baisa(intdiv($salary->multiplyBasisPoints(1200)->baisa, 5000) * 5000)->max(Money::omr(20));
        $requestedAt = $payday->subDays(12)->setTime(9, 30);
        $collected = $status === AdvanceStatus::Collected;

        $advance = Advance::factory()->for($employee)->forAmount($amount)->inStatus($status)->create([
            'pay_cycle_id' => $payCycleId,
            'disclosure_id' => $disclosure->id,
            'due_date' => $payday,
            'requested_at' => $requestedAt,
            'disclosure_accepted_at' => $requestedAt,
            'disbursed_at' => $requestedAt->addMinutes(2),
            'collected_at' => $collected ? $payday->setTime(6, 5) : null,
            'collection_attempts' => match ($status) {
                AdvanceStatus::Collected => 1,
                AdvanceStatus::Overdue => 3,
                default => 0,
            },
            'next_collection_attempt_on' => $status === AdvanceStatus::Disbursed ? $payday : null,
            'failure_reason' => $status === AdvanceStatus::Overdue ? 'Insufficient funds in the account.' : null,
        ]);

        $advance->ledgerEntries()->create([
            'type' => LedgerEntryType::Disbursement,
            'status' => LedgerEntryStatus::Succeeded,
            'amount_baisa' => $amount,
            'provider' => 'fake-bank',
            'external_reference' => 'ACH-DEMO'.$advance->id,
            'occurred_at' => $requestedAt->addMinutes(2),
        ]);

        if ($status !== AdvanceStatus::Disbursed) {
            $advance->ledgerEntries()->create([
                'type' => LedgerEntryType::Collection,
                'status' => $collected ? LedgerEntryStatus::Succeeded : LedgerEntryStatus::Failed,
                'amount_baisa' => $advance->total_due_baisa,
                'provider' => 'fake-bank',
                'external_reference' => 'DD-DEMO'.$advance->id,
                'failure_reason' => $collected ? null : 'Insufficient funds in the account.',
                'occurred_at' => $payday->setTime(6, 5),
            ]);
        }
    }
}
