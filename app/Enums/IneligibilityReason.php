<?php

namespace App\Enums;

/**
 * Why an employee cannot take an advance right now.
 */
enum IneligibilityReason: string
{
    case EmployerNotActive = 'employer_not_active';
    case NotEnrolled = 'not_enrolled';
    case EmploymentNotActive = 'employment_not_active';
    case SalaryNotVerified = 'salary_not_verified';
    case NoActiveMandate = 'no_active_mandate';
    case TenureTooShort = 'tenure_too_short';
    case HasUnpaidAdvance = 'has_unpaid_advance';
    case CutoffPassed = 'cutoff_passed';
    case MaxDrawsReached = 'max_draws_reached';
    case LimitReached = 'limit_reached';

    /**
     * A message for the employee. It is also the English translation key.
     */
    public function message(): string
    {
        return match ($this) {
            self::EmployerNotActive => __('Your employer has not activated salary advances yet.'),
            self::NotEnrolled => __('Your enrolment has not been approved yet.'),
            self::EmploymentNotActive => __('Advances are not available during a notice period or after leaving.'),
            self::SalaryNotVerified => __('Your salary has not been verified yet.'),
            self::NoActiveMandate => __('Set up your direct-debit mandate to use advances.'),
            self::TenureTooShort => __('You can use advances after completing your minimum time in the job.'),
            self::HasUnpaidAdvance => __('An earlier advance has not been repaid yet.'),
            self::CutoffPassed => __('Advances reopen after payday.'),
            self::MaxDrawsReached => __('You have used the maximum number of advances this pay cycle.'),
            self::LimitReached => __('You have used your full limit for this pay cycle.'),
        };
    }
}
