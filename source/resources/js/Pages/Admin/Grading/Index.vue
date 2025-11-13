<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    CalculatorIcon,
    DocumentArrowDownIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    game: Object,
    questions: Array,
    groups: Array,
    groupAnswers: Object,
});

const selectedGroup = ref(null);
const editingAnswer = ref(null);

const answerForm = useForm({
    group_id: null,
    correct_answer: '',
    is_void: false,
});

const selectGroup = (group) => {
    selectedGroup.value = group;
    editingAnswer.value = null;
};

const getGroupAnswer = (groupId, questionId) => {
    const groupData = props.groupAnswers[groupId];
    if (!groupData) return null;
    return groupData.find(answer => answer.question_id === questionId);
};

const editAnswer = (question, group) => {
    const existing = getGroupAnswer(group.id, question.id);
    editingAnswer.value = { question, group };
    answerForm.group_id = group.id;
    answerForm.correct_answer = existing?.correct_answer || '';
    answerForm.is_void = existing?.is_void || false;
};

const saveAnswer = (question) => {
    answerForm.post(
        route('admin.games.grading.setAnswer', {
            game: props.game.id,
            question: question.id
        }),
        {
            onSuccess: () => {
                editingAnswer.value = null;
                answerForm.reset();
            },
        }
    );
};

const toggleVoid = (question, group) => {
    router.post(
        route('admin.games.grading.toggleVoid', {
            game: props.game.id,
            question: question.id,
            group: group.id
        })
    );
};

const calculateScores = () => {
    if (confirm('This will recalculate scores for all submissions in this game. Continue?')) {
        router.post(route('admin.games.grading.calculateScores', props.game.id));
    }
};

const exportCSV = () => {
    window.location.href = route('admin.games.grading.exportCSV', props.game.id);
};

const exportDetailedCSV = (groupId = null) => {
    if (groupId) {
        window.location.href = route('admin.games.grading.exportDetailedCSVByGroup', {
            game: props.game.id,
            group: groupId
        });
    } else {
        window.location.href = route('admin.games.grading.exportDetailedCSV', props.game.id);
    }
};
</script>

<template>
    <Head :title="`Grading - ${game.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <Link
                        :href="route('admin.games.show', game.id)"
                        class="text-sm text-gray-500 hover:text-gray-700 mr-2"
                    >
                        ← Back to Game
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Grading: {{ game.title }}
                    </h2>
                </div>
                <div class="flex space-x-2">
                    <button
                        @click="exportCSV"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                        Export CSV
                    </button>
                    <PrimaryButton @click="calculateScores">
                        <CalculatorIcon class="w-4 h-4 mr-2" />
                        Calculate Scores
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Instructions -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                Set group-specific correct answers for each question. Questions can have different answers for different groups.
                                Mark questions as "void" to exclude them from scoring for specific groups.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Group Selection -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Select Group</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <button
                                v-for="group in groups"
                                :key="group.id"
                                @click="selectGroup(group)"
                                class="px-4 py-3 text-sm font-medium rounded-lg border-2 transition"
                                :class="
                                    selectedGroup?.id === group.id
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'
                                "
                            >
                                {{ group.name }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Questions List -->
                <div v-if="selectedGroup" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Questions for {{ selectedGroup.name }}
                            </h3>
                            <button
                                @click="exportDetailedCSV(selectedGroup.id)"
                                class="text-sm text-indigo-600 hover:text-indigo-900"
                            >
                                Export Detailed CSV
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="question in questions"
                                :key="question.id"
                                class="border rounded-lg p-4"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Q{{ question.display_order }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ question.question_type }}
                                            </span>
                                            <span class="text-sm text-gray-500">{{ question.points }} points</span>
                                        </div>
                                        <p class="text-gray-900 mb-2">{{ question.question_text }}</p>

                                        <div
                                            v-if="getGroupAnswer(selectedGroup.id, question.id)"
                                            class="mt-3 p-3 bg-gray-50 rounded"
                                        >
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-700">Correct Answer:</p>
                                                    <p class="text-gray-900">
                                                        {{ getGroupAnswer(selectedGroup.id, question.id).correct_answer }}
                                                    </p>
                                                    <span
                                                        v-if="getGroupAnswer(selectedGroup.id, question.id).is_void"
                                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 mt-1"
                                                    >
                                                        <XCircleIcon class="w-3 h-3 mr-1" />
                                                        Void
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-else class="mt-3 p-3 bg-yellow-50 rounded">
                                            <p class="text-sm text-yellow-700">No answer set for this group</p>
                                        </div>

                                        <!-- Edit Form -->
                                        <div
                                            v-if="editingAnswer?.question.id === question.id && editingAnswer?.group.id === selectedGroup.id"
                                            class="mt-4 p-4 bg-indigo-50 rounded-lg"
                                        >
                                            <form @submit.prevent="saveAnswer(question)">
                                                <div class="mb-3">
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                                        Correct Answer
                                                    </label>
                                                    <input
                                                        v-model="answerForm.correct_answer"
                                                        type="text"
                                                        required
                                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="Enter correct answer..."
                                                    />
                                                </div>
                                                <div class="mb-3">
                                                    <label class="flex items-center">
                                                        <input
                                                            v-model="answerForm.is_void"
                                                            type="checkbox"
                                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                        />
                                                        <span class="ml-2 text-sm text-gray-700">
                                                            Mark as void (exclude from scoring)
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <button
                                                        type="submit"
                                                        :disabled="answerForm.processing"
                                                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                                                    >
                                                        Save Answer
                                                    </button>
                                                    <button
                                                        type="button"
                                                        @click="editingAnswer = null"
                                                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300"
                                                    >
                                                        Cancel
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="ml-4 flex flex-col space-y-2">
                                        <button
                                            @click="editAnswer(question, selectedGroup)"
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            Set Answer
                                        </button>
                                        <button
                                            @click="toggleVoid(question, selectedGroup)"
                                            class="text-sm text-gray-600 hover:text-gray-900"
                                        >
                                            Toggle Void
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p v-if="questions.length === 0" class="text-center text-gray-500 py-8">
                                No questions in this game yet.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center text-gray-500">
                        Select a group above to set answers and manage grading
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
