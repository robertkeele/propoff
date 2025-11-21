<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    questions: {
        type: Array,
        required: true,
    },
    adminGradingGroups: {
        type: Array,
        required: true,
    },
});

const answerForms = ref({});

// Initialize answer forms
props.questions.forEach(question => {
    answerForms.value[question.id] = useForm({
        correct_answer: question.answer?.correct_answer || '',
        is_void: question.answer?.is_void || false,
    });
});

const setAnswer = (questionId) => {
    answerForms.value[questionId].post(
        route('admin.events.event-answers.setAnswer', [props.event.id, questionId]),
        {
            preserveScroll: true,
        }
    );
};

const toggleVoid = (questionId) => {
    answerForms.value[questionId].post(
        route('admin.events.event-answers.toggleVoid', [props.event.id, questionId]),
        {
            preserveScroll: true,
        }
    );
};

const getTypeLabel = (type) => {
    const labels = {
        multiple_choice: 'Multiple Choice',
        yes_no: 'Yes/No',
        numeric: 'Numeric',
        text: 'Text',
    };
    return labels[type] || type;
};
</script>

<template>
    <Head title="Event Answers" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Event Answers - {{ event.name }}
                </h2>
                <Link
                    :href="route('admin.events.show', event.id)"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Event
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Info Banner -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">About Event Answers</h3>
                    <p class="text-sm text-blue-800 mb-2">
                        Set the correct answers for this event. These answers will be used to grade submissions from groups that use <strong>Admin Grading</strong>.
                    </p>
                    <p class="text-sm text-blue-800">
                        Groups using <strong>Captain Grading</strong> will not be affected by these answers.
                    </p>
                </div>

                <!-- Admin Grading Groups -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">
                            Groups Using Admin Grading ({{ adminGradingGroups.length }})
                        </h3>

                        <div v-if="adminGradingGroups.length === 0" class="text-center py-8">
                            <p class="text-gray-500">No groups are using admin grading for this event.</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div
                                v-for="group in adminGradingGroups"
                                :key="group.id"
                                class="border rounded-lg p-4"
                            >
                                <h4 class="font-semibold mb-2">{{ group.name }}</h4>
                                <p class="text-sm text-gray-600">{{ group.members_count }} members</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions List -->
                <div class="space-y-6">
                    <div
                        v-for="question in questions"
                        :key="question.id"
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <!-- Question Header -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-sm font-semibold text-gray-500">Q{{ question.order + 1 }}</span>
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-700">
                                            {{ getTypeLabel(question.question_type) }}
                                        </span>
                                        <span v-if="question.answer" class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                            Answer Set
                                        </span>
                                        <span v-if="question.answer?.is_void" class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-700">
                                            Voided
                                        </span>
                                    </div>
                                    <p class="text-gray-900 font-medium mb-2">{{ question.question_text }}</p>
                                    <p class="text-sm text-gray-600">Points: {{ question.points }}</p>
                                </div>
                            </div>

                            <!-- Options (for multiple choice) -->
                            <div v-if="question.question_type === 'multiple_choice' && question.options" class="mb-4">
                                <p class="text-sm font-semibold text-gray-700 mb-2">Options:</p>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(option, index) in question.options"
                                        :key="index"
                                        class="px-3 py-1 bg-gray-100 text-gray-700 rounded text-sm"
                                    >
                                        {{ typeof option === 'object' ? option.label : option }}
                                        <span v-if="typeof option === 'object' && option.points" class="text-xs text-gray-500">
                                            (+{{ option.points }} pts)
                                        </span>
                                    </span>
                                </div>
                            </div>

                            <!-- Answer Set Info -->
                            <div v-if="question.answer" class="mb-4 p-3 bg-gray-50 rounded">
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">Current Answer:</span> {{ question.answer.correct_answer }}
                                </p>
                                <p v-if="question.answer.set_by" class="text-xs text-gray-500 mt-1">
                                    Set by {{ question.answer.set_by.name }} on {{ new Date(question.answer.set_at).toLocaleString() }}
                                </p>
                            </div>

                            <!-- Grading Form -->
                            <div class="border-t pt-4 mt-4">
                                <form @submit.prevent="setAnswer(question.id)" class="space-y-4">
                                    <!-- Correct Answer -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Correct Answer
                                        </label>
                                        <input
                                            v-model="answerForms[question.id].correct_answer"
                                            type="text"
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Enter the correct answer"
                                            required
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            This will be used to grade all groups using admin grading
                                        </p>
                                    </div>

                                    <!-- Void Checkbox -->
                                    <div class="flex items-center">
                                        <input
                                            v-model="answerForms[question.id].is_void"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                        <label class="ml-2 text-sm text-gray-700">
                                            Mark this question as void (exclude from scoring for admin-graded groups)
                                        </label>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="flex gap-2">
                                        <button
                                            type="submit"
                                            :disabled="answerForms[question.id].processing"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold disabled:opacity-50"
                                        >
                                            {{ question.answer ? 'Update Answer' : 'Set Answer' }}
                                        </button>

                                        <button
                                            v-if="question.answer"
                                            type="button"
                                            @click="toggleVoid(question.id)"
                                            class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold"
                                        >
                                            {{ question.answer.is_void ? 'Unvoid' : 'Void' }} Question
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="questions.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <p class="text-gray-500 mb-4">No questions to grade yet.</p>
                        <Link
                            :href="route('admin.events.event-questions.index', event.id)"
                            class="text-blue-500 hover:text-blue-600 font-semibold"
                        >
                            Manage Event Questions
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
