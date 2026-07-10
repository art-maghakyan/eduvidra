<script setup>
import { ref, computed, watch } from 'vue'
import { Combobox, ComboboxInput, ComboboxOptions, ComboboxOption, TransitionRoot } from '@headlessui/vue'
import { useDebounceFn, useElementBounding, onClickOutside } from '@vueuse/core'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
    size: { type: String, default: 'sm' },  // 'sm' | 'lg'
})

const query    = ref('')
const results  = ref({ courses: [] })
const loading  = ref(false)
const selected = ref(null)

const wrapperRef = ref(null)
const { bottom, left, width } = useElementBounding(wrapperRef)

const isLg = computed(() => props.size === 'lg')

const courseResults = computed(() => results.value.courses)

const hasResults   = computed(() => courseResults.value.length > 0)
const noResults    = computed(() => query.value.length >= 2 && !loading.value && !hasResults.value)
const showDropdown = computed(() => query.value.length >= 2 && (hasResults.value || noResults.value || loading.value))

const fetchResults = useDebounceFn(async (q) => {
    if (q.length < 2) {
        results.value = { courses: [] }
        return
    }
    loading.value = true
    try {
        const { data } = await axios.get('/search', { params: { q }, withCredentials: true })
        results.value = data
    } catch {
        results.value = { courses: [] }
    } finally {
        loading.value = false
    }
}, 300)

watch(query, fetchResults)

function resetSearch() {
    query.value   = ''
    results.value = { courses: [] }
}

// Clicking anywhere outside the search box clears it. onClickOutside only fires for clicks
// outside wrapperRef, so clicking a result (which lives inside the wrapper) still navigates.
onClickOutside(wrapperRef, resetSearch)

function onSelect(course) {
    if (!course) return
    selected.value = null
    resetSearch()
    router.visit(`/courses/${course.slug}`)
}

const wrapperClass = computed(() => isLg.value ? 'relative w-full' : 'relative w-80')

const inputClass = computed(() =>
    isLg.value
        ? 'w-full rounded-xl bg-white py-3.5 pl-12 pr-4 text-base text-gray-900 placeholder-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-white/60'
        : 'w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400'
)

const iconSize = computed(() => isLg.value ? 'h-5 w-5' : 'h-4 w-4')
const iconPos  = computed(() => isLg.value ? 'left-4' : 'left-3')

// lg: escape overflow clipping by anchoring to the viewport with fixed positioning
const lgDropdownStyle = computed(() => ({
    position: 'fixed',
    top:   `${bottom.value + 6}px`,
    left:  `${left.value}px`,
    width: `${width.value}px`,
    zIndex: 9999,
}))
</script>

<template>
    <Combobox as="div" v-model="selected" nullable immediate @update:modelValue="onSelect">
        <div ref="wrapperRef" :class="wrapperClass">
            <!-- Search icon -->
            <span :class="['pointer-events-none absolute top-1/2 -translate-y-1/2 text-gray-400', iconPos]">
                <svg :class="iconSize" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </span>

            <!-- Input -->
            <ComboboxInput
                :class="inputClass"
                placeholder="Search online courses"
                autocomplete="off"
                :displayValue="() => query"
                @change="query = $event.target.value"
            />

            <!-- Loading spinner -->
            <span v-if="loading" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg :class="iconSize" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="animate-spin">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
            </span>

            <!-- Dropdown -->
            <TransitionRoot
                leave="transition ease-in duration-100"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
            >
                <ComboboxOptions
                    v-if="showDropdown"
                    static
                    :style="isLg ? lgDropdownStyle : {}"
                    :class="[
                        'max-h-80 overflow-y-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl focus:outline-none dark:border-gray-700 dark:bg-gray-800',
                        isLg ? '' : 'absolute left-0 right-0 top-full z-50 mt-1.5',
                    ]"
                >
                    <!-- Courses -->
                    <template v-if="courseResults.length > 0">
                        <li class="px-3 pb-1 pt-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Courses
                        </li>
                        <ComboboxOption
                            v-for="course in courseResults"
                            :key="`course-${course.id}`"
                            :value="course"
                            v-slot="{ active }"
                            as="template"
                        >
                            <li :class="['cursor-pointer select-none px-3 py-2', active ? 'bg-blue-50 dark:bg-gray-700' : '']">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ course.title }}</p>
                                <p v-if="course.description" class="truncate text-xs text-gray-500 dark:text-gray-400">{{ course.description }}</p>
                            </li>
                        </ComboboxOption>
                    </template>

                    <!-- No results -->
                    <li v-if="noResults" class="px-3 py-4 text-center text-sm text-gray-400 dark:text-gray-500">
                        No results for "<span class="font-medium text-gray-600 dark:text-gray-300">{{ query }}</span>"
                    </li>
                </ComboboxOptions>
            </TransitionRoot>
        </div>
    </Combobox>
</template>
