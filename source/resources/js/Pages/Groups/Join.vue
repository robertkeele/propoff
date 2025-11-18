<template>
    <Head title="Join Group" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Join a Group</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <div class="flex items-center justify-center mb-6">
                                <UserGroupIcon class="w-16 h-16 text-indigo-600" />
                            </div>
                            <h3 class="text-center text-2xl font-bold text-gray-900 mb-2">Join a Group</h3>
                            <p class="text-center text-gray-600">Enter the group code to join</p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-6">
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Group Code
                                </label>
                                <input
                                    v-model="form.code"
                                    type="text"
                                    id="code"
                                    placeholder="ABC123"
                                    class="w-full text-center text-2xl font-mono uppercase border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                    maxlength="10"
                                />
                                <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">{{ form.errors.code }}</p>
                                <p class="mt-1 text-sm text-gray-500 text-center">Enter the code provided by your group creator</p>
                            </div>

                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <h4 class="font-semibold text-yellow-900 mb-2">Don't have a code?</h4>
                                <p class="text-sm text-yellow-800 mb-3">Ask your group administrator for the join code, or create your own group.</p>
                                <Link
                                    :href="route('groups.create')"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm rounded-md hover:bg-yellow-700"
                                >
                                    Create Your Own Group
                                </Link>
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
                                    Join Group
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
import { UserGroupIcon } from '@heroicons/vue/24/outline';

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('groups.join'), {
        onSuccess: () => form.reset(),
    });
};
</script>
