<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-[#fffaf3] text-[#362415] font-sans selection:bg-[#F25C05] selection:text-white relative overflow-x-clip"
    >
        <!-- TOPBAR GUEST NAVBAR (STICKY TOP-0 Z-40) -->
        <header
            class="sticky top-0 z-40 w-full backdrop-blur-md bg-white/90 border-b-2 border-gray-900 px-4 sm:px-6 py-3 shadow-xs"
        >
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <Link href="/" class="flex items-center gap-2 group">
                    <span class="text-xl sm:text-2xl font-black uppercase tracking-tight text-[#362415] group-hover:text-[#F25C05] transition-colors">
                        Siswa<span class="text-[#F25C05]">Mart</span>
                    </span>
                </Link>

                <Link
                    href="/"
                    class="text-xs font-black uppercase px-3 py-1.5 border-2 border-gray-900 rounded-xl bg-amber-300 hover:bg-amber-400 text-gray-900 shadow-[2px_2px_0px_0px_#111827] active:translate-y-0.5 transition"
                >
                    Kembali ke Katalog
                </Link>
            </div>
        </header>

        <!-- KONTEN GUEST UTAMA -->
        <div class="flex-1 flex flex-col items-center justify-center p-4 sm:p-6">
            <div
                class="w-full sm:max-w-md bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_#111827]"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
