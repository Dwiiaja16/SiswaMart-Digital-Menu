<script setup>
import { Link } from '@inertiajs/vue3';
import AppFooter from '../Components/AppFooter.vue';
import BackToTop from '../Components/BackToTop.vue';
import FloatingWhatsApp from '../Components/FloatingWhatsApp.vue';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col justify-between overflow-hidden bg-[#FAF6EE] font-sans text-[#2C1E16] selection:bg-[#F25C05] selection:text-white"
    >
        <!-- TOPBAR GUEST NAVBAR -->
        <header
            class="sticky top-0 z-40 w-full border-b border-[#2C1E16]/10 bg-white/85 px-4 py-3 backdrop-blur-md shadow-[0_2px_12px_rgba(44,30,22,0.04)] sm:px-6"
        >
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <Link href="/" class="group flex items-center gap-2">
                    <span class="text-xl font-extrabold tracking-tight text-[#2C1E16] transition-colors group-hover:text-[#F25C05] sm:text-2xl">
                        Siswa<span class="text-[#F25C05]">Mart</span>
                    </span>
                    <span class="hidden rounded-full border border-amber-500/25 bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-bold text-amber-800 sm:inline-block">
                        Digital Menu
                    </span>
                </Link>

                <!-- Tombol Back Unik (Two-Tone Pill) -->
                <Link
                    href="/"
                    class="group inline-flex items-center gap-2 rounded-full border border-[#2C1E16]/15 bg-white p-1 pr-3.5 text-xs font-semibold text-[#2C1E16] shadow-xs transition-all duration-200 hover:-translate-x-0.5 hover:border-orange-400/60 hover:shadow-sm"
                >
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#FAF6EE] text-[#2C1E16] transition-colors duration-200 group-hover:bg-[#F25C05] group-hover:text-white">
                        <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </span>
                    <span>Kembali ke Katalog</span>
                </Link>
            </div>
        </header>

        <!-- KONTEN GUEST UTAMA -->
        <main class="relative z-10 flex w-full flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6 sm:py-12">
            <div
                class="w-full rounded-3xl border border-[#2C1E16]/10 bg-white/95 p-6 backdrop-blur-md shadow-[0_20px_50px_rgba(44,30,22,0.08),0_4px_12px_rgba(44,30,22,0.04)] sm:max-w-md sm:p-8"
            >
                <slot />
            </div>
        </main>

        <AppFooter class="relative z-10" />
        <BackToTop />
        <FloatingWhatsApp />
    </div>
</template>