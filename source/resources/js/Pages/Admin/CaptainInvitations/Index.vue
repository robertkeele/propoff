<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    invitations: {
        type: Array,
        required: true,
    },
});

const showCreateForm = ref(false);

const createForm = useForm({
    max_uses: null,
    expires_at: null,
});

const submitCreate = () => {
    createForm.post(route('admin.events.captain-invitations.store', props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
            createForm.reset();
        },
    });
};

const deactivateInvitation = (invitationId) => {
    router.post(route('admin.events.captain-invitations.deactivate', [props.event.id, invitationId]), {}, {
        preserveScroll: true,
    });
};

const reactivateInvitation = (invitationId) => {
    router.post(route('admin.events.captain-invitations.reactivate', [props.event.id, invitationId]), {}, {
        preserveScroll: true,
    });
};

const deleteInvitation = (invitationId) => {
    if (confirm('Are you sure you want to delete this invitation?')) {
        router.delete(route('admin.events.captain-invitations.destroy', [props.event.id, invitationId]), {
            preserveScroll: true,
        });
    }
};

const copyUrl = (url) => {
    navigator.clipboard.writeText(url);
    alert('Invitation URL copied to clipboard!');
};
</script>

<template>
    <Head title="Captain Invitations" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Captain Invitations - {{ event.name }}
                </h2>
                <Link
                    :href="route('admin.events.show', event.id)"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Event
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Info Banner -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">About Captain Invitations</h3>
                    <p class="text-sm text-blue-800">
                        Captain invitations allow users to create groups and become captains for this event.
                        Share the invitation URL with users who should have captain privileges.
                    </p>
                </div>

                <!-- Create Invitation -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Create New Invitation</h3>
                            <button
                                @click="showCreateForm = !showCreateForm"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold"
                            >
                                {{ showCreateForm ? 'Cancel' : '+ New Invitation' }}
                            </button>
                        </div>

                        <form v-if="showCreateForm" @submit.prevent="submitCreate" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Max Uses (Optional)
                                    </label>
                                    <input
                                        v-model="createForm.max_uses"
                                        type="number"
                                        min="1"
                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        placeholder="Unlimited"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">
                                        Leave empty for unlimited uses
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Expires At (Optional)
                                    </label>
                                    <input
                                        v-model="createForm.expires_at"
                                        type="datetime-local"
                                        class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">
                                        Leave empty for no expiration
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="createForm.processing"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold disabled:opacity-50"
                                >
                                    Create Invitation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Invitations List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Active Invitations ({{ invitations.length }})</h3>

                        <div v-if="invitations.length === 0" class="text-center py-12">
                            <p class="text-gray-500">No captain invitations yet.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="invitation in invitations"
                                :key="invitation.id"
                                class="border rounded-lg p-4"
                                :class="invitation.can_be_used ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'"
                            >
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <!-- Status Badge -->
                                        <div class="flex gap-2 mb-3">
                                            <span
                                                v-if="invitation.is_active && invitation.can_be_used"
                                                class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800"
                                            >
                                                Active
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800"
                                            >
                                                Inactive
                                            </span>
                                            <span
                                                v-if="invitation.max_uses"
                                                class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800"
                                            >
                                                {{ invitation.times_used }} / {{ invitation.max_uses }} uses
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800"
                                            >
                                                {{ invitation.times_used }} uses
                                            </span>
                                        </div>

                                        <!-- Invitation URL -->
                                        <div class="mb-3">
                                            <label class="text-xs text-gray-600 block mb-1">Invitation URL</label>
                                            <div class="flex gap-2">
                                                <input
                                                    :value="invitation.url"
                                                    readonly
                                                    class="flex-1 text-sm border-gray-300 rounded-md bg-gray-50 font-mono"
                                                />
                                                <button
                                                    @click="copyUrl(invitation.url)"
                                                    class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm font-semibold"
                                                >
                                                    Copy
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Details -->
                                        <div class="grid grid-cols-2 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-600">Created by:</span>
                                                <span class="font-semibold ml-2">{{ invitation.creator.name }}</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">Created:</span>
                                                <span class="ml-2">{{ new Date(invitation.created_at).toLocaleDateString() }}</span>
                                            </div>
                                            <div v-if="invitation.expires_at">
                                                <span class="text-gray-600">Expires:</span>
                                                <span class="ml-2">{{ new Date(invitation.expires_at).toLocaleString() }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-col gap-2 ml-4">
                                        <button
                                            v-if="invitation.is_active"
                                            @click="deactivateInvitation(invitation.id)"
                                            class="text-sm bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded"
                                        >
                                            Deactivate
                                        </button>
                                        <button
                                            v-else
                                            @click="reactivateInvitation(invitation.id)"
                                            class="text-sm bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded"
                                        >
                                            Reactivate
                                        </button>
                                        <button
                                            v-if="invitation.times_used === 0"
                                            @click="deleteInvitation(invitation.id)"
                                            class="text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
