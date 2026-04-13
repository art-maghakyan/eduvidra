<script setup>
import InputError from '@/Components/InputError.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('two-factor.challenge.store'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="2FA code" />

        <div class="relative z-10 overflow-hidden pb-[60px] pt-[120px] md:pt-[130px] lg:pt-[160px] dark:bg-dark">
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
                                Two-factor authentication
                            </h1>
                            <p class="mb-5 text-base text-body-color dark:text-dark-6">
                                Enter the 6-digit code from your Authenticator app to continue.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="bg-[#F4F7FF] py-14 dark:bg-dark lg:py-20">
            <div class="container mx-auto px-4">
                <div class="-mx-4 flex flex-wrap">
                    <div class="w-full px-4">
                        <div
                            class="relative mx-auto max-w-[525px] overflow-hidden rounded-lg bg-white px-8 py-14 text-center shadow-form dark:bg-dark-2 sm:px-12 md:px-[60px]"
                        >
                            <form @submit.prevent="submit">
                                <div class="mb-[22px] text-left">
                                    <input
                                        id="code"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        placeholder="123456"
                                        class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                        v-model="form.code"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="form.errors.code" />
                                </div>

                                <div class="mb-2">
                                    <button
                                        type="submit"
                                        class="w-full cursor-pointer rounded-md border border-primary bg-primary px-5 py-3 text-base text-white transition duration-300 ease-in-out hover:bg-blue-dark disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Verify
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

