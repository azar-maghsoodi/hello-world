<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useTranslations } from '@/i18n';
import { useCurrency } from '@/utils/currency';

defineOptions({ layout: ShopLayout });

defineProps({
    orders: { type: Object, required: true },
});

const page = usePage();
const t = useTranslations();
const money = useCurrency();

const statusClasses = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-600',
};

const formatDate = (value) => new Date(value).toLocaleDateString(page.props.locale);
</script>

<template>
    <Head :title="t('orders.myOrders')" />

    <h1 class="mb-6 text-2xl font-semibold text-gray-900">{{ t('orders.myOrders') }}</h1>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ t('orders.orderNumber') }}</th>
                    <th class="px-4 py-3 font-medium">{{ t('orders.date') }}</th>
                    <th class="px-4 py-3 font-medium">{{ t('orders.status') }}</th>
                    <th class="px-4 py-3 font-medium">{{ t('orders.total') }}</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="order in orders.data" :key="order.id">
                    <td class="px-4 py-3 text-gray-900">{{ order.order_number }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ formatDate(order.created_at) }}</td>
                    <td class="px-4 py-3">
                        <span :class="['rounded-full px-2 py-0.5 text-xs', statusClasses[order.status]]">
                            {{ t(`status.${order.status}`) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-900">{{ money(order.total) }}</td>
                    <td class="px-4 py-3 text-right">
                        <Link :href="`/orders/${order.id}`" class="text-gray-600 hover:text-gray-900">
                            {{ t('orders.view') }}
                        </Link>
                    </td>
                </tr>

                <tr v-if="orders.data.length === 0">
                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                        {{ t('orders.none') }}
                    </td>
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
