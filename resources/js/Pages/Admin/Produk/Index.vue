<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    products: [Object, Array],
    filters: Object,
    categories: { type: Array, default: () => [] },
});

const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.kategori_id || '');

// State Tambahan untuk Kontrol Custom Dropdown Neo-Brutalism
const isDropdownOpen = ref(false);

// Label Text Tombol Dropdown (Otomatis berubah sesuai kategori terpilih)
const selectedCategoryName = computed(() => {
    if (!selectedCategory.value) return 'Semua Kategori';
    const found = props.categories?.find(c => String(c.id_kategori) === String(selectedCategory.value));
    return found ? found.nama_kategori : 'Semua Kategori';
});

// Fungsi untuk memilih Kategori dari Custom Dropdown
const selectCategory = (id) => {
    selectedCategory.value = id;
    isDropdownOpen.value = false;
    filterByCategory();
};

const filterByCategory = () => {
    router.get(route('admin.products.index'), {
        search: search.value || undefined,
        kategori_id: selectedCategory.value || undefined,
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

// Computed Filter: Memfilter HANYA berdasarkan AWALAN / HURUF DEPAN nama produk
const productList = computed(() => {
    let raw = [];
    if (Array.isArray(props.products)) {
        raw = props.products;
    } else if (props.products?.data) {
        raw = props.products.data;
    }

    if (!search.value || search.value.trim() === '') return raw;

    const q = search.value.toLowerCase().trim();

    return raw.filter(product => {
        const productName = (product.nama_produk || product.name || '').trim().toLowerCase();
        return productName.startsWith(q);
    });
});

// Akses Moderasi Admin: Setujui Produk
const approveProduct = (product) => {
    const id = product.id_produk || product.id;
    const name = product.nama_produk || product.name || 'Produk';

    if (confirm(`Setujui produk "${name}" agar dapat tampil di katalog publik?`)) {
        router.patch(route('admin.products.approve', id), {}, {
            preserveScroll: true,
            onSuccess: () => alert('Produk berhasil disetujui!'),
            onError: () => alert('Gagal menyetujui produk.'),
        });
    }
};

// Akses Moderasi Admin: Tolak Produk
const rejectProduct = (product) => {
    const id = product.id_produk || product.id;
    const name = product.nama_produk || product.name || 'Produk';

    if (confirm(`Tolak produk "${name}"? Produk tidak akan tampil di katalog publik.`)) {
        router.patch(route('admin.products.reject', id), {}, {
            preserveScroll: true,
            onSuccess: () => alert('Produk telah ditolak.'),
            onError: () => alert('Gagal menolak produk.'),
        });
    }
};

// Eksekusi Hapus Produk (oleh Admin)
const deleteProduct = (product) => {
    const id = product.id_produk || product.id;
    const name = product.nama_produk || product.name || 'Produk';
    const sellerName = product.nama_toko || product.user?.dataPenjual?.nama_toko || product.user?.dataPenjual?.nama_lengkap || product.username || 'Penjual';

    if (confirm(`Apakah Anda yakin ingin menghapus produk "${name}" milik ${sellerName}?`)) {
        router.delete(route('admin.products.destroy', id), {
            preserveScroll: true,
            onSuccess: () => alert('Produk berhasil dihapus!'),
            onError: (errors) => alert('Gagal menghapus produk. Terjadi kesalahan pada server atau relasi data.'),
        });
    }
};

// --- LOGIKA PARSING GAMBAR PRODUK KOMPREHENSIF ---
const isInvalidImage = (path) => {
    if (!path || typeof path !== 'string') return true;
    const lower = path.trim().toLowerCase();
    return (
        !lower ||
        lower === 'null' ||
        lower === 'undefined' ||
        lower === '[]' ||
        lower === '{}' ||
        lower === 'none' ||
        lower.includes('default-product.png') ||
        lower.includes('default_product.png') ||
        lower.includes('placeholder')
    );
};

const parsePhotos = (input) => {
    if (!input) return [];

    if (Array.isArray(input)) {
        return input.flatMap(item => parsePhotos(item)).filter(Boolean);
    }

    if (typeof input === 'object' && input !== null) {
        const candidate = input.image_path || input.url || input.path || input.foto_produk_url || input.foto_produk || input.image || input.src;
        if (candidate && candidate !== input) {
            return parsePhotos(candidate);
        }
        return [];
    }

    if (typeof input === 'string') {
        let str = input.trim();
        if (isInvalidImage(str)) return [];

        if ((str.startsWith('[') && str.endsWith(']')) || (str.startsWith('{') && str.endsWith('}'))) {
            try {
                const parsed = JSON.parse(str);
                return parsePhotos(parsed);
            } catch (e) {
                str = str.replace(/[\[\]"']/g, '').trim();
                if (isInvalidImage(str)) return [];
                return [str];
            }
        }

        if (str.includes(',') && !str.startsWith('data:image')) {
            return str.split(',').map(s => s.trim()).filter(s => !isInvalidImage(s));
        }

        return [str];
    }

    return [];
};

const getImageUrl = (input) => {
    if (!input) return null;

    if (typeof input === 'string') {
        const trimmed = input.trim();
        if (isInvalidImage(trimmed)) return null;
        if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:image')) {
            return trimmed;
        }
    }

    const photos = parsePhotos(input);
    if (photos.length === 0) return null;

    let path = photos[0];
    if (typeof path !== 'string' || isInvalidImage(path)) return null;

    path = path.trim();
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:image')) {
        return path;
    }

    path = path.replace(/^[/"']+/, '').replace(/["']+$/, '');
    if (isInvalidImage(path)) return null;

    if (path.startsWith('storage/')) {
        return '/' + path;
    }

    return '/storage/' + path;
};

const getValidImageUrl = (product) => {
    if (!product) return null;
    return getImageUrl(
        product.foto_produk_url ||
        product.foto_produk ||
        product.image ||
        product.images ||
        (Array.isArray(product.semua_foto_url) && product.semua_foto_url.length > 0 ? product.semua_foto_url[0] : null)
    );
};
</script>

<template>
    <Head title="Moderasi Produk - Admin SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-8 bg-stone-100 min-h-screen text-gray-900 font-sans selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                
                <!-- Header Bar -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    <div>
                        <h1 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-gray-900">Moderasi Produk</h1>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase mt-0.5 sm:mt-1">Pantau & Kelola Seluruh Produk Katalog SiswaMart</p>
                    </div>
                </div>

                <!-- Input Pencarian Real-time & Custom Neo-Brutalist Dropdown -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    
                    <!-- Input Search per Huruf Depan -->
                    <div class="relative w-full flex items-center">
                        <svg class="w-4 h-4 absolute left-3.5 text-gray-900" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari berdasarkan huruf depan produk..." 
                            class="w-full bg-stone-50 border-2 sm:border-3 border-gray-900 rounded-xl pl-10 pr-3.5 py-2.5 text-xs font-black uppercase text-gray-900 placeholder:text-gray-400 focus:border-orange-500 focus:ring-0 transition" 
                        />
                    </div>

                    <!-- Custom Dropdown Kategori (Neo-Brutalism Style) -->
                    <div class="relative w-full sm:w-64">
                        <button 
                            type="button"
                            @click="isDropdownOpen = !isDropdownOpen"
                            class="w-full bg-amber-300 border-2 sm:border-3 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-black uppercase text-gray-900 flex items-center justify-between gap-2 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:translate-x-0.5 active:translate-y-0.5 active:shadow-none transition-all cursor-pointer"
                        >
                            <span class="truncate">
                                {{ selectedCategoryName }}
                            </span>
                            <svg 
                                class="w-4 h-4 transition-transform duration-200 text-gray-900"
                                :class="{ 'rotate-180': isDropdownOpen }"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                            >
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>

                        <!-- Menu Pilihan Dropdown -->
                        <div 
                            v-if="isDropdownOpen"
                            class="absolute right-0 left-0 mt-2 z-50 bg-white border-3 border-gray-900 rounded-xl p-1.5 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] max-h-60 overflow-y-auto"
                        >
                            <!-- Semua Kategori -->
                            <button
                                type="button"
                                @click="selectCategory('')"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-black uppercase transition-colors flex items-center justify-between"
                                :class="selectedCategory === '' ? 'bg-orange-500 text-white' : 'text-gray-900 hover:bg-stone-100'"
                            >
                                <span>Semua Kategori</span>
                                <span v-if="selectedCategory === ''">✓</span>
                            </button>

                            <!-- Loop List Kategori -->
                            <button
                                v-for="category in categories"
                                :key="category.id_kategori"
                                type="button"
                                @click="selectCategory(category.id_kategori)"
                                class="w-full text-left px-3 py-2 rounded-lg text-xs font-black uppercase transition-colors flex items-center justify-between"
                                :class="String(selectedCategory) === String(category.id_kategori) ? 'bg-orange-500 text-white' : 'text-gray-900 hover:bg-stone-100'"
                            >
                                <span class="truncate">{{ category.nama_kategori }}</span>
                                <span v-if="String(selectedCategory) === String(category.id_kategori)">✓</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Container Utama -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-3 sm:space-y-4">
                    
                    <!-- TAMPILAN MOBILE: CARD LIST -->
                    <div class="block md:hidden space-y-3">
                        <div 
                            v-for="product in productList" 
                            :key="product.id_produk || product.id"
                            class="bg-stone-50 border-2 border-gray-900 rounded-xl p-3 space-y-2.5 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <div class="flex items-start gap-2.5">
                                <div class="w-12 h-12 bg-amber-50 border-2 border-gray-900 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                    <img v-if="getValidImageUrl(product)" :src="getValidImageUrl(product)" :alt="product.nama_produk || product.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-[7px] font-black uppercase text-gray-700 bg-amber-50 leading-none text-center p-0.5">
                                        <svg class="w-4 h-4 mb-0.5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>NO PHOTO</span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="font-black text-xs uppercase text-gray-900 truncate block">{{ product.nama_produk || product.name }}</span>
                                        <div class="flex items-center gap-1 shrink-0">
                                            <span 
                                                class="border border-gray-900 text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                                                :class="{
                                                    'bg-amber-100 text-amber-900': (product.status_moderasi || 'pending') === 'pending',
                                                    'bg-emerald-100 text-emerald-900': product.status_moderasi === 'disetujui',
                                                    'bg-rose-100 text-rose-900': product.status_moderasi === 'ditolak'
                                                }"
                                            >
                                                {{ product.status_moderasi || 'pending' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-[10px] font-bold text-gray-700 truncate mt-0.5 uppercase">
                                        {{ product.nama_toko || '-' }} <span class="text-orange-600 font-bold">(@{{ product.username || '-' }})</span>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="font-black text-xs text-gray-900">Rp {{ Number(product.harga || product.price || 0).toLocaleString('id-ID') }}</span>
                                        <span class="bg-orange-100 text-orange-900 border border-gray-900 px-1.5 py-0.2 rounded text-[8px] font-black uppercase">
                                            {{ product.kategori?.nama_kategori || product.category?.name || 'Umum' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 border-t border-gray-200 pt-2.5">
                                <a 
                                    :href="route('products.show', product.id_produk || product.id)" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-stone-200 hover:bg-stone-300 border-2 border-gray-900 text-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                >
                                    <span>Lihat</span>
                                </a>
                                
                                <template v-if="(product.status_moderasi || 'pending') === 'pending'">
                                    <button 
                                        @click="approveProduct(product)"
                                        class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-emerald-400 hover:bg-emerald-300 text-gray-900 border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        Setujui
                                    </button>

                                    <button 
                                        @click="rejectProduct(product)"
                                        class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-amber-400 hover:bg-amber-300 text-gray-900 border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        Tolak
                                    </button>
                                </template>
                                <template v-else-if="product.status_moderasi === 'ditolak'">
                                    <button @click="approveProduct(product)" class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-emerald-400 hover:bg-emerald-300 text-gray-900 border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                        Setujui Kembali
                                    </button>
                                </template>

                                <button 
                                    @click="deleteProduct(product)"
                                    class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <div v-if="productList.length === 0" class="py-6 text-center text-xs font-bold uppercase text-gray-400">
                            Tidak ada produk ditemukan.
                        </div>
                    </div>

                    <!-- TAMPILAN DESKTOP: TABEL -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-4 border-gray-900 text-xs font-black uppercase tracking-wider text-gray-500 pb-3">
                                    <th class="py-3 px-2">Produk</th>
                                    <th class="py-3 px-2">Toko / Penjual</th>
                                    <th class="py-3 px-2">Kategori</th>
                                    <th class="py-3 px-2">Harga</th>
                                    <th class="py-3 px-2">Stok</th>
                                    <th class="py-3 px-2">Status Moderasi</th>
                                    <th class="py-3 px-2 text-center">Aksi Admin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-200">
                                <tr v-for="product in productList" :key="product.id_produk || product.id" class="hover:bg-stone-50">
                                    <td class="py-3 px-2 flex items-center gap-3">
                                        <div class="w-12 h-12 bg-amber-50 border-2 border-gray-900 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                            <img v-if="getValidImageUrl(product)" :src="getValidImageUrl(product)" :alt="product.nama_produk || product.name" class="w-full h-full object-cover" />
                                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-[7px] font-black uppercase text-gray-700 bg-amber-50 leading-none text-center p-0.5">
                                                <svg class="w-4 h-4 mb-0.5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>NO PHOTO</span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="font-black text-xs uppercase block text-gray-900">{{ product.nama_produk || product.name }}</span>
                                            <a 
                                                :href="route('products.show', { product: product.id_produk || product.id })" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-600 hover:underline"
                                            >
                                                Lihat Detail
                                            </a>
                                        </div>
                                    </td>
                                    <td class="py-3 px-2">
                                        <span class="font-black text-xs uppercase block text-gray-900">{{ product.nama_toko || '-' }}</span>
                                        <span class="text-[10px] font-bold text-orange-600">@{{ product.username || '-' }}</span>
                                    </td>
                                    <td class="py-3 px-2">
                                        <span class="bg-orange-100 text-orange-900 border border-gray-900 px-2 py-0.5 rounded-md text-[10px] font-black uppercase">
                                            {{ product.kategori?.nama_kategori || product.category?.name || 'Umum' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 font-black text-xs">
                                        Rp {{ Number(product.harga || product.price || 0).toLocaleString('id-ID') }}
                                    </td>
                                    <td class="py-3 px-2">
                                        <span :class="(product.status_stok || product.stock_status) === 'ready' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900'" class="border border-gray-900 text-[10px] font-black px-2 py-0.5 rounded-md uppercase">
                                            {{ (product.status_stok || product.stock_status) === 'ready' ? 'Ready' : 'PO' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2">
                                        <span 
                                            class="border border-gray-900 text-[10px] font-black px-2 py-0.5 rounded-md uppercase"
                                            :class="{
                                                'bg-amber-100 text-amber-900': (product.status_moderasi || 'pending') === 'pending',
                                                'bg-emerald-100 text-emerald-900': product.status_moderasi === 'disetujui',
                                                'bg-rose-100 text-rose-900': product.status_moderasi === 'ditolak'
                                            }"
                                        >
                                            {{ product.status_moderasi || 'pending' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <template v-if="(product.status_moderasi || 'pending') === 'pending'">
                                                <button 
                                                    @click="approveProduct(product)"
                                                    class="bg-emerald-400 hover:bg-emerald-300 text-gray-900 border-2 border-gray-900 px-2.5 py-1.5 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                                >
                                                    Setujui
                                                </button>

                                                <button 
                                                    @click="rejectProduct(product)"
                                                    class="bg-amber-400 hover:bg-amber-300 text-gray-900 border-2 border-gray-900 px-2.5 py-1.5 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                                >
                                                    Tolak
                                                </button>
                                            </template>
                                            <template v-else-if="product.status_moderasi === 'ditolak'">
                                                <button @click="approveProduct(product)" class="bg-emerald-400 hover:bg-emerald-300 text-gray-900 border-2 border-gray-900 px-2.5 py-1.5 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                    Setujui Kembali
                                                </button>
                                            </template>

                                            <button 
                                                @click="deleteProduct(product)"
                                                class="bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 px-2.5 py-1.5 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="productList.length === 0">
                                    <td colspan="7" class="text-center py-8 text-xs font-bold uppercase text-gray-400">
                                        Tidak ada produk ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION NAVIGATION -->
                    <Pagination :links="products.links || []" :meta="products" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>