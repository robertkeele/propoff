<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    event: Object,
});

const form = useForm({
    name: props.event.name,
    description: props.event.description,
    category: props.event.category,
    event_date: props.event.event_date ? props.event.event_date.split('T')[0] + 'T' + props.event.event_date.split('T')[1].substring(0, 5) : '',
    status: props.event.status,
    lock_date: props.event.lock_date ? props.event.lock_date.split('T')[0] + 'T' + props.event.lock_date.split('T')[1].substring(0, 5) : '',
});

const submit = () => {
    form.patch(route('events.update', props.event.id));
};
</script>

<template>
    <Head title="Edit Event" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Event</h2>
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
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Description -->
                        <div>
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                rows="4"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Category -->
                        <div>
                            <InputLabel for="category" value="Category" />
                            <TextInput
                                id="category"
                                v-model="form.category"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.category" />
                        </div>

                        <!-- Event Date -->
                        <div>
                            <InputLabel for="event_date" value="Event Date" />
                            <TextInput
                                id="event_date"
                                v-model="form.event_date"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.event_date" />
                        </div>

                        <!-- Status -->
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="locked">Locked</option>
                                <option value="completed">Completed</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <!-- Lock Date -->
                        <div>
                            <InputLabel for="lock_date" value="Lock Date (Optional)" />
                            <TextInput
                                id="lock_date"
                                v-model="form.lock_date"
                                type="datetime-local"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-2" :message="form.errors.lock_date" />
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a
                                :href="route('events.show', event.id)"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </a>
                            <PrimaryButton :disabled="form.processing">
                                Update Event
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
