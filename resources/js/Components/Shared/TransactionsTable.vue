<script setup>
import FlowbiteCard from '@/Components/Shared/FlowbiteCard.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Transactions' },
    description: { type: String, default: '' },
    rightBadge: { type: String, default: 'Last 7 days' },
    reportHref: { type: String, default: '#' },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});
</script>

<template>
    <FlowbiteCard>
        <div class="items-center justify-between lg:flex">
            <div class="mb-4 lg:mb-0">
                <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
                    {{ title }}
                </h3>
                <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                    {{ description }}
                </span>
            </div>
            <div class="items-center sm:flex">
                <span
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                >
                    {{ rightBadge }}
                </span>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th v-for="(c, idx) in columns" :key="idx" scope="col" class="px-6 py-3">
                            {{ c }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(r, idx) in rows"
                        :key="idx"
                        class="border-b bg-white dark:border-gray-700 dark:bg-gray-800"
                    >
                        <th
                            scope="row"
                            class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white"
                        >
                            {{ r.primary }}
                        </th>
                        <td v-for="(cell, cidx) in r.cells" :key="cidx" class="px-6 py-4">
                            <template v-if="typeof cell === 'object' && cell !== null && 'badge' in cell">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="cell.badgeClass"
                                >
                                    {{ cell.badge }}
                                </span>
                            </template>
                            <template v-else>
                                {{ cell }}
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between pt-3 sm:pt-6">
            <div />
            <div class="flex-shrink-0">
                <Link
                    :href="reportHref"
                    class="inline-flex items-center rounded-lg p-2 text-xs font-medium uppercase text-primary-700 hover:bg-gray-100 dark:text-primary-500 dark:hover:bg-gray-700 sm:text-sm"
                >
                    Transactions Report
                    <svg class="ml-1 h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </Link>
            </div>
        </div>
    </FlowbiteCard>
</template>

