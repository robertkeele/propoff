<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    MagnifyingGlassIcon,
    DocumentArrowDownIcon,
    TrashIcon,
    UsersIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    groups: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const selectedGroups = ref([]);

const filterGroups = () => {
    router.get(route('admin.groups.index'), {
        search: search.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const deleteGroup = (group) => {
    if (confirm(`Are you sure you want to delete ${group.name}? This action cannot be undone.`)) {
        router.delete(route('admin.groups.destroy', group.id));
    }
};

const bulkDelete = () => {
    if (selectedGroups.value.length === 0) {
        alert('Please select groups to delete');
        return;
    }
    if (confirm(`Delete ${selectedGroups.value.length} groups? This action cannot be undone.`)) {
        router.post(route('admin.groups.bulkDelete'), {
            group_ids: selectedGroups.value
        }, {
            onSuccess: () => {
                selectedGroups.value = [];
            }
        });
    }
};

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedGroups.value = props.groups.data.map(g => g.id);
    } else {
        selectedGroups.value = [];
    }
};

const exportCSV = () => {
    window.location.href = route('admin.groups.exportCSV');
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Manage Groups" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Groups</h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('admin.groups.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                    >
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Create Group
                    </Link>
                    <button
                        v-if="selectedGroups.length > 0"
                        @click="bulkDelete"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                    >
                        <TrashIcon class="w-4 h-4 mr-2" />
                        Delete Selected ({{ selectedGroups.length }})
                    </button>
                    <button
                        @click="exportCSV"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                    >
                        <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                        Export CSV
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search -->
                <div class="bg-white shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <MagnifyingGlassIcon class="w-4 h-4 inline mr-1" />
                            Search
                        </label>
                        <input
                            v-model="search"
                            @input="filterGroups"
                            type="text"
                            placeholder="Search by name or code..."
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                </div>

                <!-- Groups Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAll"
                                            :checked="selectedGroups.length === groups.data.length && groups.data.length > 0"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Code
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Members
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Submissions
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="group in groups.data" :key="group.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <input
                                            v-model="selectedGroups"
                                            :value="group.id"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link
                                            :href="route('admin.groups.show', group.id)"
                                            class="text-sm font-medium text-gray-900 hover:text-indigo-600"
                                        >
                                            {{ group.name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ group.code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <UsersIcon class="w-4 h-4 mr-1 text-gray-400" />
                                            {{ group.users_count }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ group.submissions_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(group.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link
                                            :href="route('admin.groups.show', group.id)"
                                            class="text-indigo-600 hover:text-indigo-900 mr-3"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.groups.edit', group.id)"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Edit
                                        </Link>
                                        <Link
                                            :href="route('admin.groups.members', group.id)"
                                            class="text-gray-600 hover:text-gray-900 mr-3"
                                        >
                                            Members
                                        </Link>
                                        <button
                                            @click="deleteGroup(group)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="groups.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        No groups found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="groups.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                <component
                                    v-for="(link, index) in groups.links"
                                    :key="index"
                                    :is="link.url ? Link : 'span'"
                                    :href="link.url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium"
                                    :class="{
                                        'bg-indigo-50 border-indigo-500 text-indigo-600': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-50': !link.active && link.url,
                                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url,
                                        'rounded-l-md': index === 0,
                                        'rounded-r-md': index === groups.links.length - 1,
                                    }"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
