<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    product: { type: Object, default: null },
    categoryOptions: { type: Array, default: () => [] },
    attributeOptions: { type: Array, default: () => [] },
});

const isEditing = computed(() => props.product !== null);

const form = useForm({
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    sku: props.product?.sku ?? '',
    description: props.product?.description ?? '',
    short_description: props.product?.short_description ?? '',
    price: props.product?.price ?? '',
    sale_price: props.product?.sale_price ?? '',
    quantity: props.product?.quantity ?? 0,
    weight: props.product?.weight ?? '',
    is_active: props.product?.is_active ?? true,
    is_featured: props.product?.is_featured ?? false,
    meta_title: props.product?.meta_title ?? '',
    meta_description: props.product?.meta_description ?? '',
    image: null,
    categories: props.product?.categories?.map((c) => c.id) ?? [],
    attributes: props.product?.attributes?.map((a) => a.id) ?? [],
});

const submit = () => {
    if (isEditing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.products.update', props.product.id));
    } else {
        form.post(route('admin.products.store'));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit product' : 'Add product'" />

    <div class="fi-page-header">
        <h2 class="fi-page-title">{{ isEditing ? 'Edit product' : 'Add product' }}</h2>
    </div>

    <form @submit.prevent="submit" class="max-w-3xl space-y-6" enctype="multipart/form-data">
        <div class="fi-form-section">
            <div class="fi-form-section-body">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="fi-field">
                        <label class="fi-label" for="name">Name</label>
                        <input id="name" v-model="form.name" type="text" required class="fi-input">
                        <p v-if="form.errors.name" class="fi-error">{{ form.errors.name }}</p>
                    </div>

                    <div class="fi-field">
                        <label class="fi-label" for="sku">SKU</label>
                        <input id="sku" v-model="form.sku" type="text" required class="fi-input">
                        <p v-if="form.errors.sku" class="fi-error">{{ form.errors.sku }}</p>
                    </div>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="slug">
                        Slug <span class="fi-hint">(optional, generated from name)</span>
                    </label>
                    <input id="slug" v-model="form.slug" type="text" class="fi-input">
                    <p v-if="form.errors.slug" class="fi-error">{{ form.errors.slug }}</p>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="short_description">Short description</label>
                    <input id="short_description" v-model="form.short_description" type="text" class="fi-input">
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="description">Description</label>
                    <textarea id="description" v-model="form.description" rows="4" class="fi-textarea" />
                </div>

                <div class="grid gap-4 sm:grid-cols-4">
                    <div class="fi-field">
                        <label class="fi-label" for="price">Price</label>
                        <input id="price" v-model="form.price" type="number" step="0.01" min="0" required class="fi-input">
                        <p v-if="form.errors.price" class="fi-error">{{ form.errors.price }}</p>
                    </div>

                    <div class="fi-field">
                        <label class="fi-label" for="sale_price">Sale price</label>
                        <input id="sale_price" v-model="form.sale_price" type="number" step="0.01" min="0" class="fi-input">
                        <p v-if="form.errors.sale_price" class="fi-error">{{ form.errors.sale_price }}</p>
                    </div>

                    <div class="fi-field">
                        <label class="fi-label" for="quantity">Quantity</label>
                        <input id="quantity" v-model.number="form.quantity" type="number" min="0" required class="fi-input">
                    </div>

                    <div class="fi-field">
                        <label class="fi-label" for="weight">Weight</label>
                        <input id="weight" v-model="form.weight" type="number" step="0.01" min="0" class="fi-input">
                    </div>
                </div>

                <div class="fi-field">
                    <label class="fi-label" for="image">Image</label>
                    <img
                        v-if="product?.image"
                        :src="`/storage/${product.image}`"
                        class="mb-2 h-20 w-20 rounded-lg border border-gray-200 object-cover"
                    >
                    <input
                        id="image"
                        type="file"
                        accept="image/*"
                        class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                        @input="form.image = $event.target.files[0]"
                    >
                    <p v-if="form.errors.image" class="fi-error">{{ form.errors.image }}</p>
                </div>

                <div class="fi-field">
                    <p class="fi-label">Categories</p>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="category in categoryOptions" :key="category.id" class="fi-checkbox-tile">
                            <input v-model="form.categories" type="checkbox" :value="category.id" class="fi-checkbox">
                            {{ category.name }}
                        </label>
                    </div>
                </div>

                <div class="fi-field">
                    <p class="fi-label">Attributes</p>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="attribute in attributeOptions" :key="attribute.id" class="fi-checkbox-tile">
                            <input v-model="form.attributes" type="checkbox" :value="attribute.id" class="fi-checkbox">
                            {{ attribute.name }}: {{ attribute.value }}
                        </label>
                        <p v-if="attributeOptions.length === 0" class="fi-hint">
                            No attributes yet — add some on the Attributes page.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="fi-field">
                        <label class="fi-label" for="meta_title">Meta title</label>
                        <input id="meta_title" v-model="form.meta_title" type="text" class="fi-input">
                    </div>
                    <div class="fi-field">
                        <label class="fi-label" for="meta_description">Meta description</label>
                        <input id="meta_description" v-model="form.meta_description" type="text" class="fi-input">
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="form.is_active" type="checkbox" class="fi-checkbox">
                        Active
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input v-model="form.is_featured" type="checkbox" class="fi-checkbox">
                        Featured
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 px-6 py-4">
                <button type="submit" :disabled="form.processing" class="fi-btn-primary">
                    {{ isEditing ? 'Save changes' : 'Create product' }}
                </button>
            </div>
        </div>
    </form>
</template>
