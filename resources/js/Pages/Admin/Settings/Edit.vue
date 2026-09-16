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

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Store Settings</h2>
            <p class="fi-page-subtitle">Currency, tax, and shipping defaults applied to every order.</p>
        </div>
    </div>

    <form @submit.prevent="submit" class="max-w-lg">
        <div class="fi-form-section">
            <div class="fi-form-section-body">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="fi-field">
                        <label class="fi-label" for="currency_code">Currency code</label>
                        <input id="currency_code" v-model="form.currency_code" type="text" maxlength="3" required class="fi-input uppercase">
                        <p v-if="form.errors.currency_code" class="fi-error">{{ form.errors.currency_code }}</p>
                    </div>

                    <div class="fi-field">
                        <label class="fi-label" for="currency_symbol">Currency symbol</label>
                        <input id="currency_symbol" v-model="form.currency_symbol" type="text" required class="fi-input">
                    </div>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="tax_rate_percent">Tax rate (%)</label>
                    <input
                        id="tax_rate_percent"
                        v-model.number="form.tax_rate_percent"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        required
                        class="fi-input"
                    >
                    <p v-if="form.errors.tax_rate" class="fi-error">{{ form.errors.tax_rate }}</p>
                    <p class="fi-hint">
                        A single flat rate applied to every order. This is not automatic EU VAT compliance — VAT rates
                        and rules vary by country and product type, so confirm the right rate with a tax advisor or a
                        VAT service (e.g. for the EU's One-Stop-Shop scheme).
                    </p>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="shipping_cost">Shipping cost</label>
                    <input id="shipping_cost" v-model="form.shipping_cost" type="number" step="0.01" min="0" required class="fi-input">
                    <p class="fi-hint">A flat shipping cost applied to every order. Set to 0 for free shipping.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 px-6 py-4">
                <button type="submit" :disabled="form.processing" class="fi-btn-primary">
                    Save settings
                </button>
            </div>
        </div>
    </form>
</template>
