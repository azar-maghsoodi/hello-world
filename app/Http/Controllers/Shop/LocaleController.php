<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string'],
        ]);

        $isActive = Language::query()
            ->where('is_active', true)
            ->where('code', $validated['locale'])
            ->exists();

        if ($isActive) {
            $request->session()->put('locale', $validated['locale']);
        }

        return back();
    }
}
