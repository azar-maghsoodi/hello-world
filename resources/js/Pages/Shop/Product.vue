<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

defineProps({
    product: { type: Object, required: true },
});
</script>

<template>
    <Head :title="product.name" />

    <nav class="mb-4 text-sm text-gray-500">
        <Link href="/" class="hover:text-gray-800">Shop</Link>
        <span class="mx-1">/</span>
        <span class="text-gray-800">{{ product.name }}</span>
    </nav>

    <div class="grid gap-8 md:grid-cols-2">
        <div class="aspect-square overflow-hidden rounded-lg border border-gray-200 bg-white">
            <img
                v-if="product.image"
                :src="`/storage/${product.image}`"
                :alt="product.name"
                class="h-full w-full object-cover"
            >
            <div v-else class="flex h-full w-full items-center justify-center text-sm text-gray-400">
                No image
            </div>
        </div>

        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ product.name }}</h1>
            <p class="mt-1 text-sm text-gray-500">SKU: {{ product.sku }}</p>

            <div class="mt-4 flex items-center gap-3">
                <span class="text-2xl font-semibold text-gray-900">
                    ${{ product.sale_price ?? product.price }}
                </span>
                <span v-if="product.sale_price" class="text-lg text-gray-400 line-through">
                    ${{ product.price }}
                </span>
            </div>

            <p class="mt-2 text-sm" :class="product.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                {{ product.quantity > 0 ? `${product.quantity} in stock` : 'Out of stock' }}
            </p>

            <p v-if="product.short_description" class="mt-4 text-gray-700">{{ product.short_description }}</p>
            <p v-if="product.description" class="mt-4 whitespace-pre-line text-sm text-gray-600">
                {{ product.description }}
            </p>

            <div v-if="product.categories?.length" class="mt-6">
                <p class="mb-2 text-sm font-medium text-gray-700">Categories</p>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-for="category in product.categories"
                        :key="category.id"
                        :href="`/categories/${category.slug}`"
                        class="rounded-full border border-gray-300 px-3 py-1 text-sm text-gray-700 hover:border-gray-500"
                    >
                        {{ category.name }}
                    </Link>
                </div>
            </div>

            <div v-if="product.attributes?.length" class="mt-6">
                <p class="mb-2 text-sm font-medium text-gray-700">Attributes</p>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="attribute in product.attributes"
                        :key="attribute.id"
                        class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-700"
                    >
                        {{ attribute.name }}: {{ attribute.value }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
