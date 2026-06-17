<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Conditions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Edit the global vendor conditions / liability / rules document (markdown),
 * shown to vendors when they book a table.
 */
class ConditionsController extends Controller
{
    public function edit(): View
    {
        return view('admin.conditions.edit', [
            'content' => Conditions::text(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => 'nullable|string|max:50000',
        ]);

        Conditions::save($data['content'] ?? '');

        return back()->with('status', 'Vendor conditions saved.');
    }
}
