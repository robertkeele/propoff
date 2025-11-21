<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { TrophyIcon } from '@heroicons/vue/24/solid';

defineProps({
    event: Object,
    leaderboard: Object,
});

const getRankColor = (rank) => {
    if (rank === 1) return 'text-yellow-500';
    if (rank === 2) return 'text-gray-400';
    if (rank === 3) return 'text-orange-600';
    return 'text-gray-600';
};

const getRankBg = (rank) => {
    if (rank === 1) return 'bg-yellow-50';
    if (rank === 2) return 'bg-gray-50';
    if (rank === 3) return 'bg-orange-50';
    return 'bg-white';
};
</script>

<template>
    <Head :title="`Leaderboard - ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <TrophyIcon class="w-8 h-8 text-yellow-500" />
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ event.name }} Leaderboard</h2>
                    <p class="text-sm text-gray-600">Global Rankings</p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="leaderboard.data.length === 0" class="text-center py-12">
                            <TrophyIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                            <p class="text-gray-500">No submissions yet. Be the first to play!</p>
                            <Link
                                :href="route('events.play', event.id)"
                                class="mt-4 inline-block text-indigo-600 hover:text-indigo-900"
                            >
                                Play Now →
                            </Link>
                        </div>

                        <!-- Top 3 Podium -->
                        <div v-else-if="leaderboard.data.length >= 3" class="mb-8">
                            <div class="flex items-end justify-center gap-4 mb-8">
                                <!-- 2nd Place -->
                                <div class="flex flex-col items-center" v-if="leaderboard.data[1]">
                                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-2">
                                        <span class="text-3xl font-bold text-gray-400">2</span>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-semibold text-gray-900">{{ leaderboard.data[1].user.name }}</div>
                                        <div class="text-sm text-gray-600">{{ leaderboard.data[1].total_score }} pts</div>
                                        <div class="text-xs text-gray-500">{{ leaderboard.data[1].percentage }}%</div>
                                    </div>
                                    <div class="w-24 h-20 bg-gray-200 mt-4"></div>
                                </div>

                                <!-- 1st Place -->
                                <div class="flex flex-col items-center" v-if="leaderboard.data[0]">
                                    <TrophyIcon class="w-10 h-10 text-yellow-500 mb-2" />
                                    <div class="w-24 h-24 rounded-full bg-yellow-100 flex items-center justify-center mb-2">
                                        <span class="text-4xl font-bold text-yellow-600">1</span>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-bold text-lg text-gray-900">{{ leaderboard.data[0].user.name }}</div>
                                        <div class="text-sm font-semibold text-yellow-600">{{ leaderboard.data[0].total_score }} pts</div>
                                        <div class="text-xs text-gray-500">{{ leaderboard.data[0].percentage }}%</div>
                                    </div>
                                    <div class="w-24 h-28 bg-yellow-200 mt-4"></div>
                                </div>

                                <!-- 3rd Place -->
                                <div class="flex flex-col items-center" v-if="leaderboard.data[2]">
                                    <div class="w-20 h-20 rounded-full bg-orange-100 flex items-center justify-center mb-2">
                                        <span class="text-3xl font-bold text-orange-600">3</span>
                                    </div>
                                    <div class="text-center">
                                        <div class="font-semibold text-gray-900">{{ leaderboard.data[2].user.name }}</div>
                                        <div class="text-sm text-gray-600">{{ leaderboard.data[2].total_score }} pts</div>
                                        <div class="text-xs text-gray-500">{{ leaderboard.data[2].percentage }}%</div>
                                    </div>
                                    <div class="w-24 h-16 bg-orange-200 mt-4"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Leaderboard Table -->
                        <div v-if="leaderboard.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Rank
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Player
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Score
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Percentage
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Answered
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr
                                        v-for="entry in leaderboard.data"
                                        :key="entry.id"
                                        :class="[
                                            getRankBg(entry.rank),
                                            entry.user_id === $page.props.auth.user.id ? 'ring-2 ring-indigo-500' : ''
                                        ]"
                                    >
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span
                                                    class="text-2xl font-bold"
                                                    :class="getRankColor(entry.rank)"
                                                >
                                                    #{{ entry.rank }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ entry.user.name }}
                                                        <span
                                                            v-if="entry.user_id === $page.props.auth.user.id"
                                                            class="ml-2 text-xs text-indigo-600"
                                                        >
                                                            (You)
                                                        </span>
                                                    </div>
                                                    <div class="text-sm text-gray-500">{{ entry.user.email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ entry.total_score }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                / {{ entry.possible_points }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-1">
                                                    <div class="text-sm font-medium"
                                                        :class="{
                                                            'text-green-600': entry.percentage >= 80,
                                                            'text-yellow-600': entry.percentage >= 60 && entry.percentage < 80,
                                                            'text-red-600': entry.percentage < 60,
                                                        }"
                                                    >
                                                        {{ entry.percentage.toFixed(1) }}%
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ entry.answered_count }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="leaderboard.links && leaderboard.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <component
                                    v-for="(link, index) in leaderboard.links"
                                    :key="index"
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                    :class="{
                                        'bg-indigo-50 border-indigo-500 text-indigo-600': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-50': !link.active && link.url,
                                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url,
                                        'rounded-l-md': index === 0,
                                        'rounded-r-md': index === leaderboard.links.length - 1,
                                    }"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Back Link -->
                <div class="mt-6 text-center">
                    <Link
                        :href="route('events.show', event.id)"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        ← Back to Event
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
