<template>
    <Head title="Submission Complete" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Submission Complete
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <CheckCircleIcon class="w-10 h-10 text-green-600" />
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">
                            Submission Complete!
                        </h3>
                        <p class="text-gray-600 mb-4">
                            Your answers for <strong>{{ game.name }}</strong> have been submitted successfully.
                        </p>
                        
                        <!-- Score Display -->
                        <div class="inline-flex items-center gap-4 bg-blue-50 rounded-lg px-6 py-4">
                            <div>
                                <div class="text-4xl font-bold text-blue-600">
                                    {{ submission.percentage }}%
                                </div>
                                <div class="text-sm text-gray-600">
                                    {{ submission.total_score }} / {{ submission.possible_points }} points
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Link (for guests only) -->
                <div v-if="personalLink" class="bg-yellow-50 border-2 border-yellow-400 rounded-lg shadow-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-start gap-3">
                            <ExclamationTriangleIcon class="w-8 h-8 text-yellow-600 flex-shrink-0" />
                            <div class="flex-1">
                                <h4 class="text-lg font-bold text-yellow-900 mb-2">
                                    ⭐ SAVE THIS LINK! ⭐
                                </h4>
                                <p class="text-yellow-900 mb-4">
                                    This is your personal results link. Save it now to view your results anytime without logging in!
                                </p>
                                
                                <div class="bg-white border-2 border-yellow-400 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <input
                                            :value="personalLink"
                                            readonly
                                            class="flex-1 text-sm bg-gray-50 border border-gray-300 rounded px-3 py-2 font-mono"
                                        />
                                        <button
                                            @click="copyPersonalLink"
                                            class="px-4 py-2 bg-yellow-600 text-white font-semibold rounded hover:bg-yellow-700 flex items-center gap-2"
                                        >
                                            <ClipboardDocumentIcon class="w-5 h-5" />
                                            {{ linkCopied ? 'Copied!' : 'Copy Link' }}
                                        </button>
                                    </div>
                                    
                                    <div class="text-xs text-gray-600 space-y-1">
                                        <p>📧 Email this link to yourself</p>
                                        <p>📋 Save it in your notes</p>
                                        <p>🔖 Bookmark this page</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="font-semibold text-gray-900 mb-4">What's Next?</h4>
                        
                        <div class="space-y-3">
                            <Link
                                v-if="personalLink"
                                :href="personalLink"
                                class="block w-full px-4 py-3 bg-blue-600 text-white text-center rounded-lg hover:bg-blue-700 font-semibold"
                            >
                                View My Results
                            </Link>
                            
                            <Link
                                :href="route('leaderboards.group', [submission.game_id, group.id])"
                                class="block w-full px-4 py-3 bg-purple-600 text-white text-center rounded-lg hover:bg-purple-700 font-semibold"
                            >
                                View {{ group.name }} Leaderboard
                            </Link>
                            
                            <Link
                                :href="route('dashboard')"
                                class="block w-full px-4 py-3 bg-gray-600 text-white text-center rounded-lg hover:bg-gray-700 font-semibold"
                            >
                                Back to Dashboard
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Reminder -->
                <div v-if="personalLink" class="mt-6 text-center text-sm text-gray-600">
                    <p>💡 Don't forget to save your personal results link above!</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    CheckCircleIcon, 
    ExclamationTriangleIcon,
    ClipboardDocumentIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
    submission: Object,
    game: Object,
    group: Object,
    personalLink: String,
});

const linkCopied = ref(false);

const copyPersonalLink = () => {
    navigator.clipboard.writeText(props.personalLink);
    linkCopied.value = true;
    setTimeout(() => {
        linkCopied.value = false;
    }, 3000);
};
</script>
