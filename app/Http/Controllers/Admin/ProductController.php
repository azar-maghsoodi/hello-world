<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Products/Index', [
            'products' => Product::query()
                ->with('categories:id,name')
                ->when($request->string('search')->trim()->isNotEmpty(), fn ($query) => $query->where(
                    'name', 'like', '%'.$request->string('search')->trim().'%'
                ))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categoryOptions' => Category::orderBy('name')->get(['id', 'name']),
            'attributeOptions' => ProductAttribute::orderBy('name')->orderBy('value')->get(['id', 'name', 'value']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $product = Product::create($validated);

        $product->categories()->sync($request->input('categories', []));
        $product->attributes()->sync($request->input('attributes', []));

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        $product->load('categories:id', 'attributes:id');

        return Inertia::render('Admin/Products/Form', [
            'product' => $product,
            'categoryOptions' => Category::orderBy('name')->get(['id', 'name']),
            'attributeOptions' => ProductAttribute::orderBy('name')->orderBy('value')->get(['id', 'name', 'value']),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request, $product);

        $product->update($validated);

        $product->categories()->sync($request->input('categories', []));
        $product->attributes()->sync($request->input('attributes', []));

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product),
            ],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'quantity' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:4096'],
            'categories' => ['array'],
            'categories.*' => ['exists:categories,id'],
            'attributes' => ['array'],
            'attributes.*' => ['exists:product_attributes,id'],
        ]);

        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('image')) {
            if ($product?->image) {
                Storage::disk('public')->delete($product->image);
            }

            $validated['image'] = $request->file('image')->store('products', 'public');
        } else {
            unset($validated['image']);
        }

        unset($validated['categories'], $validated['attributes']);

        return $validated;
    }
}
