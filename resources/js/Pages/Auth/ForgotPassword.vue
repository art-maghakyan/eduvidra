<script setup>
import InputError from '@/Components/InputError.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout>
        <Head title="Forgot password" />

        <!-- Banner -->
        <div
            class="relative z-10 overflow-hidden pb-[60px] pt-[120px] md:pt-[130px] lg:pt-[160px] dark:bg-dark"
        >
            <div
                class="absolute bottom-0 left-0 h-px w-full bg-linear-to-r from-stroke/0 via-stroke to-stroke/0 dark:via-dark-3"
            />
            <div class="container mx-auto px-4">
                <div class="-mx-4 flex flex-wrap items-center">
                    <div class="w-full px-4">
                        <div class="text-center">
                            <h1
                                class="mb-4 text-3xl font-bold text-dark dark:text-white sm:text-4xl md:text-[40px] md:leading-[1.2]"
                            >
                                Forgot password
                            </h1>
                            <p class="mb-5 text-base text-body-color dark:text-dark-6">
                                Enter your email and we’ll send a reset link.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form card -->
        <section class="bg-[#F4F7FF] py-14 dark:bg-dark lg:py-20">
            <div class="container mx-auto px-4">
                <div class="-mx-4 flex flex-wrap">
                    <div class="w-full px-4">
                        <div
                            class="relative mx-auto max-w-[525px] overflow-hidden rounded-lg bg-white px-8 py-14 text-center shadow-form dark:bg-dark-2 sm:px-12 md:px-[60px]"
                        >
                            <p class="mb-6 text-left text-sm text-body-color dark:text-dark-6">
                                Forgot your password? No problem. Just let us know your
                                email address and we will email you a password reset
                                link that will allow you to choose a new one.
                            </p>

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

                                <div class="mb-6">
                                    <button
                                        type="submit"
                                        class="w-full cursor-pointer rounded-md border border-primary bg-primary px-5 py-3 text-base text-white transition duration-300 ease-in-out hover:bg-blue-dark disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Email password reset link
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthLayout>
</template>
