<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
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

const getTypeLabel = (type) => {
    const labels = {
        multiple_choice: 'Multiple Choice',
        yes_no: 'Yes/No',
        numeric: 'Numeric',
        text: 'Text',
    };
    return labels[type] || type;
};

const toggleActive = (questionId) => {
    router.post(route('captain.groups.questions.toggleActive', [props.group.id, questionId]), {}, {
        preserveScroll: true,
    });
};

const duplicateQuestion = (questionId) => {
    router.post(route('captain.groups.questions.duplicate', [props.group.id, questionId]), {}, {
        preserveScroll: true,
    });
};

const deleteQuestion = (questionId) => {
    if (confirm('Are you sure you want to delete this question?')) {
        router.delete(route('captain.groups.questions.destroy', [props.group.id, questionId]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Manage Questions" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manage Questions - {{ group.name }}
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
                <!-- Header Actions -->
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold">Questions ({{ questions.length }})</h3>
                        <p class="text-sm text-gray-600">Customize questions for your group</p>
                    </div>
                    <Link
                        :href="route('captain.groups.questions.create', group.id)"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold"
                    >
                        + Add Custom Question
                    </Link>
                </div>

                <!-- Questions List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div v-if="questions.length === 0" class="p-12 text-center">
                        <p class="text-gray-500 mb-4">No questions yet.</p>
                        <Link
                            :href="route('captain.groups.questions.create', group.id)"
                            class="text-blue-500 hover:text-blue-600 font-semibold"
                        >
                            Add your first question
                        </Link>
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <div
                            v-for="question in questions"
                            :key="question.id"
                            class="p-6 hover:bg-gray-50 transition"
                            :class="{ 'opacity-50': !question.is_active }"
                        >
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <!-- Question Header -->
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-sm font-semibold text-gray-500">Q{{ question.order + 1 }}</span>
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-700">
                                            {{ getTypeLabel(question.question_type) }}
                                        </span>
                                        <span v-if="question.is_custom" class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-700">
                                            Custom
                                        </span>
                                        <span v-if="!question.is_active" class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-700">
                                            Inactive
                                        </span>
                                        <span v-if="question.has_answer" class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                            Answer Set
                                        </span>
                                    </div>

                                    <!-- Question Text -->
                                    <p class="text-gray-900 font-medium mb-2">{{ question.question_text }}</p>

                                    <!-- Options (for multiple choice) -->
                                    <div v-if="question.question_type === 'multiple_choice' && question.options" class="mb-2">
                                        <p class="text-sm text-gray-600 mb-1">Options:</p>
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

                                    <!-- Points -->
                                    <p class="text-sm text-gray-600">
                                        Points: <span class="font-semibold">{{ question.points }}</span>
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2 ml-4">
                                    <Link
                                        :href="route('captain.groups.questions.edit', [group.id, question.id])"
                                        class="text-sm bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-center"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="toggleActive(question.id)"
                                        class="text-sm bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded"
                                    >
                                        {{ question.is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button
                                        @click="duplicateQuestion(question.id)"
                                        class="text-sm bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded"
                                    >
                                        Duplicate
                                    </button>
                                    <button
                                        @click="deleteQuestion(question.id)"
                                        class="text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
