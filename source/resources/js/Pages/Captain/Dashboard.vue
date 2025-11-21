<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    captainGroups: {
        type: Array,
        required: true,
    },
});

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        open: 'bg-green-100 text-green-800',
        locked: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-blue-100 text-blue-800',
        completed: 'bg-purple-100 text-purple-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getGradingSourceLabel = (source) => {
    return source === 'captain' ? 'Captain Grading' : 'Admin Grading';
};

const getGradingSourceColor = (source) => {
    return source === 'captain' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
};
</script>

<template>
    <Head title="Captain Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Captain Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome Message -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-2">Welcome, Captain!</h3>
                        <p class="text-gray-600">
                            Manage your groups, customize questions, and grade submissions below.
                        </p>
                    </div>
                </div>

                <!-- Groups List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold">Your Groups</h3>
                        </div>

                        <div v-if="captainGroups.length === 0" class="text-center py-12">
                            <p class="text-gray-500 mb-4">You are not a captain of any groups yet.</p>
                            <p class="text-sm text-gray-400">
                                Ask an admin for a captain invitation link to create your first group.
                            </p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="group in captainGroups"
                                :key="group.id"
                                class="border rounded-lg p-5 hover:shadow-md transition-shadow"
                            >
                                <!-- Group Header -->
                                <div class="mb-4">
                                    <h4 class="text-lg font-semibold mb-2">
                                        {{ group.name }}
                                    </h4>
                                    <div class="flex gap-2 mb-2">
                                        <span :class="getStatusColor(group.event.status)" class="px-2 py-1 rounded text-xs font-semibold">
                                            {{ group.event.status }}
                                        </span>
                                        <span :class="getGradingSourceColor(group.grading_source)" class="px-2 py-1 rounded text-xs font-semibold">
                                            {{ getGradingSourceLabel(group.grading_source) }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Event: {{ group.event.name }}
                                    </p>
                                </div>

                                <!-- Stats -->
                                <div class="mb-4 space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Members:</span>
                                        <span class="font-semibold">{{ group.members_count }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Questions:</span>
                                        <span class="font-semibold">{{ group.questions_count }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Submissions:</span>
                                        <span class="font-semibold">{{ group.submissions_count }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Join Code:</span>
                                        <span class="font-mono font-semibold">{{ group.join_code }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2">
                                    <Link
                                        :href="route('captain.groups.show', group.id)"
                                        class="w-full bg-blue-500 hover:bg-blue-600 text-white text-center py-2 px-4 rounded font-semibold transition"
                                    >
                                        Manage Group
                                    </Link>
                                    <Link
                                        :href="route('captain.groups.questions.index', group.id)"
                                        class="w-full bg-gray-500 hover:bg-gray-600 text-white text-center py-2 px-4 rounded font-semibold transition"
                                    >
                                        Manage Questions
                                    </Link>
                                    <Link
                                        :href="route('captain.groups.grading.index', group.id)"
                                        class="w-full bg-green-500 hover:bg-green-600 text-white text-center py-2 px-4 rounded font-semibold transition"
                                    >
                                        Grade Submissions
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
