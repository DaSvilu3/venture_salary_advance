<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\EmployerAgreement;
use App\Support\AgreementPresenter;
use App\Support\Clock;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Platform view of an employer's programme agreement and its signed copy.
 */
class EmployerAgreementController extends Controller
{
    public function show(Employer $employer, Clock $clock): Response
    {
        $agreement = EmployerAgreement::forEmployer($employer);

        return Inertia::render('agreements/show', AgreementPresenter::present($employer, $agreement, 'admin', $clock));
    }

    public function downloadSigned(Employer $employer): HttpResponse
    {
        $agreement = EmployerAgreement::forEmployer($employer);

        abort_if($agreement->signed_path === null, 404);

        return Storage::disk('local')->download($agreement->signed_path, $agreement->signed_name);
    }
}
