<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    games: Object,
});
</script>

<template>
    <Head title="Games" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Games</h2>
                <Link :href="route('games.create')" v-if="$page.props.auth.user.role === 'admin'">
                    <PrimaryButton>
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Create Game
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="games.data.length === 0" class="text-center py-12">
                            <p class="text-gray-500">No games found.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="game in games.data"
                                :key="game.id"
                                class="border rounded-lg p-6 hover:shadow-md transition"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <Link
                                            :href="route('games.show', game.id)"
                                            class="text-xl font-semibold text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ game.name }}
                                        </Link>
                                        <p class="mt-2 text-gray-600">{{ game.description }}</p>

                                        <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ game.questions_count }} questions
                                            </span>
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                                {{ game.submissions_count }} submissions
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-gray-100 text-gray-800': game.status === 'draft',
                                                    'bg-green-100 text-green-800': game.status === 'active',
                                                    'bg-yellow-100 text-yellow-800': game.status === 'locked',
                                                    'bg-blue-100 text-blue-800': game.status === 'completed',
                                                }"
                                            >
                                                {{ game.status }}
                                            </span>
                                        </div>

                                        <div class="mt-2 text-sm text-gray-500">
                                            <span>Event Date: {{ new Date(game.event_date).toLocaleDateString() }}</span>
                                            <span v-if="game.lock_date" class="ml-4">
                                                Lock Date: {{ new Date(game.lock_date).toLocaleDateString() }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="ml-4 flex space-x-2">
                                        <Link
                                            :href="route('games.show', game.id)"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            v-if="$page.props.auth.user.role === 'admin' || $page.props.auth.user.id === game.created_by"
                                            :href="route('games.edit', game.id)"
                                            class="text-gray-600 hover:text-gray-900"
                                        >
                                            Edit
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="games.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <component
                                    v-for="(link, index) in games.links"
                                    :key="index"
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                    :class="{
                                        'bg-indigo-50 border-indigo-500 text-indigo-600': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-50': !link.active && link.url,
                                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url,
                                        'rounded-l-md': index === 0,
                                        'rounded-r-md': index === games.links.length - 1,
                                    }"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
