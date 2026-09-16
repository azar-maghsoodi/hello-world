<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const statusClasses = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-600',
};

const filterByStatus = (status) => {
    router.get(route('admin.orders.index'), status ? { status } : {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Orders" />

    <h2 class="mb-6 text-xl font-semibold text-gray-900">Orders</h2>

    <div class="mb-4 flex gap-2">
        <button
            type="button"
            :class="[
                'rounded-md border px-3 py-1.5 text-sm',
                !filters.status ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 text-gray-700',
            ]"
            @click="filterByStatus(null)"
        >
            All
        </button>
        <button
            v-for="status in statuses"
            :key="status"
            type="button"
            :class="[
                'rounded-md border px-3 py-1.5 text-sm capitalize',
                filters.status === status ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-300 text-gray-700',
            ]"
            @click="filterByStatus(status)"
        >
            {{ status }}
        </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Order #</th>
                    <th class="px-4 py-3 font-medium">Customer</th>
                    <th class="px-4 py-3 font-medium">Date</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="order in orders.data" :key="order.id">
                    <td class="px-4 py-3 text-gray-900">{{ order.order_number }}</td>
                    <td class="px-4 py-3 text-gray-500">
                        {{ order.user?.name }}
                        <span class="block text-xs text-gray-400">{{ order.user?.email }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    <td class="px-4 py-3">
                        <span :class="['rounded-full px-2 py-0.5 text-xs capitalize', statusClasses[order.status]]">
                            {{ order.status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-900">${{ order.total }}</td>
                    <td class="px-4 py-3 text-right">
                        <Link :href="`/admin/orders/${order.id}`" class="text-gray-600 hover:text-gray-900">
                            View
                        </Link>
                    </td>
                </tr>

                <tr v-if="orders.data.length === 0">
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="orders.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="(link, index) in orders.links"
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
