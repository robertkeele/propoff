<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    submission: Object,
});
</script>

<template>
    <Head :title="`Submission - ${submission.event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Submission Results: {{ submission.event.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Score Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <div class="text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                                :class="{
                                    'bg-green-100': submission.percentage >= 80,
                                    'bg-yellow-100': submission.percentage >= 60 && submission.percentage < 80,
                                    'bg-red-100': submission.percentage < 60,
                                }"
                            >
                                <span class="text-3xl font-bold"
                                    :class="{
                                        'text-green-600': submission.percentage >= 80,
                                        'text-yellow-600': submission.percentage >= 60 && submission.percentage < 80,
                                        'text-red-600': submission.percentage < 60,
                                    }"
                                >
                                    {{ submission.percentage.toFixed(0) }}%
                                </span>
                            </div>

                            <h3 class="text-2xl font-bold text-gray-900 mb-2">
                                {{ submission.total_score }} / {{ submission.possible_points }} Points
                            </h3>

                            <div class="flex items-center justify-center gap-6 text-sm text-gray-600">
                                <span>Submitted: {{ new Date(submission.submitted_at).toLocaleDateString() }}</span>
                                <span v-if="submission.group">Group: {{ submission.group.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Results -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Answers</h3>

                        <div class="space-y-6">
                            <div
                                v-for="(question, index) in submission.group.group_questions"
                                :key="question.id"
                                class="border rounded-lg p-6"
                                :class="{
                                    'border-green-200 bg-green-50': submission.user_answers.find(a => a.group_question_id === question.id)?.is_correct,
                                    'border-red-200 bg-red-50': submission.user_answers.find(a => a.group_question_id === question.id) && !submission.user_answers.find(a => a.group_question_id === question.id)?.is_correct,
                                    'border-gray-200': !submission.user_answers.find(a => a.group_question_id === question.id),
                                }"
                            >
                                <div class="flex items-start gap-4">
                                    <!-- Icon -->
                                    <div class="flex-shrink-0 mt-1">
                                        <CheckCircleIcon
                                            v-if="submission.user_answers.find(a => a.group_question_id === question.id)?.is_correct"
                                            class="w-6 h-6 text-green-600"
                                        />
                                        <XCircleIcon
                                            v-else-if="submission.user_answers.find(a => a.group_question_id === question.id)"
                                            class="w-6 h-6 text-red-600"
                                        />
                                        <div v-else class="w-6 h-6 rounded-full bg-gray-300"></div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-3">
                                            <h4 class="font-semibold text-gray-900">
                                                Question {{ index + 1 }}
                                            </h4>
                                            <span class="text-sm font-medium"
                                                :class="{
                                                    'text-green-600': submission.user_answers.find(a => a.group_question_id === question.id)?.is_correct,
                                                    'text-red-600': submission.user_answers.find(a => a.group_question_id === question.id) && !submission.user_answers.find(a => a.group_question_id === question.id)?.is_correct,
                                                    'text-gray-600': !submission.user_answers.find(a => a.group_question_id === question.id),
                                                }"
                                            >
                                                {{
                                                    submission.user_answers.find(a => a.group_question_id === question.id)?.points_earned || 0
                                                }} / {{ question.points }} points
                                            </span>
                                        </div>

                                        <p class="text-gray-700 mb-3">{{ question.question_text }}</p>

                                        <div class="space-y-2">
                                            <div>
                                                <span class="text-sm font-medium text-gray-600">Your Answer:</span>
                                                <span class="ml-2 text-gray-900">
                                                    {{
                                                        submission.user_answers.find(a => a.group_question_id === question.id)?.answer_text ||
                                                        'No answer provided'
                                                    }}
                                                </span>
                                            </div>

                                            <!-- Show correct answer if available and answer was wrong -->
                                            <div
                                                v-if="
                                                    submission.user_answers.find(a => a.group_question_id === question.id) &&
                                                    !submission.user_answers.find(a => a.group_question_id === question.id)?.is_correct &&
                                                    question.correct_answer
                                                "
                                                class="pt-2 border-t border-gray-200"
                                            >
                                                <span class="text-sm font-medium text-green-700">Correct Answer:</span>
                                                <span class="ml-2 text-green-900">
                                                    {{ question.correct_answer }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center">
                    <Link
                        :href="route('submissions.index')"
                        class="text-gray-600 hover:text-gray-900"
                    >
                        ← Back to My Submissions
                    </Link>

                    <Link
                        :href="route('leaderboards.event', submission.event.id)"
                        class="text-indigo-600 hover:text-indigo-900"
                    >
                        View Leaderboard →
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
