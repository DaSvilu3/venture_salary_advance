<?php

namespace Tests\Feature\Http\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WorkflowControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_platform_admin_sees_the_workflow_overview(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.workflow.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/workflow')
                ->has('terms')
                ->has('counts')
                ->has('editableTerms.max_limit'));
    }

    public function test_non_admin_cannot_view_the_workflow_overview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.workflow.show'))
            ->assertForbidden();
    }
}
