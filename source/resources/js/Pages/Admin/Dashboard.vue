<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    UserGroupIcon,
    TrophyIcon,
    UsersIcon,
    DocumentTextIcon,
    ChartBarIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';

defineProps({
    stats: Object,
    recentEvents: Array,
    recentSubmissions: Array,
    recentUsers: Array,
    eventsByStatus: Object,
});

const statCards = [
    { name: 'Total Events', key: 'total_events', icon: TrophyIcon, color: 'bg-blue-500' },
    { name: 'Total Questions', key: 'total_questions', icon: DocumentTextIcon, color: 'bg-green-500' },
    { name: 'Total Users', key: 'total_users', icon: UsersIcon, color: 'bg-purple-500' },
    { name: 'Total Groups', key: 'total_groups', icon: UserGroupIcon, color: 'bg-yellow-500' },
    { name: 'Total Submissions', key: 'total_submissions', icon: ChartBarIcon, color: 'bg-indigo-500' },
    { name: 'Completed Submissions', key: 'completed_submissions', icon: ClockIcon, color: 'bg-pink-500' },
];

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Link
                                :href="route('admin.events.create')"
                                class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <TrophyIcon class="h-5 w-5 mr-2 text-gray-400" />
                                Events
                            </Link>
                            <Link
                                :href="route('admin.question-templates.index')"
                                class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <DocumentTextIcon class="h-5 w-5 mr-2 text-gray-400" />
                                Templates
                            </Link>
                            <Link
                                :href="route('admin.users.index')"
                                class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <UsersIcon class="h-5 w-5 mr-2 text-gray-400" />
                                Users
                            </Link>
                            <Link
                                :href="route('admin.groups.index')"
                                class="flex items-center justify-center px-4 py-3 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <UserGroupIcon class="h-5 w-5 mr-2 text-gray-400" />
                                Groups
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="stat in statCards"
                        :key="stat.key"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div :class="[stat.color, 'rounded-md p-3']">
                                    <component :is="stat.icon" class="h-6 w-6 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">{{ stat.name }}</p>
                                    <p class="text-2xl font-semibold text-gray-900">
                                        {{ stats[stat.key] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events by Status -->
                <!-- <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Events by Status</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div
                                v-for="(count, status) in eventsByStatus"
                                :key="status"
                                class="text-center p-4 border rounded-lg"
                            >
                                <p class="text-sm text-gray-500 capitalize">{{ status }}</p>
                                <p class="text-2xl font-bold text-gray-900">{{ count }}</p>
                            </div>
                        </div>
                    </div>
                </div> -->

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Events -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Recent Events</h3>
                                <Link
                                    :href="route('admin.events.index')"
                                    class="text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    View all
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="event in recentEvents"
                                    :key="event.id"
                                    class="border-l-4 pl-4 py-2"
                                    :class="{
                                        'border-gray-300': event.status === 'draft',
                                        'border-green-500': event.status === 'open',
                                        'border-yellow-500': event.status === 'locked',
                                        'border-blue-500': event.status === 'completed',
                                    }"
                                >
                                    <Link
                                        :href="route('admin.events.show', event.id)"
                                        class="text-sm font-medium text-gray-900 hover:text-indigo-600"
                                    >
                                        {{ event.name }}
                                    </Link>
                                    <p class="text-xs text-gray-500">
                                        {{ event.questions_count }} questions • Created {{ formatDate(event.created_at) }}
                                    </p>
                                </div>
                                <p v-if="recentEvents.length === 0" class="text-sm text-gray-500 text-center py-4">
                                    No recent events
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Submissions -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Submissions</h3>
                            <div class="space-y-3">
                                <div
                                    v-for="submission in recentSubmissions"
                                    :key="submission.id"
                                    class="flex justify-between items-start border-b pb-2"
                                >
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ submission.user_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ submission.event_name }} • {{ submission.group_name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-gray-900">
                                            {{ submission.percentage }}%
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ formatDate(submission.submitted_at) }}
                                        </p>
                                    </div>
                                </div>
                                <p v-if="recentSubmissions.length === 0" class="text-sm text-gray-500 text-center py-4">
                                    No recent submissions
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Users</h3>
                            <Link
                                :href="route('admin.users.index')"
                                class="text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                View all
                            </Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in recentUsers" :key="user.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <Link
                                                :href="route('admin.users.show', user.id)"
                                                class="hover:text-indigo-600"
                                            >
                                                {{ user.name }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="{
                                                    'bg-purple-100 text-purple-800': user.role === 'admin',
                                                    'bg-gray-100 text-gray-800': user.role === 'user',
                                                }"
                                            >
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="recentUsers.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No recent users
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
