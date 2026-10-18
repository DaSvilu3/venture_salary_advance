<?php

namespace Tests\Feature\Http;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnsureRoleTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Every portal paired with every role that must not reach it.
     *
     * @return array<string, array{string, Role}>
     */
    public static function forbiddenPortalVisits(): array
    {
        $portals = [
            '/app' => Role::Employee,
            '/employer' => Role::EmployerAdmin,
            '/admin' => Role::PlatformAdmin,
            '/partner' => Role::PartnerViewer,
        ];

        $cases = [];

        foreach ($portals as $path => $owner) {
            foreach (Role::cases() as $role) {
                if ($role !== $owner) {
                    $cases["{$role->value} visiting {$path}"] = [$path, $role];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('forbiddenPortalVisits')]
    public function test_forbids_roles_from_other_portals(string $path, Role $role): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->get($path)
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_from_a_portal(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }
}
