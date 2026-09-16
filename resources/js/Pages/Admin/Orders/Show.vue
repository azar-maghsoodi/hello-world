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

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Order {{ order.order_number }}</h2>
            <p class="fi-page-subtitle">{{ order.user?.name }} — {{ order.user?.email }}</p>
        </div>

        <form @submit.prevent="updateStatus" class="flex items-center gap-2">
            <select v-model="statusForm.status" class="fi-select capitalize">
                <option v-for="status in statuses" :key="status" :value="status" class="capitalize">
                    {{ status }}
                </option>
            </select>
            <button type="submit" :disabled="statusForm.processing" class="fi-btn-primary">
                Update status
            </button>
        </form>
    </div>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th>Unit price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in order.items" :key="item.id">
                    <td class="fi-cell-primary">
                        <Link v-if="item.product" :href="`/products/${item.product.slug}`" class="hover:underline">
                            {{ item.product_name }}
                        </Link>
                        <span v-else>{{ item.product_name }} <span class="text-xs text-gray-400">(deleted)</span></span>
                    </td>
                    <td>{{ item.product_sku }}</td>
                    <td>{{ item.quantity }}</td>
                    <td>{{ money(item.unit_price) }}</td>
                    <td class="fi-cell-primary">{{ money(item.total) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="fi-card mt-6 ml-auto max-w-xs space-y-1 p-4 text-sm">
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
        <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-950">
            <span>Total</span>
            <span>{{ money(order.total) }}</span>
        </div>
    </div>

    <div v-if="order.shipping_address || order.notes" class="mt-6 grid gap-4 sm:grid-cols-2">
        <div v-if="order.shipping_address" class="fi-card p-4">
            <p class="fi-label mb-1">Shipping address</p>
            <p class="whitespace-pre-line text-sm text-gray-600">{{ order.shipping_address }}</p>
        </div>
        <div v-if="order.notes" class="fi-card p-4">
            <p class="fi-label mb-1">Notes</p>
            <p class="whitespace-pre-line text-sm text-gray-600">{{ order.notes }}</p>
        </div>
    </div>
</template>
