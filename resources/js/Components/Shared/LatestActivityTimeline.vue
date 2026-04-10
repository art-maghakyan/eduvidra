<script setup>
import FlowbiteCard from '@/Components/Shared/FlowbiteCard.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Latest Activity' },
    viewAllHref: { type: String, default: '#' },
    items: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <FlowbiteCard>
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ title }}
            </h3>
            <Link
                :href="viewAllHref"
                class="inline-flex items-center rounded-lg p-2 text-sm font-medium text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700"
            >
                View all
            </Link>
        </div>

        <ol class="relative border-l border-gray-200 dark:border-gray-700">
            <li v-for="(i, idx) in items" :key="idx" class="mb-6 ml-4 last:mb-0">
                <div
                    class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full"
                    :class="i.dotClass ?? 'bg-primary-600'"
                />
                <time class="mb-1 text-sm font-normal leading-none text-gray-400 dark:text-gray-500">
                    {{ i.time }}
                </time>
                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ i.title }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ i.body }}
                </p>
            </li>
        </ol>
    </FlowbiteCard>
</template>

