<script setup>
import { computed } from 'vue';

const version = computed(() => import.meta.env.VITE_APP_VERSION || 'dev');
</script>

<template>
    <span class="pointer-events-none select-none text-xs opacity-50">
        v-{{ version }}
    </span>
</template>
