<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import { ref } from "vue";

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

// Helper & State Modal
const showProfileModal = ref(false);
const user = usePage().props.auth?.user || {};

const profileForm = useForm({
    username: user.username || '',
    password: ''
});

function submitProfile() {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            showProfileModal.value = false;
            profileForm.reset('password');
            alert('Profil berhasil diperbarui');
        }
    });
}

// Fungsi Putuskan Tautan Akun Google
function disconnectGoogle() {
    if (confirm('Apakah Anda yakin ingin memutuskan tautan akun Google ini?')) {
        router.post('/auth/google/disconnect', {}, {
            preserveScroll: true,
            onSuccess: () => {
                alert('Tautan akun Google berhasil diputuskan!');
            },
            onError: (errors) => {
                alert('Gagal memutuskan tautan: ' + (errors.error || 'Terjadi kesalahan.'));
            }
        });
    }
}

const safeRoute = (name, params = {}) => {
    if (!name) return '#';

    try {
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return window.route(name, params);
        }
        if (typeof route === 'function') {
            return route(name, params);
        }
    } catch (e) {
        // Fallback jika Ziggy belum dimuat
    }

    const fallbacks = {
        'admin.sellers.index': '/admin/sellers',
        'admin.penjual.index': '/admin/sellers',
    };

    return fallbacks[name] || '/admin/sellers';
};
</script>

<template>
    <Head title="Dashboard Overview Admin - SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-6 sm:py-8 bg-stone-50 min-h-[calc(100vh-80px)] selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                
                <!-- BANNER UTAMA ADMIN NEO-BRUTALISM -->
                <div class="bg-[#fbbf24] border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-white border-2 border-gray-900 rounded-full text-[10px] font-black uppercase tracking-wider mb-2 shadow-[2px_2px_0px_0px_#111827]">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Administrator Portal
                        </div>
                        <h1 class="text-xl sm:text-2xl font-black text-gray-900 uppercase tracking-tight">Selamat datang, Admin!</h1>
                        <p class="text-xs sm:text-sm font-bold text-gray-800">Beranda dan pusat kontrol sistem SiswaMart</p>
                    </div>

                    <button type="button" @click="showProfileModal = true" class="px-3.5 sm:px-5 py-3 border-2 border-gray-900 bg-white hover:bg-stone-100 text-gray-900 font-black text-xs uppercase tracking-wider rounded-2xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-1.5 shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Profile Saya</span>
                    </button>
                </div>

                <!-- Modal Edit Profil Neo‑Brutalism -->
                <Teleport to="body">
                    <div v-if="showProfileModal" class="fixed inset-0 flex items-center justify-center z-60 bg-black/50 backdrop-blur-sm" @click.self="showProfileModal = false">
                        <div class="bg-white border-4 border-gray-900 rounded-3xl shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] p-6 sm:p-8 w-full max-w-md relative">
                            <button type="button" @click="showProfileModal = false" class="absolute top-3 right-3 text-gray-600 hover:text-gray-800 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <h2 class="text-xl font-black text-gray-900 uppercase mb-4 text-center">Edit Profil Administrator</h2>
                            <form @submit.prevent="submitProfile" class="space-y-4">
                                <!-- USERNAME -->
                                <div>
                                    <label class="block text-sm font-black text-gray-800 mb-1">Username Administrator</label>
                                    <input v-model="profileForm.username" type="text" class="w-full border-2 border-gray-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-900 font-bold" required />
                                </div>
                                
                                <!-- PASSWORD BARU -->
                                <div>
                                    <label class="block text-sm font-black text-gray-800 mb-1">Password Baru (opsional)</label>
                                    <input v-model="profileForm.password" type="password" class="w-full border-2 border-gray-900 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-900 font-bold" placeholder="Masukkan password baru..." />
                                </div>

                                <!-- INTEGRASI AKUN GOOGLE -->
                                <div class="border-2 border-gray-900 rounded-2xl p-4 bg-stone-50 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-black uppercase text-gray-900">AKUN GMAIL GOOGLE</span>
                                        <span 
                                            class="text-[10px] font-black border border-gray-900 px-2 py-0.5 rounded-md uppercase"
                                            :class="user.email ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                        >
                                            {{ user.email ? 'TERTAUT' : 'BELUM TERHUBUNG' }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-gray-600">
                                        Email Terhubung: <span class="text-gray-900 font-black">{{ user.email || 'Belum ada email Google' }}</span>
                                    </p>
                                    
                                    <!-- JIKA SUDAH TERHUBUNG: TAMPILKAN TOMBOL GANTI & PUTUSKAN -->
                                    <div v-if="user.email" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <a 
                                            href="/auth/google" 
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-gray-900 bg-white text-gray-900 font-black text-[11px] uppercase rounded-xl hover:bg-stone-100 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer text-center no-underline"
                                        >
                                            <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24">
                                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                            </svg>
                                            <span>GANTI AKUN</span>
                                        </a>

                                        <button 
                                            type="button"
                                            @click="disconnectGoogle"
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-2 border-2 border-gray-900 bg-rose-500 hover:bg-rose-600 text-white font-black text-[11px] uppercase rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            <span>PUTUSKAN TAUTAN</span>
                                        </button>
                                    </div>

                                    <!-- JIKA BELUM TERHUBUNG: TAMPILKAN TOMBOL TAUTKAN -->
                                    <a 
                                        v-else
                                        href="/auth/google" 
                                        class="w-full inline-flex items-center justify-center gap-2 px-3 py-2 border-2 border-gray-900 bg-white text-gray-900 font-black text-xs uppercase rounded-xl hover:bg-stone-100 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer text-center no-underline"
                                    >
                                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
                                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                                        </svg>
                                        <span>TAUTKAN AKUN GOOGLE</span>
                                    </a>
                                </div>

                                <div class="flex justify-end space-x-3 mt-4">
                                    <button type="button" @click="showProfileModal = false" class="px-4 py-2 border-2 border-gray-900 bg-white text-gray-900 font-black text-sm uppercase rounded-xl hover:bg-stone-100 cursor-pointer">Batal</button>
                                    <button type="submit" :disabled="profileForm.processing" class="px-4 py-2 border-2 border-gray-900 bg-gray-900 text-white font-black text-sm uppercase rounded-xl hover:bg-gray-800 disabled:opacity-50 cursor-pointer">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </Teleport>

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

                    <!-- Toko Aktif -->
                    <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-1 sm:space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Toko Aktif</span>
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

                        <Link
                            :href="safeRoute('admin.sellers.index')"
                            class="text-xs font-black text-orange-600 hover:underline uppercase flex items-center gap-1.5 cursor-pointer"
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
                                    <th class="p-3 sm:p-3.5">Nama Toko</th>
                                    <th class="p-3 sm:p-3.5 rounded-r-xl">No WhatsApp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-100">
                                <tr
                                    v-for="seller in recentSellers"
                                    :key="seller.user_id || seller.username"
                                    class="hover:bg-stone-50/50 transition"
                                >
                                    <!-- SISWA PENJUAL -->
                                    <td class="py-3 sm:py-4 px-3 sm:px-3.5 font-black text-xs uppercase text-gray-900">
                                        {{ seller.data_penjual?.nama_lengkap || seller.dataPenjual?.nama_lengkap || seller.username }}
                                        <span class="block text-[10px] font-bold text-orange-600">@{{ seller.username }}</span>
                                    </td>

                                    <!-- NAMA TOKO -->
                                    <td class="py-3 sm:py-4 px-3 sm:px-3.5 text-xs font-bold text-gray-700 uppercase">
                                        {{ seller.data_penjual?.nama_toko || seller.dataPenjual?.nama_toko || '-' }}
                                    </td>

                                    <!-- WHATSAPP -->
                                    <td class="py-3 sm:py-4 px-3 sm:px-3.5 text-xs font-bold text-emerald-600">
                                        {{ seller.data_penjual?.nomor_whatsapp || seller.dataPenjual?.nomor_whatsapp || '-' }}
                                    </td>
                                </tr>

                                <tr v-if="!recentSellers || recentSellers.length === 0">
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