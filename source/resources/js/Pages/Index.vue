<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="PropOff - Game Prediction Platform" />

    <div
        class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-gradient-to-br from-propoff-blue via-propoff-dark-green to-propoff-red selection:bg-propoff-orange selection:text-white"
    >
        <div v-if="canLogin" class="sm:fixed sm:top-0 sm:right-0 p-6 text-end z-10">
            <Link
                v-if="$page.props.auth.user"
                :href="route('dashboard')"
                class="font-semibold text-white hover:text-propoff-orange focus:outline focus:outline-2 focus:rounded-sm focus:outline-propoff-orange"
                >Dashboard</Link
            >
            <Link
                v-if="$page.props.auth.user"
                :href="route('logout')"
                method="post"
                as="button"
                class="ms-4 font-semibold text-white hover:text-propoff-orange focus:outline focus:outline-2 focus:rounded-sm focus:outline-propoff-orange"
                >Log Out</Link
            >
            <template v-else>
                <Link
                    :href="route('login')"
                    class="font-semibold text-white hover:text-propoff-orange focus:outline focus:outline-2 focus:rounded-sm focus:outline-propoff-orange"
                    >Log in</Link
                >

                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="ms-4 font-semibold text-white hover:text-propoff-orange focus:outline focus:outline-2 focus:rounded-sm focus:outline-propoff-orange"
                    >Register</Link
                >
            </template>
        </div>

        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <!-- Logo and Title -->
            <div class="flex flex-col items-center mb-12">
                <img 
                    src="/images/ActionBurst.png" 
                    alt="PropOff Logo" 
                    class="h-32 w-auto mb-6 drop-shadow-2xl"
                />
                <h1 class="text-6xl font-bold text-white mb-4 text-center drop-shadow-lg">
                    Predict. Compete. <span class="text-propoff-orange">Win.</span>
                </h1>
                <p class="text-2xl text-orange-200 text-center max-w-2xl">
                    Test your prediction skills in the ultimate prop betting challenge. Join groups, answer questions about game events, climb the leaderboard, and prove you're the best.
                </p>
            </div>

            <!-- Feature Cards -->
            <div class="mt-16">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <!-- Create Games Card -->
                    <div class="scale-100 p-6 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 bg-propoff-red/40 flex items-center justify-center rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-white">Prop Questions</h2>
                        </div>
                        <p class="text-gray-200 text-sm leading-relaxed">
                            Answer prediction questions about real game events. Each question is worth different points based on difficulty.
                        </p>
                    </div>

                    <!-- Group Competition Card -->
                    <div class="scale-100 p-6 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 bg-propoff-blue/40 flex items-center justify-center rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-white">Group Competition</h2>
                        </div>
                        <p class="text-gray-200 text-sm leading-relaxed">
                            Compete with friends in private groups. Each group has unique correct answers and rankings.
                        </p>
                    </div>

                    <!-- Real-time Leaderboards Card -->
                    <div class="scale-100 p-6 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 bg-propoff-green/40 flex items-center justify-center rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-white">Live Leaderboards</h2>
                        </div>
                        <p class="text-gray-200 text-sm leading-relaxed">
                            Track your rank in real-time. See how you stack up against the competition.
                        </p>
                    </div>

                    <!-- Guest Access Card -->
                    <div class="scale-100 p-6 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 bg-propoff-orange/40 flex items-center justify-center rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-white">Point System</h2>
                        </div>
                        <p class="text-gray-200 text-sm leading-relaxed">
                            Strategic scoring system where harder predictions earn more points. Every answer counts towards your total.
                        </p>
                    </div>

                    <!-- Smart Grading Card -->
                    <div class="scale-100 p-6 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 bg-propoff-dark-green/40 flex items-center justify-center rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-white">Auto Grading</h2>
                        </div>
                        <p class="text-gray-200 text-sm leading-relaxed">
                            Automatic scoring with group-specific answer keys. Fair and transparent results.
                        </p>
                    </div>

                    <!-- Export Results Card -->
                    <div class="scale-100 p-6 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="h-12 w-12 bg-propoff-blue/40 flex items-center justify-center rounded-full mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" class="w-6 h-6 stroke-white">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-white">Export Data</h2>
                        </div>
                        <p class="text-gray-200 text-sm leading-relaxed">
                            Download detailed results as CSV. Perfect for record keeping and analysis.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="flex justify-center mt-16">
                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('register')"
                    class="px-8 py-4 bg-gradient-to-r from-propoff-red to-propoff-orange text-white font-bold text-lg rounded-lg shadow-2xl hover:shadow-propoff-orange/50 hover:scale-105 transition-all duration-300"
                >
                    Get Started Free
                </Link>
                <Link
                    v-else
                    :href="route('dashboard')"
                    class="px-8 py-4 bg-gradient-to-r from-propoff-red to-propoff-orange text-white font-bold text-lg rounded-lg shadow-2xl hover:shadow-propoff-orange/50 hover:scale-105 transition-all duration-300"
                >
                    Go to Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
