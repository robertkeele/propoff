<template>
    <Head title="My Results" />

    <GuestLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Welcome -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            Welcome back, {{ user.name }}!
                        </h2>
                        <p class="text-gray-600">Here are your game results</p>
                        
                        <!-- Save This Link Notice -->
                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex items-start gap-3">
                                <InformationCircleIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                                <div class="flex-1">
                                    <p class="text-sm text-blue-900 font-medium">Save this link!</p>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Bookmark this page to return and view your results anytime.
                                    </p>
                                    <div class="mt-2 flex items-center gap-2">
                                        <input
                                            :value="currentUrl"
                                            readonly
                                            class="flex-1 text-xs bg-white border border-blue-300 rounded px-2 py-1"
                                        />
                                        <button
                                            @click="copyLink"
                                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                                        >
                                            {{ copied ? 'Copied!' : 'Copy' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submissions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">My Submissions</h3>
                        
                        <div v-if="submissions.length === 0" class="text-center py-12">
                            <TrophyIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <p class="text-gray-600">No submissions yet</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="submission in submissions"
                                :key="submission.id"
                                class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900">{{ submission.event_name }}</h4>
                                        <p class="text-sm text-gray-600">{{ submission.group_name }}</p>
                                        
                                        <div class="mt-2 flex items-center gap-4">
                                            <div>
                                                <span class="text-2xl font-bold text-blue-600">{{ submission.percentage }}%</span>
                                                <span class="text-sm text-gray-600 ml-1">
                                                    ({{ submission.total_score }}/{{ submission.possible_points }} points)
                                                </span>
                                            </div>
                                        </div>

                                        <p v-if="submission.submitted_at" class="text-xs text-gray-500 mt-2">
                                            Submitted: {{ formatDate(submission.submitted_at) }}
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-2">
                                        <span
                                            :class="submission.is_complete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                            class="px-3 py-1 rounded-full text-xs font-medium"
                                        >
                                            {{ submission.is_complete ? 'Complete' : 'In Progress' }}
                                        </span>
                                        
                                        <Link
                                            v-if="submission.can_edit"
                                            :href="route('submissions.continue', submission.id)"
                                            class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 text-center"
                                        >
                                            {{ submission.is_complete ? 'View' : 'Continue' }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { TrophyIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
    submissions: Array,
});

const copied = ref(false);

const currentUrl = computed(() => {
    return window.location.href;
});

const copyLink = () => {
    navigator.clipboard.writeText(currentUrl.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

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