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

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Dashboard</h2>
            <p class="fi-page-subtitle">An overview of your store.</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="fi-stat-card">
            <p class="fi-stat-label">Categories</p>
            <p class="fi-stat-value">{{ stats.categories }}</p>
        </div>
        <div class="fi-stat-card">
            <p class="fi-stat-label">Products</p>
            <p class="fi-stat-value">{{ stats.products }}</p>
        </div>
        <div class="fi-stat-card">
            <p class="fi-stat-label">Attributes</p>
            <p class="fi-stat-value">{{ stats.attributes }}</p>
        </div>
        <div class="fi-stat-card">
            <p class="fi-stat-label">Users</p>
            <p class="fi-stat-value">{{ stats.users }}</p>
        </div>
    </div>
</template>
