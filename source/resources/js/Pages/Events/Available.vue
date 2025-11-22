<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlayIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

defineProps({
    events: Object,
});

const selectedGroups = ref({});

const startEvent = (eventId, eventGroups) => {
    const groupId = selectedGroups.value[eventId];

    if (!groupId) {
        alert('Please select a group before playing');
        return;
    }

    router.post(route('submissions.start', eventId), {
        group_id: groupId
    });
};
</script>

<template>
    <Head title="Available Events" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Available Events</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="events.data.length === 0" class="text-center py-12">
                            <p class="text-gray-500">No events available to play at this time.</p>
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="event in events.data"
                                :key="event.id"
                                class="border rounded-lg p-6 hover:shadow-lg transition flex flex-col"
                            >
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-900">{{ event.name }}</h3>
                                    <p class="mt-2 text-gray-600 text-sm line-clamp-3">{{ event.description }}</p>

                                    <div class="mt-4 space-y-2 text-sm">
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ new Date(event.event_date).toLocaleDateString() }}
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ event.questions_count }} questions
                                        </div>
                                        <div class="flex items-center text-gray-600">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            {{ event.creator.name }}
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ event.category }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 space-y-3">
                                    <!-- Group Selection -->
                                    <div v-if="event.groups && event.groups.length > 0">
                                        <label :for="`group-select-${event.id}`" class="block text-sm font-medium text-gray-700 mb-1">
                                            Select Your Group
                                        </label>
                                        <select
                                            :id="`group-select-${event.id}`"
                                            v-model="selectedGroups[event.id]"
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="">Choose a group...</option>
                                            <option
                                                v-for="group in event.groups"
                                                :key="group.id"
                                                :value="group.id"
                                            >
                                                {{ group.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- No Groups Message -->
                                    <div v-else class="text-sm text-orange-600 bg-orange-50 rounded-md p-3">
                                        You need to join a group to play this event.
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex gap-2">
                                        <Link
                                            :href="route('events.show', event.id)"
                                            class="flex-1 text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            View Details
                                        </Link>
                                        <button
                                            v-if="event.groups && event.groups.length > 0"
                                            @click="startEvent(event.id, event.groups)"
                                            class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                                        >
                                            <PlayIcon class="w-4 h-4 mr-1" />
                                            Play
                                        </button>
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
