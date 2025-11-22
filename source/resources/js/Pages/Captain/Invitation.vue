<template>
    <Head :title="`Member Invitation - ${group.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Member Invitation
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Invite members to join {{ group.name }}
                    </p>
                </div>
                <Link
                    :href="route('captain.groups.show', group.id)"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    <ArrowLeftIcon class="w-4 h-4 mr-2" />
                    Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Group Info -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <UserGroupIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900">{{ group.name }}</h3>
                            <p class="text-sm text-gray-600 mt-1">
                                Event: {{ group.event.name }} | {{ group.event.category }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ formatDate(group.event.event_date) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Invitation Link Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Invitation Link</h3>
                        <span
                            :class="[
                                'px-3 py-1 rounded-full text-xs font-semibold',
                                invitation.is_active
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-red-100 text-red-800'
                            ]"
                        >
                            {{ invitation.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <!-- URL Display and Copy -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Share this link with members
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                :value="invitation.url"
                                readonly
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-white text-sm font-mono"
                            />
                            <button
                                @click="copyToClipboard"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                            >
                                <ClipboardDocumentIcon class="w-5 h-5 mr-2" />
                                {{ copied ? 'Copied!' : 'Copy' }}
                            </button>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="text-center py-6 border-t border-b border-gray-200 my-6">
                        <p class="text-sm text-gray-600 mb-4">Scan QR Code to Join</p>
                        <div class="inline-block bg-white p-4 rounded-lg shadow-sm">
                            <div ref="qrcode" class="w-64 h-64"></div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-blue-600">{{ invitation.times_used }}</div>
                            <div class="text-sm text-blue-700">Times Used</div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="text-2xl font-bold text-purple-600">
                                {{ invitation.max_uses || '∞' }}
                            </div>
                            <div class="text-sm text-purple-700">Max Uses</div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-3">
                        <button
                            @click="toggleInvitation"
                            :disabled="toggleForm.processing"
                            :class="[
                                'inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest transition',
                                invitation.is_active
                                    ? 'bg-red-600 text-white hover:bg-red-700'
                                    : 'bg-green-600 text-white hover:bg-green-700'
                            ]"
                        >
                            {{ invitation.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                            @click="showRegenerateModal = true"
                            class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700"
                        >
                            <ArrowPathIcon class="w-4 h-4 mr-2" />
                            Regenerate Link
                        </button>
                        <button
                            @click="showSettingsModal = true"
                            class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            <Cog6ToothIcon class="w-4 h-4 mr-2" />
                            Settings
                        </button>
                    </div>
                </div>

                <!-- Warning if inactive -->
                <div v-if="!invitation.is_active" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" />
                        <div>
                            <h4 class="font-semibold text-yellow-900">Invitation Inactive</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                This invitation link is currently inactive. New members cannot join using this link.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Regenerate Confirmation Modal -->
    <Teleport to="body">
        <div
            v-if="showRegenerateModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showRegenerateModal = false"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Regenerate Invitation Link?</h3>
                <p class="text-sm text-gray-600 mb-6">
                    This will create a new invitation link and reset the usage counter. The old link will no longer work.
                </p>
                <div class="flex gap-3 justify-end">
                    <button
                        @click="showRegenerateModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        @click="regenerateInvitation"
                        :disabled="regenerateForm.processing"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md text-sm font-medium hover:bg-yellow-700 disabled:opacity-50"
                    >
                        {{ regenerateForm.processing ? 'Regenerating...' : 'Regenerate' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Settings Modal -->
    <Teleport to="body">
        <div
            v-if="showSettingsModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showSettingsModal = false"
        >
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Invitation Settings</h3>

                <form @submit.prevent="updateSettings" class="space-y-4">
                    <div>
                        <label for="max_uses" class="block text-sm font-medium text-gray-700 mb-2">
                            Maximum Uses (leave empty for unlimited)
                        </label>
                        <input
                            id="max_uses"
                            type="number"
                            v-model.number="settingsForm.max_uses"
                            min="1"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                            placeholder="Unlimited"
                        />
                        <p v-if="settingsForm.errors.max_uses" class="mt-1 text-sm text-red-600">
                            {{ settingsForm.errors.max_uses }}
                        </p>
                    </div>

                    <div>
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                            Expiration Date (leave empty for no expiration)
                        </label>
                        <input
                            id="expires_at"
                            type="datetime-local"
                            v-model="settingsForm.expires_at"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                        />
                        <p v-if="settingsForm.errors.expires_at" class="mt-1 text-sm text-red-600">
                            {{ settingsForm.errors.expires_at }}
                        </p>
                    </div>

                    <div class="flex gap-3 justify-end pt-4">
                        <button
                            type="button"
                            @click="showSettingsModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="settingsForm.processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ settingsForm.processing ? 'Saving...' : 'Save Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeftIcon,
    UserGroupIcon,
    ClipboardDocumentIcon,
    ArrowPathIcon,
    Cog6ToothIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import QRCode from 'qrcode';

const props = defineProps({
    group: Object,
    invitation: Object,
});

const copied = ref(false);
const showRegenerateModal = ref(false);
const showSettingsModal = ref(false);
const qrcode = ref(null);

const toggleForm = useForm({});
const regenerateForm = useForm({});
const settingsForm = useForm({
    max_uses: props.invitation.max_uses,
    expires_at: props.invitation.expires_at,
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

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(props.invitation.url);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const toggleInvitation = () => {
    toggleForm.post(route('captain.groups.invitation.toggle', props.group.id), {
        preserveScroll: true,
    });
};

const regenerateInvitation = () => {
    regenerateForm.post(route('captain.groups.invitation.regenerate', props.group.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRegenerateModal.value = false;
            // Regenerate QR code with new URL
            generateQRCode();
        },
    });
};

const updateSettings = () => {
    settingsForm.patch(route('captain.groups.invitation.update', props.group.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSettingsModal.value = false;
        },
    });
};

const generateQRCode = () => {
    if (qrcode.value) {
        QRCode.toCanvas(props.invitation.url, {
            width: 256,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#ffffff',
            },
        }, (error, canvas) => {
            if (error) {
                console.error('QR Code generation error:', error);
                return;
            }
            qrcode.value.innerHTML = '';
            qrcode.value.appendChild(canvas);
        });
    }
};

onMounted(() => {
    generateQRCode();
});
</script>
