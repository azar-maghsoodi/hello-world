<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

defineProps({
    tasks: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    title: '',
    description: '',
});

const submit = () => {
    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const toggleCompleted = (task) => {
    router.put(route('tasks.update', task.id), {
        completed: !task.completed,
    }, {
        preserveScroll: true,
    });
};

const destroy = (task) => {
    if (confirm('Delete this task?')) {
        router.delete(route('tasks.destroy', task.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Tasks" />

    <div class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-xl px-4">
            <h1 class="mb-6 text-2xl font-semibold text-gray-900">Tasks</h1>

            <form @submit.prevent="submit" class="mb-8 space-y-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="title">Title</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                        placeholder="What needs to be done?"
                    >
                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700" for="description">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="2"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring"
                        placeholder="Optional details"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Add task
                </button>
            </form>

            <ul class="space-y-2">
                <li
                    v-for="task in tasks"
                    :key="task.id"
                    class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            :checked="task.completed"
                            class="mt-1 h-4 w-4 rounded border-gray-300"
                            @change="toggleCompleted(task)"
                        >
                        <div>
                            <p :class="['font-medium', task.completed ? 'text-gray-400 line-through' : 'text-gray-900']">
                                {{ task.title }}
                            </p>
                            <p v-if="task.description" class="text-sm text-gray-500">{{ task.description }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="text-sm text-red-600 hover:text-red-800"
                        @click="destroy(task)"
                    >
                        Delete
                    </button>
                </li>

                <li v-if="tasks.length === 0" class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                    No tasks yet. Add one above.
                </li>
            </ul>
        </div>
    </div>
</template>
