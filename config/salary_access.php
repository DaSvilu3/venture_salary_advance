<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Programme agreement version
    |--------------------------------------------------------------------------
    |
    | The version of the salary advance programme agreement employers accept.
    |
    */

    'agreement_version' => '2026.1',

    /*
    |--------------------------------------------------------------------------
    | Default program terms
    |--------------------------------------------------------------------------
    |
    | Used when no platform-wide program terms row exists yet. These are
    | placeholders until pricing and limits are settled with the bank, the
    | funder and the Central Bank of Oman. Amounts are in baisa.
    |
    */

    'default_terms' => [
        'limit_ratio_bps' => 1875,
        'max_limit_baisa' => 500_000,
        'min_advance_baisa' => 10_000,
        'fee_model' => 'flat',
        'flat_fee_baisa' => 2_500,
        'min_tenure_months' => 3,
        'cutoff_days_before_payday' => 3,
        'max_draws_per_cycle' => 3,
        'collection_max_attempts' => 3,
        'collection_retry_days' => 2,
        'collection_offset_days' => 0,
        'disbursement_sla_hours' => 24,
        'auto_approve' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration drivers
    |--------------------------------------------------------------------------
    |
    | Only the "fake" drivers exist. They stand in for the bank (CBO Direct
    | Debit and Mandate Management System, ACH), the funder and payroll (WPS)
    | until real partners are signed.
    |
    */

    'drivers' => [
        'bank' => env('SALARY_ACCESS_BANK_DRIVER', 'fake'),
        'funder' => env('SALARY_ACCESS_FUNDER_DRIVER', 'fake'),
        'payroll' => env('SALARY_ACCESS_PAYROLL_DRIVER', 'fake'),
    ],

];
