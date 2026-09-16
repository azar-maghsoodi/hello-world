<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);

const navigation = [
    { name: 'Dashboard', href: '/admin', routeName: 'admin.dashboard' },
    { name: 'Categories', href: '/admin/categories', routeName: 'admin.categories.index' },
    { name: 'Products', href: '/admin/products', routeName: 'admin.products.index' },
    { name: 'Attributes', href: '/admin/attributes', routeName: 'admin.attributes.index' },
    { name: 'Orders', href: '/admin/orders', routeName: 'admin.orders.index' },
    { name: 'Users', href: '/admin/users', routeName: 'admin.users.index' },
    { name: 'Languages', href: '/admin/languages', routeName: 'admin.languages.index' },
    { name: 'Settings', href: '/admin/settings', routeName: 'admin.settings.edit' },
];

const isCurrent = (routeName) => route().current(routeName) || route().current(`${routeName.replace('.index', '')}.*`);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <aside class="w-56 shrink-0 border-r border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-4">
                <Link href="/" class="text-base font-semibold text-gray-900">Shop Admin</Link>
            </div>

            <nav class="space-y-1 p-3 text-sm">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'block rounded-md px-3 py-2',
                        isCurrent(item.routeName)
                            ? 'bg-gray-900 text-white'
                            : 'text-gray-700 hover:bg-gray-100',
                    ]"
                >
                    {{ item.name }}
                </Link>
            </nav>
        </aside>

        <div class="flex-1">
            <header class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
                <h1 class="text-lg font-semibold text-gray-900">Admin</h1>

                <div class="flex items-center gap-4 text-sm">
                    <span class="text-gray-500">{{ user?.name }}</span>
                    <button type="button" class="text-gray-600 hover:text-gray-900" @click="logout">
                        Log out
                    </button>
                </div>
            </header>

            <div v-if="flashSuccess" class="px-6 pt-4">
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">
                    {{ flashSuccess }}
                </div>
            </div>

            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
