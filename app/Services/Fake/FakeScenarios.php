<?php

namespace App\Services\Fake;

use App\Models\Employee;
use Illuminate\Contracts\Cache\Repository;

/**
 * Forced outcomes for the fake gateways, set from the admin simulation console so failure paths can be
 * demonstrated. Each forced failure is used up by the next matching request.
 */
class FakeScenarios
{
    public const DISBURSEMENT = 'disbursement';

    public const COLLECTION = 'collection';

    public const FUNDING = 'funding';

    public const SALARY_VERIFICATION = 'salary_verification';

    public function __construct(private readonly Repository $cache) {}

    public const OPERATIONS = [self::DISBURSEMENT, self::COLLECTION, self::FUNDING, self::SALARY_VERIFICATION];

    private const INDEX_KEY = 'fake-scenarios.index';

    public function failNext(string $operation, Employee $employee, int $times = 1): void
    {
        $this->cache->forever($this->key($operation, $employee->id), $this->pendingFailures($operation, $employee) + $times);
        $this->remember($operation, $employee->id);
    }

    /**
     * Every forced failure still waiting to be used.
     *
     * @return list<array{operation: string, employee_id: int, remaining: int}>
     */
    public function all(): array
    {
        $pending = [];

        foreach ((array) $this->cache->get(self::INDEX_KEY, []) as $entry) {
            [$operation, $employeeId] = explode(':', (string) $entry);
            $remaining = (int) $this->cache->get($this->key($operation, (int) $employeeId), 0);

            if ($remaining > 0) {
                $pending[] = ['operation' => $operation, 'employee_id' => (int) $employeeId, 'remaining' => $remaining];
            }
        }

        return $pending;
    }

    public function clearAll(): void
    {
        foreach ($this->all() as $entry) {
            $this->cache->forget($this->key($entry['operation'], $entry['employee_id']));
        }

        $this->cache->forget(self::INDEX_KEY);
    }

    private function remember(string $operation, int $employeeId): void
    {
        $index = (array) $this->cache->get(self::INDEX_KEY, []);
        $index[] = "{$operation}:{$employeeId}";

        $this->cache->forever(self::INDEX_KEY, array_values(array_unique($index)));
    }

    public function pendingFailures(string $operation, Employee $employee): int
    {
        return (int) $this->cache->get($this->key($operation, $employee->id), 0);
    }

    /**
     * Use up one forced failure, if any is pending.
     */
    public function shouldFail(string $operation, int $employeeId): bool
    {
        $key = $this->key($operation, $employeeId);
        $remaining = (int) $this->cache->get($key, 0);

        if ($remaining <= 0) {
            return false;
        }

        $remaining === 1 ? $this->cache->forget($key) : $this->cache->forever($key, $remaining - 1);

        return true;
    }

    public function clear(string $operation, Employee $employee): void
    {
        $this->cache->forget($this->key($operation, $employee->id));
    }

    private function key(string $operation, int $employeeId): string
    {
        return "fake-scenarios.{$operation}.{$employeeId}";
    }
}
