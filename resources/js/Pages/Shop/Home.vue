<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

defineProps({
    categories: { type: Array, default: () => [] },
    featuredProducts: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Shop" />

    <section class="mb-10">
        <h1 class="mb-4 text-2xl font-semibold text-gray-900">Shop by category</h1>

        <div v-if="categories.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <Link
                v-for="category in categories"
                :key="category.id"
                :href="`/categories/${category.slug}`"
                class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm hover:border-gray-400"
            >
                <p class="font-medium text-gray-900">{{ category.name }}</p>
                <p v-if="category.children_count" class="mt-1 text-xs text-gray-500">
                    {{ category.children_count }} subcategories
                </p>
            </Link>
        </div>
        <p v-else class="text-sm text-gray-500">No categories yet.</p>
    </section>

    <section>
        <h2 class="mb-4 text-xl font-semibold text-gray-900">Featured products</h2>

        <div v-if="featuredProducts.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <Link
                v-for="product in featuredProducts"
                :key="product.id"
                :href="`/products/${product.slug}`"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-gray-400"
            >
                <p class="font-medium text-gray-900">{{ product.name }}</p>
                <p class="mt-1 text-sm text-gray-500">${{ product.sale_price ?? product.price }}</p>
            </Link>
        </div>
        <p v-else class="text-sm text-gray-500">No featured products yet.</p>
    </section>
</template>
