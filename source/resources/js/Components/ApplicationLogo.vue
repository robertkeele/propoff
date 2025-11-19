<script setup>
defineProps({
    size: {
        type: String,
        default: 'md', // xs, sm, md, lg, xl
    },
});

const sizeClasses = {
    xs: 'h-6 w-auto',
    sm: 'h-10 w-auto',
    md: 'h-16 w-auto',
    lg: 'h-24 w-auto',
    xl: 'h-32 w-auto',
};
</script>

<template>
    <img
        src="/images/ActionBurst.png"
        alt="PropOff Logo"
        :class="sizeClasses[size]"
        class="object-contain"
    />
</template>
