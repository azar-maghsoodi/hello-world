<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/utils/currency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    order: { type: Object, required: true },
    statuses: { type: Array, default: () => [] },
});

const money = useCurrency();
const statusForm = useForm({ status: props.order.status });

const updateStatus = () => {
    statusForm.put(route('admin.orders.update', props.order.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Order ${order.order_number}`" />

    <nav class="mb-4 text-sm text-gray-500">
        <Link href="/admin/orders" class="hover:text-gray-800">Orders</Link>
        <span class="mx-1">/</span>
        <span class="text-gray-800">{{ order.order_number }}</span>
    </nav>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Order {{ order.order_number }}</h2>
            <p class="text-sm text-gray-500">
                {{ order.user?.name }} — {{ order.user?.email }}
            </p>
        </div>

        <form @submit.prevent="updateStatus" class="flex items-center gap-2">
            <select
                v-model="statusForm.status"
                class="rounded-md border-gray-300 text-sm capitalize shadow-sm focus:border-blue-300 focus:ring"
            >
                <option v-for="status in statuses" :key="status" :value="status" class="capitalize">
                    {{ status }}
                </option>
            </select>
            <button
                type="submit"
                :disabled="statusForm.processing"
                class="rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                Update status
            </button>
        </form>
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
                        <span v-else>{{ item.product_name }} <span class="text-xs text-gray-400">(deleted)</span></span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ item.product_sku }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ item.quantity }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ money(item.unit_price) }}</td>
                    <td class="px-4 py-3 text-gray-900">{{ money(item.total) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 ml-auto max-w-xs space-y-1 text-sm">
        <div class="flex justify-between text-gray-600">
            <span>Subtotal</span>
            <span>{{ money(order.subtotal) }}</span>
        </div>
        <div class="flex justify-between text-gray-600">
            <span>Tax</span>
            <span>{{ money(order.tax) }}</span>
        </div>
        <div class="flex justify-between text-gray-600">
            <span>Shipping</span>
            <span>{{ money(order.shipping_cost) }}</span>
        </div>
        <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
            <span>Total</span>
            <span>{{ money(order.total) }}</span>
        </div>
    </div>

    <div v-if="order.shipping_address || order.notes" class="mt-6 grid gap-4 sm:grid-cols-2">
        <div v-if="order.shipping_address">
            <p class="mb-1 text-sm font-medium text-gray-700">Shipping address</p>
            <p class="whitespace-pre-line text-sm text-gray-600">{{ order.shipping_address }}</p>
        </div>
        <div v-if="order.notes">
            <p class="mb-1 text-sm font-medium text-gray-700">Notes</p>
            <p class="whitespace-pre-line text-sm text-gray-600">{{ order.notes }}</p>
        </div>
    </div>
</template>
