<script setup>
import { onMounted, onUnmounted, ref } from 'vue';

const isVisible = ref(false);
const updateVisibility = () => {
    isVisible.value = window.scrollY > 300;
};
const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

onMounted(() => {
    updateVisibility();
    window.addEventListener('scroll', updateVisibility, { passive: true });
});
onUnmounted(() => window.removeEventListener('scroll', updateVisibility));
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        leave-active-class="transition-all duration-200 ease-out"
        leave-to-class="translate-y-2 opacity-0"
    >
        <button
            v-if="isVisible"
            type="button"
            aria-label="Kembali ke atas"
            class="fixed bottom-5 left-1/2 -translate-x-1/2 z-40 flex h-10 w-10 items-center justify-center rounded-full border border-[#2C1E16]/15 bg-white/80 text-[#2C1E16] backdrop-blur-md shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:text-orange-600 cursor-pointer"
            @click="scrollToTop"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 14 6-6 6 6" />
            </svg>
        </button>
    </Transition>
</template>
