<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputError from '@/Components/InputError.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            showPassword.value = false;
            showPasswordConfirmation.value = false;
        },
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Sign up" />

        <!-- Centered form column -->
        <div class="flex min-h-screen items-center justify-center px-4 py-28">
            <div class="w-full max-w-[525px]">
                <div class="mb-8 text-center">
                    <h1
                        class="mb-3 text-3xl font-bold text-dark dark:text-white sm:text-4xl md:text-[40px] md:leading-[1.2]"
                    >
                        Sign up
                    </h1>
                    <p class="text-base text-body-color dark:text-dark-6">
                        Create your free account and start learning today.
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

                            <form @submit.prevent="submit" novalidate>
                                <div class="mb-[22px] text-left">
                                    <input
                                        id="name"
                                        type="text"
                                        placeholder="Name"
                                        class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                        v-model="form.name"
                                        required
                                        autofocus
                                        autocomplete="name"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <div class="mb-[22px] text-left">
                                    <input
                                        id="email"
                                        type="email"
                                        placeholder="Email"
                                        class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                        v-model="form.email"
                                        required
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
                                            autocomplete="new-password"
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
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
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
                                                <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 8 10 8a18.34 18.34 0 0 1-3.16 4.56" />
                                                <path d="M6.61 6.61A18.5 18.5 0 0 0 2 12s3.5 8 10 8a10.9 10.9 0 0 0 5.39-1.39" />
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div class="mb-[22px] text-left">
                                    <div class="relative">
                                        <input
                                            id="password_confirmation"
                                            :type="showPasswordConfirmation ? 'text' : 'password'"
                                            placeholder="Confirm password"
                                            class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 pr-12 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                            v-model="form.password_confirmation"
                                            required
                                            autocomplete="new-password"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-4 text-dark/60 transition hover:text-dark focus:outline-none dark:text-white/60 dark:hover:text-white"
                                            :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'"
                                            @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        >
                                            <svg
                                                v-if="!showPasswordConfirmation"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="h-5 w-5"
                                            >
                                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
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
                                                <path d="M10.58 10.58A2 2 0 0 0 12 14a2 2 0 0 0 1.42-.58" />
                                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 8 10 8a18.34 18.34 0 0 1-3.16 4.56" />
                                                <path d="M6.61 6.61A18.5 18.5 0 0 0 2 12s3.5 8 10 8a10.9 10.9 0 0 0 5.39-1.39" />
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.password_confirmation"
                                    />
                                </div>

                                <div class="mb-6">
                                    <button
                                        type="submit"
                                        class="w-full cursor-pointer rounded-md border border-primary bg-primary px-5 py-3 text-base text-white transition duration-300 ease-in-out hover:bg-blue-dark disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Sign up
                                    </button>
                                </div>
                            </form>

                        <p class="text-base text-body-secondary">
                            Already have an account?
                            <Link :href="route('login')" class="text-primary hover:underline">
                                Sign in
                            </Link>
                        </p>
                    </div>
                </div>
            </div>
    </AuthLayout>
</template>
