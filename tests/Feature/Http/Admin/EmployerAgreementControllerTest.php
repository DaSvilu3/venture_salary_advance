<?php

namespace Tests\Feature\Http\Admin;

use App\Models\Employer;
use App\Models\EmployerAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EmployerAgreementControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_agreement_page_renders_read_only_for_the_platform(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.employers.agreement.show', $employer))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agreements/show')
                ->where('context', 'admin')
                ->where('employer.id', $employer->id)
                ->where('agreement.status', 'pending')
            );
    }

    public function test_admin_downloads_the_employers_signed_copy(): void
    {
        Storage::fake('local');
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();
        Storage::disk('local')->put('employer-agreements/x/signed.pdf', 'contents');
        EmployerAgreement::factory()->for($employer)->accepted()->create([
            'signed_path' => 'employer-agreements/x/signed.pdf',
            'signed_name' => 'signed-agreement.pdf',
            'signed_uploaded_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.employers.agreement.signed.download', $employer))
            ->assertOk()
            ->assertDownload('signed-agreement.pdf');
    }

    public function test_downloading_without_a_signed_copy_is_not_found(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.employers.agreement.signed.download', $employer))
            ->assertNotFound();
    }
}
