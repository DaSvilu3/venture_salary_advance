<?php

namespace Tests\Feature\Http\Employer;

use App\Enums\AgreementStatus;
use App\Models\Employer;
use App\Models\EmployerAgreement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AgreementControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_agreement_is_created_pending_on_first_use(): void
    {
        $employer = Employer::factory()->create();

        $agreement = EmployerAgreement::forEmployer($employer);

        $this->assertSame(AgreementStatus::Pending, $agreement->status);
        $this->assertSame(config('salary_access.agreement_version'), $agreement->version);
        $this->assertSame($agreement->id, EmployerAgreement::forEmployer($employer)->id);
    }

    public function test_the_agreement_page_renders_for_the_employer(): void
    {
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->get(route('employer.agreement.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('agreements/show')
                ->where('context', 'employer')
                ->where('employer.id', $hr->employer->id)
                ->where('agreement.status', AgreementStatus::Pending->value)
                ->where('agreement.signed', null)
            );
    }

    public function test_accepting_records_the_decision_and_audits_it(): void
    {
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->post(route('employer.agreement.accept'))
            ->assertRedirect();

        $agreement = $hr->employer->agreement()->sole();
        $this->assertSame(AgreementStatus::Accepted, $agreement->status);
        $this->assertNotNull($agreement->decided_at);
        $this->assertNull($agreement->reject_reason);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employer_agreement.accepted', 'actor_id' => $hr->id]);
    }

    public function test_rejecting_requires_a_reason(): void
    {
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->post(route('employer.agreement.reject'), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(AgreementStatus::Pending, EmployerAgreement::forEmployer($hr->employer)->status);
    }

    public function test_rejecting_with_a_reason_records_it(): void
    {
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->post(route('employer.agreement.reject'), ['reason' => 'The fee is too high.'])
            ->assertRedirect();

        $agreement = $hr->employer->agreement()->sole();
        $this->assertSame(AgreementStatus::Rejected, $agreement->status);
        $this->assertSame('The fee is too high.', $agreement->reject_reason);
        $this->assertNotNull($agreement->decided_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employer_agreement.rejected', 'actor_id' => $hr->id]);
    }

    public function test_uploading_a_signed_copy_stores_it_privately(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->post(route('employer.agreement.signed.store'), [
                'file' => UploadedFile::fake()->create('signed-agreement.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $agreement = $hr->employer->agreement()->sole();
        $this->assertStringStartsWith("employer-agreements/{$hr->employer->id}/", $agreement->signed_path);
        $this->assertSame('signed-agreement.pdf', $agreement->signed_name);
        $this->assertNotNull($agreement->signed_uploaded_at);
        Storage::disk('local')->assertExists($agreement->signed_path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employer_agreement.signed_uploaded', 'actor_id' => $hr->id]);
    }

    public function test_the_signed_copy_must_be_a_pdf_under_five_megabytes(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->post(route('employer.agreement.signed.store'), [
                'file' => UploadedFile::fake()->create('scan.png', 10, 'image/png'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($hr)
            ->post(route('employer.agreement.signed.store'), [
                'file' => UploadedFile::fake()->create('large.pdf', 6_000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertNull($hr->employer->agreement()->first()?->signed_path);
    }

    public function test_downloading_without_a_signed_copy_is_not_found(): void
    {
        $hr = $this->hrUser();

        $this->actingAs($hr)
            ->get(route('employer.agreement.signed.download'))
            ->assertNotFound();
    }

    public function test_the_employer_downloads_its_signed_copy(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();
        Storage::disk('local')->put('employer-agreements/x/signed.pdf', 'contents');
        EmployerAgreement::factory()->for($hr->employer)->accepted()->create([
            'signed_path' => 'employer-agreements/x/signed.pdf',
            'signed_name' => 'signed-agreement.pdf',
            'signed_uploaded_at' => now(),
        ]);

        $this->actingAs($hr)
            ->get(route('employer.agreement.signed.download'))
            ->assertOk()
            ->assertDownload('signed-agreement.pdf');
    }

    private function hrUser(): User
    {
        return User::factory()->employerAdmin()->for(Employer::factory())->create();
    }
}
