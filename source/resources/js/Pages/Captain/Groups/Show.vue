<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
});

const showJoinCode = ref(false);

const deleteForm = useForm({});

const deleteGroup = () => {
    if (confirm('Are you sure you want to delete this group? This action cannot be undone.')) {
        deleteForm.delete(route('captain.groups.destroy', props.group.id));
    }
};

const copyJoinCode = () => {
    navigator.clipboard.writeText(props.group.join_code);
    alert('Join code copied to clipboard!');
};
</script>

<template>
    <Head :title="`Group: ${group.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ group.name }}
                </h2>
                <Link
                    :href="route('captain.dashboard')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Group Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Group Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <span class="text-sm text-gray-600">Event:</span>
                                <p class="font-semibold">{{ group.event.name }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Event Status:</span>
                                <p class="font-semibold capitalize">{{ group.event.status }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Grading Source:</span>
                                <p class="font-semibold capitalize">{{ group.grading_source }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600">Join Code:</span>
                                <div class="flex items-center gap-2">
                                    <p class="font-mono font-semibold">{{ group.join_code }}</p>
                                    <button
                                        @click="copyJoinCode"
                                        class="text-xs bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded"
                                    >
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="group.description" class="mb-4">
                            <span class="text-sm text-gray-600">Description:</span>
                            <p class="mt-1">{{ group.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Statistics</h3>

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <p class="text-3xl font-bold text-blue-600">{{ stats.total_members }}</p>
                                <p class="text-sm text-gray-600">Members</p>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <p class="text-3xl font-bold text-green-600">{{ stats.total_captains }}</p>
                                <p class="text-sm text-gray-600">Captains</p>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <p class="text-3xl font-bold text-purple-600">{{ stats.total_questions }}</p>
                                <p class="text-sm text-gray-600">Questions</p>
                            </div>
                            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                <p class="text-3xl font-bold text-yellow-600">{{ stats.total_submissions }}</p>
                                <p class="text-sm text-gray-600">Submissions</p>
                            </div>
                            <div class="text-center p-4 bg-red-50 rounded-lg">
                                <p class="text-3xl font-bold text-red-600">{{ stats.answered_questions }}</p>
                                <p class="text-sm text-gray-600">Graded</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Link
                                :href="route('captain.groups.questions.index', group.id)"
                                class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
                            >
                                <svg class="w-12 h-12 text-blue-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-semibold">Manage Questions</span>
                                <span class="text-sm text-gray-600 text-center mt-1">Add, edit, or remove questions</span>
                            </Link>

                            <Link
                                :href="route('captain.groups.grading.index', group.id)"
                                class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition"
                            >
                                <svg class="w-12 h-12 text-green-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <span class="font-semibold">Grade Submissions</span>
                                <span class="text-sm text-gray-600 text-center mt-1">Set answers and calculate scores</span>
                            </Link>

                            <Link
                                :href="route('captain.groups.members.index', group.id)"
                                class="flex flex-col items-center p-6 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition"
                            >
                                <svg class="w-12 h-12 text-purple-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="font-semibold">Manage Members</span>
                                <span class="text-sm text-gray-600 text-center mt-1">View and manage group members</span>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Members List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Members ({{ group.members.length }})</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Role
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Joined
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="member in group.members" :key="member.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ member.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ member.email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                v-if="member.is_captain"
                                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"
                                            >
                                                Captain
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"
                                            >
                                                Member
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(member.joined_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 border-red-200">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Deleting this group will remove all associated data. This action cannot be undone.
                        </p>
                        <button
                            @click="deleteGroup"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-semibold"
                        >
                            Delete Group
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
