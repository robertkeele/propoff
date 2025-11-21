<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PlayIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

const props = defineProps({
    event: Object,
});

const form = useForm({
    group_id: null,
});

const showGroupSelect = ref(false);

const startSubmission = () => {
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
                                    <div class="text-3xl font-bold text-indigo-600">{{ event.questions.length }}</div>
                                    <div class="text-sm text-gray-600 mt-1">Questions</div>
                                </div>
                                <div>
                                    <div class="text-3xl font-bold text-indigo-600">
                                        {{ event.questions.reduce((sum, q) => sum + q.points, 0) }}
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
                                <li>Answer all {{ event.questions.length }} questions to the best of your ability</li>
                                <li>You can save your progress and come back later</li>
                                <li>Make sure to submit your answers before the lock date</li>
                                <li>Your score will be calculated based on correct answers</li>
                                <li v-if="event.lock_date">
                                    Lock Date: {{ new Date(event.lock_date).toLocaleString() }}
                                </li>
                            </ul>
                        </div>

                        <!-- Questions Preview -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-900 mb-3">Questions Overview</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="(question, index) in event.questions"
                                    :key="question.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <span class="text-gray-700">Question {{ index + 1 }}: {{ question.question_type.replace('_', ' ') }}</span>
                                    <span class="text-sm font-medium text-indigo-600">{{ question.points }} pts</span>
                                </div>
                            </div>
                        </div>

                        <!-- Group Selection (Optional) -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between">
                                <h4 class="text-lg font-semibold text-gray-900">Playing as</h4>
                                <button
                                    @click="showGroupSelect = !showGroupSelect"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                                >
                                    {{ showGroupSelect ? 'Hide' : 'Select Group' }}
                                </button>
                            </div>

                            <div v-if="showGroupSelect" class="mt-3">
                                <select
                                    v-model="form.group_id"
                                    class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option :value="null">Individual (No Group)</option>
                                    <!-- Groups would be loaded dynamically -->
                                </select>
                                <p class="mt-1 text-sm text-gray-500">
                                    Select a group if you want your submission to count toward a group leaderboard
                                </p>
                            </div>
                            <div v-else class="mt-2 text-sm text-gray-600">
                                Individual Play
                            </div>
                        </div>

                        <!-- Start Button -->
                        <div class="text-center">
                            <form @submit.prevent="startSubmission">
                                <PrimaryButton
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-8 py-3"
                                >
                                    <PlayIcon class="w-5 h-5 mr-2" />
                                    Start Playing
                                </PrimaryButton>
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
