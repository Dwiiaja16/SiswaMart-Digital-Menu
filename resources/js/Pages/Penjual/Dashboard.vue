<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    auth: {
        type: Object,
        default: () => ({ user: { username: 'Penjual', whatsapp_number: '', google_id: null, email: null } })
    },
    shop: {
        type: Object,
        default: () => ({ name: 'Lapak Wirausaha', is_open: false })
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

// Saklar cepat Buka/Tutup Lapak
const toggleShopForm = useForm({
    is_open: props.shop?.is_open ?? false,
});

const toggleShop = () => {
    toggleShopForm.post(route('shop.toggle-status'), {
        preserveScroll: true,
    });
};

// State Modal Edit Profil & Lapak
const showEditModal = ref(false);

const editForm = useForm({
    username: props.auth?.user?.username || '',
    whatsapp_number: props.auth?.user?.whatsapp_number || '',
    shop_name: props.shop?.name || '',
    name: props.shop?.name || '',
    current_password: '', // Konfirmasi Password Lama
    password: '',         // Password Baru
});

const updateProfile = () => {
    editForm.patch('/profile', {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset('password', 'current_password');
        },
        onError: (errors) => {
            console.log("Error Validasi:", errors);
        }
    });
};

// State & Function Modal Konfirmasi Keluar (Logout)
const showLogoutModal = ref(false);

const handleLogout = () => {
    router.post(route('logout'));
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

    <div class="min-h-screen bg-stone-50 text-gray-900 font-sans pb-16 selection:bg-orange-500 selection:text-white relative">

        <!-- Navbar Header Penjual -->
        <nav class="bg-white border-b-4 border-gray-900 px-4 sm:px-6 py-3.5 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto flex justify-between items-center gap-2">
                <div class="flex items-center gap-2 sm:gap-3">
                    <Link href="/" class="text-xl sm:text-2xl font-black uppercase tracking-tighter hover:text-orange-500 transition">
                        Siswa<span class="text-orange-500">Mart</span>
                    </Link>
                    <span class="bg-orange-100 border-2 border-gray-900 text-gray-900 text-[9px] sm:text-[10px] font-black uppercase px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-md shadow-sm">
                        Lapak
                    </span>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button
                        @click="showEditModal = true"
                        class="px-3 sm:px-4 py-2 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-sm active:scale-95 transition flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="hidden sm:inline">Edit Profil</span>
                        <span class="sm:hidden">Profil</span>
                    </button>

                    <button
                        @click="showLogoutModal = true"
                        type="button"
                        class="px-3 sm:px-4 py-2 bg-rose-400 hover:bg-rose-300 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-sm active:scale-95 transition"
                    >
                        Keluar
                    </button>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 mt-6 sm:mt-8 space-y-6 sm:space-y-8">

            <!-- Banner Status Lapak -->
            <div class="bg-amber-400 border-4 border-gray-900 rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col md:flex-row justify-between items-start md:items-center gap-4 sm:gap-6">
                <div>
                    <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest bg-gray-900 text-white px-2.5 py-1 rounded-md">
                        Status Lapak Wirausaha
                    </span>
                    <h1 class="text-xl sm:text-3xl font-black uppercase tracking-tight text-gray-900 mt-2">
                        {{ shop?.name || 'Lapak Belum Dinamai' }}
                    </h1>
                    <p class="text-xs font-bold text-gray-900 mt-1 opacity-90">
                        Halo <b>{{ auth?.user?.username }}</b>, atur status lapakmu agar pembeli tahu kapan jajanan siap dipesan via WhatsApp.
                    </p>
                </div>

                <button
                    @click="toggleShop"
                    :disabled="toggleShopForm.processing"
                    :class="shop?.is_open ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-rose-400 hover:bg-rose-300'"
                    class="w-full sm:w-auto px-5 sm:px-6 py-3 sm:py-3.5 border-3 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-2xl shadow-sm active:scale-95 transition flex items-center justify-center gap-2"
                >
                    <span class="w-3 h-3 rounded-full border border-gray-900" :class="shop?.is_open ? 'bg-emerald-900 animate-pulse' : 'bg-rose-900'"></span>
                    Status Lapak: {{ shop?.is_open ? 'BUKA' : 'TUTUP' }}
                </button>
            </div>

            <!-- Grid Ringkasan Statistik (2 KOLOM DI MOBILE) -->
            <div class="stat-grid-mobile grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-6">
                
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-3 sm:space-y-4 relative overflow-hidden">
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
                            <span class="bg-emerald-100 text-emerald-900 border border-gray-900 text-[9px] font-black px-2 py-0.5 rounded-lg uppercase">
                                Ready: {{ stats?.ready_count ?? 0 }}
                            </span>
                            <span class="bg-amber-100 text-amber-900 border border-gray-900 text-[9px] font-black px-2 py-0.5 rounded-lg uppercase">
                                PO: {{ stats?.pre_order_count ?? 0 }}
                            </span>
                            <span class="bg-rose-100 text-rose-900 border border-gray-900 text-[9px] font-black px-2 py-0.5 rounded-lg uppercase">
                                Habis: {{ stats?.out_of_stock_count ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-4 relative overflow-hidden">
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

                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-4 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-2 bg-sky-400 border-b-2 border-gray-900"></div>

                    <div class="flex justify-between items-start pt-1">
                        <div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Reputasi</span>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 mt-0.5">Rating Lapak</h3>
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

                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-3 relative overflow-hidden">
                    <div class="absolute top-0 left-0 right-0 h-2 bg-stone-900"></div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 pt-1">Menu Pintas</span>
                    
                    <div class="space-y-2">
                        <Link
                            :href="route('products.index')"
                            class="w-full flex items-center justify-center gap-2 py-2 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-[11px] uppercase tracking-wider rounded-xl text-center shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition"
                        >
                            Kelola Produk
                        </Link>
                        <Link
                            :href="route('reviews.index')"
                            class="w-full flex items-center justify-center gap-2 py-2 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-[11px] uppercase tracking-wider rounded-xl text-center shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] transition"
                        >
                            Moderasi Ulasan
                        </Link>
                        <Link
                            :href="route('catalog.index')"
                            target="_blank"
                            class="w-full flex items-center justify-center gap-1.5 py-2 bg-amber-300 hover:bg-amber-200 border-2 border-gray-900 text-gray-900 font-black text-[11px] uppercase tracking-wider rounded-xl text-center shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Lihat Katalog Publik
                        </Link>
                    </div>
                </div>

            </div>

            <!-- Preview Etalase & Ulasan -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-5">
                    <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                        <div>
                            <h2 class="text-lg font-black uppercase tracking-tight">Etalase Menu Terkini</h2>
                            <p class="text-[11px] font-bold text-gray-400 uppercase">Ringkasan status jajanan kamu</p>
                        </div>
                        <Link :href="route('products.index')" class="text-xs font-black text-orange-600 hover:underline uppercase">
                            Lihat Semua
                        </Link>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="product in (recentProducts || [])"
                            :key="product.id"
                            class="p-3.5 bg-stone-50 border-2 border-gray-900 rounded-2xl flex items-center justify-between shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-white border-2 border-gray-900 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center">
                                    <img v-if="product.image" :src="getImageUrl(product.image)" loading="lazy" class="w-full h-full object-cover" />
                                    <svg v-else class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7l1.5-3h15L21 7M3 7h18M3 7v11a2 2 0 002 2h14a2 2 0 002-2V7" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-xs uppercase text-gray-900 line-clamp-1">{{ product.name }}</h4>
                                    <span class="text-orange-600 font-black text-xs">Rp {{ Number(product.price || 0).toLocaleString('id-ID') }}</span>
                                </div>
                            </div>
                            <span
                                :class="{
                                    'bg-emerald-100 text-emerald-900': product.stock_status === 'ready',
                                    'bg-amber-100 text-amber-900': product.stock_status === 'pre_order',
                                    'bg-rose-100 text-rose-900': product.stock_status === 'out_of_stock'
                                }"
                                class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg border border-gray-900 shrink-0"
                            >
                                {{ stockLabel(product.stock_status) }}
                            </span>
                        </div>
                        <div v-if="!recentProducts || recentProducts.length === 0" class="text-center py-8 text-gray-400 font-bold text-xs uppercase">
                            Belum ada produk di etalase.
                        </div>
                    </div>
                </div>

                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-5">
                    <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                        <div>
                            <h2 class="text-lg font-black uppercase tracking-tight">Ulasan Pembeli Terbaru</h2>
                            <p class="text-[11px] font-bold text-gray-400 uppercase">Testimoni masuk dari siswa</p>
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
                                <span class="bg-amber-100 text-amber-900 border border-gray-900 text-[10px] px-2 py-0.5 rounded-md font-black flex items-center gap-1">
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

        </main>

        <!-- MODAL EDIT PROFIL & LAPAK -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl w-full max-w-md p-6 sm:p-8 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-5 max-h-[90vh] overflow-y-auto">
                
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                    <h3 class="text-lg font-black uppercase text-gray-900">Edit Profil & Lapak</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-900 font-black text-sm">✕</button>
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

                    <!-- 2. NAMA LAPAK USAHA -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-black uppercase text-gray-700">Nama Lapak Usaha</label>
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

                    <!-- 4. SEKSI KEAMANAN PASSWORD DENGAN IKON MATA -->
                    <div class="border-t-2 border-gray-100 pt-3 space-y-3">
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">
                            Ganti Password (Opsional)
                        </span>

                        <!-- INPUT PASSWORD BARU -->
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
                                    class="absolute right-3 text-gray-500 hover:text-gray-900 focus:outline-none p-1"
                                    title="Lihat / Sembunyikan Password"
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

                        <!-- INPUT PASSWORD LAMA (MUNCUL JIKA PASSWORD BARU MULA DIKETIK) -->
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
                                    class="absolute right-3 text-gray-500 hover:text-gray-900 focus:outline-none p-1"
                                    title="Lihat / Sembunyikan Password"
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

                    <!-- 5. INTEGRASI GMAIL / GOOGLE OAUTH -->
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

                        <!-- Tombol Aksi (Tautkan / Ganti Akun & Tombol Putuskan Tautan) -->
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

                            <!-- Tombol Putuskan Tautan (Hanya muncul jika sudah tertaut) -->
                            <button 
                                v-if="auth?.user?.google_id"
                                @click="router.post(route('google.disconnect'))"
                                type="button"
                                class="px-3 py-1.5 bg-rose-400 hover:bg-rose-500 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                            >
                                Putuskan
                            </button>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI SIMPAN / BATAL -->
                    <div class="flex gap-3 pt-2">
                        <button 
                            type="button" 
                            @click="showEditModal = false"
                            class="flex-1 py-3 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl transition"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            :disabled="editForm.processing"
                            class="flex-1 py-3 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- MODAL KONFIRMASI KELUAR (LOGOUT) -->
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
                        Apakah kamu yakin ingin keluar dari akun lapak ini?
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

<style scoped>
/* =========================================================
   MOBILE RESPONSIVE OVERRIDE — PENJUAL DASHBOARD
   ========================================================= */

@media (max-width: 640px) {
    /* 1. KUNCI SCREEN AGAR TIDAK BISA DI-SCROLL KE KANAN (OFFSIDE FIX) */
    :global(html), :global(body) {
        overflow-x: hidden !important;
        width: 100vw !important;
    }

    .min-h-screen {
        overflow-x: hidden !important;
        width: 100% !important;
    }

    /* 2. HEADER NAVBAR FIX */
    nav {
        padding: 0.5rem 0.75rem !important;
    }
    nav .max-w-7xl {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
    }
    /* Logo SiswaMart lebih ringkas */
    nav a.text-xl, nav a.text-2xl {
        font-size: 1.1rem !important;
    }
    /* Sembunyikan badge "LAPAK" kecil di HP agar hemat tempat */
    nav span.bg-orange-100 {
        display: none !important;
    }
    /* Perkecil tombol Edit Profil & Keluar */
    nav button {
        padding: 0.35rem 0.55rem !important;
        font-size: 0.65rem !important;
        border-radius: 0.5rem !important;
    }

    /* 3. CARD CONTAINER FIT SCREEN */
    main.max-w-7xl {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    /* 4. PENYESUAIAN CARD STATISTIK 2-KOLOM */
    .grid.grid-cols-2 {
        gap: 0.5rem !important;
    }
    .grid.grid-cols-2 > div {
        padding: 0.65rem 0.55rem !important;
        border-radius: 1rem !important;
        border-width: 2px !important;
        box-shadow: 3px 3px 0px 0px #111827 !important;
    }

    /* Icon Bulat di Atas Card */
    .w-10.h-10 {
        width: 1.6rem !important;
        height: 1.6rem !important;
        border-radius: 0.5rem !important;
    }
    .w-10.h-10 svg {
        width: 0.85rem !important;
        height: 0.85rem !important;
    }

    /* Ukuran Teks Angka & Judul Card */
    .text-3xl {
        font-size: 1.25rem !important;
        line-height: 1.4rem !important;
    }
    h3.text-xs {
        font-size: 0.65rem !important;
    }

    /* 5. KHUSUS CARD MENU PINTAS (RAPIKAN TOMBOL) */
    .space-y-2 {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.35rem !important;
    }
    .space-y-2 a {
        padding: 0.35rem 0.25rem !important;
        font-size: 0.58rem !important;
        line-height: 1 !important;
        border-radius: 0.5rem !important;
        box-shadow: 1.5px 1.5px 0px 0px #111827 !important;
        white-space: nowrap !important;
        letter-spacing: normal !important;
    }

    /* 6. ETALASE & ULASAN CARD */
    .grid.grid-cols-1.lg\:grid-cols-2 > div {
        padding: 0.85rem !important;
        border-radius: 1.25rem !important;
        box-shadow: 4px 4px 0px 0px #111827 !important;
    }
}
</style>