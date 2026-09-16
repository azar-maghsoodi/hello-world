<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    currency_code: props.settings.currency_code,
    currency_symbol: props.settings.currency_symbol,
    tax_rate_percent: Math.round(props.settings.tax_rate * 10000) / 100,
    shipping_cost: props.settings.shipping_cost,
});

const submit = () => {
    form.transform((data) => ({
        currency_code: data.currency_code,
        currency_symbol: data.currency_symbol,
        tax_rate: data.tax_rate_percent / 100,
        shipping_cost: data.shipping_cost,
    })).put(route('admin.settings.update'));
};
</script>

<template>
    <Head title="Settings" />

    <h2 class="mb-6 text-xl font-semibold text-gray-900">Store Settings</h2>

    <form @submit.prevent="submit" class="max-w-lg space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="currency_code">Currency code</label>
                <input
                    id="currency_code"
                    v-model="form.currency_code"
                    type="text"
                    maxlength="3"
                    required
                    class="w-full rounded-md border-gray-300 uppercase shadow-sm focus:border-blue-300 focus:ring"
                >
                <p v-if="form.errors.currency_code" class="mt-1 text-sm text-red-600">
                    {{ form.errors.currency_code }}
                </p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="currency_symbol">
                    Currency symbol
                </label>
                <input
                    id="currency_symbol"
                    v-model="form.currency_symbol"
                    type="text"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="tax_rate_percent">
                Tax rate (%)
            </label>
            <input
                id="tax_rate_percent"
                v-model.number="form.tax_rate_percent"
                type="number"
                step="0.01"
                min="0"
                max="100"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
            <p v-if="form.errors.tax_rate" class="mt-1 text-sm text-red-600">{{ form.errors.tax_rate }}</p>
            <p class="mt-1 text-xs text-gray-500">
                A single flat rate applied to every order. This is not automatic EU VAT compliance — VAT rates
                and rules vary by country and product type, so confirm the right rate with a tax advisor or a
                VAT service (e.g. for the EU's One-Stop-Shop scheme).
            </p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="shipping_cost">
                Shipping cost
            </label>
            <input
                id="shipping_cost"
                v-model="form.shipping_cost"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
            <p class="mt-1 text-xs text-gray-500">A flat shipping cost applied to every order. Set to 0 for free shipping.</p>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
        >
            Save settings
        </button>
    </form>
</template>
