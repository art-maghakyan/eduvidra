<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    course: { type: Object, required: true },
    chat: { type: Object, default: () => ({ available: false, messages: [] }) },
});

const draft = ref('');
const error = ref('');
const streaming = ref(false);
const transcript = ref(null);

// The only client-side message state, and only for the turn currently in
// flight. Both turns are persisted server-side, so once the stream ends the
// reloaded props take over and this is cleared — a reload can never disagree
// with what the student watched being typed.
const pending = ref(null);

const scrollToLatest = () => {
    nextTick(() => {
        if (transcript.value) {
            transcript.value.scrollTop = transcript.value.scrollHeight;
        }
    });
};

const isEmpty = computed(() => !props.chat.messages.length && !pending.value);

watch(() => props.chat.messages.length, scrollToLatest, { immediate: true });
watch(() => pending.value?.answer.length, scrollToLatest);

// Laravel accepts the XSRF-TOKEN cookie echoed back as a header. axios does this
// automatically, but axios cannot read a response body progressively in the
// browser, so the streaming request is made with fetch and signs itself.
const csrfToken = () => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
};

/**
 * Reads an SSE body frame by frame.
 *
 * Frames are separated by a blank line and a chunk from the network may split
 * one anywhere, so bytes are buffered until a separator actually arrives rather
 * than assuming one read is one event.
 */
const readEvents = async (body, onEvent) => {
    const reader = body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    for (;;) {
        const { value, done } = await reader.read();

        if (done) {
            break;
        }

        buffer += decoder.decode(value, { stream: true });

        let separator;

        while ((separator = buffer.indexOf('\n\n')) !== -1) {
            const frame = buffer.slice(0, separator);
            buffer = buffer.slice(separator + 2);

            let name = 'message';
            let data = '';

            for (const line of frame.split('\n')) {
                if (line.startsWith('event:')) {
                    name = line.slice(6).trim();
                } else if (line.startsWith('data:')) {
                    data += line.slice(5).trim();
                }
            }

            if (data) {
                onEvent(name, JSON.parse(data));
            }
        }
    }
};

const send = async () => {
    const question = draft.value.trim();

    if (!question || streaming.value) {
        return;
    }

    error.value = '';
    draft.value = '';
    pending.value = { question, answer: '' };
    streaming.value = true;

    // Once the server has answered 200 the question is persisted, so the props
    // have to be reloaded however the stream then ends — otherwise a failure
    // half way through would wipe the question off the screen.
    let opened = false;

    try {
        const response = await fetch(`/courses/${props.course.slug}/chat`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                // Makes Laravel answer validation and authorisation failures
                // with JSON instead of a redirect fetch cannot follow usefully.
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({ message: question }),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => ({}));

            throw new Error(body.message || 'The tutor is unavailable right now.');
        }

        opened = true;

        await readEvents(response.body, (name, data) => {
            if (name === 'delta') {
                pending.value.answer += data.text;
            } else if (name === 'error') {
                error.value = data.message;
            }
        });
    } catch (e) {
        error.value = e.message;

        if (!opened) {
            // Nothing was persisted, so give the student their text back.
            draft.value = question;
        }
    } finally {
        if (opened) {
            // Swap the locally-streamed turn for the persisted one.
            await new Promise((resolve) => {
                router.reload({ only: ['chat'], onFinish: resolve });
            });
        }

        streaming.value = false;
        pending.value = null;
    }
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
                            <p v-if="isEmpty" class="text-sm text-gray-400 dark:text-gray-500">
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

                            <!-- The turn currently streaming. Replaced by the
                                 persisted pair as soon as the stream ends. -->
                            <template v-if="pending">
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] whitespace-pre-wrap rounded-lg bg-blue-600 px-3 py-2 text-sm text-white">{{ pending.question }}</div>
                                </div>

                                <div class="flex justify-start">
                                    <div class="max-w-[85%] whitespace-pre-wrap rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                                        <span>{{ pending.answer }}</span>
                                        <span
                                            v-if="streaming"
                                            class="ml-0.5 inline-block h-4 w-1.5 animate-pulse bg-gray-400 align-text-bottom dark:bg-gray-400"
                                            aria-hidden="true"
                                        />
                                        <span v-if="!pending.answer" class="sr-only">The tutor is answering…</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <form class="border-t border-gray-200 p-4 dark:border-gray-700" @submit.prevent="send">
                            <label for="tutor-message" class="sr-only">Your question</label>
                            <textarea
                                id="tutor-message"
                                v-model="draft"
                                rows="3"
                                maxlength="2000"
                                :disabled="streaming"
                                placeholder="e.g. What should I learn first in this course?"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-60 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                @keydown.enter.exact.prevent="send"
                            />

                            <p v-if="error" class="mt-2 text-sm text-red-600 dark:text-red-400">
                                {{ error }}
                            </p>

                            <div class="mt-3 flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ draft.length }} / 2000
                                </span>
                                <button
                                    type="submit"
                                    :disabled="streaming || !draft.trim()"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    {{ streaming ? 'Answering…' : 'Ask' }}
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
