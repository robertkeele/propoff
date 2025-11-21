<template>
    <Head title="Edit Question" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Edit Question
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Modify question for {{ event.name }}
                    </p>
                </div>
                <Link
                    :href="route('admin.events.questions.index', event.id)"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    <ArrowLeftIcon class="w-4 h-4 mr-2" />
                    Back to Questions
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Warning if answers exist -->
                <div v-if="question.user_answers_count > 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 mt-0.5" />
                        <div>
                            <h3 class="font-semibold text-yellow-900">Warning: Existing Answers</h3>
                            <p class="text-sm text-yellow-700 mt-1">
                                This question has {{ question.user_answers_count }} submitted {{ question.user_answers_count === 1 ? 'answer' : 'answers' }}.
                                Changing the question text or options may affect grading accuracy.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Edit Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Question Text -->
                        <div>
                            <label for="question_text" class="block text-sm font-medium text-gray-700 mb-2">
                                Question Text <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="question_text"
                                v-model="form.question_text"
                                rows="4"
                                class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                required
                            ></textarea>
                            <p v-if="form.errors.question_text" class="mt-1 text-sm text-red-600">
                                {{ form.errors.question_text }}
                            </p>
                        </div>

                        <!-- Question Type (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Question Type
                            </label>
                            <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                                <span :class="typeClass(question.question_type)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                    {{ formatType(question.question_type) }}
                                </span>
                                <p class="text-sm text-gray-600 mt-2">
                                    Question type cannot be changed after creation.
                                </p>
                            </div>
                        </div>

                        <!-- Options (for multiple choice) -->
                        <div v-if="question.question_type === 'multiple_choice'" class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">
                                Answer Options <span class="text-red-500">*</span>
                            </label>
                            <p class="text-sm text-gray-500 mb-2">
                                Set bonus points for each option. Leave at 0 for no bonus (players get only base question points).
                            </p>

                            <div class="space-y-2">
                                <div
                                    v-for="(option, index) in form.options"
                                    :key="index"
                                    class="flex items-start gap-2"
                                >
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-700 font-medium rounded-full text-sm mt-1">
                                        {{ String.fromCharCode(65 + index) }}
                                    </span>
                                    <div class="flex-1 space-y-1">
                                        <input
                                            type="text"
                                            v-model="form.options[index].label"
                                            class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                            :placeholder="`Option ${String.fromCharCode(65 + index)}`"
                                        />
                                        <div class="flex items-center gap-2">
                                            <label class="text-xs text-gray-500">Bonus:</label>
                                            <input
                                                type="number"
                                                v-model.number="form.options[index].points"
                                                min="0"
                                                step="1"
                                                class="w-20 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                                placeholder="0"
                                            />
                                            <span class="text-xs text-gray-400">+bonus pts (optional)</span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeOption(index)"
                                        class="flex-shrink-0 p-2 text-red-600 hover:bg-red-50 rounded mt-1"
                                        :disabled="form.options.length <= 2"
                                    >
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="addOption"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <PlusIcon class="w-4 h-4 mr-2" />
                                Add Option
                            </button>

                            <p v-if="form.errors.options" class="text-sm text-red-600">
                                {{ form.errors.options }}
                            </p>
                        </div>

                        <!-- Points and Order -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="points" class="block text-sm font-medium text-gray-700 mb-2">
                                    Base Points <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="points"
                                    v-model.number="form.points"
                                    min="1"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    required
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Points awarded for answering (+ any option bonus)
                                </p>
                                <p v-if="form.errors.points" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.points }}
                                </p>
                            </div>

                            <div>
                                <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">
                                    Order Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="display_order"
                                    v-model.number="form.display_order"
                                    min="0"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    required
                                />
                                <p v-if="form.errors.display_order" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.display_order }}
                                </p>
                            </div>
                        </div>

                        <!-- Question Stats -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3">Question Statistics</h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">Submitted Answers</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ question.user_answers_count || 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Created</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ formatDate(question.created_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Last Updated</p>
                                    <p class="text-lg font-semibold text-gray-900">{{ formatDate(question.updated_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('admin.events.questions.index', event.id)"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Question</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    event: Object,
    question: Object,
});

// Normalize options to new format if needed
const normalizeOptions = (options) => {
    if (!options || options.length === 0) return [];
    // Check if already in new format (has objects with 'label' key)
    if (typeof options[0] === 'object' && options[0].label !== undefined) {
        return options;
    }
    // Convert old format (strings) to new format (objects)
    return options.map(opt => ({
        label: opt,
        points: 0
    }));
};

const form = useForm({
    question_text: props.question.question_text,
    question_type: props.question.question_type,
    points: props.question.points,
    display_order: props.question.display_order,
    options: normalizeOptions(props.question.options),
});

const formatDate = (date) => {
    if (!date) return 'No date';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};

const typeClass = (type) => {
    if (!type) return 'bg-gray-100 text-gray-800';
    const classes = {
        multiple_choice: 'bg-purple-100 text-purple-800',
        yes_no: 'bg-blue-100 text-blue-800',
        numeric: 'bg-green-100 text-green-800',
        text: 'bg-orange-100 text-orange-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const formatType = (type) => {
    if (!type) return 'Unknown';
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const addOption = () => {
    form.options.push({ label: '', points: 0 });
};

const removeOption = (index) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
    }
};

const submit = () => {
    form.patch(route('admin.events.questions.update', [props.event.id, props.question.id]));
};
</script>