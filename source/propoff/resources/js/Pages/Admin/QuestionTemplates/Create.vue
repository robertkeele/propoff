<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    category: '',
    question_text: '',
    question_type: 'multiple_choice',
    options: [],
    variables: [],
});

const newOption = ref('');
const newVariable = ref('');

const addOption = () => {
    if (newOption.value.trim()) {
        form.options.push(newOption.value.trim());
        newOption.value = '';
    }
};

const removeOption = (index) => {
    form.options.splice(index, 1);
};

const addVariable = () => {
    if (newVariable.value.trim()) {
        form.variables.push(newVariable.value.trim());
        newVariable.value = '';
    }
};

const removeVariable = (index) => {
    form.variables.splice(index, 1);
};

const submit = () => {
    form.post(route('admin.question-templates.store'));
};
</script>

<template>
    <Head title="Create Question Template" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <Link
                    :href="route('admin.question-templates.index')"
                    class="text-sm text-gray-500 hover:text-gray-700 mr-2"
                >
                    ← Back to Templates
                </Link>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Question Template</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Template Name -->
                        <div>
                            <InputLabel for="name" value="Template Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                A descriptive name for this template
                            </p>
                        </div>

                        <!-- Category -->
                        <div>
                            <InputLabel for="category" value="Category" />
                            <TextInput
                                id="category"
                                v-model="form.category"
                                type="text"
                                class="mt-1 block w-full"
                            />
                            <InputError :message="form.errors.category" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Optional: Group similar templates (e.g., "Sports", "Movies")
                            </p>
                        </div>

                        <!-- Question Type -->
                        <div>
                            <InputLabel for="question_type" value="Question Type" />
                            <select
                                id="question_type"
                                v-model="form.question_type"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="yes_no">Yes/No</option>
                                <option value="numeric">Numeric</option>
                                <option value="text">Text</option>
                            </select>
                            <InputError :message="form.errors.question_type" class="mt-2" />
                        </div>

                        <!-- Question Text -->
                        <div>
                            <InputLabel for="question_text" value="Question Text" />
                            <textarea
                                id="question_text"
                                v-model="form.question_text"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            ></textarea>
                            <InputError :message="form.errors.question_text" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Use {variable} syntax for dynamic content (e.g., "Who will win {team1} vs {team2}?")
                            </p>
                        </div>

                        <!-- Variables -->
                        <div>
                            <InputLabel value="Variables" />
                            <div class="mt-2 space-y-2">
                                <div
                                    v-for="(variable, index) in form.variables"
                                    :key="index"
                                    class="flex items-center gap-2"
                                >
                                    <span class="flex-1 px-3 py-2 bg-gray-100 rounded-md text-sm">{
{{ variable }}}</span>
                                    <button
                                        type="button"
                                        @click="removeVariable(index)"
                                        class="px-3 py-2 text-sm text-red-600 hover:text-red-800"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <TextInput
                                    v-model="newVariable"
                                    type="text"
                                    class="flex-1"
                                    placeholder="Variable name (e.g., team1)"
                                    @keyup.enter="addVariable"
                                />
                                <button
                                    type="button"
                                    @click="addVariable"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                >
                                    Add Variable
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Add variables that can be replaced when creating questions
                            </p>
                        </div>

                        <!-- Options (for multiple choice) -->
                        <div v-if="form.question_type === 'multiple_choice'">
                            <InputLabel value="Answer Options" />
                            <div class="mt-2 space-y-2">
                                <div
                                    v-for="(option, index) in form.options"
                                    :key="index"
                                    class="flex items-center gap-2"
                                >
                                    <span class="flex-1 px-3 py-2 bg-gray-100 rounded-md text-sm">{{ option }}</span>
                                    <button
                                        type="button"
                                        @click="removeOption(index)"
                                        class="px-3 py-2 text-sm text-red-600 hover:text-red-800"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 flex gap-2">
                                <TextInput
                                    v-model="newOption"
                                    type="text"
                                    class="flex-1"
                                    placeholder="Option text"
                                    @keyup.enter="addOption"
                                />
                                <button
                                    type="button"
                                    @click="addOption"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                                >
                                    Add Option
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                Options can also use {variable} syntax
                            </p>
                        </div>

                        <!-- Example Preview -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-medium text-blue-900 mb-2">Template Example</h4>
                            <p class="text-sm text-blue-700">
                                Question: {{ form.question_text || 'Enter question text...' }}
                            </p>
                            <div v-if="form.variables.length > 0" class="mt-2">
                                <p class="text-xs text-blue-600 font-medium">Variables:</p>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span
                                        v-for="variable in form.variables"
                                        :key="variable"
                                        class="px-2 py-1 bg-blue-200 text-blue-900 text-xs rounded"
                                    >
                                        {{{ variable }}}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <Link
                                :href="route('admin.question-templates.index')"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                            >
                                Create Template
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
