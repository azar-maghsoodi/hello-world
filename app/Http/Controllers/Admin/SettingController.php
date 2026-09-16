<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => StoreSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_code' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'shipping_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $validated['currency_code'] = strtoupper($validated['currency_code']);

        StoreSetting::current()->update($validated);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings updated.');
    }
}
