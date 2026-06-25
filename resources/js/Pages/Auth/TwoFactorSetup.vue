<script setup>
import InputError from '@/Components/InputError.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    qrSvg: {
        type: String,
        default: null,
    },
    qrUrl: {
        type: String,
        default: null,
    },
});

const codeInput = ref(null);

const form = useForm({
    code: '',
});

onMounted(() => codeInput.value?.focus());

const submit = () => {
    form.post(route('two-factor.setup.store'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Set up 2FA" />

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
                                Set up 2FA
                            </h1>
                            <p class="mb-5 text-base text-body-color dark:text-dark-6">
                                Scan the QR code with your Authenticator app, then enter the 6-digit code to continue.
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
                            <div class="mb-8 flex justify-center">
                                <div v-if="qrSvg" class="rounded-lg bg-white p-3" v-html="qrSvg" />
                                <img
                                    v-else-if="qrUrl"
                                    :src="qrUrl"
                                    alt="2FA QR code"
                                    class="h-[220px] w-[220px] rounded-lg bg-white p-3"
                                />
                                <div v-else class="text-sm text-body-color dark:text-dark-6">
                                    QR code unavailable.
                                </div>
                            </div>

                            <form @submit.prevent="submit" novalidate>
                                <div class="mb-[22px] text-left">
                                    <input
                                        id="code"
                                        ref="codeInput"
                                        inputmode="numeric"
                                        autocomplete="one-time-code"
                                        placeholder="123456"
                                        class="w-full rounded-md border border-stroke bg-transparent px-5 py-3 text-base text-body-color outline-hidden transition placeholder:text-dark-6 focus:border-primary focus-visible:shadow-none dark:border-dark-3 dark:text-dark-6 dark:focus:border-primary"
                                        v-model="form.code"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.code" />
                                </div>

                                <div class="mb-2">
                                    <button
                                        type="submit"
                                        class="w-full cursor-pointer rounded-md border border-primary bg-primary px-5 py-3 text-base text-white transition duration-300 ease-in-out hover:bg-blue-dark disabled:opacity-50"
                                        :disabled="form.processing"
                                    >
                                        Confirm and continue
                                    </button>
                                </div>
                            </form>

                            <div
                                class="mt-6 rounded-md border border-stroke bg-[#F4F7FF] px-4 py-3 text-left text-xs leading-relaxed text-body-color dark:border-dark-3 dark:bg-dark dark:text-dark-6"
                                role="note"
                                aria-label="Help if you cannot scan the QR code"
                            >
                                <p class="mb-2 font-semibold text-dark dark:text-white">
                                    Can’t scan the code?
                                </p>
                                <ul class="list-disc space-y-1 pl-4">
                                    <li>
                                        Try another device (phone or tablet) or a different browser—some cameras or
                                        privacy extensions block scanning.
                                    </li>
                                    <li>
                                        Turn up screen brightness, disable strong blue-light filters, and zoom the page
                                        so the QR fills more of the camera view.
                                    </li>
                                    <li>
                                        If it still won’t work, contact your administrator. They can reset your 2FA so
                                        you can open this page again and set up a new code.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </AuthLayout>
</template>

