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
                
                <!-- 1. KARTU STATISTIK UTAMA NEO-BRUTALISM (4 GRID) -->
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

                    <!-- Total Produk Katalog -->
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

                    <!-- Kategori Menu -->
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

                <!-- 2. TABEL PENJUAL TERBARU NEO-BRUTALISM -->
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

                        <!-- PERBAIKAN: Menggunakan 'href' biasa sebagai fallback jika route() gagal atau mendefinisikan route jika ziggy jalan. -->
                        <Link
                            :href="route ? route('admin.sellers.index') : '/admin/sellers'"
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