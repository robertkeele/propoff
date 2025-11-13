<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

const props = defineProps({
    userGroups: Array,
    publicGroups: Array,
});

const showJoinModal = ref(false);
const joinForm = useForm({
    code: '',
});

const joinGroup = () => {
    joinForm.post(route('groups.join'), {
        onSuccess: () => {
            showJoinModal.value = false;
            joinForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Groups" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Groups</h2>
                <div class="flex gap-2">
                    <button
                        @click="showJoinModal = true"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        Join Group
                    </button>
                    <Link :href="route('groups.create')">
                        <PrimaryButton>
                            <PlusIcon class="w-5 h-5 mr-2" />
                            Create Group
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- My Groups -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">My Groups</h3>

                        <div v-if="userGroups.length === 0" class="text-center py-8 text-gray-500">
                            You're not a member of any groups yet.
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <Link
                                v-for="group in userGroups"
                                :key="group.id"
                                :href="route('groups.show', group.id)"
                                class="border rounded-lg p-4 hover:shadow-md transition"
                            >
                                <div class="flex items-start gap-3">
                                    <UserGroupIcon class="w-8 h-8 text-indigo-600 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 truncate">{{ group.name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ group.description }}</p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span>{{ group.users_count }} members</span>
                                            <span class="mx-2">•</span>
                                            <span class="font-mono text-xs">{{ group.code }}</span>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Public Groups -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Public Groups</h3>

                        <div v-if="publicGroups.length === 0" class="text-center py-8 text-gray-500">
                            No public groups available.
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="group in publicGroups"
                                :key="group.id"
                                class="border rounded-lg p-4 hover:shadow-md transition"
                            >
                                <div class="flex items-start gap-3">
                                    <UserGroupIcon class="w-8 h-8 text-gray-400 flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 truncate">{{ group.name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ group.description }}</p>
                                        <div class="mt-2 text-sm text-gray-500">
                                            {{ group.users_count }} members
                                        </div>
                                        <Link
                                            :href="route('groups.show', group.id)"
                                            class="mt-3 inline-block text-indigo-600 hover:text-indigo-900 text-sm"
                                        >
                                            View Group →
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Join Group Modal -->
        <div
            v-if="showJoinModal"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
            @click.self="showJoinModal = false"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Join a Group</h3>
                <form @submit.prevent="joinGroup">
                    <div class="mb-4">
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                            Group Code
                        </label>
                        <TextInput
                            id="code"
                            v-model="joinForm.code"
                            type="text"
                            class="w-full"
                            placeholder="Enter 8-character code"
                            required
                            autofocus
                        />
                        <p class="mt-1 text-sm text-gray-500">
                            Enter the group code provided by the group creator
                        </p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showJoinModal = false"
                            class="px-4 py-2 text-gray-700 hover:text-gray-900"
                        >
                            Cancel
                        </button>
                        <PrimaryButton type="submit" :disabled="joinForm.processing">
                            Join Group
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
