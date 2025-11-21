<template>
    <Head :title="`User: ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Details: {{ user.name }}</h2>
                <Link :href="route('admin.users.index')" class="text-sm text-indigo-600 hover:text-indigo-900">
                    ← Back to Users
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- User Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">User Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Name</label>
                                <p class="text-gray-900">{{ user.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Email</label>
                                <p class="text-gray-900">{{ user.email }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Role</label>
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    user.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'
                                ]">
                                    {{ user.role }}
                                </span>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Member Since</label>
                                <p class="text-gray-900">{{ new Date(user.created_at).toLocaleDateString() }}</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button @click="showRoleModal = true" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 mr-2">
                                Change Role
                            </button>
                            <button @click="confirmDelete" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                Delete User
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <TrophyIcon class="w-8 h-8 text-yellow-500 mr-3" />
                                <div>
                                    <p class="text-sm text-gray-500">Total Submissions</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ statistics.total_submissions }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <UserGroupIcon class="w-8 h-8 text-blue-500 mr-3" />
                                <div>
                                    <p class="text-sm text-gray-500">Groups</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ statistics.groups_count }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <ChartBarIcon class="w-8 h-8 text-green-500 mr-3" />
                                <div>
                                    <p class="text-sm text-gray-500">Average Score</p>
                                    <p class="text-2xl font-bold text-gray-900">{{ statistics.average_score }}%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <StarIcon class="w-8 h-8 text-purple-500 mr-3" />
                                <div>
                                    <p class="text-sm text-gray-500">Best Rank</p>
                                    <p class="text-2xl font-bold text-gray-900">#{{ statistics.best_rank || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Submissions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Submissions</h3>
                        <div v-if="submissions.length === 0" class="text-center py-6 text-gray-500">
                            No submissions yet
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="submission in submissions" :key="submission.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ submission.event.title }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ submission.group.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-semibold">{{ submission.total_score }}</span> / {{ submission.total_possible }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                submission.is_complete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                            ]">
                                                {{ submission.is_complete ? 'Complete' : 'In Progress' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(submission.updated_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Groups -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Group Memberships</h3>
                        <div v-if="groups.length === 0" class="text-center py-6 text-gray-500">
                            Not a member of any groups
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="group in groups" :key="group.id" class="border border-gray-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900">{{ group.name }}</h4>
                                <p class="text-sm text-gray-500">Code: {{ group.join_code }}</p>
                                <p class="text-sm text-gray-600 mt-2">{{ group.members_count }} members</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Change Modal -->
        <div v-if="showRoleModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Change User Role</h3>
                <form @submit.prevent="updateRole">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Role</label>
                        <select v-model="roleForm.role" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showRoleModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { TrophyIcon, UserGroupIcon, ChartBarIcon, StarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user: Object,
    statistics: Object,
    submissions: Array,
    groups: Array,
});

const showRoleModal = ref(false);
const roleForm = useForm({
    role: props.user.role,
});

const updateRole = () => {
    roleForm.post(route('admin.users.update-role', props.user.id), {
        onSuccess: () => {
            showRoleModal.value = false;
        },
    });
};

const confirmDelete = () => {
    if (confirm(`Are you sure you want to delete ${props.user.name}? This action cannot be undone.`)) {
        router.delete(route('admin.users.destroy', props.user.id));
    }
};
</script>
