<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flashSuccess = computed(() => page.props.flash?.success);

const icons = {
    dashboard: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    categories: '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>',
    products: '<path d="M12 3 4 7v10l8 4 8-4V7l-8-4Z"/><path d="M4 7l8 4 8-4"/><path d="M12 11v10"/>',
    attributes: '<line x1="4" y1="6" x2="20" y2="6"/><circle cx="9" cy="6" r="1.75"/><line x1="4" y1="12" x2="20" y2="12"/><circle cx="15" cy="12" r="1.75"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="7" cy="18" r="1.75"/>',
    orders: '<path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/>',
    users: '<circle cx="8" cy="8" r="3"/><path d="M2 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="8" r="2.5"/><path d="M14.5 14.2c3 .5 5.5 3 5.5 5.8"/>',
    languages: '<circle cx="12" cy="12" r="9"/><line x1="3" y1="12" x2="21" y2="12"/><path d="M12 3c2.5 2.5 4 5.7 4 9s-1.5 6.5-4 9c-2.5-2.5-4-5.7-4-9s1.5-6.5 4-9Z"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M22 12h-3M5 12H2M19.07 4.93l-2.12 2.12M7.05 16.95l-2.12 2.12M19.07 19.07l-2.12-2.12M7.05 7.05 4.93 4.93"/>',
};

const navigation = [
    { name: 'Dashboard', href: '/admin', routeName: 'admin.dashboard', icon: 'dashboard' },
    {
        group: 'Catalog',
        items: [
            { name: 'Categories', href: '/admin/categories', routeName: 'admin.categories.index', icon: 'categories' },
            { name: 'Products', href: '/admin/products', routeName: 'admin.products.index', icon: 'products' },
            { name: 'Attributes', href: '/admin/attributes', routeName: 'admin.attributes.index', icon: 'attributes' },
        ],
    },
    {
        group: 'Sales',
        items: [
            { name: 'Orders', href: '/admin/orders', routeName: 'admin.orders.index', icon: 'orders' },
        ],
    },
    {
        group: 'Configuration',
        items: [
            { name: 'Users', href: '/admin/users', routeName: 'admin.users.index', icon: 'users' },
            { name: 'Languages', href: '/admin/languages', routeName: 'admin.languages.index', icon: 'languages' },
            { name: 'Settings', href: '/admin/settings', routeName: 'admin.settings.edit', icon: 'settings' },
        ],
    },
];

const isCurrent = (routeName) => route().current(routeName) || route().current(`${routeName.replace('.index', '')}.*`);

const initials = computed(() =>
    (user.value?.name ?? '')
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-50">
        <aside class="fi-sidebar">
            <Link href="/" class="fi-sidebar-brand">
                <span class="flex size-7 items-center justify-center rounded-lg bg-primary-600 text-sm font-bold text-white">S</span>
                Shop Admin
            </Link>

            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <Link
                    v-for="item in navigation.filter((entry) => entry.name)"
                    :key="item.name"
                    :href="item.href"
                    :class="['fi-nav-link', isCurrent(item.routeName) && 'fi-nav-link-active']"
                >
                    <svg class="fi-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" v-html="icons[item.icon]" />
                    {{ item.name }}
                </Link>

                <template v-for="group in navigation.filter((entry) => entry.group)" :key="group.group">
                    <p class="fi-nav-group-label">{{ group.group }}</p>
                    <Link
                        v-for="item in group.items"
                        :key="item.name"
                        :href="item.href"
                        :class="['fi-nav-link', isCurrent(item.routeName) && 'fi-nav-link-active']"
                    >
                        <svg class="fi-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" v-html="icons[item.icon]" />
                        {{ item.name }}
                    </Link>
                </template>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="fi-topbar">
                <div />

                <div class="flex items-center gap-3">
                    <div class="flex size-8 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700">
                        {{ initials }}
                    </div>
                    <span class="text-sm font-medium text-gray-700">{{ user?.name }}</span>
                    <button type="button" class="fi-btn-secondary !px-3 !py-1.5 text-xs" @click="logout">
                        Log out
                    </button>
                </div>
            </header>

            <div v-if="flashSuccess" class="px-6 pt-6">
                <div class="fi-alert-success">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                    {{ flashSuccess }}
                </div>
            </div>

            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
