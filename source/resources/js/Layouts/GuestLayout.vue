<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-propoff-blue via-propoff-dark-green to-propoff-red">
        <div>
            <Link href="/" class="flex flex-col items-center">
                <ApplicationLogo size="xl" />
            </Link>
        </div>

        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/95 backdrop-blur-sm shadow-2xl overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
