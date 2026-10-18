<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{Role, string}>
     */
    public static function portalHomes(): array
    {
        return [
            'employee' => [Role::Employee, '/app'],
            'employer admin' => [Role::EmployerAdmin, '/employer'],
            'platform admin' => [Role::PlatformAdmin, '/admin'],
            'partner viewer' => [Role::PartnerViewer, '/partner'],
        ];
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    #[DataProvider('portalHomes')]
    public function test_dashboard_redirects_each_role_to_its_portal(Role $role, string $portalHome): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect($portalHome);

        $this->actingAs($user)
            ->get($portalHome)
            ->assertOk();
    }
}
