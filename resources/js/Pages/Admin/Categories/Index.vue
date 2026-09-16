<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    categories: { type: Array, default: () => [] },
});

const destroy = (category) => {
    if (confirm(`Delete category "${category.name}"? Subcategories will become top-level.`)) {
        router.delete(route('admin.categories.destroy', category.id));
    }
};
</script>

<template>
    <Head title="Categories" />

    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-900">Categories</h2>
        <Link
            href="/admin/categories/create"
            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black"
        >
            Add category
        </Link>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Parent</th>
                    <th class="px-4 py-3 font-medium">Products</th>
                    <th class="px-4 py-3 font-medium">Active</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="category in categories" :key="category.id">
                    <td class="px-4 py-3 text-gray-900">{{ category.name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ category.parent?.name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ category.products_count }}</td>
                    <td class="px-4 py-3">
                        <span
                            :class="[
                                'rounded-full px-2 py-0.5 text-xs',
                                category.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600',
                            ]"
                        >
                            {{ category.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Link
                            :href="`/admin/categories/${category.id}/edit`"
                            class="mr-3 text-gray-600 hover:text-gray-900"
                        >
                            Edit
                        </Link>
                        <button type="button" class="text-red-600 hover:text-red-800" @click="destroy(category)">
                            Delete
                        </button>
                    </td>
                </tr>

                <tr v-if="categories.length === 0">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No categories yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
