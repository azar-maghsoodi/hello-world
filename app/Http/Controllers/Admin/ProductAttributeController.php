<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductAttributeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Attributes/Index', [
            'attributes' => ProductAttribute::query()
                ->withCount('products')
                ->orderBy('name')
                ->orderBy('value')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAttribute($request);

        ProductAttribute::create($validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute created.');
    }

    public function update(Request $request, ProductAttribute $attribute): RedirectResponse
    {
        $validated = $this->validateAttribute($request, $attribute);

        $attribute->update($validated);

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated.');
    }

    public function destroy(ProductAttribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute deleted.');
    }

    private function validateAttribute(Request $request, ?ProductAttribute $attribute = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_attributes')
                    ->where('value', $request->input('value'))
                    ->ignore($attribute),
            ],
            'value' => ['required', 'string', 'max:255'],
        ]);
    }
}
