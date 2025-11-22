<template>
    <Head title="Become a Captain" />

    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <div class="bg-indigo-100 rounded-full p-3">
                    <UserGroupIcon class="h-12 w-12 text-indigo-600" />
                </div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Become a Captain
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                You've been invited to create a group for this event
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <div class="space-y-6">
                    <!-- Event Info -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-blue-900">{{ event.name }}</h3>
                        <p class="text-sm text-blue-700 mt-1">{{ event.category }}</p>
                        <p class="text-xs text-blue-600 mt-2">
                            Event Date: {{ formatDate(event.event_date) }}
                        </p>
                        <p v-if="event.description" class="text-sm text-blue-700 mt-2">
                            {{ event.description }}
                        </p>
                    </div>

                    <!-- What you'll be able to do -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 mb-3">As a Captain, you can:</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start">
                                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                                <span>Create and manage your own group</span>
                            </li>
                            <li class="flex items-start">
                                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                                <span>Customize questions for your group</span>
                            </li>
                            <li class="flex items-start">
                                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                                <span>Invite members to join your group</span>
                            </li>
                            <li class="flex items-start">
                                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                                <span>Set correct answers and grade submissions</span>
                            </li>
                            <li class="flex items-start">
                                <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" />
                                <span>View your group's leaderboard</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Login/Register Options -->
                    <div class="space-y-3 pt-4 border-t">
                        <p class="text-sm text-gray-700 text-center font-medium">
                            To continue, please login or create an account:
                        </p>

                        <Link
                            :href="route('login')"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Login to Continue
                        </Link>

                        <Link
                            :href="route('register')"
                            class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Create New Account
                        </Link>
                    </div>

                    <div class="text-center">
                        <p class="text-xs text-gray-500">
                            Don't have an account? Creating one is quick and free!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { UserGroupIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    event: Object,
    invitation: Object,
});

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
