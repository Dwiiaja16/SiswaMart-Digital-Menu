<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalSellers: 0,
            activeShops: 0,
            totalProducts: 0,
            totalCategories: 0,
        }),
    },
    recentSellers: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Dashboard Overview Admin - SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8 bg-stone-50 min-h-[calc(100vh-80px)] selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                
                <!-- Kartu Statistik Neo-Brutalism -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                    <!-- Total Penjual -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-1 sm:space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Total Penjual</span>
                            <div class="w-7 h-7 sm:w-9 sm:h-9 bg-indigo-100 border-2 border-gray-900 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-indigo-600 tracking-tight">
                            {{ stats.totalSellers }}
                        </div>
                        <p class="text-[10px] sm:text-[11px] font-black text-gray-500 uppercase line-clamp-1">
                            Siswa wirausaha
                        </p>
                    </div>

                    <!-- Lapak Aktif -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-1 sm:space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Lapak Aktif</span>
                            <div class="w-7 h-7 sm:w-9 sm:h-9 bg-emerald-100 border-2 border-gray-900 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0l-3-3m3 3l3-3" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">
                            {{ stats.activeShops }}
                        </div>
                        <p class="text-[10px] sm:text-[11px] font-black text-gray-500 uppercase line-clamp-1">
                            Toko siap jualan
                        </p>
                    </div>

                    <!-- Total Produk Promosi -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-1 sm:space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Total Produk</span>
                            <div class="w-7 h-7 sm:w-9 sm:h-9 bg-amber-100 border-2 border-gray-900 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-amber-600 tracking-tight">
                            {{ stats.totalProducts }}
                        </div>
                        <p class="text-[10px] sm:text-[11px] font-black text-gray-500 uppercase line-clamp-1">
                            Di katalog publik
                        </p>
                    </div>

                    <!-- Kategori Produk -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-1 sm:space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Kategori Menu</span>
                            <div class="w-7 h-7 sm:w-9 sm:h-9 bg-sky-100 border-2 border-gray-900 rounded-lg sm:rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-sky-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-2xl sm:text-3xl font-black text-sky-600 tracking-tight">
                            {{ stats.totalCategories }}
                        </div>
                        <p class="text-[10px] sm:text-[11px] font-black text-gray-500 uppercase line-clamp-1">
                            Kelompok jajanan
                        </p>
                    </div>
                </div>

                <!-- Tombol Aksi Cepat & Navigasi Admin -->
                <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-3">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-orange-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">
                                Aksi Cepat Manajemen
                            </h3>
                            <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase">
                                Pintasan navigasi pengelolaan sistem SiswaMart
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 lg:flex lg:flex-wrap gap-2.5 sm:gap-4 pt-1">
                        <!-- Kelola Penjual -->
                        <Link
                            :href="route('admin.sellers.index')"
                            class="px-3 sm:px-5 py-2.5 sm:py-3 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-[10px] sm:text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Kelola Penjual
                        </Link>

                        <!-- Kelola Kategori -->
                        <Link
                            :href="route('admin.categories.index')"
                            class="px-3 sm:px-5 py-2.5 sm:py-3 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 font-black text-[10px] sm:text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            Kelola Kategori
                        </Link>

                        <!-- Moderasi Produk -->
                        <Link
                            :href="route('admin.products.index')"
                            class="px-3 sm:px-5 py-2.5 sm:py-3 bg-sky-400 hover:bg-sky-300 border-2 border-gray-900 text-gray-900 font-black text-[10px] sm:text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                                <path d="m3.3 7 8.7 5 8.7-5" />
                                <path d="M12 22V12" />
                            </svg>
                            Moderasi Produk
                        </Link>

                        <!-- Katalog Publik -->
                        <Link
                            :href="route('catalog.index')"
                            target="_blank"
                            class="px-3 sm:px-5 py-2.5 sm:py-3 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-[10px] sm:text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Katalog Publik
                        </Link>

                        <!-- Rekap Lapak -->
                        <Link
                            :href="route('admin.recap')"
                            class="col-span-2 sm:col-span-1 px-3 sm:px-5 py-2.5 sm:py-3 bg-yellow-300 hover:bg-yellow-200 border-2 border-gray-900 text-gray-900 font-black text-[10px] sm:text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Rekap Lapak
                        </Link>
                    </div>
                </div>

                <!-- Tabel Penjual Terbaru -->
                <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4 sm:space-y-6">
                    <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3 sm:pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 bg-emerald-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">
                                    Penjual Baru
                                </h3>
                                <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase">
                                    Siswa wirausaha baru terdaftar
                                </p>
                            </div>
                        </div>

                        <Link
                            href="/admin/sellers"
                            class="text-xs font-black text-orange-600 hover:underline uppercase flex items-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            Lihat Semua
                        </Link>
                    </div>

                    <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                        <table class="w-full text-left border-collapse min-w-[340px]">
                            <thead>
                                <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                    <th class="p-3 sm:p-3.5 rounded-l-xl">SISWA PENJUAL</th>
                                    <th class="p-3 sm:p-3.5">Nama Lapak</th>
                                    <th class="p-3 sm:p-3.5 rounded-r-xl">WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-100">
                                <tr
                                    v-for="seller in recentSellers"
                                    :key="seller.id"
                                    class="hover:bg-stone-50/50 transition"
                                >
                                    <td class="py-3 sm:py-4 px-3 sm:px-3.5 font-black text-xs uppercase text-gray-900">
                                        {{ seller.username || '-' }}
                                    </td>
                                    <td class="py-3 sm:py-4 px-3 sm:px-3.5 text-xs font-bold text-gray-700 uppercase">
                                        {{ seller.shop ? seller.shop.name : "-" }}
                                    </td>
                                    <td class="py-3 sm:py-4 px-3 sm:px-3.5 text-xs font-bold text-emerald-600">
                                        {{ seller.whatsapp_number }}
                                    </td>
                                </tr>
                                <tr v-if="recentSellers.length === 0">
                                    <td colspan="3" class="py-8 text-center text-xs font-black uppercase text-gray-400">
                                        Belum ada data penjual.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* =========================================================
   MOBILE RESPONSIVE FIXES — ADMIN DASHBOARD OVERVIEW
   ========================================================= */

@media (max-width: 640px) {
    /* Prevents horizontal scrolling (offside) */
    .max-w-7xl {
        width: 100% !important;
        max-width: 100vw !important;
        box-sizing: border-box !important;
        overflow-x: hidden !important;
    }

    /* Cards Responsive Adjustment */
    .bg-white.border-2, 
    .bg-white.border-4 {
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 0.85rem !important;
    }

    /* Statistic Numbers */
    .text-2xl {
        font-size: 1.5rem !important;
        line-height: 1.75rem !important;
    }

    /* Quick Action Buttons (2-Column Grid on Mobile) */
    .grid-cols-1.sm\:grid-cols-2 {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 0.5rem !important;
    }

    .grid-cols-1.sm\:grid-cols-2 a {
        padding: 0.65rem 0.5rem !important;
        font-size: 0.65rem !important;
        border-radius: 0.75rem !important;
        box-shadow: 2px 2px 0px 0px #111827 !important;
        flex-direction: column !important;
        text-align: center !important;
        gap: 0.25rem !important;
    }

    .grid-cols-1.sm\:grid-cols-2 a svg {
        width: 1.1rem !important;
        height: 1.1rem !important;
    }

    /* Table Fixes */
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }

    table {
        min-width: 100% !important;
        width: 100% !important;
        table-layout: fixed !important;
    }

    table th, table td {
        padding: 0.5rem 0.35rem !important;
        font-size: 0.65rem !important;
        word-break: break-word !important;
    }
}
</style>