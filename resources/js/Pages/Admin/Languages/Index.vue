<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    languages: { type: Array, default: () => [] },
});

const createForm = useForm({
    code: '',
    name: '',
    native_name: '',
    is_active: true,
    is_default: false,
});

const submitCreate = () => {
    createForm.post(route('admin.languages.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const editingId = ref(null);
const editForm = useForm({ code: '', name: '', native_name: '', is_active: true, is_default: false });

const startEdit = (language) => {
    editingId.value = language.id;
    editForm.clearErrors();
    editForm.code = language.code;
    editForm.name = language.name;
    editForm.native_name = language.native_name;
    editForm.is_active = language.is_active;
    editForm.is_default = language.is_default;
};

const cancelEdit = () => {
    editingId.value = null;
};

const submitEdit = (language) => {
    editForm.put(route('admin.languages.update', language.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const destroy = (language) => {
    if (confirm(`Remove language "${language.name}"?`)) {
        router.delete(route('admin.languages.destroy', language.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Languages" />

    <div class="fi-page-header">
        <div>
            <h2 class="fi-page-title">Languages</h2>
            <p class="fi-page-subtitle max-w-2xl">
                Languages shown here appear in the storefront's language switcher. The storefront UI currently ships
                translations for English, French, German, Spanish, and Italian — adding a language with another code
                will make it selectable, but the site will fall back to English until translations for it are added.
            </p>
        </div>
    </div>

    <form @submit.prevent="submitCreate" class="fi-card mb-6 grid max-w-2xl grid-cols-2 gap-3 p-4 sm:grid-cols-4">
        <div class="fi-field">
            <label class="fi-label" for="new-code">Code</label>
            <input id="new-code" v-model="createForm.code" type="text" placeholder="nl" required class="fi-input">
        </div>
        <div class="fi-field">
            <label class="fi-label" for="new-name">Name</label>
            <input id="new-name" v-model="createForm.name" type="text" placeholder="Dutch" required class="fi-input">
        </div>
        <div class="fi-field">
            <label class="fi-label" for="new-native">Native name</label>
            <input id="new-native" v-model="createForm.native_name" type="text" placeholder="Nederlands" required class="fi-input">
        </div>
        <div class="flex items-end">
            <button type="submit" :disabled="createForm.processing" class="fi-btn-primary w-full">
                Add language
            </button>
        </div>
        <p v-if="createForm.errors.code" class="fi-error col-span-full">{{ createForm.errors.code }}</p>
    </form>

    <div class="fi-table-wrapper">
        <table class="fi-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Native name</th>
                    <th>Active</th>
                    <th>Default</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="language in languages" :key="language.id">
                    <template v-if="editingId === language.id">
                        <td class="!py-2">
                            <input v-model="editForm.code" type="text" class="fi-input w-20">
                        </td>
                        <td class="!py-2">
                            <input v-model="editForm.name" type="text" class="fi-input">
                        </td>
                        <td class="!py-2">
                            <input v-model="editForm.native_name" type="text" class="fi-input">
                        </td>
                        <td>
                            <input v-model="editForm.is_active" type="checkbox" class="fi-checkbox">
                        </td>
                        <td>
                            <input v-model="editForm.is_default" type="checkbox" class="fi-checkbox">
                        </td>
                        <td class="text-right">
                            <button type="button" class="fi-link mr-4" @click="submitEdit(language)">
                                Save
                            </button>
                            <button type="button" class="fi-link text-gray-500" @click="cancelEdit">
                                Cancel
                            </button>
                        </td>
                    </template>
                    <template v-else>
                        <td class="fi-cell-primary">{{ language.code }}</td>
                        <td>{{ language.name }}</td>
                        <td>{{ language.native_name }}</td>
                        <td>
                            <span :class="language.is_active ? 'fi-badge-success' : 'fi-badge-gray'">
                                {{ language.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <span v-if="language.is_default" class="fi-badge-info">Default</span>
                        </td>
                        <td class="text-right">
                            <button type="button" class="fi-link mr-4" @click="startEdit(language)">
                                Edit
                            </button>
                            <button
                                type="button"
                                class="fi-btn-danger-text disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="language.is_default"
                                @click="destroy(language)"
                            >
                                Delete
                            </button>
                        </td>
                    </template>
                </tr>

                <tr v-if="languages.length === 0">
                    <td colspan="6" class="fi-table-empty">No languages yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
