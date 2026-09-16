<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    stats: { type: Object, required: true },
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <h2 class="mb-6 text-xl font-semibold text-gray-900">Dashboard</h2>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Categories</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ stats.categories }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Products</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ stats.products }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Attributes</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ stats.attributes }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">Users</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">{{ stats.users }}</p>
        </div>
    </div>
</template>
