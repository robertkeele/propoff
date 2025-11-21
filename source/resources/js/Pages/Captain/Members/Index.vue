<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    group: {
        type: Object,
        required: true,
    },
    members: {
        type: Array,
        required: true,
    },
});

const promoteToCaptain = (userId) => {
    if (confirm('Are you sure you want to promote this member to captain?')) {
        router.post(route('captain.groups.members.promote', [props.group.id, userId]), {}, {
            preserveScroll: true,
        });
    }
};

const demoteFromCaptain = (userId) => {
    if (confirm('Are you sure you want to demote this captain to a regular member?')) {
        router.post(route('captain.groups.members.demote', [props.group.id, userId]), {}, {
            preserveScroll: true,
        });
    }
};

const removeMember = (userId) => {
    if (confirm('Are you sure you want to remove this member from the group?')) {
        router.delete(route('captain.groups.members.remove', [props.group.id, userId]), {
            preserveScroll: true,
        });
    }
};

const regenerateJoinCode = () => {
    if (confirm('Are you sure you want to regenerate the join code? The old code will stop working.')) {
        router.post(route('captain.groups.members.regenerateJoinCode', props.group.id), {}, {
            preserveScroll: true,
        });
    }
};

const copyJoinCode = () => {
    navigator.clipboard.writeText(props.group.join_code);
    alert('Join code copied to clipboard!');
};
</script>

<template>
    <Head title="Manage Members" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Manage Members - {{ group.name }}
                </h2>
                <Link
                    :href="route('captain.groups.show', group.id)"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Group
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Join Code Card -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Join Code</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Share this code with members to join your group.
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="flex-1 bg-gray-100 p-4 rounded-lg">
                                <p class="text-2xl font-mono font-bold text-center">
                                    {{ group.join_code }}
                                </p>
                            </div>
                            <button
                                @click="copyJoinCode"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold"
                            >
                                Copy Code
                            </button>
                            <button
                                @click="regenerateJoinCode"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-semibold"
                            >
                                Regenerate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Members List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Members ({{ members.length }})</h3>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Role
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Joined
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Submissions
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="member in members" :key="member.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ member.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ member.email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                v-if="member.is_captain"
                                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"
                                            >
                                                Captain
                                            </span>
                                            <span
                                                v-else
                                                class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800"
                                            >
                                                Member
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(member.joined_at).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ member.submissions_count }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                            <button
                                                v-if="!member.is_captain"
                                                @click="promoteToCaptain(member.id)"
                                                class="text-blue-600 hover:text-blue-900 font-semibold"
                                            >
                                                Promote
                                            </button>
                                            <button
                                                v-if="member.is_captain"
                                                @click="demoteFromCaptain(member.id)"
                                                class="text-yellow-600 hover:text-yellow-900 font-semibold"
                                            >
                                                Demote
                                            </button>
                                            <button
                                                @click="removeMember(member.id)"
                                                class="text-red-600 hover:text-red-900 font-semibold"
                                            >
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-900 mb-2">About Captains</h4>
                        <p class="text-sm text-blue-800">
                            Captains have full control over the group, including managing questions, grading, and member management.
                            You can promote multiple members to captain.
                        </p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="font-semibold text-yellow-900 mb-2">⚠️ Note</h4>
                        <p class="text-sm text-yellow-800">
                            You cannot remove members who have already submitted answers. You also cannot demote the last captain.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
