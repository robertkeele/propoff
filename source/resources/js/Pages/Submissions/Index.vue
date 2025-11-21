<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline';

defineProps({
    submissions: Object,
});
</script>

<template>
    <Head title="My Submissions" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Submissions</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="submissions.data.length === 0" class="text-center py-12">
                            <p class="text-gray-500">You haven't submitted any events yet.</p>
                            <Link
                                :href="route('events.available')"
                                class="mt-4 inline-block text-indigo-600 hover:text-indigo-900"
                            >
                                Browse Available Events →
                            </Link>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="submission in submissions.data"
                                :key="submission.id"
                                class="border rounded-lg p-6 hover:shadow-md transition"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3">
                                            <Link
                                                :href="submission.is_complete
                                                    ? route('submissions.show', submission.id)
                                                    : route('submissions.continue', submission.id)"
                                                class="text-xl font-semibold text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ submission.event.name }}
                                            </Link>
                                            <span v-if="submission.is_complete" class="inline-flex items-center gap-1 text-green-600">
                                                <CheckCircleIcon class="w-5 h-5" />
                                                <span class="text-sm font-medium">Completed</span>
                                            </span>
                                            <span v-else class="inline-flex items-center gap-1 text-yellow-600">
                                                <ClockIcon class="w-5 h-5" />
                                                <span class="text-sm font-medium">In Progress</span>
                                            </span>
                                        </div>

                                        <div v-if="submission.group" class="mt-2 text-sm text-gray-500">
                                            Group: {{ submission.group.name }}
                                        </div>

                                        <div v-if="submission.is_complete" class="mt-4">
                                            <div class="flex items-center gap-6 text-sm">
                                                <div>
                                                    <span class="text-gray-500">Score:</span>
                                                    <span class="ml-2 font-semibold text-gray-900">
                                                        {{ submission.total_score }} / {{ submission.possible_points }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <span class="text-gray-500">Percentage:</span>
                                                    <span class="ml-2 font-semibold"
                                                        :class="{
                                                            'text-green-600': submission.percentage >= 80,
                                                            'text-yellow-600': submission.percentage >= 60 && submission.percentage < 80,
                                                            'text-red-600': submission.percentage < 60,
                                                        }"
                                                    >
                                                        {{ submission.percentage.toFixed(1) }}%
                                                    </span>
                                                </div>
                                                <div class="text-gray-500">
                                                    Submitted: {{ new Date(submission.submitted_at).toLocaleDateString() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ml-4">
                                        <Link
                                            :href="submission.is_complete
                                                ? route('submissions.show', submission.id)
                                                : route('submissions.continue', submission.id)"
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                                        >
                                            {{ submission.is_complete ? 'View' : 'Continue' }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="submissions.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <component
                                    v-for="(link, index) in submissions.links"
                                    :key="index"
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                    :class="{
                                        'bg-indigo-50 border-indigo-500 text-indigo-600': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-50': !link.active && link.url,
                                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url,
                                        'rounded-l-md': index === 0,
                                        'rounded-r-md': index === submissions.links.length - 1,
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
