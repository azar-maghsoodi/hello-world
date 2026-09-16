<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ShopLayout from '@/Layouts/ShopLayout.vue';

defineOptions({ layout: ShopLayout });

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Sign up" />

    <div class="mx-auto max-w-sm">
        <h1 class="mb-6 text-2xl font-semibold text-gray-900">Create an account</h1>

        <form @submit.prevent="submit" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
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

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="password">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="password_confirmation">
                    Confirm password
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                Sign up
            </button>
        </form>

        <p class="mt-4 text-sm text-gray-600">
            Already have an account?
            <Link href="/login" class="text-gray-900 underline">Log in</Link>
        </p>
    </div>
</template>
