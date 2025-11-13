<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { UserGroupIcon, PencilIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    group: Object,
    recentSubmissions: Array,
    isMember: Boolean,
});

const leaveGroup = () => {
    if (confirm('Are you sure you want to leave this group?')) {
        router.post(route('groups.leave', props.group.id));
    }
};

const copyGroupCode = () => {
    navigator.clipboard.writeText(props.group.code);
    alert('Group code copied to clipboard!');
};
</script>

<template>
    <Head :title="group.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <UserGroupIcon class="w-8 h-8 text-indigo-600" />
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ group.name }}</h2>
                        <p class="text-sm text-gray-600">{{ group.users.length }} members</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-if="$page.props.auth.user.id === group.created_by"
                        :href="route('groups.edit', group.id)"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        <PencilIcon class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                    <button
                        v-if="isMember && $page.props.auth.user.id !== group.created_by"
                        @click="leaveGroup"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                    >
                        <ArrowRightOnRectangleIcon class="w-4 h-4 mr-2" />
                        Leave Group
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Group Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">About</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ group.description || 'No description' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created by</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ group.creator.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Visibility</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800': group.is_public,
                                                    'bg-gray-100 text-gray-800': !group.is_public,
                                                }"
                                            >
                                                {{ group.is_public ? 'Public' : 'Private' }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Total Submissions</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ group.submissions_count || 0 }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Group Code</h3>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-600 mb-1">Share this code with others:</p>
                                            <p class="text-2xl font-mono font-bold text-gray-900">{{ group.code }}</p>
                                        </div>
                                        <button
                                            @click="copyGroupCode"
                                            class="px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Members -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Members ({{ group.users.length }})</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="user in group.users"
                                :key="user.id"
                                class="flex items-center gap-3 p-3 border rounded-lg"
                            >
                                <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-indigo-600 font-semibold">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-gray-900 truncate">
                                        {{ user.name }}
                                        <span v-if="user.id === group.created_by" class="text-xs text-gray-500">(Owner)</span>
                                    </div>
                                    <div class="text-sm text-gray-500 truncate">{{ user.email }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Submissions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Submissions</h3>

                        <div v-if="recentSubmissions.length === 0" class="text-center py-8 text-gray-500">
                            No submissions yet from this group.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="submission in recentSubmissions"
                                :key="submission.id"
                                class="flex items-center justify-between p-4 border rounded-lg hover:shadow-md transition"
                            >
                                <div>
                                    <div class="font-medium text-gray-900">{{ submission.user.name }}</div>
                                    <div class="text-sm text-gray-600">{{ submission.game.name }}</div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        {{ new Date(submission.submitted_at).toLocaleDateString() }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold"
                                        :class="{
                                            'text-green-600': submission.percentage >= 80,
                                            'text-yellow-600': submission.percentage >= 60 && submission.percentage < 80,
                                            'text-red-600': submission.percentage < 60,
                                        }"
                                    >
                                        {{ submission.percentage.toFixed(1) }}%
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ submission.total_score }} / {{ submission.possible_points }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
