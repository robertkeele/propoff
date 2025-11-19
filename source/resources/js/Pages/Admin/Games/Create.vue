<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    event_type: '',
    description: '',
    event_date: '',
    lock_date: '',
    status: 'draft',
});

const submit = () => {
    form.post(route('admin.games.store'));
};
</script>

<template>
    <Head title="Create Game" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <Link
                        :href="route('admin.games.index')"
                        class="text-sm text-gray-500 hover:text-gray-700 mr-2"
                    >
                        ← Back to Games
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Game</h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Game Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Give your game a descriptive name
                            </p>
                        </div>

                        <!-- Event Type -->
                        <div>
                            <InputLabel for="event_type" value="Event Type" />
                            <TextInput
                                id="event_type"
                                v-model="form.event_type"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., NFL, NCAA, NBA"
                                required
                            />
                            <InputError :message="form.errors.event_type" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                What type of event is this? (e.g., Trivia Night, Sports Challenge)
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            ></textarea>
                            <InputError :message="form.errors.description" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Provide details about this game
                            </p>
                        </div>

                        <!-- Event Date -->
                        <div>
                            <InputLabel for="event_date" value="Event Date" />
                            <input
                                id="event_date"
                                v-model="form.event_date"
                                type="datetime-local"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            />
                            <InputError :message="form.errors.event_date" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                When will this event take place?
                            </p>
                        </div>

                        <!-- Lock Date -->
                        <div>
                            <InputLabel for="lock_date" value="Lock Date (Optional)" />
                            <input
                                id="lock_date"
                                v-model="form.lock_date"
                                type="datetime-local"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            />
                            <InputError :message="form.errors.lock_date" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                When should submissions be locked? (submissions cannot be changed after this date)
                            </p>
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="open">Open</option>
                                <option value="locked">Locked</option>
                                <option value="completed">Completed</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Set the current status of the game
                            </p>
                        </div>

                        <!-- Status Descriptions -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Status Guide:</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><strong>Draft:</strong> Game is being created, not visible to users</li>
                                <li><strong>Open:</strong> Game is active and accepting submissions</li>
                                <li><strong>Locked:</strong> Game is closed for new submissions</li>
                                <li><strong>Completed:</strong> Game is finished and results are final</li>
                            </ul>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <Link
                                :href="route('admin.games.index')"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                            >
                                Create Game
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Next Steps Card -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 mb-2">Next Steps</h4>
                    <p class="text-sm text-blue-700">
                        After creating the game, you'll be able to:
                    </p>
                    <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                        <li>Add questions to your game</li>
                        <li>Set group-specific correct answers</li>
                        <li>Manage game settings and status</li>
                        <li>View submissions and statistics</li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
