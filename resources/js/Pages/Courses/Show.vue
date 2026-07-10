<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    course: { type: Object, required: true },
});
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
