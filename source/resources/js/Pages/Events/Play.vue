<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PlayIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    event: Object,
    userGroups: Array,
});

const form = useForm({
    group_id: null,
});

const showGroupSelect = ref(false);

// Automatically select the first group if there's only one
onMounted(() => {
    if (props.userGroups && props.userGroups.length === 1) {
        form.group_id = props.userGroups[0].id;
    }
});

const hasGroups = computed(() => props.userGroups && props.userGroups.length > 0);

const startSubmission = () => {
    if (!hasGroups.value) {
        alert('You must join a group before you can play this event.');
        return;
    }
    form.post(route('submissions.start', props.event.id));
};
</script>

<template>
    <Head :title="`Play ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Play: {{ event.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <!-- Event Info -->
                        <div class="text-center mb-8">
                            <h3 class="text-3xl font-bold text-gray-900">{{ event.name }}</h3>
                            <p class="mt-2 text-gray-600">{{ event.description }}</p>
                        </div>

                        <!-- Event Details -->
                        <div class="bg-gray-50 rounded-lg p-6 mb-8">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                                <div>
                                    <div class="text-3xl font-bold text-indigo-600">{{ event.event_questions?.length || 0 }}</div>
                                    <div class="text-sm text-gray-600 mt-1">Questions</div>
                                </div>
                                <div>
                                    <div class="text-3xl font-bold text-indigo-600">
                                        {{ event.event_questions?.reduce((sum, q) => sum + q.points, 0) || 0 }}
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">Total Points</div>
                                </div>
                                <div>
                                    <div class="text-3xl font-bold text-indigo-600">{{ event.category }}</div>
                                    <div class="text-sm text-gray-600 mt-1">Category</div>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Instructions</h4>
                            <ul class="list-disc list-inside space-y-2 text-gray-600">
                                <li>Answer all {{ event.event_questions?.length || 0 }} questions to the best of your ability</li>
                                <li>You can save your progress and come back later</li>
                                <li>Make sure to submit your answers before the lock date</li>
                                <li>Your score will be calculated based on correct answers</li>
                                <li v-if="event.lock_date">
                                    Lock Date: {{ new Date(event.lock_date).toLocaleString() }}
                                </li>
                            </ul>
                        </div>

                        <!-- Questions Preview -->
                        <div class="mb-8" v-if="event.event_questions && event.event_questions.length > 0">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Questions Overview</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(question, index) in event.event_questions"
                                    :key="question.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <span class="text-gray-700">Question {{ index + 1 }}: {{ question.question_type.replace('_', ' ') }}</span>
                                    <span class="text-sm font-medium text-indigo-600">{{ question.points }} pts</span>
                                </div>
                            </div>
                        </div>

                        <!-- Group Selection -->
                        <div class="mb-8">
                            <!-- No Groups Warning -->
                            <div v-if="!hasGroups" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                                <div class="flex items-start gap-3">
                                    <ExclamationTriangleIcon class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <h4 class="font-semibold text-yellow-900">No Group Membership</h4>
                                        <p class="text-sm text-yellow-700 mt-1">
                                            You must join a group before you can play this event. Please ask the captain of a group for an invitation link.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Group Selection -->
                            <div v-else>
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Select Your Group</h4>

                                <select
                                    v-model="form.group_id"
                                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    required
                                >
                                    <option :value="null" disabled>Choose a group...</option>
                                    <option
                                        v-for="group in userGroups"
                                        :key="group.id"
                                        :value="group.id"
                                    >
                                        {{ group.name }} (Code: {{ group.code }})
                                    </option>
                                </select>
                                <p class="mt-2 text-sm text-gray-500">
                                    Your submission will count toward this group's leaderboard
                                </p>
                            </div>
                        </div>

                        <!-- Start Button -->
                        <div class="text-center">
                            <form @submit.prevent="startSubmission">
                                <PrimaryButton
                                    type="submit"
                                    :disabled="form.processing || !hasGroups || !form.group_id"
                                    class="px-8 py-3"
                                >
                                    <PlayIcon class="w-5 h-5 mr-2" />
                                    Start Playing
                                </PrimaryButton>
                                <p v-if="!hasGroups" class="mt-2 text-sm text-red-600">
                                    You must join a group first
                                </p>
                                <p v-else-if="!form.group_id" class="mt-2 text-sm text-gray-600">
                                    Please select a group above
                                </p>
                            </form>
                        </div>

                        <!-- Back Link -->
                        <div class="mt-6 text-center">
                            <a
                                :href="route('events.available')"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                ← Back to Available Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
