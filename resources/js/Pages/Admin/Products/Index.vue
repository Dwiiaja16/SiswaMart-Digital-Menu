<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    products: [Object, Array],
    filters: Object,
});

const search = ref(props.filters?.search || '');

// Computed untuk menangani data produk secara aman (baik format Paginasi maupun Array biasa)
const productList = computed(() => {
    if (!props.products) return [];
    return Array.isArray(props.products) ? props.products : (props.products.data || []);
});

// Debounce Search untuk pencarian nama produk / toko
let searchTimer;
watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('admin.products.index'), { search: value }, { preserveState: true, replace: true });
    }, 300);
});

// Eksekusi Hapus Produk
const deleteProduct = (product) => {
    if (confirm(`Apakah Anda yakin ingin menghapus produk "${product.name}" milik ${product.shop?.name || 'Penjual'}?`)) {
        router.delete(route('admin.products.destroy', product.id), {
            preserveScroll: true,
            onSuccess: () => alert('Produk berhasil dihapus!'),
        });
    }
};

// Helper Format URL Gambar
const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    const path = typeof imagePath === 'object' ? (imagePath.image_path || imagePath.url) : imagePath;
    if (!path) return null;
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    return path.startsWith('/') ? path : '/' + path;
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

                <!-- Filter & Search Input -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex items-center gap-4">
                    <div class="relative w-full flex items-center">
                        <svg class="w-4 h-4 absolute left-3.5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari nama produk atau nama lapak..." 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl pl-10 pr-3.5 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0" 
                        />
                    </div>
                </div>

                <!-- Main Section Container -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-3 sm:space-y-4">
                    
                    <!-- TAMPILAN MOBILE: CARD LIST -->
                    <div class="block md:hidden space-y-3">
                        <div 
                            v-for="product in productList" 
                            :key="product.id"
                            class="bg-stone-50 border-2 border-gray-900 rounded-xl p-3 space-y-2.5 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <!-- Top: Thumbnail & Info Utama -->
                            <div class="flex items-start gap-2.5">
                                <div class="w-12 h-12 bg-stone-200 border-2 border-gray-900 rounded-lg overflow-hidden flex-shrink-0">
                                    <img v-if="product.image || product.images?.[0]" :src="getImageUrl(product.image || product.images?.[0])" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="font-black text-xs uppercase text-gray-900 truncate block">{{ product.name }}</span>
                                        <span :class="product.stock_status === 'ready' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900'" class="border border-gray-900 text-[8px] font-black px-1.5 py-0.5 rounded uppercase shrink-0">
                                            {{ product.stock_status === 'ready' ? 'Ready' : (product.stock_status === 'pre_order' ? 'PO' : 'Habis') }}
                                        </span>
                                    </div>
                                    <div class="text-[10px] font-bold text-gray-500 truncate mt-0.5">
                                        {{ product.shop?.name || '-' }} <span class="text-gray-400">(@{{ product.shop?.user?.username || '-' }})</span>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <span class="font-black text-xs text-gray-900">Rp {{ Number(product.price).toLocaleString('id-ID') }}</span>
                                        <span class="bg-orange-100 text-orange-900 border border-gray-900 px-1.5 py-0.2 rounded text-[8px] font-black uppercase">
                                            {{ product.category?.name || product.categories?.[0]?.name || 'Umum' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Action Buttons -->
                            <div class="flex items-center gap-2 border-t border-gray-200 pt-2.5">
                                <!-- PAKAI TAG <a> BUKAN <Link> AGAR OPEN IN NEW TAB WORK PROPERLY -->
                                <a 
                                    :href="route('products.show', product.id)" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-stone-200 hover:bg-stone-300 border-2 border-gray-900 text-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                >
                                    <span>Lihat</span>
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                        <polyline points="15 3 21 3 21 9"/>
                                        <line x1="10" y1="14" x2="21" y2="3"/>
                                    </svg>
                                </a>
                                <button 
                                    @click="deleteProduct(product)"
                                    class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                >
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <div v-if="productList.length === 0" class="py-6 text-center text-xs font-bold uppercase text-gray-400">
                            Tidak ada produk ditemukan.
                        </div>
                    </div>

                    <!-- TAMPILAN DESKTOP & TABLET: TABEL UTAMA -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-4 border-gray-900 text-xs font-black uppercase tracking-wider text-gray-500 pb-3">
                                    <th class="py-3 px-2">Produk</th>
                                    <th class="py-3 px-2">Lapak / Penjual</th>
                                    <th class="py-3 px-2">Kategori</th>
                                    <th class="py-3 px-2">Harga</th>
                                    <th class="py-3 px-2">Stok</th>
                                    <th class="py-3 px-2 text-center">Aksi Admin</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-200">
                                <tr v-for="product in productList" :key="product.id" class="hover:bg-stone-50">
                                    <td class="py-3 px-2 flex items-center gap-3">
                                        <div class="w-12 h-12 bg-stone-200 border-2 border-gray-900 rounded-xl overflow-hidden flex-shrink-0">
                                            <img v-if="product.image || product.images?.[0]" :src="getImageUrl(product.image || product.images?.[0])" class="w-full h-full object-cover" />
                                        </div>
                                        <div>
                                            <span class="font-black text-xs uppercase block text-gray-900">{{ product.name }}</span>
                                            <!-- LINK LIHAT PUBLIK (AMANKAN PARAMETER ZIGGY) -->
                                            <a 
                                                :href="route('products.show', { product: product.id })" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-600 hover:underline"
                                            >
                                                Lihat Publik
                                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                    <polyline points="15 3 21 3 21 9"/>
                                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="py-3 px-2">
                                        <span class="font-black text-xs uppercase block">{{ product.shop?.name || '-' }}</span>
                                        <span class="text-[10px] font-bold text-gray-400">@{{ product.shop?.user?.username || '-' }}</span>
                                    </td>
                                    <td class="py-3 px-2">
                                        <span class="bg-orange-100 text-orange-900 border border-gray-900 px-2 py-0.5 rounded-md text-[10px] font-black uppercase">
                                            {{ product.category?.name || product.categories?.[0]?.name || 'Umum' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 font-black text-xs">
                                        Rp {{ Number(product.price).toLocaleString('id-ID') }}
                                    </td>
                                    <td class="py-3 px-2">
                                        <span :class="product.stock_status === 'ready' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900'" class="border border-gray-900 text-[10px] font-black px-2 py-0.5 rounded-md uppercase">
                                            {{ product.stock_status === 'ready' ? 'Ready' : (product.stock_status === 'pre_order' ? 'PO' : 'Habis') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <button 
                                            @click="deleteProduct(product)"
                                            class="inline-flex items-center gap-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 px-3 py-1.5 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                        >
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18"/>
                                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="productList.length === 0">
                                    <td colspan="6" class="text-center py-8 text-xs font-bold uppercase text-gray-400">
                                        Tidak ada produk ditemukan.
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