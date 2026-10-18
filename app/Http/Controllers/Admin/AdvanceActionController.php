<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Advances\AdvanceLifecycle;
use App\Domain\Advances\InvalidAdvanceTransition;
use App\Enums\AdvanceStatus;
use App\Http\Controllers\Controller;
use App\Jobs\CollectAdvance;
use App\Models\Advance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Manual operations on a single advance. Each one goes through the lifecycle and is audited.
 */
class AdvanceActionController extends Controller
{
    public function cancel(Request $request, Advance $advance, AdvanceLifecycle $lifecycle): RedirectResponse
    {
        $this->transition($request, $advance, $lifecycle, AdvanceStatus::Cancelled);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Advance cancelled.')]);

        return back();
    }

    public function writeOff(Request $request, Advance $advance, AdvanceLifecycle $lifecycle): RedirectResponse
    {
        $this->transition($request, $advance, $lifecycle, AdvanceStatus::WrittenOff);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Advance written off.')]);

        return back();
    }

    public function retryCollection(Request $request, Advance $advance): RedirectResponse
    {
        if (! in_array($advance->status, AdvanceStatus::outstanding(), true)) {
            throw ValidationException::withMessages(['advance' => __('Only advances that are paid out and not yet repaid can be collected.')]);
        }

        CollectAdvance::dispatch($advance->id, $request->user()->id);

        Inertia::flash('toast', ['type' => 'info', 'message' => __('Collection requested from the bank.')]);

        return back();
    }

    private function transition(Request $request, Advance $advance, AdvanceLifecycle $lifecycle, AdvanceStatus $to): void
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);

        try {
            $lifecycle->transition($advance, $to, $request->user(), meta: array_filter(['note' => $data['note'] ?? null]));
        } catch (InvalidAdvanceTransition) {
            throw ValidationException::withMessages(['advance' => __('This action is not possible for an advance that is :status.', ['status' => $advance->status->value])]);
        }
    }
}
