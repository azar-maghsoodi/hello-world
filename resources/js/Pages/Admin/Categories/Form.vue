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

    <div class="fi-page-header">
        <h2 class="fi-page-title">{{ isEditing ? 'Edit category' : 'Add category' }}</h2>
    </div>

    <form @submit.prevent="submit" class="max-w-xl">
        <div class="fi-form-section">
            <div class="fi-form-section-body">
                <div class="fi-field">
                    <label class="fi-label" for="name">Name</label>
                    <input id="name" v-model="form.name" type="text" required class="fi-input">
                    <p v-if="form.errors.name" class="fi-error">{{ form.errors.name }}</p>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="slug">
                        Slug <span class="fi-hint">(optional, generated from name)</span>
                    </label>
                    <input id="slug" v-model="form.slug" type="text" class="fi-input">
                    <p v-if="form.errors.slug" class="fi-error">{{ form.errors.slug }}</p>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="parent_id">Parent category</label>
                    <select id="parent_id" v-model="form.parent_id" class="fi-select">
                        <option value="">None (top-level category)</option>
                        <option v-for="parent in parentOptions" :key="parent.id" :value="parent.id">
                            {{ parent.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.parent_id" class="fi-error">{{ form.errors.parent_id }}</p>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="description">Description</label>
                    <textarea id="description" v-model="form.description" rows="3" class="fi-textarea" />
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="sort_order">Sort order</label>
                    <input id="sort_order" v-model.number="form.sort_order" type="number" min="0" class="fi-input w-32">
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="fi-checkbox">
                    Active
                </label>
            </div>

            <div class="flex items-center gap-3 px-6 py-4">
                <button type="submit" :disabled="form.processing" class="fi-btn-primary">
                    {{ isEditing ? 'Save changes' : 'Create category' }}
                </button>
            </div>
        </div>
    </form>
</template>
