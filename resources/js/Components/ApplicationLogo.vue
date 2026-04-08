<script setup>

import logoUrl from '@img/logo.png';

defineProps({
    className: {
        type: String,
        default: 'w-auto h-32'
    }
});
</script>

<template>
    <img 
        :src="logoUrl" 
        :class="className" 
        alt="Eduvidra Logo" 
        class="object-contain"
    />
</template>