<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    submission: Object,
});

const currentQuestionIndex = ref(0);
const answers = ref({});

// Load existing answers
props.submission.user_answers.forEach(answer => {
    answers.value[answer.group_question_id] = answer.answer_text;
});

const currentQuestion = computed(() => {
    return props.submission.group.group_questions[currentQuestionIndex.value];
});

const progress = computed(() => {
    const answered = Object.keys(answers.value).length;
    const total = props.submission.group.group_questions.length;
    return (answered / total) * 100;
});

const isLastQuestion = computed(() => {
    return currentQuestionIndex.value === props.submission.group.group_questions.length - 1;
});

const canGoNext = computed(() => {
    return currentQuestionIndex.value < props.submission.group.group_questions.length - 1;
});

const canGoPrevious = computed(() => {
    return currentQuestionIndex.value > 0;
});

const nextQuestion = () => {
    if (canGoNext.value) {
        currentQuestionIndex.value++;
    }
};

const previousQuestion = () => {
    if (canGoPrevious.value) {
        currentQuestionIndex.value--;
    }
};

const goToQuestion = (index) => {
    currentQuestionIndex.value = index;
};

const saveAnswers = () => {
    const answersArray = Object.entries(answers.value).map(([groupQuestionId, answerText]) => ({
        group_question_id: parseInt(groupQuestionId),
        answer_text: answerText,
    }));

    router.post(route('submissions.saveAnswers', props.submission.id), {
        answers: answersArray,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Show success message
        },
    });
};

const submitForm = () => {
    if (confirm('Are you sure you want to submit? You won\'t be able to change your answers after submitting.')) {
        // Save answers first
        const answersArray = Object.entries(answers.value).map(([groupQuestionId, answerText]) => ({
            group_question_id: parseInt(groupQuestionId),
            answer_text: answerText,
        }));

        router.post(route('submissions.saveAnswers', props.submission.id), {
            answers: answersArray,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // Then submit and redirect to confirmation page
                router.post(route('submissions.submit', props.submission.id), {}, {
                    onSuccess: () => {
                        router.get(route('submissions.confirmation', props.submission.id));
                    }
                });
            },
        });
    }
};

// Helper functions to handle both old format (string) and new format (object with label/points)
const getOptionLabel = (option) => {
    if (typeof option === 'string') return option;
    return option.label || option;
};

const getOptionValue = (option) => {
    if (typeof option === 'string') return option;
    return option.label || option;
};

const getOptionPoints = (option) => {
    if (typeof option === 'string') return 0;
    return option.points || 0;
};
</script>

<template>
    <Head :title="`${submission.event.name} - Quiz`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ submission.event.name }}</h2>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ Object.keys(answers).length }} of {{ submission.group.group_questions.length }} questions answered
                    </p>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="flex gap-6">
                    <!-- Question Navigation Sidebar -->
                    <div class="w-48 flex-shrink-0">
                        <div class="bg-white rounded-lg shadow-sm p-4 sticky top-6">
                            <h3 class="font-semibold text-gray-900 mb-3">Questions</h3>
                            <div class="space-y-2">
                                <button
                                    v-for="(question, index) in submission.group.group_questions"
                                    :key="question.id"
                                    @click="goToQuestion(index)"
                                    class="w-full text-left px-3 py-2 rounded transition"
                                    :class="{
                                        'bg-indigo-600 text-white': index === currentQuestionIndex,
                                        'bg-green-100 text-green-800': answers[question.id] && index !== currentQuestionIndex,
                                        'bg-gray-100 text-gray-700 hover:bg-gray-200': !answers[question.id] && index !== currentQuestionIndex,
                                    }"
                                >
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium">Q{{ index + 1 }}</span>
                                        <span v-if="answers[question.id]" class="text-xs">✓</span>
                                    </div>
                                </button>
                            </div>

                            <div class="mt-4 pt-4 border-t">
                                <button
                                    @click="saveAnswers"
                                    class="w-full px-3 py-2 text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 rounded transition"
                                >
                                    Save Progress
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Main Question Area -->
                    <div class="flex-1">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-8">
                                <!-- Question -->
                                <div class="mb-8">
                                    <div class="flex items-start justify-between mb-4">
                                        <h3 class="text-2xl font-bold text-gray-900">
                                            Question {{ currentQuestionIndex + 1 }}
                                        </h3>
                                        <span class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm font-medium rounded">
                                            {{ currentQuestion.points }} points
                                        </span>
                                    </div>
                                    <p class="text-lg text-gray-700">{{ currentQuestion.question_text }}</p>
                                </div>

                                <!-- Answer Input -->
                                <div class="mb-8">
                                    <!-- Multiple Choice -->
                                    <div v-if="currentQuestion.question_type === 'multiple_choice' && currentQuestion.options">
                                        <div class="space-y-3">
                                            <label
                                                v-for="(option, index) in currentQuestion.options"
                                                :key="index"
                                                class="flex items-center justify-between p-4 border-2 rounded-lg cursor-pointer transition"
                                                :class="{
                                                    'border-indigo-600 bg-indigo-50': answers[currentQuestion.id] === getOptionValue(option),
                                                    'border-gray-200 hover:border-gray-300': answers[currentQuestion.id] !== getOptionValue(option),
                                                }"
                                            >
                                                <div class="flex items-center flex-1">
                                                    <input
                                                        type="radio"
                                                        v-model="answers[currentQuestion.id]"
                                                        :value="getOptionValue(option)"
                                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                                    />
                                                    <span class="ml-3 text-gray-900">{{ getOptionLabel(option) }}</span>
                                                </div>
                                                <div v-if="getOptionPoints(option) > 0" class="ml-4 flex items-center gap-1">
                                                    <span class="text-xs font-medium text-indigo-600 bg-indigo-100 px-2 py-1 rounded">
                                                        +{{ getOptionPoints(option) }} bonus
                                                    </span>
                                                </div>
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-3">
                                            Base: {{ currentQuestion.points }} pts per question + any bonus shown
                                        </p>
                                    </div>

                                    <!-- True/False -->
                                    <div v-else-if="currentQuestion.question_type === 'true_false'">
                                        <div class="space-y-3">
                                            <label
                                                class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                                                :class="{
                                                    'border-indigo-600 bg-indigo-50': answers[currentQuestion.id] === 'True',
                                                    'border-gray-200 hover:border-gray-300': answers[currentQuestion.id] !== 'True',
                                                }"
                                            >
                                                <input
                                                    type="radio"
                                                    v-model="answers[currentQuestion.id]"
                                                    value="True"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span class="ml-3 text-gray-900">True</span>
                                            </label>
                                            <label
                                                class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition"
                                                :class="{
                                                    'border-indigo-600 bg-indigo-50': answers[currentQuestion.id] === 'False',
                                                    'border-gray-200 hover:border-gray-300': answers[currentQuestion.id] !== 'False',
                                                }"
                                            >
                                                <input
                                                    type="radio"
                                                    v-model="answers[currentQuestion.id]"
                                                    value="False"
                                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                                />
                                                <span class="ml-3 text-gray-900">False</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Short Answer or Number -->
                                    <div v-else>
                                        <TextInput
                                            v-model="answers[currentQuestion.id]"
                                            :type="currentQuestion.question_type === 'number' ? 'number' : 'text'"
                                            class="w-full"
                                            :placeholder="currentQuestion.question_type === 'number' ? 'Enter a number' : 'Enter your answer'"
                                        />
                                    </div>
                                </div>

                                <!-- Navigation -->
                                <div class="flex items-center justify-between">
                                    <button
                                        @click="previousQuestion"
                                        :disabled="!canGoPrevious"
                                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded disabled:opacity-50 disabled:cursor-not-allowed transition"
                                    >
                                        ← Previous
                                    </button>

                                    <div class="text-sm text-gray-600">
                                        Question {{ currentQuestionIndex + 1 }} of {{ submission.group.group_questions.length }}
                                    </div>

                                    <button
                                        v-if="!isLastQuestion"
                                        @click="nextQuestion"
                                        class="px-4 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded transition"
                                    >
                                        Next →
                                    </button>
                                    <PrimaryButton
                                        v-else
                                        @click="submitForm"
                                        class="px-6 py-2"
                                    >
                                        Submit Answers
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button (always visible at bottom) -->
                        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-yellow-900">Ready to submit?</h4>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Make sure you've answered all questions before submitting.
                                    </p>
                                </div>
                                <PrimaryButton
                                    @click="submitForm"
                                    class="ml-4"
                                >
                                    Submit Final Answers
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
