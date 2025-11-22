<template>
    <Head title="Create Group" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Group</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Info Banner -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-blue-900 mb-2">Become a Captain!</h3>
                    <p class="text-sm text-blue-800 mb-2">
                        By creating a group, you'll become a captain with the ability to customize questions and manage your group.
                    </p>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Customize questions for your group</li>
                        <li>Set correct answers (if using captain grading)</li>
                        <li>Manage group members</li>
                        <li>Track your group's leaderboard</li>
                    </ul>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Event Selection -->
                        <div class="mb-6">
                            <label for="event_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Select Event <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="event_id"
                                v-model="form.event_id"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="">Choose an event...</option>
                                <option
                                    v-for="event in events"
                                    :key="event.id"
                                    :value="event.id"
                                >
                                    {{ event.name }} - {{ formatDate(event.event_date) }}
                                </option>
                            </select>
                            <p v-if="form.errors.event_id" class="mt-1 text-sm text-red-600">{{ form.errors.event_id }}</p>
                            <p class="mt-1 text-sm text-gray-500">Select the event this group will participate in</p>
                        </div>

                        <!-- Group Name -->
                        <div class="mb-6">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Group Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                id="name"
                                placeholder="My Awesome Group"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            <p class="mt-1 text-sm text-gray-500">Choose a unique name for your group</p>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Description (Optional)
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                                placeholder="Enter a description for your group"
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <!-- Grading Source -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Grading Source <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="{ 'border-indigo-500 bg-indigo-50': form.grading_source === 'captain' }">
                                    <input
                                        type="radio"
                                        v-model="form.grading_source"
                                        value="captain"
                                        class="mt-1 mr-3"
                                    />
                                    <div>
                                        <div class="font-semibold">Captain Grading</div>
                                        <div class="text-sm text-gray-600">
                                            You set the correct answers and grade submissions in real-time.
                                            Best for live events where you want immediate scoring.
                                        </div>
                                    </div>
                                </label>

                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="{ 'border-indigo-500 bg-indigo-50': form.grading_source === 'admin' }">
                                    <input
                                        type="radio"
                                        v-model="form.grading_source"
                                        value="admin"
                                        class="mt-1 mr-3"
                                    />
                                    <div>
                                        <div class="font-semibold">Admin Grading</div>
                                        <div class="text-sm text-gray-600">
                                            The admin sets answers after the event ends. Your group will be graded
                                            based on admin answers. Best for events where grading happens later.
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.grading_source" class="mt-1 text-sm text-red-600">{{ form.errors.grading_source }}</p>
                        </div>

                        <!-- Public/Private -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    v-model="form.is_public"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Make this group public (others can find and join)</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t">
                            <Link
                                :href="route('groups.index')"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                            >
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Group & Become Captain</span>
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
    events: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    description: '',
    event_id: '',
    grading_source: 'captain',
    is_public: false,
});

const submit = () => {
    form.post(route('groups.store'));
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>
