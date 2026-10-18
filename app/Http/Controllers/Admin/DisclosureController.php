<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Disclosure;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The terms employees accept with each advance. Published versions are never edited; publish a new one.
 */
class DisclosureController extends Controller
{
    public function index(): Response
    {
        $current = Disclosure::current();

        return Inertia::render('admin/disclosures', [
            'disclosures' => Disclosure::query()->withCount('advances')->latest('id')->get()->map(fn (Disclosure $disclosure): array => [
                'id' => $disclosure->id,
                'version' => $disclosure->version,
                'body_en' => $disclosure->body_en,
                'body_ar' => $disclosure->body_ar,
                'published_at' => $disclosure->published_at?->toIso8601String(),
                'is_current' => $current?->is($disclosure) ?? false,
                'advances_count' => $disclosure->advances_count,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'version' => ['required', 'string', 'max:20', 'unique:disclosures,version'],
            'body_en' => ['required', 'string', 'max:5000'],
            'body_ar' => ['required', 'string', 'max:5000'],
        ]);

        $disclosure = Disclosure::query()->create($data);
        AuditLog::record('disclosure.drafted', $disclosure, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Draft :version saved.', ['version' => $disclosure->version])]);

        return to_route('admin.disclosures.index');
    }

    public function publish(Request $request, Disclosure $disclosure, Clock $clock): RedirectResponse
    {
        if ($disclosure->published_at !== null) {
            throw ValidationException::withMessages(['disclosure' => __('This version is already published.')]);
        }

        $disclosure->update(['published_at' => $clock->now()]);
        AuditLog::record('disclosure.published', $disclosure, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version :version is now shown to employees.', ['version' => $disclosure->version])]);

        return to_route('admin.disclosures.index');
    }
}
