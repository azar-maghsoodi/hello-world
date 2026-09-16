<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    taxRate: { type: Number, default: 0 },
    profile: { type: Object, default: null },
});

const defaultAddress = [
    props.profile?.address_line1,
    props.profile?.address_line2,
    [props.profile?.city, props.profile?.state, props.profile?.postal_code].filter(Boolean).join(', '),
    props.profile?.country,
].filter(Boolean).join('\n');

const tax = computed(() => Math.round(props.subtotal * props.taxRate * 100) / 100);
const total = computed(() => Math.round((props.subtotal + tax.value) * 100) / 100);

const form = useForm({
    shipping_address: defaultAddress,
    notes: '',
});

const submit = () => {
    form.post(route('checkout.store'));
};
</script>

<template>
    <Head title="Checkout" />

    <h1 class="mb-6 text-2xl font-semibold text-gray-900">Checkout</h1>

    <div class="grid gap-8 md:grid-cols-2">
        <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="shipping_address">
                    Shipping address
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
                    Order notes <span class="text-gray-400">(optional)</span>
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
                Place order
            </button>
        </form>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-medium text-gray-700">Order summary</h2>

            <ul class="mb-4 space-y-2">
                <li v-for="item in items" :key="item.product.id" class="flex justify-between text-sm">
                    <span class="text-gray-700">{{ item.product.name }} × {{ item.quantity }}</span>
                    <span class="text-gray-900">${{ item.total.toFixed(2) }}</span>
                </li>
            </ul>

            <div class="space-y-1 border-t border-gray-200 pt-3 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>${{ subtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Tax</span>
                    <span>${{ tax.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between font-semibold text-gray-900">
                    <span>Total</span>
                    <span>${{ total.toFixed(2) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
