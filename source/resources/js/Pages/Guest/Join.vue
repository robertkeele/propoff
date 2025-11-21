<template>
    <Head title="Join Event" />

    <div class="min-h-screen bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
            <!-- Event Info -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <TrophyIcon class="w-10 h-10 text-blue-600" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ invitation.event.name }}
                </h1>
                <p class="text-gray-600">
                    {{ invitation.event.category }}
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    Event Date: {{ formatDate(invitation.event.event_date) }}
                </p>
            </div>

            <!-- Group Info -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
                <div class="flex items-center gap-2">
                    <UserGroupIcon class="w-5 h-5 text-purple-600" />
                    <p class="text-sm text-purple-900">
                        You're joining: <strong>{{ invitation.group.name }}</strong>
                    </p>
                </div>
            </div>

            <!-- Registration Form -->
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Enter Your Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Your name"
                        required
                        autofocus
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email (Optional - for future features)
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="your.email@example.com"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Optional: We'll send you a link to view your results later (coming soon)
                    </p>
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    <span v-if="form.processing">Joining...</span>
                    <span v-else>Join Event</span>
                </button>
            </form>

            <!-- Info Box -->
            <div class="mt-6 text-center text-sm text-gray-600">
                <p>No account required! Just enter your name to get started.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { TrophyIcon, UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    invitation: Object,
});

const form = useForm({
    name: '',
    email: '',
});

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const submit = () => {
    form.post(route('guest.register', props.invitation.token));
};
</script>