<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

const props = defineProps({
    category: { type: Object, required: true },
    products: { type: Object, required: true },
});
</script>

<template>
    <Head :title="category.name" />

    <nav class="mb-4 text-sm text-gray-500">
        <Link href="/" class="hover:text-gray-800">Shop</Link>
        <span class="mx-1">/</span>
        <Link v-if="category.parent" :href="`/categories/${category.parent.slug}`" class="hover:text-gray-800">
            {{ category.parent.name }}
        </Link>
        <span v-if="category.parent" class="mx-1">/</span>
        <span class="text-gray-800">{{ category.name }}</span>
    </nav>

    <h1 class="mb-2 text-2xl font-semibold text-gray-900">{{ category.name }}</h1>
    <p v-if="category.description" class="mb-6 text-sm text-gray-600">{{ category.description }}</p>

    <div v-if="category.children?.length" class="mb-8 flex flex-wrap gap-2">
        <Link
            v-for="child in category.children"
            :key="child.id"
            :href="`/categories/${child.slug}`"
            class="rounded-full border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:border-gray-500"
        >
            {{ child.name }}
        </Link>
    </div>

    <div v-if="products.data.length" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <Link
            v-for="product in products.data"
            :key="product.id"
            :href="`/products/${product.slug}`"
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:border-gray-400"
        >
            <p class="font-medium text-gray-900">{{ product.name }}</p>
            <p class="mt-1 text-sm text-gray-500">${{ product.sale_price ?? product.price }}</p>
        </Link>
    </div>
    <p v-else class="text-sm text-gray-500">No products in this category yet.</p>

    <div v-if="products.links?.length > 3" class="mt-8 flex flex-wrap gap-1">
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
