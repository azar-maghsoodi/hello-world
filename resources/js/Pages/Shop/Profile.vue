<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

const props = defineProps({
    user: { type: Object, required: true },
    profile: { type: Object, default: null },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.profile?.phone ?? '',
    date_of_birth: props.profile?.date_of_birth ?? '',
    bio: props.profile?.bio ?? '',
    address_line1: props.profile?.address_line1 ?? '',
    address_line2: props.profile?.address_line2 ?? '',
    city: props.profile?.city ?? '',
    state: props.profile?.state ?? '',
    postal_code: props.profile?.postal_code ?? '',
    country: props.profile?.country ?? '',
});

const submit = () => {
    form.put(route('profile.update'));
};
</script>

<template>
    <Head title="My Profile" />

    <div class="mx-auto max-w-xl">
        <h1 class="mb-6 text-2xl font-semibold text-gray-900">My Profile</h1>

        <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="phone">Phone</label>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="date_of_birth">
                        Date of birth
                    </label>
                    <input
                        id="date_of_birth"
                        v-model="form.date_of_birth"
                        type="date"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="bio">Bio</label>
                <textarea
                    id="bio"
                    v-model="form.bio"
                    rows="3"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="address_line1">Address</label>
                <input
                    id="address_line1"
                    v-model="form.address_line1"
                    type="text"
                    placeholder="Address line 1"
                    class="mb-2 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
                <input
                    id="address_line2"
                    v-model="form.address_line2"
                    type="text"
                    placeholder="Address line 2 (optional)"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="city">City</label>
                    <input
                        id="city"
                        v-model="form.city"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="state">State</label>
                    <input
                        id="state"
                        v-model="form.state"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="postal_code">
                        Postal code
                    </label>
                    <input
                        id="postal_code"
                        v-model="form.postal_code"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="country">Country</label>
                    <input
                        id="country"
                        v-model="form.country"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                    >
                </div>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                Save changes
            </button>
        </form>
    </div>
</template>
