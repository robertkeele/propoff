<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    title: '',
    category: '',
    question_text: '',
    question_type: 'multiple_choice',
    default_options: [
        { label: '', points: 0 },
        { label: '', points: 0 }
    ],
    variables: [],
    default_points: 1,
});

const newVariable = ref('');

const addOption = () => {
    form.default_options.push({ label: '', points: 0 });
};

const removeOption = (index) => {
    if (form.default_options.length > 2) {
        form.default_options.splice(index, 1);
    }
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
                            <InputLabel for="title" value="Template Name" />
                            <TextInput
                                id="title"
                                v-model="form.title"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.title" class="mt-2" />
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

                        <!-- Base Points -->
                        <div>
                            <InputLabel for="default_points" value="Base Points" />
                            <TextInput
                                id="default_points"
                                v-model.number="form.default_points"
                                type="number"
                                min="1"
                                step="1"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.default_points" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Points awarded for answering correctly (+ any option bonus)
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
                                    <span class="flex-1 px-3 py-2 bg-gray-100 rounded-md text-sm">{{ variable }}</span>
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
                            <p class="mt-1 mb-3 text-sm text-gray-500">
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
                                        {{ variable }}
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
