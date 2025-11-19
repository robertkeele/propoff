<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    TrophyIcon,
    UserGroupIcon,
    ChartBarIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    activeGames: Array,
    userGroups: Array,
    recentResults: Array,
    stats: Object,
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (status) => {
    const colors = {
        'draft': 'bg-gray-100 text-gray-800',
        'open': 'bg-propoff-green/20 text-propoff-dark-green',
        'locked': 'bg-propoff-orange/20 text-propoff-red',
        'completed': 'bg-propoff-dark-green/20 text-propoff-dark-green',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Welcome Message -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900">
                            Welcome back, {{ $page.props.auth.user.name }}!
                        </h3>
                        <p class="mt-2 text-gray-600">Here's what's happening with your games</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-md bg-blue-500 p-3">
                                    <TrophyIcon class="h-6 w-6 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Total Games</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.total_games }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-md bg-green-500 p-3">
                                    <ChartBarIcon class="h-6 w-6 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Submissions</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.total_submissions }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-md bg-purple-500 p-3">
                                    <UserGroupIcon class="h-6 w-6 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">My Groups</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.groups_count }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="rounded-md bg-yellow-500 p-3">
                                    <ClockIcon class="h-6 w-6 text-white" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Avg Score</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.average_score?.toFixed(1) || 0 }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Active Games -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Active Games</h3>
                                <Link
                                    :href="route('games.available')"
                                    class="text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    View all
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <Link
                                    v-for="game in activeGames"
                                    :key="game.id"
                                    :href="route('games.show', game.id)"
                                    class="block border-l-4 pl-4 py-2 hover:bg-gray-50 transition"
                                    :class="{
                                        'border-green-500': game.status === 'open',
                                        'border-yellow-500': game.status === 'locked',
                                    }"
                                >
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ game.title }}</p>
                                            <p class="text-xs text-gray-500">Event: {{ formatDate(game.event_date) }}</p>
                                        </div>
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                            :class="getStatusColor(game.status)"
                                        >
                                            {{ game.status }}
                                        </span>
                                    </div>
                                </Link>
                                <p v-if="activeGames.length === 0" class="text-sm text-gray-500 text-center py-4">
                                    No active games at the moment
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- My Groups -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">My Groups</h3>
                                <Link
                                    :href="route('groups.index')"
                                    class="text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    Manage
                                </Link>
                            </div>
                            <div class="space-y-3">
                                <Link
                                    v-for="group in userGroups"
                                    :key="group.id"
                                    :href="route('groups.show', group.id)"
                                    class="block p-3 border rounded-lg hover:bg-gray-50 transition"
                                >
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ group.name }}</p>
                                            <p class="text-xs text-gray-500">Code: {{ group.code }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm text-gray-600">{{ group.users_count }} members</p>
                                        </div>
                                    </div>
                                </Link>
                                <p v-if="userGroups.length === 0" class="text-sm text-gray-500 text-center py-4">
                                    You haven't joined any groups yet
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Results</h3>
                            <Link
                                :href="route('submissions.index')"
                                class="text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                View all
                            </Link>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Game</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="result in recentResults" :key="result.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Link
                                                :href="route('submissions.show', result.id)"
                                                class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                                            >
                                                {{ result.game.title }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ result.group.name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ result.percentage }}%
                                            </span>
                                            <span class="text-xs text-gray-500 ml-1">
                                                ({{ result.total_score }}/{{ result.possible_points }})
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(result.submitted_at) }}
                                        </td>
                                    </tr>
                                    <tr v-if="recentResults.length === 0">
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No submissions yet. Start playing games!
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <Link
                                :href="route('games.available')"
                                class="flex flex-col items-center justify-center p-6 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >
                                <TrophyIcon class="h-8 w-8 text-indigo-600 mb-2" />
                                <span class="text-sm font-medium text-gray-900">Browse Games</span>
                            </Link>
                            <Link
                                :href="route('groups.index')"
                                class="flex flex-col items-center justify-center p-6 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >
                                <UserGroupIcon class="h-8 w-8 text-indigo-600 mb-2" />
                                <span class="text-sm font-medium text-gray-900">My Groups</span>
                            </Link>
                            <Link
                                :href="route('leaderboards.index')"
                                class="flex flex-col items-center justify-center p-6 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >
                                <ChartBarIcon class="h-8 w-8 text-indigo-600 mb-2" />
                                <span class="text-sm font-medium text-gray-900">Leaderboards</span>
                            </Link>
                            <Link
                                :href="route('submissions.index')"
                                class="flex flex-col items-center justify-center p-6 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                            >
                                <ClockIcon class="h-8 w-8 text-indigo-600 mb-2" />
                                <span class="text-sm font-medium text-gray-900">My Submissions</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
