<template>
    <Head :title="`Create Questions - ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Manage Questions
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Add questions to {{ event.name }} from templates or create manually
                    </p>
                </div>
                <Link
                    :href="route('admin.events.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    <ArrowLeftIcon class="w-4 h-4 mr-2" />
                    Back to Events
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <!-- Event Context -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <InformationCircleIcon class="w-5 h-5 text-blue-600 mt-0.5" />
                        <div>
                            <h3 class="font-semibold text-blue-900">{{ event.name }}</h3>
                            <p class="text-sm text-blue-700 mt-1">
                                Category: {{ event.category }} | Current Questions: {{ currentQuestions.length }} | Event Date: {{ formatDate(event?.event_date) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- LEFT: Available Templates -->
                    <div class="lg:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="border-b border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <DocumentPlusIcon class="w-5 h-5 inline mr-2" />
                                    Available Templates ({{ availableTemplates.length }})
                                </h3>

                                <div v-if="availableTemplates.length === 0" class="text-center py-8 text-gray-500">
                                    No templates available for the {{ event.category }} category.
                                </div>

                                <div v-else class="space-y-3">
                                    <!-- Select All / Deselect All -->
                                    <div class="flex gap-2 pb-4 border-b">
                                        <button @click="selectAllTemplates"
                                                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                            Select All
                                        </button>
                                        <button @click="deselectAllTemplates"
                                                class="px-3 py-1 text-sm bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                                            Deselect All
                                        </button>
                                        <button @click="bulkCreateSelected"
                                                :disabled="selectedTemplateIds.length === 0"
                                                class="ml-auto px-4 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700 disabled:bg-green-400">
                                            Add {{ selectedTemplateIds.length }} Selected
                                        </button>
                                    </div>

                                    <!-- Template List -->
                                    <div class="space-y-2">
                                        <div v-for="template in availableTemplates" :key="template.id"
                                             class="flex items-start gap-3 p-3 border border-gray-200 rounded hover:bg-gray-50">

                                            <!-- Checkbox -->
                                            <input
                                                type="checkbox"
                                                :value="template.id"
                                                v-model="selectedTemplateIds"
                                                class="mt-1"
                                            />

                                            <!-- Template Info -->
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <h4 class="font-semibold text-gray-900">{{ template.title }}</h4>
                                                    <span :class="['px-2 py-0.5 text-xs rounded', typeClass(template.question_type)]">
                                                        {{ formatType(template.question_type) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mb-2">{{ template.question_text }}</p>

                                                <!-- Variables Badge -->
                                                <div v-if="template.variables?.length" class="text-xs">
                                                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded">
                                                        {{ template.variables.length }} variable{{ template.variables.length !== 1 ? 's' : '' }}:
                                                        {{ template.variables.join(', ') }}
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Single Add Button -->
                                            <button @click="addSingleTemplate(template)"
                                                    class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Current Questions -->
                    <div>
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <ListBulletIcon class="w-5 h-5 inline mr-2" />
                                    Current Questions ({{ currentQuestions.length }})
                                </h3>

                                <div v-if="currentQuestions.length === 0" class="text-center py-8 text-gray-500">
                                    <p>No questions added yet.</p>
                                    <p class="text-sm mt-2">Select templates from the left or create a custom question below.</p>
                                </div>

                                <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                                    <div v-for="(question, index) in currentQuestions" :key="question.id"
                                         class="p-2 bg-gray-50 rounded border border-gray-200 text-sm">

                                        <div class="flex justify-between items-start gap-2">
                                            <div class="flex-1">
                                                <p class="font-semibold text-gray-900 line-clamp-2">
                                                    {{ question.question_text }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Order: {{ question.display_order }} | Points: {{ question.points }}
                                                </p>
                                            </div>
                                            <button @click="deleteQuestion(question.id)"
                                                    class="text-red-500 hover:text-red-700 flex-shrink-0">
                                                <TrashIcon class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Manual Question Button - Always show -->
                                <button @click="showManualForm = !showManualForm"
                                        class="w-full mt-4 px-3 py-2 text-sm bg-purple-600 text-white rounded hover:bg-purple-700">
                                    {{ showManualForm ? '- Hide Form' : '+ Add Custom Question' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Question Form (if expanded) -->
                <div v-if="showManualForm" class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Create Custom Question
                        </h3>
                        <button @click="showManualForm = false" class="text-gray-500 hover:text-gray-700">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitManual" class="space-y-6">
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
                                <label for="order" class="block text-sm font-medium text-gray-700 mb-2">
                                    Order Number
                                </label>
                                <input
                                    type="number"
                                    id="order"
                                    v-model.number="form.order"
                                    min="0"
                                    class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <button
                                type="button"
                                @click="showManualForm = false"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Save</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Variable Input Modal -->
                <div v-if="showVariableModal && currentTemplate" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showVariableModal = false"></div>

                    <!-- Modal -->
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                            <!-- Header -->
                            <div class="border-b border-gray-200 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        Set Variable Values
                                    </h3>
                                    <button @click="showVariableModal = false" class="text-gray-400 hover:text-gray-500">
                                        <XMarkIcon class="w-6 h-6" />
                                    </button>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Template: <span class="font-medium">{{ currentTemplate.title }}</span>
                                </p>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-4">
                                <!-- Preview with placeholders -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                                    <h4 class="text-sm font-semibold text-blue-900 mb-2">Preview</h4>
                                    <p class="text-sm text-gray-700">{{ getPreviewText() }}</p>
                                </div>

                                <!-- Variable inputs -->
                                <div class="space-y-4">
                                    <div v-for="variable in currentTemplate.variables" :key="variable">
                                        <label :for="'var-' + variable" class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ variable }}
                                            <span class="text-xs text-gray-500 ml-1">(will replace {{ variable }})</span>
                                        </label>
                                        <input
                                            :id="'var-' + variable"
                                            type="text"
                                            v-model="variableValues[variable]"
                                            class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                            :placeholder="`Enter value for ${variable}`"
                                            required
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-end gap-3">
                                <button
                                    @click="showVariableModal = false"
                                    type="button"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="submitTemplateWithVariables"
                                    type="button"
                                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                >
                                    Add Question
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ArrowLeftIcon,
    InformationCircleIcon,
    DocumentPlusIcon,
    ListBulletIcon,
    TrashIcon,
    PlusIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    event: Object,
    availableTemplates: Array,
    currentQuestions: Array,
    nextOrder: Number,
});

// State
const selectedTemplateIds = ref([]);
const showManualForm = ref(false);
const showVariableModal = ref(false);
const currentTemplate = ref(null);
const variableValues = ref({});

// Form for manual creation
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
    order: props.nextOrder || 1,
    options: [
        { label: '', points: 0 },
        { label: '', points: 0 }
    ],
});

// Methods
const selectAllTemplates = () => {
    selectedTemplateIds.value = props.availableTemplates.map(t => t.id);
};

const deselectAllTemplates = () => {
    selectedTemplateIds.value = [];
};

const bulkCreateSelected = () => {
    if (selectedTemplateIds.value.length === 0) return;

    router.post(
        route('admin.events.event-questions.bulkCreateFromTemplates', props.event.id),
        {
            templates: selectedTemplateIds.value,
            starting_order: props.nextOrder,
        },
        {
            onSuccess: () => {
                selectedTemplateIds.value = [];
            }
        }
    );
};

const addSingleTemplate = (template) => {
    // Check if template has variables
    if (template.variables && template.variables.length > 0) {
        // Show modal to collect variable values
        currentTemplate.value = template;
        // Initialize variable values
        variableValues.value = {};
        template.variables.forEach(variable => {
            variableValues.value[variable] = '';
        });
        showVariableModal.value = true;
    } else {
        // No variables, add directly
        router.post(
            route('admin.events.event-questions.createFromTemplate', [props.event.id, template.id]),
            {
                variable_values: {},
                order: props.nextOrder,
                points: template.default_points,
            }
        );
    }
};

const submitTemplateWithVariables = () => {
    if (!currentTemplate.value) return;

    router.post(
        route('admin.events.event-questions.createFromTemplate', [props.event.id, currentTemplate.value.id]),
        {
            variable_values: variableValues.value,
            order: props.nextOrder,
            points: currentTemplate.value.default_points,
        },
        {
            onSuccess: () => {
                showVariableModal.value = false;
                currentTemplate.value = null;
                variableValues.value = {};
            }
        }
    );
};

const deleteQuestion = (questionId) => {
    if (confirm('Delete this question?')) {
        router.delete(
            route('admin.events.event-questions.destroy', [props.event.id, questionId]),
            {
                onSuccess: () => {
                    router.visit(route('admin.events.event-questions.create', props.event.id));
                }
            }
        );
    }
};

const formatType = (type) => {
    if (!type) return 'Unknown';
    return type.split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

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

const addOption = () => {
    form.options.push({ label: '', points: 0 });
};

const removeOption = (index) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
    }
};

const submitManual = () => {
    form.post(route('admin.events.event-questions.store', props.event.id), {
        onSuccess: () => {
            showManualForm.value = false;
            form.reset();
        }
    });
};

const getPreviewText = () => {
    if (!currentTemplate.value) return '';

    let text = currentTemplate.value.question_text;

    // Replace variables with their current values (or placeholder if empty)
    if (currentTemplate.value.variables) {
        currentTemplate.value.variables.forEach(variable => {
            const value = variableValues.value[variable] || `{${variable}}`;
            text = text.replace(new RegExp(`\\{${variable}\\}`, 'g'), value);
        });
    }

    return text;
};
</script>
