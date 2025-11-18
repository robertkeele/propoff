<template>
    <Head title="Create Group" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Group</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div class="mb-6">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Group Name
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    id="name"
                                    placeholder="My Awesome Group"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                <p class="mt-1 text-sm text-gray-500">Choose a unique name for your group</p>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                <h3 class="font-semibold text-blue-900 mb-2">What happens next?</h3>
                                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                                    <li>You'll be assigned as the group creator</li>
                                    <li>A unique join code will be generated</li>
                                    <li>Share the code with others to join your group</li>
                                    <li>You can manage members and participate in games together</li>
                                </ul>
                            </div>

                            <div class="flex justify-end space-x-4">
                                <Link
                                    :href="route('groups.index')"
                                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                                >
                                    Cancel
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Create Group
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('groups.store'));
};
</script>
