<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const showBackToTop = ref(false);
const phone = computed(() => String(page.props.app?.cs_whatsapp || '').replace(/\D/g, ''));
const whatsappUrl = computed(() => {
    const message = 'Halo Admin SiswaMart SMKN 11 Bandung, saya ingin bertanya / membutuhkan bantuan terkait layanan SiswaMart.';
    return `https://wa.me/${phone.value}?text=${encodeURIComponent(message)}`;
});

const updateScrollState = () => {
    showBackToTop.value = window.scrollY > 300;
};

const scrollToTop = () => window.scrollTo({ top: 0, behavior: 'smooth' });

onMounted(() => {
    updateScrollState();
    window.addEventListener('scroll', updateScrollState, { passive: true });
});

onUnmounted(() => window.removeEventListener('scroll', updateScrollState));
</script>

<template>
    <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-3">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <button
                v-if="showBackToTop"
                type="button"
                aria-label="Kembali ke atas"
                class="bg-yellow-400 border-2 border-neutral-900 rounded-xl shadow-[3px_3px_0px_#171717] p-3 text-neutral-900 transition active:translate-y-0.5"
                @click="scrollToTop"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 15 7-7 7 7" />
                </svg>
            </button>
        </Transition>

        <a
            v-if="phone"
            :href="whatsappUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="bg-[#25D366] text-white border-2 border-neutral-900 rounded-full shadow-[3px_3px_0px_#171717] flex items-center gap-2 px-4 py-3 text-sm font-black transition active:translate-y-0.5"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M20.52 3.48A11.9 11.9 0 0 0 12.05 0C5.48 0 .13 5.35.13 11.92c0 2.1.55 4.15 1.6 5.95L0 24l6.3-1.65a11.92 11.92 0 0 0 5.74 1.46h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.24-6.16-3.45-8.41ZM12.05 21.8a9.9 9.9 0 0 1-5.05-1.38l-.36-.21-3.74.98 1-3.65-.24-.38a9.9 9.9 0 1 1 8.39 4.64Zm5.43-7.42c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.96 1.18-.18.2-.36.22-.66.07-.3-.15-1.27-.47-2.42-1.5a9.12 9.12 0 0 1-1.68-2.1c-.18-.3-.02-.46.13-.61.13-.13.3-.36.45-.54.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.07-.15-.68-1.63-.93-2.23-.24-.58-.49-.5-.68-.5h-.58c-.2 0-.53.08-.8.38-.28.3-1.06 1.03-1.06 2.5 0 1.48 1.08 2.9 1.23 3.1.15.2 2.13 3.25 5.16 4.56.72.31 1.28.5 1.72.64.72.23 1.38.2 1.9.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.08-.12-.28-.2-.58-.35Z" />
            </svg>
            CS SiswaMart
        </a>
    </div>
</template>
