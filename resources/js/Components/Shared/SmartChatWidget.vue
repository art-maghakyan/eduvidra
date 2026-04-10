<script setup>
import FlowbiteCard from '@/Components/Shared/FlowbiteCard.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Smart chat' },
    viewAllHref: { type: String, default: '#' },
    messages: {
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

        <div class="space-y-4">
            <div
                v-for="(m, idx) in messages"
                :key="idx"
                class="flex items-start gap-3"
                :class="m.align === 'right' ? 'justify-end' : ''"
            >
                <div
                    v-if="m.align !== 'right'"
                    class="h-8 w-8 flex-none rounded-full"
                    :class="m.avatarClass ?? 'bg-gray-200 dark:bg-gray-700'"
                />
                <div
                    class="min-w-0 rounded-lg p-3 text-sm"
                    :class="
                        m.align === 'right'
                            ? 'bg-primary-700 text-white'
                            : 'bg-gray-50 text-gray-700 dark:bg-gray-700/60 dark:text-gray-200'
                    "
                >
                    {{ m.text }}
                </div>
                <div
                    v-if="m.align === 'right'"
                    class="h-8 w-8 flex-none rounded-full"
                    :class="m.avatarClass ?? 'bg-primary-200 dark:bg-primary-900/30'"
                />
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <input
                type="text"
                disabled
                value="Type a message…"
                class="block w-full rounded-lg border border-gray-200 bg-gray-50 p-2.5 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400"
            />
            <button
                type="button"
                disabled
                class="inline-flex items-center rounded-lg bg-primary-700 px-4 py-2.5 text-sm font-medium text-white opacity-60"
            >
                Send
            </button>
        </div>
    </FlowbiteCard>
</template>

