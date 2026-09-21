<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    shops: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_active: 0,
            total_passive: 0,
            total_suspended: 0,
            new_products_week: 0,
        }),
    },
});

// Konfirmasi suspend
const confirmShop = ref(null);

const openConfirm = (shop) => { confirmShop.value = shop; };
const closeConfirm = () => { confirmShop.value = null; };

const doToggleSuspend = () => {
    if (!confirmShop.value) return;
    router.patch(route('admin.shops.toggle-suspend', confirmShop.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => closeConfirm(),
    });
};
</script>

<template>
    <Head title="Rekap Keaktifan Lapak — SiswaMart Admin" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8 bg-[#f7f5ef] min-h-[calc(100vh-80px)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-gray-900">
                            Rekap Lapak
                        </h1>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                            Pantau keaktifan & kelola status lapak penjual
                        </p>
                    </div>
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[3px_3px_0px_0px_#111827] hover:shadow-none hover:translate-x-[3px] hover:translate-y-[3px] transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Dashboard
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                    <!-- Toko Aktif -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Toko Aktif</span>
                            <div class="w-7 h-7 bg-emerald-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-emerald-600 tracking-tight">{{ stats.total_active }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Lapak berjalan normal</p>
                    </div>

                    <!-- Toko Pasif -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Perlu Ditinjau</span>
                            <div class="w-7 h-7 bg-amber-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-amber-600 tracking-tight">{{ stats.total_passive }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Tidak aktif 7 hari</p>
                    </div>

                    <!-- Toko Suspended -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Toko Suspend</span>
                            <div class="w-7 h-7 bg-rose-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-rose-600 tracking-tight">{{ stats.total_suspended }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Diblokir dari katalog</p>
                    </div>

                    <!-- Produk Baru Minggu Ini -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Produk Baru</span>
                            <div class="w-7 h-7 bg-sky-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-sky-600 tracking-tight">{{ stats.new_products_week }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Ditambahkan 7 hari ini</p>
                    </div>
                </div>

                <!-- Tabel Rekap Lapak -->
                <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827]">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-3 mb-4">
                        <div class="w-8 h-8 bg-[#fbbf24] border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_0px_#111827]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Keaktifan Lapak</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Data rekap 7 hari terakhir</p>
                        </div>
                    </div>

                    <!-- Table wrapper with horizontal scroll on mobile -->
                    <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0" style="-webkit-overflow-scrolling: touch;">
                        <table class="w-full text-left border-collapse min-w-[640px]">
                            <thead>
                                <tr class="bg-[#f7f5ef] border-b-2 border-gray-900 text-[10px] font-black uppercase tracking-widest text-gray-600">
                                    <th class="px-3 py-3 rounded-l-xl">Lapak & Pemilik</th>
                                    <th class="px-3 py-3">Status Toko</th>
                                    <th class="px-3 py-3">Terakhir Aktif</th>
                                    <th class="px-3 py-3 text-center">Produk Baru (7hr)</th>
                                    <th class="px-3 py-3 text-center">Keaktifan</th>
                                    <th class="px-3 py-3 text-center rounded-r-xl">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="shop in shops" :key="shop.id" class="hover:bg-[#fdf9f0] transition">
                                    <!-- Nama Lapak & Pemilik -->
                                    <td class="px-3 py-3.5">
                                        <div class="font-black text-xs text-gray-900 uppercase">{{ shop.name }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-0.5">{{ shop.owner.username }}</div>
                                    </td>

                                    <!-- Status Buka/Tutup -->
                                    <td class="px-3 py-3.5">
                                        <span v-if="shop.status === 'suspended'" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border-2 border-gray-900 bg-rose-100 text-rose-800 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]">
                                            Suspended
                                        </span>
                                        <span v-else-if="shop.is_open" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border-2 border-gray-900 bg-emerald-100 text-emerald-800 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                            Buka
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border-2 border-gray-900 bg-gray-100 text-gray-600 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                            Tutup
                                        </span>
                                    </td>

                                    <!-- Last Active -->
                                    <td class="px-3 py-3.5 text-xs font-bold text-gray-600">
                                        {{ shop.last_status_change_at }}
                                    </td>

                                    <!-- Produk Baru -->
                                    <td class="px-3 py-3.5 text-center">
                                        <span class="text-sm font-black" :class="shop.new_products_count > 0 ? 'text-sky-600' : 'text-gray-400'">
                                            {{ shop.new_products_count }}
                                        </span>
                                    </td>

                                    <!-- Badge Keaktifan -->
                                    <td class="px-3 py-3.5 text-center">
                                        <span
                                            class="inline-block px-2.5 py-1 rounded-lg border-2 border-gray-900 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]"
                                            :class="{
                                                'bg-emerald-200 text-emerald-900': shop.activity_badge === 'active',
                                                'bg-amber-200 text-amber-900': shop.activity_badge === 'passive',
                                                'bg-rose-200 text-rose-900': shop.activity_badge === 'suspended',
                                            }"
                                        >
                                            <span v-if="shop.activity_badge === 'active'">Aktif</span>
                                            <span v-else-if="shop.activity_badge === 'passive'">Perlu Ditinjau</span>
                                            <span v-else>Suspended</span>
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-3 py-3.5 text-center">
                                        <button
                                            @click="openConfirm(shop)"
                                            class="px-3 py-1.5 border-2 border-gray-900 rounded-lg font-black text-[10px] uppercase shadow-[2px_2px_0px_0px_#111827] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all active:scale-95"
                                            :class="shop.status === 'suspended'
                                                ? 'bg-emerald-400 hover:bg-emerald-300 text-gray-900'
                                                : 'bg-rose-400 hover:bg-rose-300 text-gray-900'"
                                        >
                                            {{ shop.status === 'suspended' ? 'Aktifkan' : 'Suspend' }}
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="shops.length === 0">
                                    <td colspan="6" class="py-10 text-center text-xs font-black uppercase text-gray-400">
                                        Belum ada data lapak.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Konfirmasi Dialog Suspend -->
        <Teleport to="body">
            <div
                v-if="confirmShop"
                class="fixed inset-0 z-[999] flex items-center justify-center p-4"
                @click.self="closeConfirm"
            >
                <!-- Overlay -->
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

                <!-- Modal Card -->
                <div class="relative bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 w-full max-w-sm shadow-[8px_8px_0px_0px_#111827]">
                    <!-- Icon -->
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl border-2 border-gray-900 flex items-center justify-center shadow-[3px_3px_0px_0px_#111827]"
                        :class="confirmShop.status === 'suspended' ? 'bg-emerald-300' : 'bg-rose-300'"
                    >
                        <svg class="w-7 h-7 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path v-if="confirmShop.status !== 'suspended'" stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-black uppercase text-center text-gray-900 tracking-tight">
                        {{ confirmShop.status === 'suspended' ? 'Aktifkan Lapak?' : 'Suspend Lapak?' }}
                    </h3>
                    <p class="text-xs font-bold text-gray-500 text-center mt-2 leading-relaxed">
                        Lapak <span class="font-black text-gray-900">{{ confirmShop.name }}</span>
                        <span v-if="confirmShop.status !== 'suspended'">
                            akan diblokir dari katalog publik. Produknya tidak akan muncul sampai diaktifkan kembali.
                        </span>
                        <span v-else>
                            akan diaktifkan kembali dan produknya akan kembali muncul di katalog publik.
                        </span>
                    </p>

                    <div class="flex gap-3 mt-6">
                        <button
                            @click="closeConfirm"
                            class="flex-1 py-2.5 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest bg-white hover:bg-stone-100 shadow-[3px_3px_0px_0px_#111827] hover:shadow-none hover:translate-x-[3px] hover:translate-y-[3px] transition-all"
                        >
                            Batal
                        </button>
                        <button
                            @click="doToggleSuspend"
                            class="flex-1 py-2.5 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[3px_3px_0px_0px_#111827] hover:shadow-none hover:translate-x-[3px] hover:translate-y-[3px] transition-all"
                            :class="confirmShop.status === 'suspended' ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-rose-400 hover:bg-rose-300'"
                        >
                            {{ confirmShop.status === 'suspended' ? 'Ya, Aktifkan' : 'Ya, Suspend' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>
