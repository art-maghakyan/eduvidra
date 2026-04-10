<script setup>
defineProps({
    year: { type: Number, required: true },
    brand: { type: String, default: 'Eduvidra' },
});
</script>

<template>
    <footer
        class="mx-0 my-6 rounded-lg bg-white p-4 shadow md:flex md:items-center md:justify-between md:p-6 xl:p-8 dark:bg-gray-800"
    >
        <ul class="mb-6 flex flex-wrap items-center space-y-1 md:mb-0">
            <li>
                <a
                    href="#"
                    class="mr-4 text-sm font-normal text-gray-500 hover:underline md:mr-6 dark:text-gray-400"
                >
                    Terms
                </a>
            </li>
            <li>
                <a
                    href="#"
                    class="mr-4 text-sm font-normal text-gray-500 hover:underline md:mr-6 dark:text-gray-400"
                >
                    Privacy
                </a>
            </li>
            <li>
                <a
                    href="#"
                    class="mr-4 text-sm font-normal text-gray-500 hover:underline md:mr-6 dark:text-gray-400"
                >
                    Licensing
                </a>
            </li>
            <li>
                <a
                    href="#"
                    class="text-sm font-normal text-gray-500 hover:underline dark:text-gray-400"
                >
                    Contact
                </a>
            </li>
        </ul>
        <div class="text-sm text-gray-500 dark:text-gray-400">
            © {{ year }} {{ brand }}. All rights reserved.
        </div>
    </footer>
</template>

