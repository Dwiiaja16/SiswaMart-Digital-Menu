<script setup>
import { ref, watch } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: {
        type: Object,
        default: () => ({ user: { username: 'Penjual', whatsapp_number: '', google_id: null, email: null } })
    },
    shop: {
        type: Object,
        default: () => ({ name: 'Toko Wirausaha', is_open: false })
    },
    stats: {
        type: Object,
        default: () => ({
            total_products: 0,
            ready_count: 0,
            pre_order_count: 0,
            out_of_stock_count: 0,
            total_reviews: 0,
            avg_rating: '0.0'
        })
    },
    recentProducts: {
        type: Array,
        default: () => []
    },
    recentReviews: {
        type: Array,
        default: () => []
    }
});

// Helper aman untuk path gambar thumbnail
const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    if (imagePath.startsWith('http')) return imagePath;
    return imagePath.startsWith('/') ? imagePath : '/' + imagePath;
};

// Saklar cepat Buka/Tutup Toko
const toggleShopForm = useForm({
    is_open: props.shop?.is_open ?? false,
});

const toggleShop = () => {
    toggleShopForm.post(route('shop.toggle-status'), {
        preserveScroll: true,
    });
};

// State Modal Profile Saya & Edit Akun
const showProfileModal = ref(false);

// Lock scroll dan interaksi halaman belakang saat modal terbuka
watch(showProfileModal, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'auto';
    }
});

const editForm = useForm({
    username: props.auth?.user?.username || '',
    whatsapp_number: props.auth?.user?.whatsapp_number || '',
    shop_name: props.shop?.name || '',
    name: props.shop?.name || '',
    current_password: '', // Password Lama
    password: '',         // Password Baru
});

const updateProfile = () => {
    editForm.patch('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            showProfileModal.value = false;
            editForm.reset('password', 'current_password');
        },
        onError: (errors) => {
            console.log("Error Validasi:", errors);
        }
    });
};

const stockLabel = (status) => {
    if (status === 'ready') return 'Ready';
    if (status === 'pre_order') return 'Pre-Order';
    return 'Habis';
};

// State toggle lihat/sembunyikan password
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
</script>

<template>
    <Head title="Dashboard Penjual - SiswaMart" />

    <AuthenticatedLayout>
        <div class="space-y-6 sm:space-y-8 pb-12">

            <!-- 1. BANNER STATUS TOKO NEO-BRUTALISM -->
            <div class="bg-amber-400 border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col md:flex-row justify-between items-start md:items-center gap-4 sm:gap-6">
                <div>
                    <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest bg-gray-900 text-white px-2.5 py-1 rounded-md shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                        Status Toko Wirausaha
                    </span>
                    <h1 class="text-xl sm:text-3xl font-black uppercase tracking-tight text-gray-900 mt-2">
                        {{ shop?.name || 'Toko Belum Dinamai' }}
                    </h1>
                    <p class="text-xs font-bold text-gray-900 mt-1 opacity-90">
                        Halo <b>{{ auth?.user?.username }}</b>, atur status Toko kamu agar pembeli tahu kapan jajanan siap dipesan via WhatsApp.
                    </p>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 w-full md:w-auto">
                    <button
                        @click="showProfileModal = true"
                        class="px-3.5 sm:px-5 py-3 border-3 border-gray-900 bg-white hover:bg-stone-100 text-gray-900 font-black text-xs uppercase tracking-wider rounded-2xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-1.5 shrink-0"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Profile Saya</span>
                    </button>

                    <button
                        @click="toggleShop"
                        :disabled="toggleShopForm.processing"
                        :class="shop?.is_open ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-rose-400 hover:bg-rose-300'"
                        class="w-full sm:w-auto px-5 sm:px-6 py-3 border-3 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-2xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-2"
                    >
                        <span class="w-3 h-3 rounded-full border border-gray-900" :class="shop?.is_open ? 'bg-emerald-900 animate-pulse' : 'bg-rose-900'"></span>
                        Status Toko: {{ shop?.is_open ? 'BUKA' : 'TUTUP' }}
                    </button>
                </div>
            </div>

            <!-- 2. GRID KARTU STATISTIK UTAMA -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-6">
                
                <!-- Total Menu Produk -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-3 sm:space-y-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-2 bg-orange-500 border-b-2 border-gray-900"></div>

                    <div class="flex justify-between items-start pt-1">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Inventory</span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 mt-0.5">Total Menu Produk</h3>
                        </div>
                        <div class="w-10 h-10 bg-orange-400 border-2 border-gray-900 rounded-2xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="text-3xl font-black text-gray-900 tracking-tight">{{ stats?.total_products ?? 0 }} <span class="text-xs font-black text-gray-400 uppercase">Item</span></div>
                        
                        <div class="flex items-center gap-1.5 mt-3 flex-wrap">
                            <span class="bg-emerald-100 text-emerald-900 border border-gray-900 text-[9px] font-black px-2 py-0.5 rounded-lg uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                Ready: {{ stats?.ready_count ?? 0 }}
                            </span>
                            <span class="bg-amber-100 text-amber-900 border border-gray-900 text-[9px] font-black px-2 py-0.5 rounded-lg uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                PO: {{ stats?.pre_order_count ?? 0 }}
                            </span>
                            <span class="bg-rose-100 text-rose-900 border border-gray-900 text-[9px] font-black px-2 py-0.5 rounded-lg uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                Habis: {{ stats?.out_of_stock_count ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Ulasan Masuk -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-3 sm:space-y-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-2 bg-amber-400 border-b-2 border-gray-900"></div>

                    <div class="flex justify-between items-start pt-1">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Feedback</span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 mt-0.5">Ulasan Masuk</h3>
                        </div>
                        <div class="w-10 h-10 bg-amber-400 border-2 border-gray-900 rounded-2xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="text-3xl font-black text-gray-900 tracking-tight">{{ stats?.total_reviews ?? 0 }} <span class="text-xs font-black text-gray-400 uppercase">Testimoni</span></div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mt-2">Ditinjau oleh pembeli</p>
                    </div>
                </div>

                <!-- Rating Toko -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-3 sm:space-y-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-2 bg-sky-400 border-b-2 border-gray-900"></div>

                    <div class="flex justify-between items-start pt-1">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Reputasi</span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 mt-0.5">Rating Toko</h3>
                        </div>
                        <div class="w-10 h-10 bg-sky-400 border-2 border-gray-900 rounded-2xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-5 h-5 fill-amber-300 stroke-gray-900" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="text-3xl font-black text-gray-900 tracking-tight flex items-baseline gap-1">
                            {{ stats?.avg_rating ?? '0.0' }}
                            <span class="text-xs font-black text-amber-500 uppercase">★ / 5.0</span>
                        </div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider mt-2">Kualitas & Layanan Toko</p>
                    </div>
                </div>

            </div>

            <!-- 3. PREVIEW ETALASE & ULASAN TERKINI -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
                
                <!-- Etalase Menu Terkini -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4 sm:space-y-5">
                    <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-black uppercase tracking-tight">Etalase Menu Terkini</h2>
                            <p class="text-[10px] sm:text-[11px] font-bold text-gray-400 uppercase">Ringkasan status jajanan kamu</p>
                        </div>
                        <Link :href="route('penjual.products.index')" class="text-xs font-black text-orange-600 hover:underline uppercase">
                            Lihat Semua
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="product in (recentProducts || [])"
                            :key="product.id"
                            class="p-3.5 bg-stone-50 border-2 border-gray-900 rounded-2xl flex items-center justify-between shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-12 h-12 bg-white border-2 border-gray-900 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center">
                                    <img v-if="product.image" :src="getImageUrl(product.image)" loading="lazy" class="w-full h-full object-cover" />
                                    <svg v-else class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l1.5-3h15L21 7M3 7h18M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-black text-xs uppercase text-gray-900 truncate">{{ product.name }}</h4>
                                    <span class="text-orange-600 font-black text-xs">Rp {{ Number(product.price || 0).toLocaleString('id-ID') }}</span>
                                </div>
                            </div>
                            <span
                                :class="{
                                    'bg-emerald-100 text-emerald-900': product.stock_status === 'ready',
                                    'bg-amber-100 text-amber-900': product.stock_status === 'pre_order',
                                    'bg-rose-100 text-rose-900': product.stock_status === 'out_of_stock'
                                }"
                                class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg border border-gray-900 shrink-0 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                            >
                                {{ stockLabel(product.stock_status) }}
                            </span>
                        </div>
                        
                        <div v-if="!recentProducts || recentProducts.length === 0" class="text-center py-8 text-gray-400 font-bold text-xs uppercase">
                            Belum ada produk di etalase.
                        </div>
                    </div>
                </div>

                <!-- Ulasan Pembeli Terbaru -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4 sm:space-y-5">
                    <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                        <div>
                            <h2 class="text-base sm:text-lg font-black uppercase tracking-tight">Ulasan Pembeli Terbaru</h2>
                            <p class="text-[10px] sm:text-[11px] font-bold text-gray-400 uppercase">Testimoni masuk dari siswa</p>
                        </div>
                        <Link :href="route('reviews.index')" class="text-xs font-black text-orange-600 hover:underline uppercase">
                            Kelola Ulasan
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="rev in (recentReviews || [])"
                            :key="rev.id"
                            class="p-3.5 bg-stone-50 border-2 border-gray-900 rounded-2xl space-y-1 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <div class="flex justify-between items-center">
                                <span class="font-black text-xs uppercase text-gray-900">{{ rev.reviewer_name }}</span>
                                <span class="bg-amber-100 text-amber-900 border border-gray-900 text-[10px] px-2 py-0.5 rounded-md font-black flex items-center gap-1 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                    <svg class="w-3 h-3 text-amber-500 fill-amber-400" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                    <span>{{ rev.rating }}/5</span>
                                </span>
                            </div>
                            <p class="text-xs font-medium text-gray-700 italic line-clamp-2">"{{ rev.comment }}"</p>
                            <div class="text-[10px] font-black text-gray-400 uppercase pt-1">
                                Pada Produk: <span class="text-gray-900">{{ rev.product?.name || 'Produk' }}</span>
                            </div>
                        </div>

                        <div v-if="!recentReviews || recentReviews.length === 0" class="text-center py-8 text-gray-400 font-bold text-xs uppercase">
                            Belum ada ulasan yang masuk.
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- MODAL PROFILE SAYA (TERKUNCI: SIDEBAR & SCROLL BELAKANG TIDAK BISA DIKLIK) -->
        <div v-if="showProfileModal" class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/60 p-4 pointer-events-auto">
            
            <!-- Backdrop Clickable -->
            <div @click="showProfileModal = false" class="fixed inset-0"></div>

            <!-- Card Content Modal (pointer-events-auto agar isi modal tetap bisa diklik) -->
            <div class="relative bg-white border-4 border-gray-900 rounded-3xl w-full max-w-md p-6 sm:p-8 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-5 max-h-[90vh] overflow-y-auto z-10 pointer-events-auto">
                
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                    <h3 class="text-lg font-black uppercase text-gray-900">Profile Saya</h3>
                    <button @click="showProfileModal = false" class="w-8 h-8 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 rounded-xl flex items-center justify-center font-black text-gray-900 transition cursor-pointer">✕</button>
                </div>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <!-- 1. USERNAME PENJUAL -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-black uppercase text-gray-700">Username Penjual</label>
                        <input 
                            v-model="editForm.username" 
                            type="text" 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:border-orange-500" 
                            required 
                        />
                        <div v-if="editForm.errors.username" class="text-rose-600 text-[10px] font-bold">{{ editForm.errors.username }}</div>
                    </div>

                    <!-- 2. NAMA TOKO USAHA -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-black uppercase text-gray-700">Nama Toko Usaha</label>
                        <input 
                            v-model="editForm.shop_name" 
                            @input="editForm.name = editForm.shop_name"
                            type="text" 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:border-orange-500" 
                            required 
                        />
                        <div v-if="editForm.errors.shop_name" class="text-rose-600 text-[10px] font-bold">{{ editForm.errors.shop_name }}</div>
                    </div>

                    <!-- 3. NOMOR WHATSAPP AKTIF -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-black uppercase text-gray-700">Nomor WhatsApp Aktif</label>
                        <input 
                            v-model="editForm.whatsapp_number" 
                            type="text" 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:border-orange-500" 
                            required 
                        />
                        <div v-if="editForm.errors.whatsapp_number" class="text-rose-600 text-[10px] font-bold">{{ editForm.errors.whatsapp_number }}</div>
                    </div>

                    <!-- 4. UBAH PASSWORD -->
                    <div class="border-t-2 border-gray-100 pt-3 space-y-3">
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">
                            Ganti Password (Opsional)
                        </span>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-black uppercase text-gray-700">
                                Password Baru
                            </label>
                            <div class="relative flex items-center">
                                <input 
                                    v-model="editForm.password" 
                                    :type="showNewPassword ? 'text' : 'password'" 
                                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl pl-4 pr-11 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:border-orange-500 transition" 
                                    placeholder="Ketik jika ingin ganti password..." 
                                />
                                <button 
                                    type="button" 
                                    @click="showNewPassword = !showNewPassword"
                                    class="absolute right-3 text-gray-500 hover:text-gray-900 focus:outline-none p-1 cursor-pointer"
                                >
                                    <svg v-if="showNewPassword" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                        <path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                        <line x1="2" y1="2" x2="22" y2="22"/>
                                    </svg>
                                </button>
                            </div>
                            <div v-if="editForm.errors.password" class="text-rose-600 text-[10px] font-bold">
                                {{ editForm.errors.password }}
                            </div>
                        </div>

                        <div v-if="editForm.password" class="space-y-1.5 bg-amber-100 border-2 border-gray-900 rounded-2xl p-3">
                            <div class="flex justify-between items-center">
                                <label class="block text-xs font-black uppercase text-gray-900">
                                    Password Saat Ini <span class="text-rose-600">*</span>
                                </label>
                                <span class="text-[9px] font-black uppercase bg-gray-900 text-white px-1.5 py-0.5 rounded">
                                    Konfirmasi Keamanan
                                </span>
                            </div>
                            <div class="relative flex items-center">
                                <input 
                                    v-model="editForm.current_password" 
                                    :type="showCurrentPassword ? 'text' : 'password'" 
                                    class="w-full bg-white border-2 border-gray-900 rounded-xl pl-4 pr-11 py-2 text-xs font-bold text-gray-900 focus:border-orange-500" 
                                    placeholder="Masukkan password lama kamu..." 
                                    :required="!!editForm.password"
                                />
                                <button 
                                    type="button" 
                                    @click="showCurrentPassword = !showCurrentPassword"
                                    class="absolute right-3 text-gray-500 hover:text-gray-900 focus:outline-none p-1 cursor-pointer"
                                >
                                    <svg v-if="showCurrentPassword" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                        <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                        <path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                        <line x1="2" y1="2" x2="22" y2="22"/>
                                    </svg>
                                </button>
                            </div>
                            <div v-if="editForm.errors.current_password" class="text-rose-600 text-[10px] font-bold">
                                {{ editForm.errors.current_password }}
                            </div>
                        </div>
                    </div>

                    <!-- 5. INTEGRASI GOOGLE OAUTH -->
                    <div class="bg-stone-100 border-2 border-gray-900 rounded-2xl p-3.5 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black uppercase text-gray-900">AKUN GMAIL GOOGLE</span>
                            <span 
                                :class="auth?.user?.google_id ? 'bg-sky-200 text-sky-900 border-sky-900' : 'bg-amber-200 text-amber-900 border-amber-900'"
                                class="px-2 py-0.5 text-[8px] font-black uppercase rounded border-2"
                            >
                                {{ auth?.user?.google_id ? 'TERTAUT' : 'BELUM TERTAUT' }}
                            </span>
                        </div>
                        
                        <p class="text-[10px] font-bold text-gray-500 leading-tight">
                            {{ auth?.user?.email ? 'Email: ' + auth?.user?.email : 'Belum ada email Gmail yang tertaut.' }}
                        </p>

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <a 
                                :href="route('google.login')" 
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white hover:bg-stone-50 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                {{ auth?.user?.google_id ? 'GANTI AKUN GOOGLE' : 'TAUTKAN AKUN GOOGLE' }}
                            </a>

                            <button 
                                v-if="auth?.user?.google_id"
                                @click="router.post(route('google.disconnect'))"
                                type="button"
                                class="px-3 py-1.5 bg-rose-400 hover:bg-rose-500 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition cursor-pointer"
                            >
                                Putuskan
                            </button>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI SIMPAN / BATAL -->
                    <div class="flex gap-3 pt-2">
                        <button 
                            type="button" 
                            @click="showProfileModal = false"
                            class="flex-1 py-3 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="editForm.processing"
                            class="flex-1 py-3 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition cursor-pointer"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </AuthenticatedLayout>
</template>