<?php

namespace App\Http\Controllers\Employer;

use App\Domain\Roster\ImportRoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RosterImportController extends EmployerPortalController
{
    public function store(Request $request, ImportRoster $import): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:1024'],
        ]);

        $result = $import->handle($this->employer($request), $request->file('file')->getRealPath(), $request->user());

        Inertia::flash('toast', [
            'type' => $result['errors'] === [] ? 'success' : 'warning',
            'message' => __(':count employees added and invited.', ['count' => $result['added']]),
        ]);

        return to_route('employer.employees.index')->with('import_result', $result);
    }
}
