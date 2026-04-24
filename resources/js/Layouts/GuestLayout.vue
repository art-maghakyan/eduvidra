<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppVersion from '@/Components/AppVersion.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot />
        </div>

        <div class="pointer-events-none fixed bottom-2 right-3 z-50 text-gray-700">
            <AppVersion />
        </div>
    </div>
</template>
