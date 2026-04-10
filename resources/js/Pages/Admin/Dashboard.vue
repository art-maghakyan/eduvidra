<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AdminDashboardWidgets from '@/Components/Admin/Dashboard/DashboardWidgets.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentYear = new Date().getFullYear();
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Admin Dashboard
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats cards -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Admin
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
                                class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-200 dark:ring-emerald-800"
                            >
                                {{ role }}
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
                            Effective admin permissions.
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Admin tools
                        </h3>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Link
                                :href="route('admin.profile.edit')"
                                class="inline-flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900"
                            >
                                Edit admin profile
                            </Link>
                            <Link
                                :href="route('admin.dashboard')"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                            >
                                Refresh dashboard
                            </Link>
                            <div
                                class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                            >
                                Users management (coming soon)
                            </div>
                            <div
                                class="rounded-lg border border-dashed border-gray-300 p-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                            >
                                Audit log (coming soon)
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Access summary
                        </h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            This is a UI-only summary based on the shared Inertia props.
                        </p>
                        <div class="mt-4">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Permissions list
                            </div>
                            <div class="mt-2">
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
                                        v-for="perm in (page.props.auth.user.permissions ?? [])"
                                        :key="perm"
                                        class="rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-200"
                                    >
                                        {{ perm }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- “Looks complete” widget grid -->
                <div class="mt-6 grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <!-- System health (static) -->
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800 xl:col-span-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                System health (placeholder)
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                OK
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400">API</div>
                                <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">99.9%</div>
                                <div class="mt-2 h-2 rounded bg-gray-200 dark:bg-gray-700">
                                    <div class="h-2 w-11/12 rounded bg-emerald-500" />
                                </div>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Queue</div>
                                <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">Idle</div>
                                <div class="mt-2 h-2 rounded bg-gray-200 dark:bg-gray-700">
                                    <div class="h-2 w-2/3 rounded bg-blue-500" />
                                </div>
                            </div>
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Errors</div>
                                <div class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">0</div>
                                <div class="mt-2 h-2 rounded bg-gray-200 dark:bg-gray-700">
                                    <div class="h-2 w-1/12 rounded bg-gray-400" />
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            These are static UI widgets; we can later wire them to real metrics.
                        </p>
                    </div>

                    <!-- Admin activity (static) -->
                    <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Admin activity
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Static</span>
                        </div>
                        <ol class="mt-4 space-y-4">
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full bg-emerald-500" />
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        Access granted: admin dashboard
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Today
                                    </div>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 flex-none rounded-full bg-blue-500" />
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        Reviewed permissions list
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
                                        Users management
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
                            Admin overview
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            JS-free table layout (Flowbite style).
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Key</th>
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
                                        Roles
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ (page.props.auth.user.roles ?? []).join(', ') || '—' }}
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

                <!-- Placeholder table: “Recent events” -->
                <div class="mt-6 rounded-lg bg-white shadow dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Recent events
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Static audit-style rows (UI only).
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
                                    <th scope="col" class="px-6 py-3">Event</th>
                                    <th scope="col" class="px-6 py-3">Actor</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Login
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ page.props.auth.user.email }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                            Success
                                        </span>
                                    </td>
                                </tr>
                                <tr class="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Permission check
                                    </th>
                                    <td class="px-6 py-4">
                                        System
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-200">
                                            Allowed
                                        </span>
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800">
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        Users import
                                    </th>
                                    <td class="px-6 py-4">
                                        Admin
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Pending
                                        </span>
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
                            View audit log
                        </Link>
                    </div>
                </div>

                <AdminDashboardWidgets :user="page.props.auth.user" :current-year="currentYear" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>

