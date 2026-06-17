<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-10 sm:justify-center sm:pt-0">

        <!-- Brand block -->
        <div class="mb-6 flex flex-col items-center gap-3">
            <Link href="/" class="block">
                <ApplicationLogo class="h-16 w-16" />
            </Link>
            <div class="text-center">
                <h1 class="text-2xl font-extrabold tracking-tight text-gray-800 leading-none">
                    Habit<span class="text-green-600">Track</span>
                </h1>
                <p class="mt-1 text-sm text-gray-500">Your daily food &amp; health companion</p>
            </div>
        </div>

        <!-- Auth card -->
        <div class="w-full overflow-hidden bg-white px-6 py-6 shadow-md sm:max-w-md sm:rounded-xl">
            <slot />
        </div>

    </div>
</template>
