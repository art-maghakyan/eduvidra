<script setup>
import FlowbiteCard from '@/Components/Shared/FlowbiteCard.vue';
import FlowbiteFooter from '@/Components/Shared/FlowbiteFooter.vue';
import LatestActivityTimeline from '@/Components/Shared/LatestActivityTimeline.vue';
import SmartChatWidget from '@/Components/Shared/SmartChatWidget.vue';
import TransactionsTable from '@/Components/Shared/TransactionsTable.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    user: { type: Object, required: true },
    currentYear: { type: Number, required: true },
});

const activityItems = [
    { time: 'Today', title: 'Signed in', body: 'Accessed your dashboard.', dotClass: 'bg-primary-600' },
    { time: 'Yesterday', title: 'Started “Intro to Eduvidra”', body: 'Progress saved automatically.', dotClass: 'bg-emerald-500' },
    { time: 'Coming soon', title: 'Certificates', body: 'Earn certificates after completing courses.', dotClass: 'bg-gray-400' },
];

const chatMessages = [
    { align: 'left', text: 'Hi! What would you like to learn today?' },
    { align: 'right', text: 'Show me my next steps.' },
    { align: 'left', text: 'Coming soon: chat will suggest lessons, quizzes, and reminders based on progress.' },
];

const transactionColumns = ['Transaction', 'Date', 'Amount', 'Status'];
const transactionRows = [
    {
        primary: 'Course enrollment',
        cells: [
            '2026-04-09',
            '$19.00',
            { badge: 'Paid', badgeClass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' },
        ],
    },
    {
        primary: 'Subscription renewal',
        cells: [
            '2026-04-08',
            '$9.00',
            { badge: 'Processing', badgeClass: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200' },
        ],
    },
    {
        primary: 'Invoice',
        cells: [
            '2026-04-07',
            '$0.00',
            { badge: 'Draft', badgeClass: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
        ],
    },
];
</script>

<template>
    <div class="mt-6 grid gap-4 xl:grid-cols-2 2xl:grid-cols-3">
        <!-- Statistics this month -->
        <FlowbiteCard>
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Statistics this month
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Static
                </span>
            </div>
            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                <li class="py-3 sm:py-4">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900 dark:text-white">
                                Lessons completed
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                +12% vs last month
                            </p>
                        </div>
                        <div class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
                            18
                        </div>
                    </div>
                </li>
                <li class="py-3 sm:py-4">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900 dark:text-white">
                                Hours studied
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                +4% vs last month
                            </p>
                        </div>
                        <div class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
                            26h
                        </div>
                    </div>
                </li>
                <li class="py-3 sm:py-4">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-gray-900 dark:text-white">
                                Quiz attempts
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                -2% vs last month
                            </p>
                        </div>
                        <div class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
                            7
                        </div>
                    </div>
                </li>
            </ul>
            <div class="mt-4">
                <Link
                    href="#"
                    class="inline-flex items-center text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"
                >
                    View full report
                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </Link>
            </div>
        </FlowbiteCard>

        <!-- Insights -->
        <FlowbiteCard>
            <div class="flex items-center gap-3">
                <div class="rounded-lg bg-primary-50 p-2 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        Insights
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Personalized recommendations (coming soon)
                    </div>
                </div>
            </div>
            <h3 class="mt-4 text-2xl font-medium text-gray-900 dark:text-white">
                You’re on track to improve by 44% next year
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Placeholder copy. Once we have real learning stats, we’ll generate insights from progress.
            </p>
            <div class="mt-4">
                <Link
                    href="#"
                    class="inline-flex items-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900"
                >
                    See recommendations
                </Link>
            </div>
        </FlowbiteCard>

        <LatestActivityTimeline :items="activityItems" />

        <SmartChatWidget
            :title="'Smart chat'"
            :messages="chatMessages.map((m) => ({ ...m, text: m.text.replace('{{name}}', user.name) }))"
        />

        <div class="2xl:col-span-3">
            <TransactionsTable
                title="Transactions"
                description="Dummy “latest transactions” until billing/enrollments are connected."
                :columns="transactionColumns"
                :rows="transactionRows"
            />
        </div>
    </div>

    <FlowbiteFooter :year="currentYear" :brand="'Eduvidra'" />
</template>

