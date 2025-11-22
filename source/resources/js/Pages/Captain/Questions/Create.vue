<template>
    <Head :title="`Add Question - ${group.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Add Custom Question
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Create a custom question for {{ group.name }}
                    </p>
                </div>
                <Link
                    :href="route('captain.groups.questions.index', group.id)"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    <ArrowLeftIcon class="w-4 h-4 mr-2" />
                    Back to Questions
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Group Context -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <InformationCircleIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                        <div>
                            <h3 class="font-semibold text-blue-900">{{ group.name }}</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Event: {{ group.event?.name || 'N/A' }} | Current Questions: {{ currentQuestionCount }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
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
                                placeholder="Enter your question here..."
                                required
                            ></textarea>
                            <p v-if="form.errors.question_text" class="mt-1 text-sm text-red-600">
                                {{ form.errors.question_text }}
                            </p>
                        </div>

                        <!-- Question Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Question Type <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    v-for="type in questionTypes"
                                    :key="type.value"
                                    :class="[
                                        'flex items-center p-3 border-2 rounded-lg cursor-pointer transition',
                                        form.question_type === type.value
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.question_type"
                                        :value="type.value"
                                        class="text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm font-medium text-gray-900">{{ type.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Options (for multiple choice) -->
                        <div v-if="form.question_type === 'multiple_choice'" class="space-y-3">
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
                                            required
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
                            </div>

                            <div>
                                <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">
                                    Display Order
                                </label>
                                <input
                                    type="number"
                                    id="display_order"
                                    v-model.number="form.display_order"
                                    min="1"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    Order in which this question appears
                                </p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('captain.groups.questions.index', group.id)"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Saving...</span>
                                <span v-else>Save</span>
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
    InformationCircleIcon,
    TrashIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    currentQuestionCount: {
        type: Number,
        default: 0,
    },
});

const questionTypes = [
    { value: 'multiple_choice', label: 'Multiple Choice' },
    { value: 'yes_no', label: 'Yes/No' },
    { value: 'numeric', label: 'Numeric' },
    { value: 'text', label: 'Text' },
];

const form = useForm({
    question_text: '',
    question_type: 'multiple_choice',
    points: 1,
    display_order: (props.currentQuestionCount || 0) + 1,
    options: [
        { label: '', points: 0 },
        { label: '', points: 0 }
    ],
    is_active: true,
    is_custom: true,
});

const addOption = () => {
    form.options.push({ label: '', points: 0 });
};

const removeOption = (index) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        options: form.question_type === 'multiple_choice' ? form.options : null,
    }))
    .post(route('captain.groups.questions.store', props.group.id), {
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};
</script>
