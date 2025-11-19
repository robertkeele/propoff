<template>
    <Head title="Invitation Expired" />

    <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <XCircleIcon class="w-10 h-10 text-red-600" />
            </div>
            
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                Invitation Expired
            </h1>
            
            <p class="text-gray-600 mb-6">
                {{ message || 'This invitation is no longer valid or has expired.' }}
            </p>
            
            <div class="bg-gray-50 rounded-lg p-4 text-left text-sm text-gray-700">
                <p class="font-medium mb-2">This could be because:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>The invitation has expired</li>
                    <li>The invitation has been deactivated</li>
                    <li>Maximum uses have been reached</li>
                </ul>
            </div>

            <p class="mt-6 text-sm text-gray-600">
                Please contact the game administrator for a new invitation link.
            </p>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { XCircleIcon } from '@heroicons/vue/24/outline';

defineProps({
    message: String,
});
</script>