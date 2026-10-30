<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employer;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Platform review of the company documents an employer has uploaded.
 */
class EmployerDocumentController extends Controller
{
    public function download(Employer $employer, int $document): HttpResponse
    {
        $document = $employer->documents()->findOrFail($document);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function approve(Request $request, Employer $employer, int $document, Clock $clock): RedirectResponse
    {
        $document = $employer->documents()->findOrFail($document);

        $document->update([
            'status' => DocumentStatus::Approved,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => $clock->now(),
            'review_note' => $request->string('note')->toString() ?: null,
        ]);

        AuditLog::record('employer_document.approved', $document, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document approved.')]);

        return back();
    }

    public function reject(Request $request, Employer $employer, int $document, Clock $clock): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $document = $employer->documents()->findOrFail($document);

        $document->update([
            'status' => DocumentStatus::Rejected,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => $clock->now(),
            'review_note' => $data['note'],
        ]);

        AuditLog::record('employer_document.rejected', $document, $request->user(), ['note' => $data['note']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document rejected.')]);

        return back();
    }
}
