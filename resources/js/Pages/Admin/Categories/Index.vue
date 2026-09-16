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

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Categories</h2>
            <p class="fi-page-subtitle">Organize your catalog into categories and subcategories.</p>
        </div>
        <Link href="/admin/categories/create" class="fi-btn-primary">
            Add category
        </Link>
    </div>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Parent</th>
                    <th>Products</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="category in categories" :key="category.id">
                    <td class="fi-cell-primary">{{ category.name }}</td>
                    <td>{{ category.parent?.name ?? '—' }}</td>
                    <td>{{ category.products_count }}</td>
                    <td>
                        <span :class="category.is_active ? 'fi-badge-success' : 'fi-badge-gray'">
                            {{ category.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-right">
                        <Link :href="`/admin/categories/${category.id}/edit`" class="fi-link mr-4">
                            Edit
                        </Link>
                        <button type="button" class="fi-btn-danger-text" @click="destroy(category)">
                            Delete
                        </button>
                    </td>
                </tr>

                <tr v-if="categories.length === 0">
                    <td colspan="5" class="fi-table-empty">No categories yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
