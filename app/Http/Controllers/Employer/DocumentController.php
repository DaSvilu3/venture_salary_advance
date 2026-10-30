<?php

namespace App\Http\Controllers\Employer;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\AuditLog;
use App\Models\EmployerDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DocumentController extends EmployerPortalController
{
    public function index(Request $request): Response
    {
        return Inertia::render('employer/documents', [
            'documents' => $this->employer($request)->documents()
                ->latest()
                ->get()
                ->map(fn (EmployerDocument $document): array => $document->toPortalArray()),
            'types' => array_column(DocumentType::cases(), 'value'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $employer = $this->employer($request);
        $file = $request->file('file');

        $document = $employer->documents()->create([
            'type' => $data['type'],
            'original_name' => $file->getClientOriginalName(),
            'path' => $file->store("employer-documents/{$employer->id}", 'local'),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'status' => DocumentStatus::Pending,
        ]);

        AuditLog::record('employer_document.uploaded', $document, $request->user(), ['type' => $data['type']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document uploaded.')]);

        return to_route('employer.documents.index');
    }

    public function download(Request $request, int $document): HttpResponse
    {
        $document = $this->employer($request)->documents()->findOrFail($document);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroy(Request $request, int $document): RedirectResponse
    {
        $document = $this->employer($request)->documents()->findOrFail($document);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        AuditLog::record('employer_document.deleted', $document, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document deleted.')]);

        return back();
    }
}
