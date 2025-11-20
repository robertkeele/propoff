<template>
    <Head title="Edit Question Template" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Question Template</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Template Name -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input
                                type="text"
                                id="title"
                                v-model="form.title"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>

                        <!-- Category -->
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category (Optional)</label>
                            <input
                                type="text"
                                id="category"
                                v-model="form.category"
                                placeholder="e.g., Sports, Entertainment, etc."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <div v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</div>
                        </div>

                        <!-- Base Points -->
                        <div>
                            <label for="default_points" class="block text-sm font-medium text-gray-700">Base Points</label>
                            <input
                                type="number"
                                id="default_points"
                                v-model.number="form.default_points"
                                min="1"
                                step="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <p class="mt-1 text-sm text-gray-500">
                                Points awarded for answering correctly (+ any option bonus)
                            </p>
                            <div v-if="form.errors.default_points" class="text-red-600 text-sm mt-1">{{ form.errors.default_points }}</div>
                        </div>

                        <!-- Question Type -->
                        <div>
                            <label for="question_type" class="block text-sm font-medium text-gray-700">Question Type</label>
                            <select
                                id="question_type"
                                v-model="form.question_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="yes_no">Yes/No</option>
                                <option value="numeric">Numeric</option>
                                <option value="text">Text</option>
                            </select>
                            <div v-if="form.errors.question_type" class="text-red-600 text-sm mt-1">{{ form.errors.question_type }}</div>
                        </div>

                        <!-- Question Text -->
                        <div>
                            <label for="question_text" class="block text-sm font-medium text-gray-700">Question Text</label>
                            <textarea 
                                id="question_text"
                                v-model="form.question_text" 
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Use {variable} syntax for dynamic content"
                                required
                            ></textarea>
                            <p class="mt-1 text-sm text-gray-500">Use curly braces for variables, e.g., {team1}, {player1}</p>
                            <div v-if="form.errors.question_text" class="text-red-600 text-sm mt-1">{{ form.errors.question_text }}</div>
                        </div>

                        <!-- Variables -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Variables</label>
                            <div class="space-y-2">
                                <div v-for="(variable, index) in form.variables" :key="index" class="flex items-center space-x-2">
                                    <span class="text-gray-600">{</span>
                                    <input 
                                        type="text" 
                                        v-model="form.variables[index]" 
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="variable_name"
                                    />
                                    <span class="text-gray-600">}</span>
                                    <button 
                                        type="button" 
                                        @click="removeVariable(index)" 
                                        class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                @click="addVariable" 
                                class="mt-2 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                            >
                                Add Variable
                            </button>
                        </div>

                        <!-- Options (for multiple choice) -->
                        <div v-if="form.question_type === 'multiple_choice'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Answer Options</label>
                            <p class="text-sm text-gray-500 mb-3">
                                Set the option labels and optional bonus points. Options can use {'{'}variable{'}'} syntax.
                            </p>
                            <div class="space-y-3">
                                <div
                                    v-for="(option, index) in form.default_options"
                                    :key="index"
                                    class="border border-gray-200 rounded-lg p-3 bg-gray-50"
                                >
                                    <div class="flex items-start gap-2">
                                        <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-700 font-medium rounded-full text-sm mt-1">
                                            {{ String.fromCharCode(65 + index) }}
                                        </span>
                                        <div class="flex-1 space-y-1">
                                            <input
                                                type="text"
                                                v-model="form.default_options[index].label"
                                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                :placeholder="`Option ${String.fromCharCode(65 + index)}`"
                                            />
                                            <div class="flex items-center gap-2">
                                                <label class="text-xs text-gray-500">Bonus:</label>
                                                <input
                                                    type="number"
                                                    v-model.number="form.default_options[index].points"
                                                    min="0"
                                                    step="1"
                                                    class="w-20 text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                                    placeholder="0"
                                                />
                                                <span class="text-xs text-gray-400">+bonus pts (optional)</span>
                                            </div>
                                        </div>
                                        <button
                                            v-if="form.default_options.length > 2"
                                            type="button"
                                            @click="removeOption(index)"
                                            class="flex-shrink-0 px-3 py-2 text-sm text-red-600 hover:text-red-800"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="addOption"
                                class="mt-3 w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition"
                            >
                                + Add Option
                            </button>
                        </div>

                        <!-- Preview -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-blue-900 mb-2">Preview</h4>
                            <p class="text-sm text-gray-700 mb-2">{{ form.question_text }}</p>
                            <div v-if="form.variables.length > 0" class="text-xs text-gray-600">
                                <strong>Variables:</strong> 
                                <span v-for="(variable, index) in form.variables" :key="index" class="ml-1 font-mono">
                                    {{ '{' + variable + '}' }}{{ index < form.variables.length - 1 ? ',' : '' }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <Link :href="route('admin.question-templates.index')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                Cancel
                            </Link>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Updating...' : 'Update Template' }}
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

const props = defineProps({
    template: Object,
});

// Normalize options to new format if needed (backward compatibility)
const normalizeOptions = (options) => {
    if (!options || options.length === 0) return [];

    // Check if already in new format
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
    title: props.template.title,
    category: props.template.category || '',
    question_type: props.template.question_type,
    question_text: props.template.question_text,
    variables: props.template.variables || [],
    default_options: normalizeOptions(props.template.default_options),
    default_points: props.template.default_points || 1,
});

const addVariable = () => {
    form.variables.push('');
};

const removeVariable = (index) => {
    form.variables.splice(index, 1);
};

const addOption = () => {
    form.default_options.push({ label: '', points: 0 });
};

const removeOption = (index) => {
    if (form.default_options.length > 2) {
        form.default_options.splice(index, 1);
    }
};

const submit = () => {
    form.put(route('admin.question-templates.update', props.template.id));
};
</script>
