<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};
</script>

<template>
    <header class="sticky top-0 z-40 px-3 sm:px-6 py-2.5 sm:py-3 transition-all duration-200 backdrop-blur-md bg-white/90 border-b-2 border-gray-900 shadow-xs">
        <div class="max-w-5xl mx-auto bg-white/95 sm:bg-white/90 backdrop-blur-md border-2 border-gray-900 rounded-2xl sm:rounded-full px-3.5 sm:px-6 py-2 sm:py-2.5 flex items-center justify-between gap-2 sm:gap-6 shadow-sm sm:shadow-[0_8px_20px_rgba(54,36,21,0.06)]">
            <!-- LOGO -->
            <Link :href="route('catalog.index')" class="flex items-center gap-2 group shrink-0">
                <div class="text-lg sm:text-2xl font-black tracking-tighter text-[#362415] group-hover:text-[#F25C05] transition-colors">
                    Siswa<span class="text-[#F25C05]">Mart</span>
                </div>
            </Link>

            <!-- DESKTOP NAV MENU -->
            <nav class="hidden md:flex items-center gap-6">
                <Link :href="route('catalog.index')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                    Beranda
                </Link>
                <Link :href="route('catalog.categories')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                    Kategori
                </Link>
                <Link :href="route('how-to-order')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                    Cara Memesan
                </Link>
                <Link :href="route('about')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                    Tentang Kami
                </Link>
            </nav>

            <!-- ACTION BUTTON -->
            <div class="hidden md:flex items-center gap-2 shrink-0">
                <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="px-5 py-2 rounded-full bg-[#362415] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#F25C05] transition-colors duration-200">
                    Dashboard
                </Link>
                <Link v-else :href="route('login')" class="px-5 py-2 rounded-full bg-[#F25C05] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#362415] transition-colors duration-200">
                    Masuk
                </Link>
            </div>

            <!-- MOBILE HAMBURGER -->
            <div class="flex md:hidden items-center gap-2">
                <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="px-3 py-1.5 rounded-full bg-[#362415] text-[#F4F0E6] text-xs font-bold">
                    Dashboard
                </Link>
                <Link v-else :href="route('login')" class="px-3 py-1.5 rounded-full bg-[#F25C05] text-[#F4F0E6] text-xs font-bold">
                    Masuk
                </Link>
                <button
                    @click="toggleMobileMenu"
                    type="button"
                    class="p-2 rounded-xl border-2 border-gray-900 bg-white text-gray-900 hover:bg-[#F9C22E] transition-colors focus:outline-none cursor-pointer"
                    aria-label="Menu"
                >
                    <svg v-if="!mobileMenuOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- MOBILE DRAWER -->
        <div
            v-if="mobileMenuOpen"
            class="md:hidden mt-2 bg-[#FCFAF5] border-2 border-gray-900 rounded-2xl p-4 shadow-md space-y-2.5 max-w-5xl mx-auto"
        >
            <div class="flex flex-col space-y-1 text-sm font-bold text-[#362415]">
                <Link :href="route('catalog.index')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                    Beranda
                </Link>
                <Link :href="route('catalog.categories')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                    Kategori & Budget
                </Link>
                <Link :href="route('how-to-order')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                    Cara Memesan
                </Link>
                <Link :href="route('about')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                    Tentang Kami
                </Link>
            </div>
        </div>
    </header>
</template>
