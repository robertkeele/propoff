<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    group: Object,
});

const form = useForm({
    name: props.group.name,
    code: props.group.code,
});

const submit = () => {
    form.patch(route('admin.groups.update', props.group.id));
};
</script>

<template>
    <Head title="Edit Group" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <Link
                        :href="route('admin.groups.show', group.id)"
                        class="text-sm text-gray-500 hover:text-gray-700 mr-2"
                    >
                        ← Back to Group
                    </Link>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Group</h2>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Group Name" />
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
                                The display name for this group
                            </p>
                        </div>

                        <!-- Code -->
                        <div>
                            <InputLabel for="code" value="Group Code" />
                            <TextInput
                                id="code"
                                v-model="form.code"
                                type="text"
                                class="mt-1 block w-full uppercase"
                                required
                                maxlength="50"
                                pattern="[A-Z0-9-]+"
                            />
                            <InputError :message="form.errors.code" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-500">
                                Members use this code to join the group. Only uppercase letters, numbers, and hyphens allowed.
                            </p>
                        </div>

                        <!-- Warning Box -->
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="font-medium text-yellow-900 mb-2">⚠️ Important</h4>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• Changing the group code will invalidate the old code</li>
                                <li>• Members will need the new code to join</li>
                                <li>• Existing members will not be affected</li>
                            </ul>
                        </div>

                        <!-- Group Info -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Group Information</h4>
                            <dl class="text-sm text-gray-600 space-y-1">
                                <div class="flex justify-between">
                                    <dt>Created:</dt>
                                    <dd>{{ new Date(group.created_at).toLocaleDateString() }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t">
                            <Link
                                :href="route('admin.groups.show', group.id)"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :disabled="form.processing"
                            >
                                Update Group
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
