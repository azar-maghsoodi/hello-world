<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    category: { type: Object, default: null },
    parentOptions: { type: Array, default: () => [] },
});

const isEditing = computed(() => props.category !== null);

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    parent_id: props.category?.parent_id ?? '',
    description: props.category?.description ?? '',
    is_active: props.category?.is_active ?? true,
    sort_order: props.category?.sort_order ?? 0,
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.categories.update', props.category.id));
    } else {
        form.post(route('admin.categories.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit category' : 'Add category'" />

    <h2 class="mb-6 text-xl font-semibold text-gray-900">
        {{ isEditing ? 'Edit category' : 'Add category' }}
    </h2>

    <form @submit.prevent="submit" class="max-w-xl space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="name">Name</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="slug">
                Slug <span class="text-gray-400">(optional, generated from name)</span>
            </label>
            <input
                id="slug"
                v-model="form.slug"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
            <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="parent_id">Parent category</label>
            <select
                id="parent_id"
                v-model="form.parent_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
                <option value="">None (top-level category)</option>
                <option v-for="parent in parentOptions" :key="parent.id" :value="parent.id">
                    {{ parent.name }}
                </option>
            </select>
            <p v-if="form.errors.parent_id" class="mt-1 text-sm text-red-600">{{ form.errors.parent_id }}</p>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="description">Description</label>
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            />
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="sort_order">Sort order</label>
            <input
                id="sort_order"
                v-model.number="form.sort_order"
                type="number"
                min="0"
                class="w-32 rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300">
            Active
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
            >
                {{ isEditing ? 'Save changes' : 'Create category' }}
            </button>
        </div>
    </form>
</template>
