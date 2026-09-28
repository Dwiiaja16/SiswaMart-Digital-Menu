<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

// State Sidebar & Mobile Drawer
const isMobileSidebarOpen = ref(false);

// State Notifikasi untuk Admin
const notifications = ref([]);
const unreadCount = ref(0);
const showingNotifDropdown = ref(false);
let pollTimer = null;

// State Dropdown User & Modal Logout
const showingUserDropdown = ref(false);
const showLogoutModal = ref(false);

// Fetch notifikasi admin via polling
async function fetchNotifications() {
    if (user.value?.role !== 'admin') return;
    try {
        const res = await axios.get(route('admin.notifications.index'));
        notifications.value = res.data.notifications || [];
        unreadCount.value = res.data.unread_count || 0;
    } catch (e) {
        // Silent catch
    }
}

const handleNotificationClick = (notif) => {
    showingNotifDropdown.value = false;
    if (!notif.is_read) {
        notif.is_read = true;
        if (unreadCount.value > 0) unreadCount.value--;
    }
    router.patch(route('admin.notifications.read', notif.id));
};

onMounted(() => {
    if (user.value?.role === 'admin') {
        fetchNotifications();
        pollTimer = setInterval(fetchNotifications, 15000);
    }
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});

const handleLogout = () => {
    router.post(route('logout'));
};

// Menu Navigasi Admin Neo-Brutalism
const adminNavItems = [
    {
        name: 'Dashboard Utama',
        routeName: 'dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        color: 'bg-stone-100 hover:bg-stone-200',
    },
    {
        name: 'Kelola Penjual',
        routeName: 'admin.sellers.index',
        icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        color: 'bg-orange-500 hover:bg-orange-400 text-white',
    },
    {
        name: 'Kelola Kategori',
        routeName: 'admin.categories.index',
        icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        color: 'bg-amber-400 hover:bg-amber-300',
    },
    {
        name: 'Moderasi Produk',
        routeName: 'admin.products.index',
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        color: 'bg-sky-400 hover:bg-sky-300',
    },
    {
        name: 'Rekap Toko',
        routeName: 'admin.recap',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        color: 'bg-emerald-400 hover:bg-emerald-300',
    },
    {
        name: 'Katalog Publik',
        routeName: 'catalog.index',
        icon: 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
        color: 'bg-stone-200 hover:bg-stone-300',
        external: true,
    },
];

// Menu Navigasi Penjual Neo-Brutalism
const sellerNavItems = [
    {
        name: 'Dashboard Toko',
        routeName: 'dashboard',
        icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        color: 'bg-amber-400 hover:bg-amber-300',
    },
    {
        name: 'Kelola Produk',
        routeName: 'products.index',
        icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        color: 'bg-orange-500 hover:bg-orange-400 text-white',
    },
    {
        name: 'Ulasan & Rating',
        routeName: 'reviews.index',
        icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        color: 'bg-sky-400 hover:bg-sky-300',
    },
    {
        name: 'Katalog Publik',
        routeName: 'catalog.index',
        icon: 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
        color: 'bg-stone-100 hover:bg-stone-200',
        external: true,
    },
];

// Daftar Navigasi Aktif berdasarkan Role
const currentNavItems = computed(() => {
    return user.value?.role === 'admin' ? adminNavItems : sellerNavItems;
});

// Helper Pengecekan Menu Aktif
const isRouteActive = (routeName) => {
    try {
        if (!routeName) return false;
        return route().current(routeName) || route().current(routeName + '.*');
    } catch {
        return false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-stone-100 font-sans selection:bg-orange-500 selection:text-white flex flex-col w-full relative overflow-x-hidden">
        
        <!-- NAVBAR HEADER UTAMA (FULL WIDTH TANPA BORDER TERLEPAS) -->
        <nav class="bg-white border-b-4 border-gray-900 z-30 px-4 sm:px-6 shrink-0 w-full">
            <div class="w-full flex justify-between items-center h-16">
                
                <!-- BRAND & TOGGLE SIDEBAR -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <button 
                        @click="isMobileSidebarOpen = !isMobileSidebarOpen"
                        type="button"
                        class="lg:hidden p-2 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 rounded-xl text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                        aria-label="Toggle Navigation"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <Link href="/dashboard" class="text-xl sm:text-2xl font-black uppercase tracking-tighter hover:text-orange-500 transition">
                        Siswa<span class="text-orange-500">Mart</span>
                    </Link>

                    <span 
                        :class="user.role === 'admin' ? 'bg-amber-100 text-amber-900' : 'bg-orange-100 text-orange-900'"
                        class="hidden sm:inline-block border-2 border-gray-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-md shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                    >
                        {{ user.role === 'admin' ? 'Panel Admin' : 'Toko Penjual' }}
                    </span>
                </div>

                <!-- NAVBAR KANAN -->
                <div class="flex items-center gap-2 sm:gap-3">
                    
                    <!-- Notifikasi (Admin) -->
                    <template v-if="user.role === 'admin'">
                        <div class="relative">
                            <button
                                @click="showingNotifDropdown = !showingNotifDropdown"
                                type="button"
                                class="relative p-2 sm:p-2.5 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 rounded-xl text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center"
                                title="Notifikasi Produk Baru"
                            >
                                <svg class="h-4 w-4 sm:h-5 sm:w-5 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span
                                    v-if="unreadCount > 0"
                                    class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white border-2 border-gray-900 animate-pulse"
                                >
                                    {{ unreadCount }}
                                </span>
                            </button>

                            <div v-if="showingNotifDropdown" @click="showingNotifDropdown = false" class="fixed inset-0 z-40 bg-black/20 sm:bg-transparent"></div>

                            <div
                                v-if="showingNotifDropdown"
                                class="fixed sm:absolute top-16 sm:top-auto right-3 sm:right-0 left-3 sm:left-auto z-50 mt-2 sm:mt-3 w-auto sm:w-80 rounded-2xl border-4 border-gray-900 bg-white p-3 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-2"
                            >
                                <div class="border-b-2 border-gray-900 pb-2 px-1 flex justify-between items-center">
                                    <span class="text-xs font-black uppercase tracking-wider text-gray-900">Notifikasi Produk Baru</span>
                                    <span class="bg-orange-500 text-gray-900 border border-gray-900 text-[9px] sm:text-[10px] font-black px-2 py-0.5 rounded-md shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                        {{ unreadCount }} Baru
                                    </span>
                                </div>

                                <div v-if="notifications.length === 0" class="py-6 text-center text-xs font-black uppercase text-gray-400">
                                    Belum ada notifikasi
                                </div>

                                <div v-else class="max-h-60 sm:max-h-64 overflow-y-auto space-y-2 pr-1">
                                    <button
                                        v-for="n in notifications"
                                        :key="n.id"
                                        type="button"
                                        @click="handleNotificationClick(n)"
                                        class="flex w-full items-start gap-2.5 p-2.5 rounded-xl border-2 border-gray-900 text-left text-xs transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-amber-100 cursor-pointer"
                                        :class="n.is_read ? 'bg-stone-50 opacity-70' : 'bg-amber-400 font-bold'"
                                    >
                                        <span
                                            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full border border-gray-900"
                                            :class="n.is_read ? 'bg-stone-400' : 'bg-rose-500 animate-ping'"
                                        ></span>
                                        <div class="leading-snug">
                                            <span class="font-black uppercase text-gray-900 block text-[11px] sm:text-xs">
                                                Produk Baru: {{ n.product?.name || 'Produk' }}
                                            </span>
                                            <p class="text-[10px] font-bold text-gray-700 mt-0.5">
                                                Dari toko: {{ n.product?.shop?.name || 'Wirausaha' }}
                                            </p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Dropdown User -->
                    <div class="relative">
                        <button
                            @click="showingUserDropdown = !showingUserDropdown"
                            type="button"
                            class="px-3 sm:px-4 py-2 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-2"
                        >
                            <span class="truncate max-w-[120px] sm:max-w-none">{{ user.username || user.name }}</span>
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

                        <div v-if="showingUserDropdown" @click="showingUserDropdown = false" class="fixed inset-0 z-40"></div>

                        <div
                            v-if="showingUserDropdown"
                            class="absolute right-0 mt-2 w-52 bg-white border-4 border-gray-900 rounded-2xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] py-2 z-50 space-y-1"
                        >
                            <div class="px-4 py-1.5 border-b-2 border-gray-900 mb-1">
                                <p class="text-xs font-black uppercase text-gray-900 truncate">{{ user.username || user.name }}</p>
                                <p class="text-[9px] font-bold text-gray-500 uppercase">{{ user.role }} SiswaMart</p>
                            </div>

                            <button
                                type="button"
                                @click="showingUserDropdown = false; showLogoutModal = true;"
                                class="w-full text-left px-4 py-2 text-xs font-black uppercase text-rose-600 hover:bg-rose-100 transition"
                            >
                                Keluar
                            </button>
                        </div>
                    </div>

                </div>

            </div>
        </nav>

        <!-- AREA KONTEN DAN SIDEBAR -->
        <div class="flex flex-1 w-full relative items-stretch">
            
            <!-- SIDEBAR DESKTOP -->
            <aside class="hidden lg:block w-64 bg-white border-r-4 border-gray-900 shrink-0 z-20 p-4 space-y-4">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 px-1 block">
                    {{ user.role === 'admin' ? 'NAVIGASI PINTAS ADMIN' : 'NAVIGASI PINTAS PENJUAL' }}
                </span>

                <nav class="space-y-2.5">
                    <template v-for="item in currentNavItems" :key="item.routeName">
                        <a
                            v-if="item.external"
                            :href="route(item.routeName)"
                            target="_blank"
                            class="flex items-center justify-between px-3.5 py-3 rounded-xl border-2 border-gray-900 font-black text-xs uppercase tracking-wider transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95"
                            :class="item.color + ' text-gray-900'"
                        >
                            <div class="flex items-center gap-3">
                                <svg class="w-4 h-4 shrink-0 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span class="truncate">{{ item.name }}</span>
                            </div>
                            <svg class="w-3.5 h-3.5 shrink-0 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>

                        <Link
                            v-else
                            :href="route(item.routeName)"
                            class="flex items-center gap-3 px-3.5 py-3 rounded-xl border-2 border-gray-900 font-black text-xs uppercase tracking-wider transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95"
                            :class="[
                                isRouteActive(item.routeName) 
                                    ? 'bg-amber-400 text-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] font-black' 
                                    : item.color + ' text-gray-900'
                            ]"
                        >
                            <svg class="w-4 h-4 shrink-0 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                            </svg>
                            <span class="truncate">{{ item.name }}</span>
                        </Link>
                    </template>
                </nav>
            </aside>

            <!-- SIDEBAR MOBILE DRAWER -->
            <div v-if="isMobileSidebarOpen" class="fixed inset-0 z-50 lg:hidden flex">
                <div @click="isMobileSidebarOpen = false" class="fixed inset-0 bg-black/40"></div>
                
                <div class="relative bg-white w-72 border-r-4 border-gray-900 p-5 space-y-6 shadow-[8px_0px_0px_0px_rgba(17,24,39,1)] flex flex-col h-full z-50">
                    <div class="flex justify-between items-center border-b-2 border-gray-900 pb-3">
                        <span class="text-xs font-black uppercase tracking-wider text-gray-900">
                            {{ user.role === 'admin' ? 'MENU ADMIN' : 'MENU PENJUAL' }}
                        </span>
                        <button @click="isMobileSidebarOpen = false" class="p-1 border-2 border-gray-900 rounded-lg bg-rose-400 text-gray-900 font-bold">
                            ✕
                        </button>
                    </div>

                    <div class="space-y-2.5 flex-1 overflow-y-auto">
                        <template v-for="item in currentNavItems" :key="item.routeName">
                            <a
                                v-if="item.external"
                                :href="route(item.routeName)"
                                target="_blank"
                                class="flex items-center justify-between px-3.5 py-3 rounded-xl border-2 border-gray-900 font-black text-xs uppercase tracking-wider transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                                :class="item.color + ' text-gray-900'"
                            >
                                <div class="flex items-center gap-3">
                                    <svg class="w-4 h-4 shrink-0 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                    </svg>
                                    <span>{{ item.name }}</span>
                                </div>
                                <svg class="w-3.5 h-3.5 shrink-0 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>

                            <Link
                                v-else
                                :href="route(item.routeName)"
                                @click="isMobileSidebarOpen = false"
                                class="flex items-center gap-3 px-3.5 py-3 rounded-xl border-2 border-gray-900 font-black text-xs uppercase tracking-wider transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                                :class="[
                                    isRouteActive(item.routeName) 
                                        ? 'bg-amber-400 text-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' 
                                        : item.color + ' text-gray-800'
                                ]"
                            >
                                <svg class="w-4 h-4 shrink-0 stroke-gray-900" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                                </svg>
                                <span>{{ item.name }}</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT AREA -->
            <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8 z-10">
                <div class="max-w-7xl mx-auto space-y-6">
                    <slot />
                </div>
            </main>

        </div>

        <!-- MODAL LOGOUT -->
        <div v-if="showLogoutModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4">
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