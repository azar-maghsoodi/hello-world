<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    users: { type: Object, required: true },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);

const updateRole = (user, role) => {
    if (user.role === role) {
        return;
    }

    router.put(route('admin.users.update', user.id), { role }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Users" />

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Users</h2>
            <p class="fi-page-subtitle">Manage customer accounts and admin access.</p>
        </div>
    </div>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users.data" :key="user.id">
                    <td class="fi-cell-primary">{{ user.name }}</td>
                    <td>{{ user.email }}</td>
                    <td>
                        <select
                            :value="user.role"
                            :disabled="user.id === currentUserId"
                            class="fi-select w-auto disabled:opacity-50"
                            @change="updateRole(user, $event.target.value)"
                        >
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </td>
                </tr>

                <tr v-if="users.data.length === 0">
                    <td colspan="3" class="fi-table-empty">No users yet.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="users.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
        <Link
            v-for="(link, index) in users.links"
            :key="index"
            :href="link.url ?? '#'"
            :class="[
                'fi-pagination-link',
                link.active && 'fi-pagination-link-active',
                !link.url && 'fi-pagination-link-disabled',
            ]"
            v-html="link.label"
        />
    </div>
</template>
