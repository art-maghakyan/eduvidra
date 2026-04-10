<script setup>
defineProps({
    padded: { type: Boolean, default: true },
    border: { type: Boolean, default: true },
    shadow: { type: Boolean, default: true },
});
</script>

<template>
    <div
        class="rounded-lg bg-white dark:bg-gray-800"
        :class="{
            'p-4 sm:p-6': padded,
            'border border-gray-200 dark:border-gray-700': border,
            'shadow-sm': shadow,
        }"
    >
        <slot />
    </div>
</template>

