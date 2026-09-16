<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->with('parent')
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
            'parentOptions' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCategory($request);

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => $category,
            'parentOptions' => Category::where('id', '!=', $category->id)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);

        if ($validated['parent_id'] === $category->id) {
            return back()->withErrors(['parent_id' => 'A category cannot be its own parent.']);
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($category),
            ],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
