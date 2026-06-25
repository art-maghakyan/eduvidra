<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Sign in" />

        <!-- Centered form column -->
        <div class="flex min-h-screen items-center justify-center px-4 py-28">
            <div class="w-full max-w-[525px]">
                <div class="mb-8 text-center">
                    <h1
                        class="mb-3 text-3xl font-bold text-dark dark:text-white sm:text-4xl md:text-[40px] md:leading-[1.2]"
                    >
                        Sign in
                    </h1>
                    <p class="text-base text-body-color dark:text-dark-6">
                        Welcome back. Sign in to continue learning.
                    </p>
                </div>

                <div
                    class="relative overflow-hidden rounded-xl bg-white px-8 py-12 text-center shadow-form dark:bg-dark-2 sm:px-12 md:px-[60px]"
                >
                    <div class="mb-8 text-center">
                        <Link href="/" class="inline-block">
                            <ApplicationLogo class-name="mx-auto h-16 w-auto" />
                        </Link>
                    </div>

                    <div
                        v-if="status"
                        class="mb-6 rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
                    >
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" novalidate>
                                <div class="mb-[22px] text-left">
                                    <input
                                        id="email"
                                        type="email"
                                        placeholder="Email"
                                        class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                        v-model="form.email"
                                        required
                                        autofocus
                                        autocomplete="username"
                                    />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>

                                <div class="mb-[22px] text-left">
                                    <div class="relative">
                                        <input
                                            id="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            placeholder="Password"
                                            class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 pr-12 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                            v-model="form.password"
                                            required
                                            autocomplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-4 text-dark/60 transition hover:text-dark focus:outline-none dark:text-white/60 dark:hover:text-white"
                                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                            @click="showPassword = !showPassword"
                                        >
                                            <svg
                                                v-if="!showPassword"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="h-5 w-5"
                                            >
                                                <path
                                                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"
                                                />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            <svg
                                                v-else
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="h-5 w-5"
                                            >
                                                <path d="M3 3l18 18" />
                                                <path
                                                    d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58"
                                                />
                                                <path
                                                    d="M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 8 10 8a18.34 18.34 0 0 1-3.16 4.56"
                                                />
                                                <path
                                                    d="M6.61 6.61A18.5 18.5 0 0 0 2 12s3.5 8 10 8a10.9 10.9 0 0 0 5.39-1.39"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div class="mb-6 flex items-center justify-between">
                                    <label class="flex items-center gap-2 text-sm text-body-color dark:text-dark-6">
                                        <Checkbox
                                            name="remember"
                                            v-model:checked="form.remember"
                                        />
                                        Remember me
                                    </label>

                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        Forgot password?
                                    </Link>
                                </div>

                                <div class="mb-6">
                                    <button
                                        type="submit"
                                        class="w-full cursor-pointer rounded-md border border-primary bg-primary px-5 py-3 text-base text-white transition duration-300 ease-in-out hover:bg-blue-dark disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Sign in
                                    </button>
                                </div>
                            </form>

                        <p class="text-base text-body-secondary">
                            Don’t have an account?
                            <Link :href="route('register')" class="text-primary hover:underline">
                                Sign up
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
    </AuthLayout>
</template>
