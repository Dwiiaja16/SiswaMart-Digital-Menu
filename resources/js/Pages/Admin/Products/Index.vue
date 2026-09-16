<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

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
    if (confirm(`Apakah Anda yakin ingin menghapus produk "${product.name}" milik ${product.shop?.name}?`)) {
        router.delete(route('admin.products.destroy', product.id), {
            preserveScroll: true,
            onSuccess: () => alert('Produk berhasil dihapus!'),
        });
    }
};

const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    if (imagePath.startsWith('http')) return imagePath;
    return imagePath.startsWith('/') ? imagePath : '/' + imagePath;
};
</script>

<template>
    <Head title="Moderasi Produk - Admin SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-8 bg-stone-100 min-h-screen text-gray-900 font-sans selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- Header Bar -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    <div>
                        <h1 class="text-2xl font-black uppercase tracking-tight text-gray-900">Moderasi Produk</h1>
                        <p class="text-xs font-bold text-gray-500 uppercase mt-1">Pantau & Kelola Seluruh Produk Katalog SiswaMart</p>
                    </div>
                    <Link :href="route('dashboard')" class="flex items-center gap-1.5 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                        <!-- SVG Arrow Left -->
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Kembali ke Dashboard
                    </Link>
                </div>

                <!-- Filter & Search Input -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex items-center gap-4">
                    <div class="relative w-full flex items-center">
                        <!-- SVG Search Icon -->
                        <svg class="w-4 h-4 absolute left-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Cari nama produk atau nama lapak..." 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl pl-11 pr-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0" 
                        />
                    </div>
                </div>

                <!-- Tabel Moderasi Produk -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] overflow-x-auto">
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
                            <tr v-for="product in products.data" :key="product.id" class="hover:bg-stone-50">
                                <td class="py-3 px-2 flex items-center gap-3">
                                    <div class="w-12 h-12 bg-stone-200 border-2 border-gray-900 rounded-xl overflow-hidden flex-shrink-0">
                                        <img v-if="product.image" :src="getImageUrl(product.image)" class="w-full h-full object-cover" />
                                    </div>
                                    <div>
                                        <span class="font-black text-xs uppercase block text-gray-900">{{ product.name }}</span>
                                        <Link :href="route('products.show', product.id)" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold text-orange-600 hover:underline">
                                            Lihat Publik
                                            <!-- SVG External Link Icon -->
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                                <polyline points="15 3 21 3 21 9"/>
                                                <line x1="10" y1="14" x2="21" y2="3"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <span class="font-black text-xs uppercase block">{{ product.shop?.name || '-' }}</span>
                                    <span class="text-[10px] font-bold text-gray-400">@{{ product.shop?.user?.username || '-' }}</span>
                                </td>
                                <td class="py-3 px-2">
                                    <span class="bg-orange-100 text-orange-900 border border-gray-900 px-2 py-0.5 rounded-md text-[10px] font-black uppercase">
                                        {{ product.category?.name || 'Umum' }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 font-black text-xs">
                                    Rp {{ Number(product.price).toLocaleString('id-ID') }}
                                </td>
                                <td class="py-3 px-2">
                                    <span :class="product.stock_status === 'ready' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900'" class="border border-gray-900 text-[10px] font-black px-2 py-0.5 rounded-md uppercase">
                                        {{ product.stock_status }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-center">
                                    <button 
                                        @click="deleteProduct(product)"
                                        class="inline-flex items-center gap-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 px-3 py-1.5 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        <!-- SVG Trash Icon -->
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="text-center py-8 text-xs font-bold uppercase text-gray-400">
                                    Tidak ada produk ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>