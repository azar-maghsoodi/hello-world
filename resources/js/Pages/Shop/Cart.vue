<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
});

const updateQuantity = (productId, quantity) => {
    router.patch(route('cart.update', productId), { quantity }, { preserveScroll: true });
};

const removeItem = (productId) => {
    router.delete(route('cart.destroy', productId), { preserveScroll: true });
};
</script>

<template>
    <Head title="Your Cart" />

    <h1 class="mb-6 text-2xl font-semibold text-gray-900">Your Cart</h1>

    <div v-if="items.length === 0" class="rounded-lg border border-dashed border-gray-300 p-8 text-center">
        <p class="text-gray-500">Your cart is empty.</p>
        <Link href="/" class="mt-3 inline-block text-sm text-gray-900 underline">Continue shopping</Link>
    </div>

    <template v-else>
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Product</th>
                        <th class="px-4 py-3 font-medium">Price</th>
                        <th class="px-4 py-3 font-medium">Quantity</th>
                        <th class="px-4 py-3 font-medium">Total</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in items" :key="item.product.id">
                        <td class="px-4 py-3">
                            <Link :href="`/products/${item.product.slug}`" class="font-medium text-gray-900 hover:underline">
                                {{ item.product.name }}
                            </Link>
                        </td>
                        <td class="px-4 py-3 text-gray-500">${{ item.unit_price }}</td>
                        <td class="px-4 py-3">
                            <input
                                type="number"
                                min="1"
                                :max="item.product.quantity"
                                :value="item.quantity"
                                class="w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-300 focus:ring"
                                @change="updateQuantity(item.product.id, Number($event.target.value))"
                            >
                        </td>
                        <td class="px-4 py-3 text-gray-900">${{ item.total.toFixed(2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                class="text-red-600 hover:text-red-800"
                                @click="removeItem(item.product.id)"
                            >
                                Remove
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6 ml-auto max-w-xs">
            <div class="flex justify-between text-sm font-semibold text-gray-900">
                <span>Subtotal</span>
                <span>${{ subtotal.toFixed(2) }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">Tax and shipping calculated at checkout.</p>

            <Link
                href="/checkout"
                class="mt-4 block rounded-md bg-gray-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-black"
            >
                Proceed to checkout
            </Link>
        </div>
    </template>
</template>
