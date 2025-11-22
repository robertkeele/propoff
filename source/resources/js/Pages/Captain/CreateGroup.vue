<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    invitation: {
        type: Object,
        required: true,
    },
    isGuest: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    name: '',
    description: '',
    grading_source: 'captain',
    captain_name: '',
    captain_email: '',
});

const submit = () => {
    form.post(route('captain.groups.store', [props.event.id, props.invitation.token]));
};

const LayoutComponent = props.isGuest ? GuestLayout : AuthenticatedLayout;
</script>

<template>
    <Head title="Create Group" />

    <component :is="LayoutComponent">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Group for {{ event.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Invitation Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ event.name }}</h3>
                    <p class="text-sm text-blue-800 mb-2">
                        You've been invited to play PropOff and to create your own group. This makes you the captain of your group for this event. That means you can add/remove questions, set the correct answers (if you choose that option), send an invite for others to join your group, and promote anyone else to be a captain.
                    </p>
                    <div class="text-sm text-blue-700">
                        <p v-if="invitation.max_uses">
                            Uses: {{ invitation.times_used }} / {{ invitation.max_uses }}
                        </p>
                        <p v-if="invitation.expires_at">
                            Expires: {{ new Date(invitation.expires_at).toLocaleString() }}
                        </p>
                    </div>
                </div>

                <!-- Create Group Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6">
                        <!-- Captain Info (for guests only) -->
                        <div v-if="isGuest" class="mb-8 pb-6 border-b">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Information</h3>

                            <div class="mb-4">
                                <InputLabel for="captain_name" value="Your Name" />
                                <TextInput
                                    id="captain_name"
                                    v-model="form.captain_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                    placeholder="Enter your name"
                                />
                                <InputError class="mt-2" :message="form.errors.captain_name" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="captain_email" value="Your Email (Optional)" />
                                <TextInput
                                    id="captain_email"
                                    v-model="form.captain_email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    placeholder="your.email@example.com (optional)"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    We'll send you a personal link to access your group later
                                </p>
                                <InputError class="mt-2" :message="form.errors.captain_email" />
                            </div>
                        </div>

                        <!-- Group Info -->
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Group Information</h3>
                        </div>

                        <div class="mb-6">
                            <InputLabel for="name" value="Group Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                                placeholder="Enter your group name"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mb-6">
                            <InputLabel for="description" value="Description (Optional)" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="3"
                                placeholder="Enter a description for your group"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="mb-6">
                            <InputLabel value="Grading Source" />
                            <div class="mt-2 space-y-3">
                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
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

                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input
                                        type="radio"
                                        v-model="form.grading_source"
                                        value="admin"
                                        class="mt-1 mr-3"
                                    />
                                    <div>
                                        <div class="font-semibold">Admin Grading</div>
                                        <div class="text-sm text-gray-600">
                                            The website admin sets answers after the event ends. Your group will be graded
                                            based on the admin's answers. Best for events where grading happens later.
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <InputError class="mt-2" :message="form.errors.grading_source" />
                        </div>

                        <div class="flex items-center justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create Group & Become Captain
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </component>
</template>
