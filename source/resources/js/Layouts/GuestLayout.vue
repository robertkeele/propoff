<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-propoff-blue via-propoff-dark-green to-propoff-red">
        <div>
            <Link href="/" class="flex flex-col items-center">
                <img 
                    src="/images/ActionBurst.png" 
                    alt="PropOff Logo" 
                    class="h-24 w-auto mb-2 drop-shadow-2xl"
                />
                <h1 class="text-3xl font-bold text-white drop-shadow-lg">PropOff</h1>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white/95 backdrop-blur-sm shadow-2xl overflow-hidden sm:rounded-lg"
        >
            <slot />
        </div>
    </div>
</template>
