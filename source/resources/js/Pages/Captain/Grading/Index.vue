<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    questions: {
        type: Array,
        required: true,
    },
});

const answerForms = ref({});

// Initialize answer forms
props.questions.forEach(question => {
    answerForms.value[question.id] = useForm({
        correct_answer: question.answer?.correct_answer || '',
        points_awarded: question.answer?.points_awarded || null,
        is_void: question.answer?.is_void || false,
    });
});

const setAnswer = (questionId) => {
    answerForms.value[questionId].post(
        route('captain.groups.grading.setAnswer', [props.group.id, questionId]),
        {
            preserveScroll: true,
            onSuccess: () => {
                // Optional: Show success message
            },
        }
    );
};

const toggleVoid = (questionId) => {
    answerForms.value[questionId].post(
        route('captain.groups.grading.toggleVoid', [props.group.id, questionId]),
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
    <Head title="Grade Submissions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Grade Submissions - {{ group.name }}
                </h2>
                <Link
                    :href="route('captain.groups.show', group.id)"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Info Banner -->
                <div
                    v-if="group.grading_source === 'admin'"
                    class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6"
                >
                    <p class="text-sm text-yellow-800">
                        ⚠️ This group uses <strong>Admin Grading</strong>. Scores will be calculated based on answers set by the admin, not captain answers.
                    </p>
                </div>

                <div
                    v-else
                    class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6"
                >
                    <p class="text-sm text-blue-800">
                        ✓ This group uses <strong>Captain Grading</strong>. Set the correct answers below to grade submissions in real-time.
                    </p>
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
                                        <span v-if="question.is_custom" class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-700">
                                            Custom
                                        </span>
                                        <span v-if="question.answer" class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                            Answer Set
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
                                    </span>
                                </div>
                            </div>

                            <!-- Grading Form -->
                            <div v-if="group.grading_source === 'captain'" class="border-t pt-4 mt-4">
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
                                    </div>

                                    <!-- Custom Points (Optional) -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Custom Points (Optional)
                                        </label>
                                        <input
                                            v-model="answerForms[question.id].points_awarded"
                                            type="number"
                                            min="0"
                                            class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            placeholder="Leave empty to use default points"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            Leave blank to use the question's default points ({{ question.points }})
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
                                            Mark this question as void (exclude from scoring)
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

                            <!-- Admin Grading Message -->
                            <div v-else class="border-t pt-4 mt-4">
                                <p class="text-sm text-gray-600">
                                    This group uses admin grading. The admin will set answers after the event.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="questions.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <p class="text-gray-500 mb-4">No questions to grade yet.</p>
                        <Link
                            :href="route('captain.groups.questions.index', group.id)"
                            class="text-blue-500 hover:text-blue-600 font-semibold"
                        >
                            Manage Questions
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
