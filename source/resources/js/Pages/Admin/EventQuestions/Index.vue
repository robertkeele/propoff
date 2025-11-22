<template>
    <Head :title="`Questions - ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Questions for {{ event.name }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage questions for this event
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('admin.events.event-questions.create', event.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Add Question
                    </Link>
                    <Link
                        :href="route('admin.events.show', event.id)"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <ArrowLeftIcon class="w-4 h-4 mr-2" />
                        Back to Event
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Event Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Total Questions</p>
                                <p class="text-2xl font-bold">{{ questions.length }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Total Points</p>
                                <p class="text-2xl font-bold">{{ totalPoints }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Event Date</p>
                                <p class="text-lg font-semibold">{{ formatDate(event.event_date) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Status</p>
                                <span :class="statusClass(event.status)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                    {{ event.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Questions List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="questions.length === 0" class="text-center py-12">
                            <DocumentTextIcon class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Questions Yet</h3>
                            <p class="text-gray-600 mb-4">Get started by creating your first question</p>
                            <Link
                                :href="route('admin.events.event-questions.create', event.id)"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                            >
                                <PlusIcon class="w-4 h-4 mr-2" />
                                Create Question
                            </Link>
                        </div>

                        <div v-else>
                            <!-- Reorder Notice -->
                            <div v-if="isReordering" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start gap-3">
                                    <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 mt-0.5" />
                                    <div class="flex-1">
                                        <p class="text-sm text-yellow-800">
                                            Drag and drop questions to reorder them. Click "Save Order" when done.
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            @click="saveOrder"
                                            :disabled="form.processing"
                                            class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700 disabled:opacity-50"
                                        >
                                            Save Order
                                        </button>
                                        <button
                                            @click="cancelReorder"
                                            class="px-3 py-1.5 bg-gray-600 text-white text-sm rounded hover:bg-gray-700"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">Questions ({{ questions.length }})</h3>
                                <button
                                    v-if="!isReordering && questions.length > 1"
                                    @click="startReorder"
                                    class="inline-flex items-center px-3 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                                >
                                    <ArrowsUpDownIcon class="w-4 h-4 mr-2" />
                                    Reorder Questions
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(question, index) in displayQuestions"
                                    :key="question.id"
                                    :draggable="isReordering"
                                    @dragstart="dragStart(index)"
                                    @dragover.prevent
                                    @drop="drop(index)"
                                    :class="{
                                        'cursor-move': isReordering,
                                        'border-l-4 border-blue-500': draggedIndex === index,
                                        'opacity-50': draggedIndex === index
                                    }"
                                    class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-700 font-bold rounded-full text-sm">
                                                    {{ question.order_number }}
                                                </span>
                                                <span :class="typeClass(question.type)" class="px-2 py-1 rounded text-xs font-medium">
                                                    {{ formatType(question.type) }}
                                                </span>
                                                <span class="text-sm text-gray-600">{{ question.points }} points</span>
                                            </div>
                                            <p class="text-gray-900 font-medium mb-2">{{ question.question_text }}</p>
                                            
                                            <!-- Options for Multiple Choice -->
                                            <div v-if="question.type === 'multiple_choice' && question.options" class="mt-2 space-y-1">
                                                <div
                                                    v-for="(option, optIndex) in question.options"
                                                    :key="optIndex"
                                                    class="text-sm text-gray-600 pl-4"
                                                >
                                                    {{ String.fromCharCode(65 + optIndex) }}. {{ option }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <Link
                                                :href="route('admin.events.event-questions.edit', [event.id, question.id])"
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded"
                                                title="Edit"
                                            >
                                                <PencilIcon class="w-5 h-5" />
                                            </Link>
                                            <button
                                                @click="duplicateQuestion(question.id)"
                                                class="p-2 text-green-600 hover:bg-green-50 rounded"
                                                title="Duplicate"
                                            >
                                                <DocumentDuplicateIcon class="w-5 h-5" />
                                            </button>
                                            <button
                                                @click="deleteQuestion(question.id)"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded"
                                                title="Delete"
                                            >
                                                <TrashIcon class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bulk Import Modal (placeholder) -->
                <div v-if="showBulkImport" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg p-6 max-w-md w-full">
                        <h3 class="text-lg font-medium mb-4">Bulk Import Questions</h3>
                        <p class="text-gray-600 mb-4">This feature allows you to import questions from another event.</p>
                        <p class="text-sm text-yellow-600 mb-4">⚠️ This feature is coming soon!</p>
                        <button
                            @click="showBulkImport = false"
                            class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    PlusIcon,
    ArrowLeftIcon,
    DocumentTextIcon,
    PencilIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    InformationCircleIcon,
    DocumentPlusIcon,
    ArrowDownTrayIcon,
    CheckCircleIcon,
    ArrowsUpDownIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    event: Object,
    questions: Array,
});

const form = useForm({
    order: []
});

const isReordering = ref(false);
const draggedIndex = ref(null);
const displayQuestions = ref([...props.questions]);
const showBulkImport = ref(false);

const totalPoints = computed(() => {
    return props.questions.reduce((sum, q) => sum + q.points, 0);
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const statusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        open: 'bg-green-100 text-green-800',
        locked: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const typeClass = (type) => {
    const classes = {
        multiple_choice: 'bg-purple-100 text-purple-800',
        yes_no: 'bg-blue-100 text-blue-800',
        numeric: 'bg-green-100 text-green-800',
        text: 'bg-orange-100 text-orange-800',
    };
    return classes[type] || 'bg-gray-100 text-gray-800';
};

const formatType = (type) => {
    return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const deleteQuestion = (questionId) => {
    if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
        router.delete(route('admin.events.event-questions.destroy', [props.event.id, questionId]), {
            preserveScroll: true,
            onSuccess: () => {
                // Reload page to get updated questions list
                router.visit(route('admin.events.event-questions.index', props.event.id));
            }
        });
    }
};

const duplicateQuestion = (questionId) => {
    router.post(route('admin.events.event-questions.duplicate', [props.event.id, questionId]), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Reload page to get updated questions list
            router.visit(route('admin.events.event-questions.index', props.event.id));
        }
    });
};

const startReorder = () => {
    isReordering.value = true;
    displayQuestions.value = [...props.questions];
};

const cancelReorder = () => {
    isReordering.value = false;
    displayQuestions.value = [...props.questions];
    draggedIndex.value = null;
};

const dragStart = (index) => {
    draggedIndex.value = index;
};

const drop = (dropIndex) => {
    if (draggedIndex.value === null || draggedIndex.value === dropIndex) return;
    
    const items = [...displayQuestions.value];
    const draggedItem = items[draggedIndex.value];
    items.splice(draggedIndex.value, 1);
    items.splice(dropIndex, 0, draggedItem);
    
    displayQuestions.value = items;
    draggedIndex.value = null;
};

const saveOrder = () => {
    form.order = displayQuestions.value.map(q => q.id);

    form.post(route('admin.events.event-questions.reorder', props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            isReordering.value = false;
        }
    });
};
</script>