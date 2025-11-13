<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilIcon, TrashIcon, PlayIcon, PlusIcon } from '@heroicons/vue/24/outline';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    game: Object,
});

const deleteGame = () => {
    if (confirm('Are you sure you want to delete this game? This action cannot be undone.')) {
        router.delete(route('games.destroy', props.game.id));
    }
};

const canEdit = () => {
    const user = window.Laravel.user;
    return user.role === 'admin' || user.id === props.game.created_by;
};
</script>

<template>
    <Head :title="game.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ game.name }}</h2>
                <div class="flex gap-2">
                    <Link
                        v-if="game.status === 'active'"
                        :href="route('games.play', game.id)"
                    >
                        <PrimaryButton>
                            <PlayIcon class="w-5 h-5 mr-2" />
                            Play Game
                        </PrimaryButton>
                    </Link>
                    <Link
                        v-if="canEdit()"
                        :href="route('games.edit', game.id)"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        <PencilIcon class="w-4 h-4 mr-2" />
                        Edit
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Game Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Game Information</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ game.description || 'No description' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Event Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ game.event_type }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Event Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ new Date(game.event_date).toLocaleString() }}
                                        </dd>
                                    </div>
                                    <div v-if="game.lock_date">
                                        <dt class="text-sm font-medium text-gray-500">Lock Date</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ new Date(game.lock_date).toLocaleString() }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-gray-100 text-gray-800': game.status === 'draft',
                                                    'bg-green-100 text-green-800': game.status === 'active',
                                                    'bg-yellow-100 text-yellow-800': game.status === 'locked',
                                                    'bg-blue-100 text-blue-800': game.status === 'completed',
                                                }"
                                            >
                                                {{ game.status }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Total Questions</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ game.questions.length }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Total Submissions</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ game.submissions_count || 0 }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created By</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ game.creator.name }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Questions</h3>
                            <Link
                                v-if="canEdit()"
                                :href="route('games.questions.create', game.id)"
                            >
                                <PrimaryButton>
                                    <PlusIcon class="w-4 h-4 mr-2" />
                                    Add Question
                                </PrimaryButton>
                            </Link>
                        </div>

                        <div v-if="game.questions.length === 0" class="text-center py-8 text-gray-500">
                            No questions added yet.
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(question, index) in game.questions"
                                :key="question.id"
                                class="border rounded-lg p-4 hover:shadow-md transition"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-700">Q{{ index + 1 }}.</span>
                                            <span class="text-gray-900">{{ question.question_text }}</span>
                                        </div>
                                        <div class="mt-2 flex items-center gap-4 text-sm text-gray-500">
                                            <span class="capitalize">{{ question.question_type.replace('_', ' ') }}</span>
                                            <span>{{ question.points }} points</span>
                                            <span v-if="question.group_question_answers.length > 0" class="text-green-600">
                                                ✓ Has correct answer
                                            </span>
                                        </div>
                                    </div>
                                    <Link
                                        v-if="canEdit()"
                                        :href="route('games.questions.edit', [game.id, question.id])"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        Edit
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-if="canEdit() && game.questions.length > 0" class="mt-4 text-center">
                            <Link
                                :href="route('games.questions.index', game.id)"
                                class="text-indigo-600 hover:text-indigo-900"
                            >
                                Manage All Questions →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="canEdit()" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Danger Zone</h3>
                        <div class="flex items-center justify-between p-4 border border-red-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Delete this game</h4>
                                <p class="text-sm text-gray-500">Once deleted, this game and all its data will be permanently removed.</p>
                            </div>
                            <DangerButton @click="deleteGame">
                                <TrashIcon class="w-4 h-4 mr-2" />
                                Delete Game
                            </DangerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
