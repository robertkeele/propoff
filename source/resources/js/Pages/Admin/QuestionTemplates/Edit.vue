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
                            <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                            <input 
                                type="text" 
                                id="name"
                                v-model="form.name" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            />
                            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
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

                        <!-- Question Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Question Type</label>
                            <select 
                                id="type"
                                v-model="form.type" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="yes_no">Yes/No</option>
                                <option value="numeric">Numeric</option>
                                <option value="text">Text</option>
                            </select>
                            <div v-if="form.errors.type" class="text-red-600 text-sm mt-1">{{ form.errors.type }}</div>
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
                        <div v-if="form.type === 'multiple_choice'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Answer Options</label>
                            <div class="space-y-2">
                                <div v-for="(option, index) in form.options" :key="index" class="flex items-center space-x-2">
                                    <input 
                                        type="text" 
                                        v-model="form.options[index]" 
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Option text (can use variables)"
                                    />
                                    <button 
                                        type="button" 
                                        @click="removeOption(index)" 
                                        class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                @click="addOption" 
                                class="mt-2 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                            >
                                Add Option
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

const form = useForm({
    name: props.template.name,
    category: props.template.category || '',
    type: props.template.type,
    question_text: props.template.question_text,
    variables: props.template.variables || [],
    options: props.template.options || [],
});

const addVariable = () => {
    form.variables.push('');
};

const removeVariable = (index) => {
    form.variables.splice(index, 1);
};

const addOption = () => {
    form.options.push('');
};

const removeOption = (index) => {
    form.options.splice(index, 1);
};

const submit = () => {
    form.put(route('admin.question-templates.update', props.template.id));
};
</script>
