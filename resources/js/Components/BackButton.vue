<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    fallbackUrl: {
        type: String,
        default: '/katalog',
    },
    label: {
        type: String,
        default: 'Kembali',
    },
});

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(props.fallbackUrl);
    }
};
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-full border border-[#2C1E16]/15 bg-white/95 px-4 py-2 text-xs font-semibold text-[#2C1E16] shadow-sm backdrop-blur-sm transition-all duration-200 hover:-translate-x-0.5 hover:border-orange-400 hover:text-orange-600 hover:shadow mb-4 sm:mb-5 cursor-pointer"
        @click="goBack"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6" />
        </svg>
        <span>{{ label }}</span>
    </button>
</template>
