<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    events: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');

const filterEvents = () => {
    router.get(route('admin.events.index'), {
        search: search.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStatusClass = (status) => {
    const classes = {
        'draft': 'bg-gray-100 text-gray-800',
        'open': 'bg-green-100 text-green-800',
        'locked': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-blue-100 text-blue-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head title="Manage Events" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Events</h2>
                <Link :href="route('admin.events.create')">
                    <PrimaryButton>
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Create Event
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <MagnifyingGlassIcon class="w-4 h-4 inline mr-1" />
                                    Search
                                </label>
                                <input
                                    v-model="search"
                                    @input="filterEvents"
                                    type="text"
                                    placeholder="Search events..."
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <FunnelIcon class="w-4 h-4 inline mr-1" />
                                    Status
                                </label>
                                <select
                                    v-model="statusFilter"
                                    @change="filterEvents"
                                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="all">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="open">Open</option>
                                    <option value="locked">Locked</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="events.data.length === 0" class="text-center py-12">
                            <p class="text-gray-500">No events found.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="event in events.data"
                                :key="event.id"
                                class="border rounded-lg p-6 hover:shadow-md transition"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <Link
                                                :href="route('admin.events.show', event.id)"
                                                class="text-xl font-semibold text-gray-900 hover:text-indigo-600"
                                            >
                                                {{ event.name }}
                                            </Link>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="getStatusClass(event.status)"
                                            >
                                                {{ event.status }}
                                            </span>
                                        </div>
                                        <p v-if="event.description" class="mt-2 text-gray-600">{{ event.description }}</p>

                                        <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                            <span>{{ event.questions_count }} questions</span>
                                            <span>{{ event.submissions_count }} submissions</span>
                                            <span>Event: {{ formatDate(event.event_date) }}</span>
                                            <span v-if="event.lock_date">Lock: {{ formatDate(event.lock_date) }}</span>
                                        </div>
                                    </div>

                                    <div class="ml-4 flex flex-col space-y-2">
                                        <Link
                                            :href="route('admin.events.show', event.id)"
                                            class="text-sm text-indigo-600 hover:text-indigo-900"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.events.edit', event.id)"
                                            class="text-sm text-gray-600 hover:text-gray-900"
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            :href="route('admin.events.event-questions.index', event.id)"
                                            class="text-sm text-gray-600 hover:text-gray-900"
                                        >
                                            Questions
                                        </Link>
                                        <Link
                                            :href="route('admin.events.grading.index', event.id)"
                                            class="text-sm text-green-600 hover:text-green-900"
                                        >
                                            Grading
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="events.links.length > 3" class="mt-6 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <component
                                    v-for="(link, index) in events.links"
                                    :key="index"
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                    :class="{
                                        'bg-indigo-50 border-indigo-500 text-indigo-600': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-50': !link.active && link.url,
                                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url,
                                        'rounded-l-md': index === 0,
                                        'rounded-r-md': index === events.links.length - 1,
                                    }"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
