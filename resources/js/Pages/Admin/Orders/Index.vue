<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrency } from '@/utils/currency';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    orders: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Array, default: () => [] },
});

const money = useCurrency();

const statusBadge = {
    pending: 'fi-badge-warning',
    processing: 'fi-badge-info',
    completed: 'fi-badge-success',
    cancelled: 'fi-badge-gray',
};

const filterByStatus = (status) => {
    router.get(route('admin.orders.index'), status ? { status } : {}, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="Orders" />

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Orders</h2>
            <p class="fi-page-subtitle">Track and fulfill customer orders.</p>
        </div>
    </div>

    <div class="mb-4 flex gap-2">
        <button type="button" :class="['fi-tab', !filters.status && 'fi-tab-active']" @click="filterByStatus(null)">
            All
        </button>
        <button
            v-for="status in statuses"
            :key="status"
            type="button"
            :class="['fi-tab', filters.status === status && 'fi-tab-active']"
            @click="filterByStatus(status)"
        >
            {{ status }}
        </button>
    </div>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="order in orders.data" :key="order.id">
                    <td class="fi-cell-primary">{{ order.order_number }}</td>
                    <td>
                        {{ order.user?.name }}
                        <span class="block text-xs text-gray-400">{{ order.user?.email }}</span>
                    </td>
                    <td>{{ new Date(order.created_at).toLocaleDateString() }}</td>
                    <td>
                        <span :class="[statusBadge[order.status] ?? 'fi-badge-gray', 'capitalize']">
                            {{ order.status }}
                        </span>
                    </td>
                    <td class="fi-cell-primary">{{ money(order.total) }}</td>
                    <td class="text-right">
                        <Link :href="`/admin/orders/${order.id}`" class="fi-link">
                            View
                        </Link>
                    </td>
                </tr>

                <tr v-if="orders.data.length === 0">
                    <td colspan="6" class="fi-table-empty">No orders found.</td>
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
                'fi-pagination-link',
                link.active && 'fi-pagination-link-active',
                !link.url && 'fi-pagination-link-disabled',
            ]"
            v-html="link.label"
        />
    </div>
</template>
