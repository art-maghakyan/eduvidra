<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { RbacPermission } from '@/constants/rbac';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const can = (permission) =>
    !!permission && (page.props.auth?.user?.permissions ?? []).includes(permission);

const profileEditRouteName = computed(() =>
    can(RbacPermission.ADMIN_ACCESS) ? 'admin.profile.edit' : 'profile.edit',
);

const brandName = 'Eduvidra';

const isSidebarOpenMobile = ref(false);
const closeSidebar = () => (isSidebarOpenMobile.value = false);
const toggleSidebar = () => (isSidebarOpenMobile.value = !isSidebarOpenMobile.value);

const isAdmin = computed(() => can(RbacPermission.ADMIN_ACCESS));

const isActive = (pattern) => {
    try {
        return route().current(pattern);
    } catch {
        return false;
    }
};

const sidebarGroupsOpen = ref({
    account: true,
    admin: true,
});

const toggleGroup = (key) => {
    sidebarGroupsOpen.value[key] = !sidebarGroupsOpen.value[key];
};

const isDark = ref(false);

const applyTheme = (dark) => {
    document.documentElement.classList.toggle('dark', dark);
    localStorage.setItem('theme', dark ? 'dark' : 'light');
};

onMounted(() => {
    const userTheme = localStorage.getItem('theme');
    const systemDark =
        window.matchMedia?.('(prefers-color-scheme: dark)')?.matches ?? false;
    isDark.value = userTheme ? userTheme === 'dark' : systemDark;
    applyTheme(isDark.value);
});

watch(isDark, (dark) => applyTheme(dark));
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Topbar -->
        <nav
            class="fixed z-30 w-full border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start">
                        <button
                            type="button"
                            @click="toggleSidebar"
                            class="inline-flex items-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-100 lg:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                        >
                            <span class="sr-only">Open sidebar</span>
                            <svg
                                class="h-6 w-6"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>

                        <Link
                            :href="can(RbacPermission.ADMIN_ACCESS) ? route('admin.dashboard') : route('dashboard')"
                            class="ml-2 flex items-center md:mr-24"
                        >
                            <span
                                class="self-center whitespace-nowrap text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white"
                            >
                                {{ brandName }}
                            </span>
                        </Link>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Theme toggle -->
                        <button
                            type="button"
                            @click="isDark = !isDark"
                            class="rounded-lg p-2.5 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                        >
                            <span class="sr-only">Toggle theme</span>
                            <svg
                                v-if="!isDark"
                                class="h-5 w-5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="h-5 w-5"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zM10 15a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>

                        <!-- Profile -->
                        <div class="relative">
                            <Link
                                :href="route(profileEditRouteName)"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                {{ user?.name }}
                            </Link>
                        </div>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex bg-gray-50 pt-16 dark:bg-gray-900">
            <!-- Sidebar -->
            <aside
                class="fixed left-0 top-0 z-20 hidden h-full w-64 flex-shrink-0 flex-col pt-16 font-normal transition-width duration-75 lg:flex"
                :class="{ '!flex': isSidebarOpenMobile }"
                aria-label="Sidebar"
            >
                <div
                    class="relative flex min-h-0 flex-1 flex-col border-r border-gray-200 bg-white pt-0 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="flex flex-1 flex-col overflow-y-auto pb-4 pt-5">
                        <div class="flex-1 space-y-1 divide-y divide-gray-200 bg-white px-3 dark:divide-gray-700 dark:bg-gray-800">
                            <ul class="space-y-2 pb-2">
                                <li class="px-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Main
                                </li>
                                <li>
                                    <Link
                                        :href="isAdmin ? route('admin.dashboard') : route('dashboard')"
                                        class="group flex items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                        :class="{
                                            'bg-gray-100 dark:bg-gray-700': isActive(isAdmin ? 'admin.dashboard' : 'dashboard'),
                                        }"
                                    >
                                        <svg
                                            class="h-5 w-5 text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                                            <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
                                        </svg>
                                        <span class="ml-3">Dashboard</span>
                                    </Link>
                                </li>

                                <li class="pt-2">
                                    <button
                                        type="button"
                                        @click="toggleGroup('account')"
                                        class="group flex w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        <svg
                                            class="h-5 w-5 text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 2a4 4 0 100 8 4 4 0 000-8zM2 16a6 6 0 1112 0v1H2v-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <span class="ml-3 flex-1 text-left">Account</span>
                                        <svg
                                            class="h-5 w-5 text-gray-500 dark:text-gray-400"
                                            :class="{ 'rotate-180': sidebarGroupsOpen.account }"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                    <ul v-if="sidebarGroupsOpen.account" class="mt-1 space-y-1 pl-11">
                                        <li>
                                            <Link
                                                :href="route(profileEditRouteName)"
                                                class="block rounded-lg p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                                :class="{
                                                    'bg-gray-100 dark:bg-gray-700': isActive('*.profile.*'),
                                                }"
                                            >
                                                Profile
                                            </Link>
                                        </li>
                                    </ul>
                                </li>

                                <li v-if="isAdmin" class="pt-2">
                                    <button
                                        type="button"
                                        @click="toggleGroup('admin')"
                                        class="group flex w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        <svg
                                            class="h-5 w-5 text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <span class="ml-3 flex-1 text-left">Admin</span>
                                        <svg
                                            class="h-5 w-5 text-gray-500 dark:text-gray-400"
                                            :class="{ 'rotate-180': sidebarGroupsOpen.admin }"
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                    <ul v-if="sidebarGroupsOpen.admin" class="mt-1 space-y-1 pl-11">
                                        <li>
                                            <Link
                                                :href="route('admin.dashboard')"
                                                class="block rounded-lg p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                                :class="{
                                                    'bg-gray-100 dark:bg-gray-700': isActive('admin.dashboard'),
                                                }"
                                            >
                                                Dashboard
                                            </Link>
                                        </li>
                                        <li v-if="can(RbacPermission.USERS_MANAGE)">
                                            <Link
                                                :href="route('admin.users.index')"
                                                class="block rounded-lg p-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                                :class="{
                                                    'bg-gray-100 dark:bg-gray-700': isActive('admin.users.*'),
                                                }"
                                            >
                                                Users
                                            </Link>
                                        </li>
                                        <li v-else>
                                            <span class="block rounded-lg p-2 text-sm font-medium text-gray-400 dark:text-gray-500">
                                                Users
                                            </span>
                                        </li>

                                        <li>
                                            <span class="block rounded-lg p-2 text-sm font-medium text-gray-400 dark:text-gray-500">
                                                Reports (soon)
                                            </span>
                                        </li>
                                        <li>
                                            <span class="block rounded-lg p-2 text-sm font-medium text-gray-400 dark:text-gray-500">
                                                Settings (soon)
                                            </span>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Mobile backdrop -->
                <div
                    v-if="isSidebarOpenMobile"
                    class="fixed inset-0 z-10 bg-gray-900/50 lg:hidden"
                    @click="closeSidebar"
                />
            </aside>

            <!-- Content -->
            <div
                id="main-content"
                class="relative h-full w-full overflow-y-auto bg-gray-50 lg:ml-64 dark:bg-gray-900"
            >
                <header v-if="$slots.header" class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                    <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
