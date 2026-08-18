<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    course: { type: Object, required: true },
    chat: { type: Object, default: () => ({ available: false, messages: [] }) },
});

const form = useForm({ message: '' });
const transcript = ref(null);

const scrollToLatest = () => {
    nextTick(() => {
        if (transcript.value) {
            transcript.value.scrollTop = transcript.value.scrollHeight;
        }
    });
};

watch(() => props.chat.messages.length, scrollToLatest, { immediate: true });

const send = () => {
    if (!form.message.trim()) {
        return;
    }

    form.post(`/courses/${props.course.slug}/chat`, {
        preserveScroll: true,
        // The reply is persisted server-side, so the reloaded props are the
        // source of truth — nothing is held in local component state.
        onSuccess: () => form.reset('message'),
    });
};
</script>

<template>
    <Head :title="course.title" />

    <div class="min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
            <Link
                href="/"
                class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
                <span aria-hidden="true">&larr;</span> Back to search
            </Link>

            <div class="mt-6 grid gap-8 lg:grid-cols-3">
                <!-- Details + curriculum -->
                <div class="lg:col-span-2">
                    <h1 class="text-3xl font-bold tracking-tight">{{ course.title }}</h1>

                    <div v-if="course.tags?.length" class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="tag in course.tags"
                            :key="tag"
                            class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                        >
                            {{ tag }}
                        </span>
                    </div>

                    <p v-if="course.description" class="mt-6 text-base leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ course.description }}
                    </p>

                    <section class="mt-10">
                        <h2 class="text-lg font-semibold">Course content</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ course.videos.length }} {{ course.videos.length === 1 ? 'lesson' : 'lessons' }}
                        </p>

                        <ol v-if="course.videos.length" class="mt-4 divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
                            <li
                                v-for="(video, index) in course.videos"
                                :key="video.id"
                                class="flex items-center gap-3 bg-white px-4 py-3 text-sm dark:bg-gray-800"
                            >
                                <span class="w-6 shrink-0 text-right text-gray-400 tabular-nums dark:text-gray-500">{{ index + 1 }}</span>
                                <span class="text-gray-800 dark:text-gray-100">{{ video.title }}</span>
                            </li>
                        </ol>
                        <p v-else class="mt-4 text-sm text-gray-400 dark:text-gray-500">
                            No lessons published yet.
                        </p>
                    </section>
                <!-- AI study tutor -->
                <section v-if="chat.available" class="mt-10">
                    <h2 class="text-lg font-semibold">Ask the AI tutor</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Answers are generated from the course outline. The tutor cannot see the lesson videos.
                    </p>

                    <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                        <div ref="transcript" class="max-h-96 space-y-4 overflow-y-auto p-4">
                            <p v-if="!chat.messages.length" class="text-sm text-gray-400 dark:text-gray-500">
                                No questions yet. Ask anything about this course.
                            </p>

                            <div
                                v-for="message in chat.messages"
                                :key="message.id"
                                class="flex"
                                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-[85%] whitespace-pre-wrap rounded-lg px-3 py-2 text-sm"
                                    :class="message.role === 'user'
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'"
                                >{{ message.content }}</div>
                            </div>
                        </div>

                        <form class="border-t border-gray-200 p-4 dark:border-gray-700" @submit.prevent="send">
                            <label for="tutor-message" class="sr-only">Your question</label>
                            <textarea
                                id="tutor-message"
                                v-model="form.message"
                                rows="3"
                                maxlength="2000"
                                :disabled="form.processing"
                                placeholder="e.g. What should I learn first in this course?"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                @keydown.enter.exact.prevent="send"
                            />

                            <p v-if="form.errors.message" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors.message }}
                            </p>

                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ form.message.length }} / 2000
                                </span>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.message.trim()"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{ form.processing ? 'Thinking…' : 'Ask' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

                <!-- Buy panel -->
                <aside class="lg:col-span-1">
                    <div class="sticky top-10 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-2xl font-bold">Enroll now</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Get lifetime access to all {{ course.videos.length }} lessons in this course.
                        </p>
                        <button
                            type="button"
                            disabled
                            class="mt-6 w-full cursor-not-allowed rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white opacity-70"
                        >
                            Buy course (coming soon)
                        </button>
                        <p class="mt-3 text-center text-xs text-gray-400 dark:text-gray-500">
                            Checkout is not available yet.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
