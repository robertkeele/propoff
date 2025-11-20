<template>
    <Head title="Question Templates" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Question Templates</h2>
                <Link :href="route('admin.question-templates.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Create Template
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search and Filter -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <input 
                                    type="text" 
                                    v-model="filters.search" 
                                    @input="filterTemplates"
                                    placeholder="Search templates..." 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <select 
                                    v-model="filters.type" 
                                    @change="filterTemplates"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Types</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="yes_no">Yes/No</option>
                                    <option value="numeric">Numeric</option>
                                    <option value="text">Text</option>
                                </select>
                            </div>
                            <div>
                                <select 
                                    v-model="filters.category" 
                                    @change="filterTemplates"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">All Categories</option>
                                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Templates Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="template in filteredTemplates" :key="template.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ template.title }}</h3>
                                    <span v-if="template.category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ template.category }}
                                    </span>
                                </div>
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    template.question_type === 'multiple_choice' ? 'bg-blue-100 text-blue-800' :
                                    template.question_type === 'yes_no' ? 'bg-green-100 text-green-800' :
                                    template.question_type === 'numeric' ? 'bg-yellow-100 text-yellow-800' :
                                    'bg-purple-100 text-purple-800'
                                ]">
                                    {{ template.question_type?.replace('_', ' ') }}
                                </span>
                            </div>

                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ template.question_text }}</p>

                            <div v-if="template.variables && template.variables.length > 0" class="mb-4">
                                <p class="text-xs text-gray-500 mb-1">Variables:</p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="variable in template.variables" :key="variable" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-indigo-50 text-indigo-700">
                                        {{ '{' + variable + '}' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                <div class="flex space-x-2">
                                    <Link :href="route('admin.question-templates.edit', template.id)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        Edit
                                    </Link>
                                    <button @click="duplicateTemplate(template.id)" class="text-green-600 hover:text-green-900 text-sm font-medium">
                                        Duplicate
                                    </button>
                                    <button @click="confirmDelete(template.id)" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                                <Link :href="route('admin.question-templates.preview', template.id)" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                    Preview
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredTemplates.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No templates found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new question template.</p>
                        <div class="mt-6">
                            <Link :href="route('admin.question-templates.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                <PlusIcon class="w-4 h-4 mr-2" />
                                Create Template
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { PlusIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    templates: Object,  // Pagination object, not array
});

const filters = ref({
    search: '',
    type: '',
    category: '',
});

const categories = computed(() => {
    if (!props.templates?.data) return [];
    const cats = props.templates.data
        .map(t => t.category)
        .filter(c => c)
        .filter((value, index, self) => self.indexOf(value) === index);
    return cats;
});

const filteredTemplates = computed(() => {
    if (!props.templates?.data) return [];
    let result = props.templates.data;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        result = result.filter(t =>
            t.title?.toLowerCase().includes(search) ||
            t.question_text?.toLowerCase().includes(search)
        );
    }

    if (filters.value.type) {
        result = result.filter(t => t.question_type === filters.value.type);
    }

    if (filters.value.category) {
        result = result.filter(t => t.category === filters.value.category);
    }

    return result;
});

const filterTemplates = () => {
    // Filters are reactive, no action needed
};

const duplicateTemplate = (id) => {
    router.post(route('admin.question-templates.duplicate', id));
};

const confirmDelete = (id) => {
    if (confirm('Are you sure you want to delete this template?')) {
        router.delete(route('admin.question-templates.destroy', id));
    }
};
</script>
