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

    <h2 class="mb-6 text-xl font-semibold text-gray-900">Users</h2>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="user in users.data" :key="user.id">
                    <td class="px-4 py-3 text-gray-900">{{ user.name }}</td>
                    <td class="px-4 py-3 text-gray-500">{{ user.email }}</td>
                    <td class="px-4 py-3">
                        <select
                            :value="user.role"
                            :disabled="user.id === currentUserId"
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-300 focus:ring disabled:opacity-50"
                            @change="updateRole(user, $event.target.value)"
                        >
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </td>
                </tr>

                <tr v-if="users.data.length === 0">
                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">No users yet.</td>
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
                'rounded-md px-3 py-1 text-sm',
                link.active ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100',
                !link.url && 'pointer-events-none opacity-50',
            ]"
            v-html="link.label"
        />
    </div>
</template>
