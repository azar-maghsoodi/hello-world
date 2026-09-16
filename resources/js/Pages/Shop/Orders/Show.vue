<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

defineProps({
    order: { type: Object, required: true },
});

const statusClasses = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <nav class="mb-4 text-sm text-gray-500">
        <Link href="/orders" class="hover:text-gray-800">My Orders</Link>
        <span class="mx-1">/</span>
        <span class="text-gray-800">{{ order.order_number }}</span>
    </nav>

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-900">Order {{ order.order_number }}</h1>
        <span :class="['rounded-full px-3 py-1 text-sm capitalize', statusClasses[order.status]]">
            {{ order.status }}
        </span>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Product</th>
                    <th class="px-4 py-3 font-medium">SKU</th>
                    <th class="px-4 py-3 font-medium">Quantity</th>
                    <th class="px-4 py-3 font-medium">Unit price</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="item in order.items" :key="item.id">
                    <td class="px-4 py-3 text-gray-900">
                        <Link v-if="item.product" :href="`/products/${item.product.slug}`" class="hover:underline">
                            {{ item.product_name }}
                        </Link>
                        <span v-else>{{ item.product_name }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ item.product_sku }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ item.quantity }}</td>
                    <td class="px-4 py-3 text-gray-500">${{ item.unit_price }}</td>
                    <td class="px-4 py-3 text-gray-900">${{ item.total }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 ml-auto max-w-xs space-y-1 text-sm">
        <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span>${{ order.subtotal }}</span>
        </div>
        <div class="flex justify-between text-gray-600">
            <span>Tax</span>
            <span>${{ order.tax }}</span>
        </div>
        <div class="flex justify-between text-gray-600">
            <span>Shipping</span>
            <span>${{ order.shipping_cost }}</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
            <span>Total</span>
            <span>${{ order.total }}</span>
        </div>
    </div>

    <div v-if="order.shipping_address" class="mt-6">
        <p class="mb-1 text-sm font-medium text-gray-700">Shipping address</p>
        <p class="whitespace-pre-line text-sm text-gray-600">{{ order.shipping_address }}</p>
    </div>
</template>
