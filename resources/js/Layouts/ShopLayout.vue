<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const cartCount = computed(() => page.props.cart?.count ?? 0);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <Link href="/" class="text-lg font-semibold text-gray-900">Shop</Link>

                <nav class="flex items-center gap-4 text-sm">
                    <Link href="/cart" class="relative text-gray-600 hover:text-gray-900">
                        Cart
                        <span
                            v-if="cartCount > 0"
                            class="absolute -top-2 -right-3 rounded-full bg-gray-900 px-1.5 py-0.5 text-xs text-white"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>
                    <template v-if="user">
                        <Link v-if="user.role === 'admin'" href="/admin" class="text-gray-600 hover:text-gray-900">
                            Admin panel
                        </Link>
                        <Link href="/orders" class="text-gray-600 hover:text-gray-900">My orders</Link>
                        <Link href="/profile" class="text-gray-600 hover:text-gray-900">{{ user.name }}</Link>
                        <button type="button" class="text-gray-600 hover:text-gray-900" @click="logout">
                            Log out
                        </button>
                    </template>
                    <template v-else>
                        <Link href="/login" class="text-gray-600 hover:text-gray-900">Log in</Link>
                        <Link
                            href="/register"
                            class="rounded-md bg-gray-900 px-3 py-1.5 text-white hover:bg-black"
                        >
                            Sign up
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <div v-if="flashSuccess || flashError" class="mx-auto mt-4 max-w-6xl px-4">
            <div
                v-if="flashSuccess"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800"
            >
                {{ flashSuccess }}
            </div>
            <div
                v-if="flashError"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800"
            >
                {{ flashError }}
            </div>
        </div>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
