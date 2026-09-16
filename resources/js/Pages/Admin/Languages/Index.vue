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

    <h2 class="mb-2 text-xl font-semibold text-gray-900">Languages</h2>
    <p class="mb-6 max-w-2xl text-sm text-gray-500">
        Languages shown here appear in the storefront's language switcher. The storefront UI currently ships
        translations for English, French, German, Spanish, and Italian — adding a language with another code
        will make it selectable, but the site will fall back to English until translations for it are added.
    </p>

    <form
        @submit.prevent="submitCreate"
        class="mb-6 grid max-w-2xl grid-cols-2 gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-4"
    >
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="new-code">Code</label>
            <input
                id="new-code"
                v-model="createForm.code"
                type="text"
                placeholder="nl"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="new-name">Name</label>
            <input
                id="new-name"
                v-model="createForm.name"
                type="text"
                placeholder="Dutch"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="new-native">Native name</label>
            <input
                id="new-native"
                v-model="createForm.native_name"
                type="text"
                placeholder="Nederlands"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>
        <div class="flex items-end">
            <button
                type="submit"
                :disabled="createForm.processing"
                class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                Add language
            </button>
        </div>
        <p v-if="createForm.errors.code" class="col-span-full text-sm text-red-600">{{ createForm.errors.code }}</p>
    </form>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Code</th>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Native name</th>
                    <th class="px-4 py-3 font-medium">Active</th>
                    <th class="px-4 py-3 font-medium">Default</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="language in languages" :key="language.id">
                    <template v-if="editingId === language.id">
                        <td class="px-4 py-2">
                            <input
                                v-model="editForm.code"
                                type="text"
                                class="w-20 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                            >
                        </td>
                        <td class="px-4 py-2">
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                            >
                        </td>
                        <td class="px-4 py-2">
                            <input
                                v-model="editForm.native_name"
                                type="text"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                            >
                        </td>
                        <td class="px-4 py-2">
                            <input v-model="editForm.is_active" type="checkbox" class="rounded border-gray-300">
                        </td>
                        <td class="px-4 py-2">
                            <input v-model="editForm.is_default" type="checkbox" class="rounded border-gray-300">
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button
                                type="button"
                                class="mr-3 text-gray-900 hover:underline"
                                @click="submitEdit(language)"
                            >
                                Save
                            </button>
                            <button type="button" class="text-gray-500 hover:underline" @click="cancelEdit">
                                Cancel
                            </button>
                        </td>
                    </template>
                    <template v-else>
                        <td class="px-4 py-3 text-gray-900">{{ language.code }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ language.name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ language.native_name }}</td>
                        <td class="px-4 py-3">
                            <span
                                :class="[
                                    'rounded-full px-2 py-0.5 text-xs',
                                    language.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600',
                                ]"
                            >
                                {{ language.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="language.is_default" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                                Default
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                class="mr-3 text-gray-600 hover:text-gray-900"
                                @click="startEdit(language)"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                class="text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="language.is_default"
                                @click="destroy(language)"
                            >
                                Delete
                            </button>
                        </td>
                    </template>
                </tr>

                <tr v-if="languages.length === 0">
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">No languages yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
