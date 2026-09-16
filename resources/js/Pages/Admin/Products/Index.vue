<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/utils/currency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const money = useCurrency();
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

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Products</h2>
            <p class="fi-page-subtitle">Manage the items available in your store.</p>
        </div>
        <Link href="/admin/products/create" class="fi-btn-primary">
            Add product
        </Link>
    </div>

    <form @submit.prevent="runSearch" class="mb-4 flex max-w-sm gap-2">
        <input v-model="search" type="text" placeholder="Search products..." class="fi-input">
        <button type="submit" class="fi-btn-secondary">
            Search
        </button>
    </form>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Categories</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in products.data" :key="product.id">
                    <td class="fi-cell-primary">{{ product.name }}</td>
                    <td>{{ product.sku }}</td>
                    <td>
                        <span v-if="product.sale_price" class="fi-cell-primary">{{ money(product.sale_price) }}</span>
                        <span v-else>{{ money(product.price) }}</span>
                    </td>
                    <td>{{ product.quantity }}</td>
                    <td>{{ product.categories.map((c) => c.name).join(', ') || '—' }}</td>
                    <td>
                        <span :class="product.is_active ? 'fi-badge-success' : 'fi-badge-gray'">
                            {{ product.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <Link :href="`/admin/products/${product.id}/edit`" class="fi-link mr-4">
                            Edit
                        </Link>
                        <button type="button" class="fi-btn-danger-text" @click="destroy(product)">
                            Delete
                        </button>
                    </td>
                </tr>

                <tr v-if="products.data.length === 0">
                    <td colspan="7" class="fi-table-empty">No products found.</td>
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
                'fi-pagination-link',
                link.active && 'fi-pagination-link-active',
                !link.url && 'fi-pagination-link-disabled',
            ]"
            v-html="link.label"
        />
    </div>
</template>
