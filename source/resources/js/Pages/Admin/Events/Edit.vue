<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    event: Object,
});

const form = useForm({
    name: props.event.name,
    category: props.event.category,
    description: props.event.description,
    event_date: props.event.event_date?.substring(0, 16) || '', // Format for datetime-local
    lock_date: props.event.lock_date?.substring(0, 16) || '',
    status: props.event.status,
});

const submit = () => {
    form.patch(route('admin.events.update', props.event.id));
};
</script>

<template>
    <Head :title="`Edit ${event.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <Link
                        :href="route('admin.events.show', event.id)"
                        class="text-sm text-gray-500 hover:text-gray-700 mr-2"
                    >
                        ← Back to Event
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Event</h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Event Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <!-- Category -->
                        <div>
                            <InputLabel for="category" value="Category" />
                            <TextInput
                                id="category"
                                v-model="form.category"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., Trivia Night, Sports Challenge"
                                required
                            />
                            <InputError :message="form.errors.category" class="mt-2" />
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
                                Submissions cannot be changed after this date
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
                        </div>

                        <!-- Event Stats (Read-only) -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Event Statistics</h4>
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ event.questions_count }}</div>
                                    <div class="text-xs text-gray-600">Questions</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ event.submissions_count }}</div>
                                    <div class="text-xs text-gray-600">Submissions</div>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">
                                        {{ new Date(event.created_at).toLocaleDateString() }}
                                    </div>
                                    <div class="text-xs text-gray-600">Created</div>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <Link
                                :href="route('admin.events.show', event.id)"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                            >
                                Update Event
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Quick Links -->
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <Link
                        :href="route('admin.events.questions.index', event.id)"
                        class="block p-4 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-center"
                    >
                        <span class="text-sm font-medium text-gray-900">Manage Questions</span>
                    </Link>
                    <Link
                        :href="route('admin.events.grading.index', event.id)"
                        class="block p-4 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-center"
                    >
                        <span class="text-sm font-medium text-gray-900">Set Answers & Grade</span>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
