<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? '');

const runSearch = () => {
    router.get(route('admin.products.index'), { search: search.value }, { preserveState: true, replace: true });
};

const destroy = (product) => {
    if (confirm(`Delete product "${product.name}"?`)) {
        router.delete(route('admin.products.destroy', product.id));
    }
};
</script>

<template>
    <Head title="Products" />

    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900">Products</h2>
        <Link
            href="/admin/products/create"
            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black"
        >
            Add product
        </Link>
    </div>

    <form @submit.prevent="runSearch" class="mb-4 flex max-w-sm gap-2">
        <input
            v-model="search"
            type="text"
            placeholder="Search products..."
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
        >
        <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">
            Search
        </button>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">SKU</th>
                    <th class="px-4 py-3 font-medium">Price</th>
                    <th class="px-4 py-3 font-medium">Stock</th>
                    <th class="px-4 py-3 font-medium">Categories</th>
                    <th class="px-4 py-3 font-medium">Active</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="product in products.data" :key="product.id">
                    <td class="px-4 py-3 text-gray-900">{{ product.name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ product.sku }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        <span v-if="product.sale_price" class="text-gray-900">${{ product.sale_price }}</span>
                        <span v-else>${{ product.price }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ product.quantity }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ product.categories.map((c) => c.name).join(', ') || '—' }}
                    </td>
                    <td class="px-4 py-3">
                        <span
                            :class="[
                                'rounded-full px-2 py-0.5 text-xs',
                                product.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600',
                            ]"
                        >
                            {{ product.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Link
                            :href="`/admin/products/${product.id}/edit`"
                            class="mr-3 text-gray-600 hover:text-gray-900"
                        >
                            Edit
                        </Link>
                        <button type="button" class="text-red-600 hover:text-red-800" @click="destroy(product)">
                            Delete
                        </button>
                    </td>
                </tr>

                <tr v-if="products.data.length === 0">
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">No products found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="products.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="(link, index) in products.links"
            :key="index"
            :href="link.url ?? '#'"
            :class="[
                'rounded-md px-3 py-1 text-sm',
                link.active ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100',
                !link.url && 'pointer-events-none opacity-50',
            ]"
            v-html="link.label"
        />
    </div>
</template>
