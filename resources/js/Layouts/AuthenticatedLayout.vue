<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const user = page.props.auth.user;

// State Notifikasi untuk Admin
const notifications = ref([]);
const unreadCount = ref(0);
const showingNotifDropdown = ref(false);
let pollTimer = null;

// State Dropdown & Modal Logout
const showingUserDropdown = ref(false);
const showLogoutModal = ref(false);

async function fetchNotifications() {
    if (user?.role !== 'admin') return;
    try {
        const res = await axios.get(route('notifications.index'));
        notifications.value = res.data.notifications || [];
        unreadCount.value = res.data.unread_count || 0;
    } catch (e) {
        console.error('Gagal mengambil notifikasi:', e);
    }
}

onMounted(() => {
    if (user?.role === 'admin') {
        fetchNotifications();
        pollTimer = setInterval(fetchNotifications, 15000); // Polling tiap 15 detik
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const handleLogout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="min-h-screen bg-stone-50 font-sans selection:bg-orange-500 selection:text-white relative">
        
        <!-- Navbar Header Utama Neo-Brutalism -->
        <nav class="bg-white border-b-4 border-gray-900 sticky top-0 z-40 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto flex justify-between items-center h-16">
                
                <!-- Brand & Role Badge -->
                <div class="flex items-center gap-3">
                    <Link href="/dashboard" class="text-2xl font-black uppercase tracking-tighter hover:text-orange-500 transition">
                        Siswa<span class="text-orange-500">Mart</span>
                    </Link>

                    <span 
                        :class="user.role === 'admin' ? 'bg-amber-100 text-amber-900' : 'bg-orange-100 text-orange-900'"
                        class="border-2 border-gray-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-md shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                    >
                        {{ user.role === 'admin' ? 'Panel Admin' : 'Lapak Penjual' }}
                    </span>
                </div>

                <!-- NAVBAR KANAN (KONDISIONAL ADMIN VS PENJUAL) -->
                <div class="flex items-center gap-3">

                    <!-- ================= 1. AKSES UNTUK ADMIN ================= -->
                    <template v-if="user.role === 'admin'">
                        
                        <!-- Notifikasi Lonceng AJAX Polling Admin -->
                        <div class="relative">
                            <button
                                @click="showingNotifDropdown = !showingNotifDropdown"
                                type="button"
                                class="relative p-2.5 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 rounded-xl text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center"
                                title="Notifikasi Produk Baru"
                            >
                                <svg class="h-5 w-5 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>

                                <span
                                    v-if="unreadCount > 0"
                                    class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white border-2 border-gray-900 animate-pulse"
                                >
                                    {{ unreadCount }}
                                </span>
                            </button>

                            <!-- Backdrop Click Away Lonceng -->
                            <div v-if="showingNotifDropdown" @click="showingNotifDropdown = false" class="fixed inset-0 z-40"></div>

                            <!-- Dropdown List Notifikasi -->
                            <div
                                v-if="showingNotifDropdown"
                                class="absolute right-0 z-50 mt-3 w-80 rounded-2xl border-4 border-gray-900 bg-white p-3 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-2"
                            >
                                <div class="border-b-2 border-gray-900 pb-2 px-1 flex justify-between items-center">
                                    <span class="text-xs font-black uppercase tracking-wider text-gray-900">Notifikasi Produk Baru</span>
                                    <span class="bg-orange-500 text-gray-900 border border-gray-900 text-[10px] font-black px-2 py-0.5 rounded-md shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                        {{ unreadCount }} Baru
                                    </span>
                                </div>

                                <div v-if="notifications.length === 0" class="py-6 text-center text-xs font-black uppercase text-gray-400">
                                    Belum ada notifikasi
                                </div>

                                <div v-else class="max-h-64 overflow-y-auto space-y-2 pr-1">
                                    <Link
                                        v-for="n in notifications"
                                        :key="n.id"
                                        :href="route('notifications.read', n.id)"
                                        method="patch"
                                        as="button"
                                        @click="showingNotifDropdown = false"
                                        class="flex w-full items-start gap-2.5 p-2.5 rounded-xl border-2 border-gray-900 text-left text-xs transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-amber-100"
                                        :class="n.is_read ? 'bg-stone-50 opacity-70' : 'bg-amber-400 font-bold'"
                                    >
                                        <span
                                            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full border border-gray-900"
                                            :class="n.is_read ? 'bg-stone-400' : 'bg-rose-500 animate-ping'"
                                        ></span>
                                        <div class="leading-snug">
                                            <span class="font-black uppercase text-gray-900">Produk Baru: {{ n.product?.name }}</span>
                                            <p class="text-[10px] font-bold text-gray-700 mt-0.5">Dari toko: {{ n.product?.shop?.name || 'Wirausaha' }}</p>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown User Admin -->
                        <div class="relative">
                            <button
                                @click="showingUserDropdown = !showingUserDropdown"
                                type="button"
                                class="px-4 py-2 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-2"
                            >
                                <span>{{ user.username || user.name }}</span>
                                <svg 
                                    class="w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': showingUserDropdown }"
                                    fill="none" 
                                    viewBox="0 0 24 24" 
                                    stroke="currentColor" 
                                    stroke-width="2.5"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <!-- Backdrop Click Away User Dropdown -->
                            <div v-if="showingUserDropdown" @click="showingUserDropdown = false" class="fixed inset-0 z-40"></div>

                            <!-- Dropdown Menu Content -->
                            <div
                                v-if="showingUserDropdown"
                                class="absolute right-0 mt-2 w-52 bg-white border-4 border-gray-900 rounded-2xl shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] py-2 z-50 space-y-1"
                            >
                                <Link
                                    :href="route('profile.edit')"
                                    @click="showingUserDropdown = false"
                                    class="block px-4 py-2 text-xs font-black uppercase text-gray-900 hover:bg-amber-400 transition"
                                >
                                    Profile Saya
                                </Link>

                                <div class="border-t-2 border-gray-900 my-1"></div>

                                <button
                                    type="button"
                                    @click="showingUserDropdown = false; showLogoutModal = true;"
                                    class="w-full text-left px-4 py-2 text-xs font-black uppercase text-rose-600 hover:bg-rose-100 transition"
                                >
                                    Keluar
                                </button>
                            </div>
                        </div>

                    </template>

                    <!-- ================= 2. AKSES UNTUK PENJUAL ================= -->
                    <template v-else>
                        <button
                            @click="showLogoutModal = true"
                            type="button"
                            class="px-4 py-2 bg-rose-400 hover:bg-rose-300 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                        >
                            Keluar
                        </button>
                    </template>

                </div>

            </div>
        </nav>

        <!-- Sub-Header Slot jika ada -->
        <header class="bg-white border-b-4 border-gray-900 shadow-sm" v-if="$slots.header">
            <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <slot />
        </main>

        <!-- MODAL KONFIRMASI KELUAR (LOGOUT KELUAR) -->
        <div v-if="showLogoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl w-full max-w-sm p-6 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-4 text-center">
                
                <div class="w-12 h-12 bg-rose-100 border-2 border-gray-900 rounded-2xl flex items-center justify-center mx-auto text-rose-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-black uppercase text-gray-900 tracking-tight">Konfirmasi Keluar</h3>
                    <p class="text-xs font-bold text-gray-500 mt-1">
                        Apakah kamu yakin ingin keluar dari akun ini?
                    </p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button 
                        type="button" 
                        @click="showLogoutModal = false"
                        class="flex-1 py-3 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl transition"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="handleLogout"
                        class="flex-1 py-3 bg-rose-500 hover:bg-rose-400 border-2 border-gray-900 text-white font-black text-xs uppercase tracking-widest rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                    >
                        Keluar
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>