<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Languages/Index', [
            'languages' => Language::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateLanguage($request);

        $language = Language::create($validated);

        if ($language->is_default) {
            $this->makeOnlyDefault($language);
        }

        return redirect()->route('admin.languages.index')->with('success', 'Language added.');
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $validated = $this->validateLanguage($request, $language);

        if ($language->is_default && ! ($validated['is_default'] ?? false)) {
            return back()->withErrors(['is_default' => 'Choose a different default language before unsetting this one.']);
        }

        $language->update($validated);

        if ($language->is_default) {
            $this->makeOnlyDefault($language);
        }

        return redirect()->route('admin.languages.index')->with('success', 'Language updated.');
    }

    public function destroy(Language $language): RedirectResponse
    {
        if ($language->is_default) {
            return back()->withErrors(['is_default' => 'You cannot delete the default language.']);
        }

        $language->delete();

        return redirect()->route('admin.languages.index')->with('success', 'Language removed.');
    }

    private function validateLanguage(Request $request, ?Language $language = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('languages', 'code')->ignore($language)],
            'name' => ['required', 'string', 'max:255'],
            'native_name' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_default'] = $request->boolean('is_default');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // A default language must also be active, so it's always selectable.
        if ($validated['is_default']) {
            $validated['is_active'] = true;
        }

        return $validated;
    }

    private function makeOnlyDefault(Language $language): void
    {
        Language::query()->where('id', '!=', $language->id)->update(['is_default' => false]);
    }
}
