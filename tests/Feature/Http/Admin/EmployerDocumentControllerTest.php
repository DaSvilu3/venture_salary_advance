<?php

namespace Tests\Feature\Http\Admin;

use App\Enums\DocumentStatus;
use App\Models\Employer;
use App\Models\EmployerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployerDocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_approving_a_document_records_the_reviewer(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();
        $document = EmployerDocument::factory()->for($employer)->create();

        $this->actingAs($admin)
            ->post(route('admin.employers.documents.approve', [$employer, $document]))
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(DocumentStatus::Approved, $document->status);
        $this->assertSame($admin->id, $document->reviewed_by);
        $this->assertNotNull($document->reviewed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employer_document.approved', 'actor_id' => $admin->id]);
    }

    public function test_rejecting_a_document_requires_a_note(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();
        $document = EmployerDocument::factory()->for($employer)->create();

        $this->actingAs($admin)
            ->post(route('admin.employers.documents.reject', [$employer, $document]), ['note' => ''])
            ->assertSessionHasErrors('note');

        $this->assertSame(DocumentStatus::Pending, $document->fresh()->status);
    }

    public function test_rejecting_a_document_with_a_note_records_it(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();
        $document = EmployerDocument::factory()->for($employer)->create();

        $this->actingAs($admin)
            ->post(route('admin.employers.documents.reject', [$employer, $document]), ['note' => 'Certificate expired in 2024.'])
            ->assertRedirect();

        $document->refresh();
        $this->assertSame(DocumentStatus::Rejected, $document->status);
        $this->assertSame('Certificate expired in 2024.', $document->review_note);
        $this->assertSame($admin->id, $document->reviewed_by);
    }

    public function test_a_document_is_not_reachable_through_another_employer(): void
    {
        $admin = User::factory()->platformAdmin()->create();
        $document = EmployerDocument::factory()->create();
        $otherEmployer = Employer::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.employers.documents.approve', [$otherEmployer, $document]))
            ->assertNotFound();

        $this->assertSame(DocumentStatus::Pending, $document->fresh()->status);
    }

    public function test_admin_downloads_an_employers_document(): void
    {
        Storage::fake('local');
        $admin = User::factory()->platformAdmin()->create();
        $employer = Employer::factory()->create();
        Storage::disk('local')->put('employer-documents/x/vat.pdf', 'contents');
        $document = EmployerDocument::factory()->for($employer)->create([
            'original_name' => 'vat.pdf',
            'path' => 'employer-documents/x/vat.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.employers.documents.download', [$employer, $document]))
            ->assertOk()
            ->assertDownload('vat.pdf');
    }
}
