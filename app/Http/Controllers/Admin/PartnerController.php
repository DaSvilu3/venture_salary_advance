<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdvanceStatus;
use App\Enums\PartnerType;
use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\AuditLog;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/partners/index', [
            'partners' => Partner::query()
                ->with('users:id,name,email,partner_id')
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->map(fn (Partner $partner): array => [
                    'id' => $partner->id,
                    'type' => $partner->type->value,
                    'name' => $partner->name,
                    'code' => $partner->code,
                    'brand_color' => $partner->brand_color,
                    'employers_count' => $partner->type === PartnerType::Bank
                        ? $partner->bankedEmployers()->count()
                        : $partner->fundedEmployers()->count(),
                    'outstanding' => (int) Advance::query()
                        ->where($partner->type === PartnerType::Bank ? 'bank_partner_id' : 'funder_partner_id', $partner->id)
                        ->whereIn('status', AdvanceStatus::outstanding())
                        ->sum('total_due_baisa'),
                    'viewers' => $partner->users->map(fn ($user): array => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])->all(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = Partner::query()->create($this->validated($request));
        AuditLog::record('partner.created', $partner, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Partner saved.')]);

        return to_route('admin.partners.index');
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validated($request, $partner));
        AuditLog::record('partner.updated', $partner, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Partner saved.')]);

        return to_route('admin.partners.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Partner $partner = null): array
    {
        return $request->validate([
            'type' => [$partner === null ? 'required' : 'prohibited', Rule::enum(PartnerType::class)],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', Rule::unique('partners', 'code')->ignore($partner?->id)],
            'brand_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
