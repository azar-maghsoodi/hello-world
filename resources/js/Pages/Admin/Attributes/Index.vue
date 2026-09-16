<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    attributes: { type: Array, default: () => [] },
});

const createForm = useForm({ name: '', value: '' });

const submitCreate = () => {
    createForm.post(route('admin.attributes.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const editingId = ref(null);
const editForm = useForm({ name: '', value: '' });

const startEdit = (attribute) => {
    editingId.value = attribute.id;
    editForm.clearErrors();
    editForm.name = attribute.name;
    editForm.value = attribute.value;
};

const cancelEdit = () => {
    editingId.value = null;
};

const submitEdit = (attribute) => {
    editForm.put(route('admin.attributes.update', attribute.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const destroy = (attribute) => {
    if (confirm(`Delete attribute "${attribute.name}: ${attribute.value}"?`)) {
        router.delete(route('admin.attributes.destroy', attribute.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Attributes" />

    <h2 class="mb-6 text-xl font-semibold text-gray-900">Product Attributes</h2>

    <form
        @submit.prevent="submitCreate"
        class="mb-6 flex max-w-lg items-end gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
    >
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="new-name">Name</label>
            <input
                id="new-name"
                v-model="createForm.name"
                type="text"
                placeholder="e.g. Color"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700" for="new-value">Value</label>
            <input
                id="new-value"
                v-model="createForm.value"
                type="text"
                placeholder="e.g. Red"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>
        <button
            type="submit"
            :disabled="createForm.processing"
            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
        >
            Add
        </button>
    </form>
    <p v-if="createForm.errors.name" class="mb-4 text-sm text-red-600">{{ createForm.errors.name }}</p>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Value</th>
                    <th class="px-4 py-3 font-medium">Products</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="attribute in attributes" :key="attribute.id">
                    <template v-if="editingId === attribute.id">
                        <td class="px-4 py-2">
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                            >
                        </td>
                        <td class="px-4 py-2">
                            <input
                                v-model="editForm.value"
                                type="text"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                            >
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ attribute.products_count }}</td>
                        <td class="px-4 py-2 text-right">
                            <button
                                type="button"
                                class="mr-3 text-gray-900 hover:underline"
                                @click="submitEdit(attribute)"
                            >
                                Save
                            </button>
                            <button type="button" class="text-gray-500 hover:underline" @click="cancelEdit">
                                Cancel
                            </button>
                        </td>
                    </template>
                    <template v-else>
                        <td class="px-4 py-3 text-gray-900">{{ attribute.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ attribute.value }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ attribute.products_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                class="mr-3 text-gray-600 hover:text-gray-900"
                                @click="startEdit(attribute)"
                            >
                                Edit
                            </button>
                            <button type="button" class="text-red-600 hover:text-red-800" @click="destroy(attribute)">
                                Delete
                            </button>
                        </td>
                    </template>
                </tr>

                <tr v-if="attributes.length === 0">
                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">No attributes yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
