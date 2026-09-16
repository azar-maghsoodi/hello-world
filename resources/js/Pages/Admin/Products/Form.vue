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

    <h2 class="mb-6 text-xl font-semibold text-gray-900">
        {{ isEditing ? 'Edit product' : 'Add product' }}
    </h2>

    <form
        @submit.prevent="submit"
        class="max-w-3xl space-y-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm"
        enctype="multipart/form-data"
    >
        <div class="grid gap-4 sm:grid-cols-2">
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
                <label class="mb-1 block text-sm font-medium text-gray-700" for="sku">SKU</label>
                <input
                    id="sku"
                    v-model="form.sku"
                    type="text"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
                <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
            </div>
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
            <label class="mb-1 block text-sm font-medium text-gray-700" for="short_description">
                Short description
            </label>
            <input
                id="short_description"
                v-model="form.short_description"
                type="text"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            >
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="description">Description</label>
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
            />
        </div>

        <div class="grid gap-4 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="price">Price</label>
                <input
                    id="price"
                    v-model="form.price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
                <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="sale_price">Sale price</label>
                <input
                    id="sale_price"
                    v-model="form.sale_price"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
                <p v-if="form.errors.sale_price" class="mt-1 text-sm text-red-600">{{ form.errors.sale_price }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="quantity">Quantity</label>
                <input
                    id="quantity"
                    v-model.number="form.quantity"
                    type="number"
                    min="0"
                    required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="weight">Weight</label>
                <input
                    id="weight"
                    v-model="form.weight"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700" for="image">Image</label>
            <img
                v-if="product?.image"
                :src="`/storage/${product.image}`"
                class="mb-2 h-20 w-20 rounded-md border border-gray-200 object-cover"
            >
            <input
                id="image"
                type="file"
                accept="image/*"
                class="block w-full text-sm text-gray-600"
                @input="form.image = $event.target.files[0]"
            >
            <p v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</p>
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-gray-700">Categories</p>
            <div class="flex flex-wrap gap-3">
                <label
                    v-for="category in categoryOptions"
                    :key="category.id"
                    class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-1.5 text-sm"
                >
                    <input v-model="form.categories" type="checkbox" :value="category.id" class="rounded border-gray-300">
                    {{ category.name }}
                </label>
            </div>
        </div>

        <div>
            <p class="mb-2 text-sm font-medium text-gray-700">Attributes</p>
            <div class="flex flex-wrap gap-3">
                <label
                    v-for="attribute in attributeOptions"
                    :key="attribute.id"
                    class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-1.5 text-sm"
                >
                    <input v-model="form.attributes" type="checkbox" :value="attribute.id" class="rounded border-gray-300">
                    {{ attribute.name }}: {{ attribute.value }}
                </label>
                <p v-if="attributeOptions.length === 0" class="text-sm text-gray-500">
                    No attributes yet — add some on the Attributes page.
                </p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="meta_title">Meta title</label>
                <input
                    id="meta_title"
                    v-model="form.meta_title"
                    type="text"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700" for="meta_description">
                    Meta description
                </label>
                <input
                    id="meta_description"
                    v-model="form.meta_description"
                    type="text"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                >
            </div>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300">
                Active
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input v-model="form.is_featured" type="checkbox" class="rounded border-gray-300">
                Featured
            </label>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
        >
            {{ isEditing ? 'Save changes' : 'Create product' }}
        </button>
    </form>
</template>
