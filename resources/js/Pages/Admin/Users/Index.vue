<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    students: {
        type: Array,
        default: () => [],
    },
});

const requiredForms = new Map();

const getRequiredForm = (student) => {
    if (!requiredForms.has(student.id)) {
        requiredForms.set(
            student.id,
            useForm({
                required: !!student.twoFactorRequired,
            }),
        );
    }

    return requiredForms.get(student.id);
};

const resetForm = useForm({});
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Users
                </h2>
                <span
                    class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200"
                >
                    Students
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Students list
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Users with the <span class="font-medium">student</span> role.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <Link
                                href="#"
                                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                            >
                                Export (soon)
                            </Link>
                            <Link
                                href="#"
                                class="inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:focus:ring-primary-900"
                            >
                                Invite (soon)
                            </Link>
                        </div>
                    </div>

                    <div
                        v-if="students.length === 0"
                        class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
                    >
                        No students found yet.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Email</th>
                                    <th scope="col" class="px-6 py-3">Created</th>
                                    <th scope="col" class="px-6 py-3">2FA</th>
                                    <th scope="col" class="px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="s in students"
                                    :key="s.id"
                                    class="border-b bg-white dark:border-gray-700 dark:bg-gray-800"
                                >
                                    <th scope="row" class="whitespace-nowrap px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        {{ s.name }}
                                    </th>
                                    <td class="px-6 py-4">{{ s.email }}</td>
                                    <td class="px-6 py-4">{{ s.createdAt ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                class="inline-flex w-fit items-center rounded-md px-2 py-1 text-xs font-medium"
                                                :class="
                                                    s.twoFactorRequired
                                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200'
                                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'
                                                "
                                            >
                                                {{ s.twoFactorRequired ? 'Required' : 'Not required' }}
                                            </span>
                                            <span
                                                class="inline-flex w-fit items-center rounded-md px-2 py-1 text-xs font-medium"
                                                :class="
                                                    s.twoFactorEnrolled
                                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200'
                                                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200'
                                                "
                                            >
                                                {{ s.twoFactorEnrolled ? 'Enrolled' : 'Not enrolled' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <form
                                                @submit.prevent="
                                                    getRequiredForm(s).post(
                                                        route('admin.users.two-factor.required', s.id),
                                                        { preserveScroll: true },
                                                    )
                                                "
                                            >
                                                <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-primary-700 focus:ring-primary-600"
                                                        v-model="getRequiredForm(s).required"
                                                    />
                                                    Require
                                                </label>
                                            </form>

                                            <form
                                                @submit.prevent="
                                                    resetForm.post(route('admin.users.two-factor.reset', s.id), {
                                                        preserveScroll: true,
                                                    })
                                                "
                                            >
                                                <button
                                                    type="submit"
                                                    class="text-xs font-medium text-red-700 hover:underline dark:text-red-400"
                                                >
                                                    Reset 2FA
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Next: add search, pagination, and per-user actions (disable/reset password/role changes).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

