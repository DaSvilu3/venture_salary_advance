<?php

namespace Tests\Feature\Http\Employer;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Employer;
use App\Models\EmployerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_uploading_a_document_stores_the_file_privately_as_pending(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();

        $this->actingAs($hr)->post(route('employer.documents.store'), [
            'type' => DocumentType::CommercialRegistration->value,
            'file' => UploadedFile::fake()->create('cr-certificate.pdf', 120, 'application/pdf'),
        ])->assertRedirect(route('employer.documents.index'));

        $document = $hr->employer->documents()->sole();
        $this->assertSame(DocumentStatus::Pending, $document->status);
        $this->assertSame(DocumentType::CommercialRegistration, $document->type);
        $this->assertSame('cr-certificate.pdf', $document->original_name);
        $this->assertStringStartsWith("employer-documents/{$hr->employer->id}/", $document->path);
        Storage::disk('local')->assertExists($document->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employer_document.uploaded', 'actor_id' => $hr->id]);
    }

    public function test_uploads_must_be_an_allowed_type_and_size_with_a_known_document_type(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();

        $this->actingAs($hr)->post(route('employer.documents.store'), [
            'type' => DocumentType::Other->value,
            'file' => UploadedFile::fake()->create('payroll.exe', 10),
        ])->assertSessionHasErrors('file');

        $this->actingAs($hr)->post(route('employer.documents.store'), [
            'type' => 'unknown',
            'file' => UploadedFile::fake()->create('vat.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('type');

        $this->assertSame(0, EmployerDocument::query()->count());
    }

    public function test_employer_can_download_its_own_document(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();
        $document = $this->documentFor($hr->employer, 'own.pdf');

        $this->actingAs($hr)
            ->get(route('employer.documents.download', $document->id))
            ->assertOk()
            ->assertDownload('own.pdf');
    }

    public function test_employer_gets_not_found_downloading_another_employers_document(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();
        $otherDocument = $this->documentFor(Employer::factory()->create(), 'theirs.pdf');

        $this->actingAs($hr)
            ->get(route('employer.documents.download', $otherDocument->id))
            ->assertNotFound();
    }

    public function test_deleting_removes_the_file_and_record(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();
        $document = $this->documentFor($hr->employer, 'old.pdf');

        $this->actingAs($hr)->delete(route('employer.documents.destroy', $document->id))->assertRedirect();

        $this->assertModelMissing($document);
        Storage::disk('local')->assertMissing($document->path);
    }

    public function test_employer_cannot_delete_another_employers_document(): void
    {
        Storage::fake('local');
        $hr = $this->hrUser();
        $otherDocument = $this->documentFor(Employer::factory()->create(), 'theirs.pdf');

        $this->actingAs($hr)->delete(route('employer.documents.destroy', $otherDocument->id))->assertNotFound();

        $this->assertModelExists($otherDocument);
        Storage::disk('local')->assertExists($otherDocument->path);
    }

    private function documentFor(Employer $employer, string $name): EmployerDocument
    {
        $path = "employer-documents/{$employer->id}/".$name;
        Storage::disk('local')->put($path, 'contents');

        return EmployerDocument::factory()->for($employer)->create(['original_name' => $name, 'path' => $path]);
    }

    private function hrUser(): User
    {
        return User::factory()->employerAdmin()->for(Employer::factory())->create();
    }
}
