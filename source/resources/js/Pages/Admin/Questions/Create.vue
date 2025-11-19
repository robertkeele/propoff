<template>
    <Head title="Create Question" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Create Question
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Add a new question to {{ game.name }}
                    </p>
                </div>
                <Link
                    :href="route('admin.games.questions.index', game.id)"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    <ArrowLeftIcon class="w-4 h-4 mr-2" />
                    Back to Questions
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Game Context -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <InformationCircleIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                        <div>
                            <h3 class="font-semibold text-blue-900">{{ game.name }}</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Current Questions: {{ game.questions_count || 0 }} | Event Date: {{ formatDate(game?.event_date) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Create Form -->
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
                            <div class="space-y-3">
                                <label
                                    v-for="type in questionTypes"
                                    :key="type.value"
                                    :class="[
                                        'flex items-start p-4 border-2 rounded-lg cursor-pointer transition',
                                        form.question_type === type.value
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.question_type"
                                        :value="type.value"
                                        class="mt-1 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center gap-2">
                                            <component :is="type.icon" class="w-5 h-5 text-gray-500" />
                                            <span class="font-medium text-gray-900">{{ type.label }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">{{ type.description }}</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.question_type" class="mt-1 text-sm text-red-600">
                                {{ form.errors.question_type }}
                            </p>
                        </div>

                        <!-- Options (for multiple choice) -->
                        <div v-if="form.question_type === 'multiple_choice'" class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">
                                Answer Options <span class="text-red-500">*</span>
                                <span class="text-gray-500 font-normal">(minimum 2 options)</span>
                            </label>
                            
                            <div class="space-y-2">
                                <div
                                    v-for="(option, index) in form.options"
                                    :key="index"
                                    class="flex items-center gap-2"
                                >
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-700 font-medium rounded-full text-sm">
                                        {{ String.fromCharCode(65 + index) }}
                                    </span>
                                    <input
                                        type="text"
                                        v-model="form.options[index]"
                                        class="flex-1 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                        :placeholder="`Option ${String.fromCharCode(65 + index)}`"
                                    />
                                    <button
                                        type="button"
                                        @click="removeOption(index)"
                                        class="flex-shrink-0 p-2 text-red-600 hover:bg-red-50 rounded"
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
                                    Points <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    id="points"
                                    v-model.number="form.points"
                                    min="1"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">Points awarded for correct answer</p>
                                <p v-if="form.errors.points" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.points }}
                                </p>
                            </div>

                            <div>
                                <label for="display_order" class="block text-sm font-medium text-gray-700 mb-2">
                                    Order Number
                                </label>
                                <input
                                    type="number"
                                    id="display_order"
                                    v-model.number="form.display_order"
                                    min="0"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                />
                                <p class="mt-1 text-sm text-gray-500">Question order in the game</p>
                                <p v-if="form.errors.display_order" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.display_order }}
                                </p>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                <EyeIcon class="w-5 h-5" />
                                Preview
                            </h3>
                            <div class="bg-white rounded-lg p-4 border border-gray-200">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 font-bold rounded-full text-sm">
                                            {{ form.order_number || '?' }}
                                        </span>
                                        <span class="text-sm text-gray-600">{{ form.points }} {{ form.points === 1 ? 'point' : 'points' }}</span>
                                    </div>
                                    <span :class="typeClass(form.type)" class="px-2 py-1 rounded text-xs font-medium">
                                        {{ formatType(form.type) }}
                                    </span>
                                </div>
                                <p class="text-gray-900 font-medium mb-3">
                                    {{ form.question_text || 'Your question will appear here...' }}
                                </p>

                                <!-- Preview options for multiple choice -->
                                <div v-if="form.question_type === 'multiple_choice' && form.options.length > 0" class="space-y-2">
                                    <div
                                        v-for="(option, index) in form.options.filter(o => o.trim())"
                                        :key="index"
                                        class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:border-gray-300"
                                    >
                                        <input
                                            type="radio"
                                            name="preview"
                                            disabled
                                            class="text-blue-600"
                                        />
                                        <span class="text-gray-700">{{ String.fromCharCode(65 + index) }}. {{ option }}</span>
                                    </div>
                                </div>

                                <!-- Preview for other types -->
                                <div v-else-if="form.question_type === 'yes_no'" class="space-y-2">
                                    <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg">
                                        <input type="radio" name="preview" disabled class="text-blue-600" />
                                        <span class="text-gray-700">Yes</span>
                                    </div>
                                    <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg">
                                        <input type="radio" name="preview" disabled class="text-blue-600" />
                                        <span class="text-gray-700">No</span>
                                    </div>
                                </div>

                                <div v-else-if="form.question_type === 'numeric'">
                                    <input
                                        type="number"
                                        disabled
                                        placeholder="Enter a number..."
                                        class="w-full border-gray-300 rounded-md shadow-sm"
                                    />
                                </div>

                                <div v-else-if="form.question_type === 'text'">
                                    <input
                                        type="text"
                                        disabled
                                        placeholder="Enter your answer..."
                                        class="w-full border-gray-300 rounded-md shadow-sm"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('admin.games.questions.index', game.id)"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                            >
                                <DocumentPlusIcon v-if="!form.processing" class="w-4 h-4 mr-2" />
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Question</span>
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
    PlusIcon,
    TrashIcon,
    EyeIcon,
    DocumentPlusIcon,
    ListBulletIcon,
    CheckCircleIcon,
    HashtagIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    game: Object,
    nextOrder: Number,
});

const questionTypes = [
    {
        value: 'multiple_choice',
        label: 'Multiple Choice',
        description: 'Choose one answer from multiple options',
        icon: ListBulletIcon
    },
    {
        value: 'yes_no',
        label: 'Yes/No',
        description: 'Simple yes or no question',
        icon: CheckCircleIcon
    },
    {
        value: 'numeric',
        label: 'Numeric',
        description: 'Answer with a number',
        icon: HashtagIcon
    },
    {
        value: 'text',
        label: 'Text',
        description: 'Free text answer',
        icon: DocumentTextIcon
    },
];

const form = useForm({
    question_text: '',
    question_type: 'multiple_choice',
    points: 10,
    display_order: props.nextOrder || 1,
    options: ['', ''],
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
    form.options.push('');
};

const removeOption = (index) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('admin.games.questions.store', props.game.id));
};
</script>