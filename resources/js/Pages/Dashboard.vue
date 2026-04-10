<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StudentDashboardWidgets from '@/Components/Student/Dashboard/DashboardWidgets.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats cards (JS-free) -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Account
                        </div>
                        <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">
                            {{ page.props.auth.user.name }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Roles
                        </div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ (page.props.auth.user.roles ?? []).length }}
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="role in (page.props.auth.user.roles ?? [])"
                                :key="role"
                                class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-900/20 dark:text-indigo-200 dark:ring-indigo-800"
                            >
                                {{ role }}
                            </span>
                            <span
                                v-if="(page.props.auth.user.roles ?? []).length === 0"
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                No roles assigned
                            </span>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Permissions
                        </div>
                        <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ (page.props.auth.user.permissions ?? []).length }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Granted permissions for your account.
                        </div>
                    </div>
                </div>

                <!-- Announcements / alerts -->
                <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-900 dark:border-blue-900/50 dark:bg-blue-900/20 dark:text-blue-100">
                    <div class="flex items-start gap-3">
                        <svg
                            class="mt-0.5 h-5 w-5 flex-none"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 100 2 1 1 0 000-2zm-1 4a1 1 0 012 0v4a1 1 0 11-2 0v-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold">
                                Welcome to Eduvidra
                            </div>
                            <div class="mt-1 text-sm text-blue-800 dark:text-blue-200">
                                This dashboard is populated with static UI blocks for now. Once we connect real student data, these widgets will become live.
                            </div>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                                Beta
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Two-column panels -->
                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Your permissions
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                RBAC
                            </span>
                        </div>
                        <div class="mt-4">
                            <div
                                v-if="(page.props.auth.user.permissions ?? []).length === 0"
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                No permissions assigned.
                            </div>
                            <ul
                                v-else
                                class="grid grid-cols-1 gap-2 sm:grid-cols-2"
                            >
                                <li
                                    v-for="perm in page.props.auth.user.permissions"
                                    :key="perm"
                                    class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200"
                                >
                                    {{ perm }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Quick actions
                        </h3>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <Link
                                :href="route('profile.edit')"
                                class="inline-flex items-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900"
                            >
                                Edit profile
                            </Link>
                            <Link
                                href="/"
                                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                            >
                                Back to home
                            </Link>
                        </div>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            These are UI placeholders; we’ll replace with real student features later.
                        </p>
                    </div>
                </div>

                <!-- “Looks complete” widget grid -->
                <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <!-- Chart placeholder -->
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800 xl:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Progress (placeholder)
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Last 30 days
                            </span>
                        </div>
                        <div class="mt-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="h-40 w-full rounded-md bg-gray-100 dark:bg-gray-700/60" />
                            <div class="mt-4 grid grid-cols-3 gap-3">
                                <div class="h-3 rounded bg-gray-100 dark:bg-gray-700/60" />
                                <div class="h-3 rounded bg-gray-100 dark:bg-gray-700/60" />
                                <div class="h-3 rounded bg-gray-100 dark:bg-gray-700/60" />
                            </div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Chart will be replaced with real learning analytics.
                            </div>
                        </div>
                    </div>

                    <!-- Activity feed -->
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Recent activity
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Static
                            </span>
                        </div>
                        <ol class="mt-4 space-y-4">
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full bg-emerald-500" />
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        Signed in
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Just now
                                    </div>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full bg-blue-500" />
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        Viewed dashboard
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Today
                                    </div>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full bg-gray-400" />
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        Completed a lesson
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Coming soon
                                    </div>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Table -->
                <div class="mt-6 rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Overview
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Simple, JS-free table component.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Item</th>
                                    <th scope="col" class="px-6 py-3">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        User ID
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ page.props.auth.user.id }}
                                    </td>
                                </tr>
                                <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Roles count
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ (page.props.auth.user.roles ?? []).length }}
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Permissions count
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ (page.props.auth.user.permissions ?? []).length }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Placeholder table: “Courses” -->
                <div class="mt-6 rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Courses
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Static rows to make the layout feel complete.
                                </p>
                            </div>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                Coming soon
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Course</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Intro to Eduvidra
                                    </th>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            Active
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="h-2 w-32 rounded bg-gray-200 dark:bg-gray-700">
                                            <div class="h-2 w-2/5 rounded bg-primary-600" />
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Basic Safety
                                    </th>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Not started
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="h-2 w-32 rounded bg-gray-200 dark:bg-gray-700">
                                            <div class="h-2 w-0 rounded bg-primary-600" />
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Final quiz
                                    </th>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
                                            Locked
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="h-2 w-32 rounded bg-gray-200 dark:bg-gray-700">
                                            <div class="h-2 w-1/5 rounded bg-primary-600" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                        <Link
                            href="#"
                            class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-400"
                        >
                            View all courses
                        </Link>
                    </div>
                </div>

                <StudentDashboardWidgets :user="page.props.auth.user" :current-year="currentYear" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
