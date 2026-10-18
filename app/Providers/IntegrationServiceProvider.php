<?php

namespace App\Providers;

use App\Contracts\BankGateway;
use App\Contracts\FunderGateway;
use App\Contracts\PayrollVerifier;
use App\Services\Fake\FakeBankGateway;
use App\Services\Fake\FakeFunderGateway;
use App\Services\Fake\FakePayrollVerifier;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Binds the bank, funder and payroll integrations to the driver chosen in config/salary_access.php.
 * Only fake drivers exist while the product is a prototype.
 */
class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<string, class-string>>
     */
    private const DRIVERS = [
        BankGateway::class => ['fake' => FakeBankGateway::class],
        FunderGateway::class => ['fake' => FakeFunderGateway::class],
        PayrollVerifier::class => ['fake' => FakePayrollVerifier::class],
    ];

    /**
     * @var array<class-string, string>
     */
    private const CONFIG_KEYS = [
        BankGateway::class => 'salary_access.drivers.bank',
        FunderGateway::class => 'salary_access.drivers.funder',
        PayrollVerifier::class => 'salary_access.drivers.payroll',
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach (self::DRIVERS as $contract => $drivers) {
            $this->app->bind($contract, function ($app) use ($contract, $drivers) {
                $driver = (string) config(self::CONFIG_KEYS[$contract]);

                if (! isset($drivers[$driver])) {
                    throw new InvalidArgumentException("No [{$driver}] driver exists for [{$contract}].");
                }

                return $app->make($drivers[$driver]);
            });
        }
    }
}
