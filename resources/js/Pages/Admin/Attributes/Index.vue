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

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Product Attributes</h2>
            <p class="fi-page-subtitle">Reusable name/value pairs you can attach to products, e.g. Color: Red.</p>
        </div>
    </div>

    <form @submit.prevent="submitCreate" class="fi-card mb-6 flex max-w-lg items-end gap-3 p-4">
        <div class="fi-field flex-1">
            <label class="fi-label" for="new-name">Name</label>
            <input id="new-name" v-model="createForm.name" type="text" placeholder="e.g. Color" required class="fi-input">
        </div>
        <div class="fi-field flex-1">
            <label class="fi-label" for="new-value">Value</label>
            <input id="new-value" v-model="createForm.value" type="text" placeholder="e.g. Red" required class="fi-input">
        </div>
        <button type="submit" :disabled="createForm.processing" class="fi-btn-primary">
            Add
        </button>
    </form>
    <p v-if="createForm.errors.name" class="fi-error mb-4">{{ createForm.errors.name }}</p>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Value</th>
                    <th>Products</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="attribute in attributes" :key="attribute.id">
                    <template v-if="editingId === attribute.id">
                        <td class="!py-2">
                            <input v-model="editForm.name" type="text" class="fi-input">
                        </td>
                        <td class="!py-2">
                            <input v-model="editForm.value" type="text" class="fi-input">
                        </td>
                        <td>{{ attribute.products_count }}</td>
                        <td class="text-right">
                            <button type="button" class="fi-link mr-4" @click="submitEdit(attribute)">
                                Save
                            </button>
                            <button type="button" class="fi-link text-gray-500" @click="cancelEdit">
                                Cancel
                            </button>
                        </td>
                    </template>
                    <template v-else>
                        <td class="fi-cell-primary">{{ attribute.name }}</td>
                        <td>{{ attribute.value }}</td>
                        <td>{{ attribute.products_count }}</td>
                        <td class="text-right">
                            <button type="button" class="fi-link mr-4" @click="startEdit(attribute)">
                                Edit
                            </button>
                            <button type="button" class="fi-btn-danger-text" @click="destroy(attribute)">
                                Delete
                            </button>
                        </td>
                    </template>
                </tr>

                <tr v-if="attributes.length === 0">
                    <td colspan="4" class="fi-table-empty">No attributes yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
