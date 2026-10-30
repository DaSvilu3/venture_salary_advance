<?php

namespace App\Http\Controllers\Employer;

use App\Enums\AgreementStatus;
use App\Models\AuditLog;
use App\Models\EmployerAgreement;
use App\Support\AgreementPresenter;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AgreementController extends EmployerPortalController
{
    public function show(Request $request, Clock $clock): Response
    {
        $employer = $this->employer($request);
        $agreement = EmployerAgreement::forEmployer($employer);

        return Inertia::render('agreements/show', AgreementPresenter::present($employer, $agreement, 'employer', $clock));
    }

    public function accept(Request $request, Clock $clock): RedirectResponse
    {
        $employer = $this->employer($request);
        $agreement = EmployerAgreement::forEmployer($employer);

        $agreement->update([
            'status' => AgreementStatus::Accepted,
            'decided_at' => $clock->now(),
            'reject_reason' => null,
            'version' => config('salary_access.agreement_version'),
        ]);

        AuditLog::record('employer_agreement.accepted', $employer, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agreement accepted.')]);

        return back();
    }

    public function reject(Request $request, Clock $clock): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $employer = $this->employer($request);
        $agreement = EmployerAgreement::forEmployer($employer);

        $agreement->update([
            'status' => AgreementStatus::Rejected,
            'decided_at' => $clock->now(),
            'reject_reason' => $data['reason'],
        ]);

        AuditLog::record('employer_agreement.rejected', $employer, $request->user(), ['reason' => $data['reason']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agreement rejected.')]);

        return back();
    }

    public function uploadSigned(Request $request, Clock $clock): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $employer = $this->employer($request);
        $agreement = EmployerAgreement::forEmployer($employer);
        $file = $data['file'];

        if ($agreement->signed_path !== null) {
            Storage::disk('local')->delete($agreement->signed_path);
        }

        $agreement->update([
            'signed_path' => $file->store("employer-agreements/{$employer->id}", 'local'),
            'signed_name' => $file->getClientOriginalName(),
            'signed_uploaded_at' => $clock->now(),
        ]);

        AuditLog::record('employer_agreement.signed_uploaded', $employer, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Signed agreement uploaded.')]);

        return back();
    }

    public function downloadSigned(Request $request): HttpResponse
    {
        $agreement = EmployerAgreement::forEmployer($this->employer($request));

        abort_if($agreement->signed_path === null, 404);

        return Storage::disk('local')->download($agreement->signed_path, $agreement->signed_name);
    }
}
