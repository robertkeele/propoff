<template>
    <Head :title="`${event.name} - ${group.name} Leaderboard`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <div class="flex items-center text-sm text-gray-500 mb-2">
                    <Link :href="route('leaderboards.event', event.id)" class="hover:text-gray-700">{{ event.name }}</Link>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ group.name }}</span>
                </div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Group Leaderboard</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Total Participants</div>
                        <div class="text-2xl font-bold text-gray-900">{{ leaderboard.data.length }}</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Average Score</div>
                        <div class="text-2xl font-bold text-gray-900">{{ averagePercentage }}%</div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Highest Score</div>
                        <div class="text-2xl font-bold text-gray-900">{{ highestPercentage }}%</div>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Rankings</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Answered</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="entry in leaderboard.data"
                                        :key="entry.id"
                                        :class="entry.user_id === $page.props.auth.user?.id ? 'bg-indigo-50' : ''"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span v-if="entry.rank === 1" class="text-2xl">🥇</span>
                                                <span v-else-if="entry.rank === 2" class="text-2xl">🥈</span>
                                                <span v-else-if="entry.rank === 3" class="text-2xl">🥉</span>
                                                <span v-else class="text-lg font-bold text-gray-900 ml-2">#{{ entry.rank }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ entry.user.name }}
                                                        <span v-if="entry.user_id === $page.props.auth.user?.id" class="ml-2 text-xs text-indigo-600">(You)</span>
                                                    </div>
                                                    <div v-if="entry.user.email" class="text-sm text-gray-500">{{ entry.user.email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">{{ entry.total_score }}/{{ entry.possible_points }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div
                                                        class="bg-indigo-600 h-2 rounded-full"
                                                        :style="{ width: entry.percentage + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-900">{{ parseFloat(entry.percentage).toFixed(1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ entry.answered_count }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ entry.created_at ? formatDate(entry.created_at) : 'N/A' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="leaderboard.data.length === 0" class="text-center py-12">
                            <TrophyIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No entries yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Be the first to complete this event!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { TrophyIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    event: Object,
    group: Object,
    leaderboard: Object, // Paginated object
});

const averagePercentage = computed(() => {
    if (props.leaderboard.data.length === 0) return 0;
    const total = props.leaderboard.data.reduce((sum, entry) => sum + parseFloat(entry.percentage), 0);
    return (total / props.leaderboard.data.length).toFixed(1);
});

const highestPercentage = computed(() => {
    if (props.leaderboard.data.length === 0) return 0;
    return Math.max(...props.leaderboard.data.map(entry => parseFloat(entry.percentage))).toFixed(1);
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
