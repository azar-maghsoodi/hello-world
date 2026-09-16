<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import { useTranslations } from '@/i18n';
import { useCurrency } from '@/utils/currency';

defineOptions({ layout: ShopLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    taxRate: { type: Number, default: 0 },
    shippingCost: { type: Number, default: 0 },
    profile: { type: Object, default: null },
});

const t = useTranslations();
const money = useCurrency();

const defaultAddress = [
    props.profile?.address_line1,
    props.profile?.address_line2,
    [props.profile?.city, props.profile?.state, props.profile?.postal_code].filter(Boolean).join(', '),
    props.profile?.country,
].filter(Boolean).join('\n');

const tax = computed(() => Math.round(props.subtotal * props.taxRate * 100) / 100);
const total = computed(() => Math.round((props.subtotal + tax.value + props.shippingCost) * 100) / 100);

const form = useForm({
    shipping_address: defaultAddress,
    notes: '',
});

const submit = () => {
    form.post(route('checkout.store'));
};
</script>

<template>
    <Head :title="t('checkout.title')" />

    <h1 class="mb-6 text-2xl font-semibold text-gray-900">{{ t('checkout.title') }}</h1>

    <div class="grid gap-8 md:grid-cols-2">
        <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="shipping_address">
                    {{ t('checkout.shippingAddress') }}
                </label>
                <textarea
                    id="shipping_address"
                    v-model="form.shipping_address"
                    rows="4"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                />
                <p v-if="form.errors.shipping_address" class="mt-1 text-sm text-red-600">
                    {{ form.errors.shipping_address }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="notes">
                    {{ t('checkout.orderNotes') }} <span class="text-gray-400">({{ t('checkout.optional') }})</span>
                </label>
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="2"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                />
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ t('checkout.placeOrder') }}
            </button>
        </form>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-medium text-gray-700">{{ t('checkout.orderSummary') }}</h2>

            <ul class="mb-4 space-y-2">
                <li v-for="item in items" :key="item.product.id" class="flex justify-between text-sm">
                    <span class="text-gray-700">{{ item.product.name }} × {{ item.quantity }}</span>
                    <span class="text-gray-900">{{ money(item.total) }}</span>
                </li>
            </ul>

            <div class="space-y-1 border-t border-gray-200 pt-3 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>{{ t('checkout.subtotal') }}</span>
                    <span>{{ money(subtotal) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>{{ t('checkout.tax') }}</span>
                    <span>{{ money(tax) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>{{ t('checkout.shipping') }}</span>
                    <span>{{ money(shippingCost) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-gray-900">
                    <span>{{ t('checkout.total') }}</span>
                    <span>{{ money(total) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
