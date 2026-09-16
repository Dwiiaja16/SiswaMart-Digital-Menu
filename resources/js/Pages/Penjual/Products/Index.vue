<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    products: Array,
    categories: Array,
});

const page = usePage();
const isOpen = computed(() => Boolean(page.props.auth.user?.shop?.is_open));
const isSuspended = computed(() => Boolean(page.props.auth.user?.is_suspended));

// Helper URL Gambar dari root public/
const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    if (imagePath.startsWith('http')) return imagePath;
    return imagePath.startsWith('/') ? imagePath : '/' + imagePath;
};

// Helper fungsi toggle kategori untuk Array
const toggleCategory = (targetForm, categoryId) => {
    if (isSuspended.value) return;
    const index = targetForm.category_ids.indexOf(categoryId);
    if (index === -1) {
        targetForm.category_ids.push(categoryId);
    } else {
        targetForm.category_ids.splice(index, 1);
    }
};

// Form Tambah Produk
const createForm = useForm({
    category_ids: [], // Menggunakan Array untuk multi-select
    name: '',
    description: '',
    price: '',
    stock_status: 'ready',
    image: null,
});

const submitCreate = () => {
    if (isSuspended.value) return;
    createForm.post(route('products.store'), {
        onSuccess: () => {
            createForm.reset();
            createForm.category_ids = [];
            createForm.stock_status = 'ready';
        },
    });
};

// Modal & Form Edit Produk
const editingProduct = ref(null);
const editForm = useForm({
    _method: 'POST',
    category_ids: [], // Menggunakan Array untuk multi-select
    name: '',
    description: '',
    price: '',
    stock_status: 'ready',
    image: null,
});

const openEditModal = (product) => {
    if (isSuspended.value) return;
    editingProduct.value = product;
    // Ambil array ID dari relasi categories yang dikirim dari controller
    editForm.category_ids = product.categories ? product.categories.map(c => c.id) : [];
    editForm.name = product.name;
    editForm.description = product.description || '';
    editForm.price = product.price;
    editForm.stock_status = product.stock_status;
    editForm.image = null;
};

const closeEditModal = () => {
    editingProduct.value = null;
};

const submitUpdate = () => {
    if (isSuspended.value) return;
    editForm.post(route('products.update', editingProduct.value.id), {
        onSuccess: () => closeEditModal(),
    });
};

const deleteProduct = (id) => {
    if (isSuspended.value) return;
    if (confirm('Yakin ingin menghapus produk ini dari menu digital?')) {
        useForm({}).delete(route('products.destroy', id));
    }
};

const toggleStatus = () => {
    if (isSuspended.value) return;
    useForm({}).post(route('shop.toggle-status'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kelola Menu Produk - SiswaMart" />

    <div class="min-h-screen bg-stone-100 text-gray-900 font-sans pb-16 selection:bg-orange-500 selection:text-white">
        
        <!-- Header Page -->
        <header class="bg-white border-b-4 border-gray-900 px-6 py-4 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black uppercase tracking-tighter text-gray-900">
                        Kelola Menu <span class="text-orange-500">Produk</span>
                    </h2>
                    <span class="bg-amber-100 border-2 border-gray-900 text-gray-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-md shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        Wirausaha Siswa
                    </span>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 mt-8 space-y-8">
            
            <!-- Banner Warning Suspend -->
            <div 
                v-if="isSuspended" 
                class="bg-rose-100 border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex items-start gap-4"
            >
                <div class="w-10 h-10 bg-rose-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-black text-gray-900 text-sm uppercase tracking-wide">Akun / Lapak Anda Sedang Di-Suspend</h4>
                    <p class="text-xs font-bold text-gray-800 mt-1">
                        Akses pengelolaan produk dan status lapak sementara dinonaktifkan oleh Admin. Seluruh produk Anda juga disembunyikan dari katalog publik. Silakan hubungi Admin SiswaMart jika ingin mengajukan pembukaan suspend.
                    </p>
                </div>
            </div>

            <!-- Card Status Operasional Lapak -->
            <div class="bg-amber-400 border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest bg-gray-900 text-white px-2.5 py-1 rounded-md">
                        Status Lapak Wirausaha
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-gray-900 mt-2">
                        {{ $page.props.auth.user?.shop?.name || 'Lapak Belum Dinamai' }}
                    </h1>
                    <p class="text-xs font-bold text-gray-900 mt-1 opacity-90">
                        Atur status lapakmu agar pembeli tahu kapan jajanan siap dipesan via WhatsApp.
                    </p>
                </div>

                <button
                    @click="toggleStatus"
                    :disabled="isSuspended"
                    :class="isSuspended ? 'bg-gray-300 border-gray-500 text-gray-500 cursor-not-allowed' : (isOpen ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-rose-400 hover:bg-rose-300')"
                    class="px-6 py-3.5 border-3 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-2xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-2"
                >
                    <span class="w-3 h-3 rounded-full border border-gray-900" :class="isOpen ? 'bg-emerald-900 animate-pulse' : 'bg-rose-900'"></span>
                    Status Lapak: {{ isSuspended ? 'DIKUNCI' : (isOpen ? 'BUKA' : 'TUTUP') }}
                </button>
            </div>

            <!-- Form Tambah Produk -->
            <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6" :class="{ 'opacity-60': isSuspended }">
                <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-4">
                    <div class="w-10 h-10 bg-orange-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">Tambah Produk Baru</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Isi formulir untuk memasukkan jajanan baru ke etalase</p>
                    </div>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Nama Produk</label>
                            <input v-model="createForm.name" :disabled="isSuspended" type="text" placeholder="Contoh: Es Teh Solo, Risoles Mayu..." class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0 disabled:bg-gray-100" required />
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Harga (Rp)</label>
                            <input v-model="createForm.price" :disabled="isSuspended" type="number" step="500" placeholder="5000" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0 disabled:bg-gray-100" required />
                        </div>
                    </div>

                    <!-- MULTI-SELECT KATEGORI JAJANAN (Tambah Produk) -->
                    <div>
                        <div class="flex justify-between items-center mb-2.5">
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700">
                                Kategori Jajanan
                            </label>
                            <span class="text-[10px] font-black text-orange-600 bg-orange-100 border border-gray-900 px-2 py-0.5 rounded-md uppercase">
                                Bisa Pilih Lebih Dari 1
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <div
                                v-for="cat in categories"
                                :key="cat.id"
                                @click="toggleCategory(createForm, cat.id)"
                                :class="[
                                    createForm.category_ids.includes(cat.id) 
                                        ? 'bg-amber-400 border-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] translate-x-[-2px] translate-y-[-2px]' 
                                        : 'bg-stone-50 border-gray-900 hover:bg-stone-100 opacity-80',
                                    isSuspended ? 'cursor-not-allowed' : 'cursor-pointer'
                                ]"
                                class="border-2 rounded-2xl p-3.5 flex items-center justify-between transition-all select-none group"
                            >
                                <span class="font-black text-xs uppercase tracking-tight text-gray-900">
                                    {{ cat.name }}
                                </span>
                                
                                <div 
                                    :class="createForm.category_ids.includes(cat.id) ? 'bg-gray-900 text-white' : 'bg-stone-200 text-gray-400 group-hover:bg-stone-300'"
                                    class="w-5 h-5 rounded-lg border border-gray-900 flex items-center justify-center shrink-0 transition"
                                >
                                    <svg v-if="createForm.category_ids.includes(cat.id)" class="w-3.5 h-3.5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div v-if="createForm.errors.category_ids" class="text-rose-600 text-[10px] font-bold mt-1.5">
                            {{ createForm.errors.category_ids }}
                        </div>
                    </div>

                    <!-- Status Stok Produk -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2.5">
                            Status Stok Produk
                        </label>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                @click="!isSuspended && (createForm.stock_status = 'ready')"
                                :class="[
                                    createForm.stock_status === 'ready' 
                                        ? 'bg-emerald-300 border-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]' 
                                        : 'bg-stone-50 border-gray-900 opacity-50 hover:opacity-80',
                                    isSuspended ? 'cursor-not-allowed' : 'cursor-pointer'
                                ]"
                                class="border-2 rounded-2xl p-4 flex items-center gap-3 transition-all select-none"
                            >
                                <div class="w-3 h-3 rounded-full bg-emerald-600 border border-gray-900"></div>
                                <div>
                                    <h4 class="font-black text-xs uppercase text-gray-900">Ready Stock</h4>
                                    <p class="text-[10px] font-bold text-gray-700">Tersedia Siap Diambil</p>
                                </div>
                            </div>

                            <div
                                @click="!isSuspended && (createForm.stock_status = 'pre_order')"
                                :class="[
                                    createForm.stock_status === 'pre_order' 
                                        ? 'bg-amber-300 border-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]' 
                                        : 'bg-stone-50 border-gray-900 opacity-50 hover:opacity-80',
                                    isSuspended ? 'cursor-not-allowed' : 'cursor-pointer'
                                ]"
                                class="border-2 rounded-2xl p-4 flex items-center gap-3 transition-all select-none"
                            >
                                <div class="w-3 h-3 rounded-full bg-amber-600 border border-gray-900"></div>
                                <div>
                                    <h4 class="font-black text-xs uppercase text-gray-900">Pre-Order (PO)</h4>
                                    <p class="text-[10px] font-bold text-gray-700">Pesan Dulu Baru Dibuat</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Foto Produk (Opsional)</label>
                        <input @input="createForm.image = $event.target.files[0]" :disabled="isSuspended" type="file" accept="image/*" class="w-full text-xs font-bold text-gray-600 bg-stone-50 border-2 border-gray-900 rounded-xl p-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-gray-900 file:text-xs file:font-black file:bg-amber-400 hover:file:bg-amber-300 disabled:opacity-50" />
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Deskripsi Singkat / Varian</label>
                        <textarea v-model="createForm.description" :disabled="isSuspended" rows="3" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0 disabled:bg-gray-100" placeholder="Contoh: Level pedas 1-5, topping keju gratis..."></textarea>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="createForm.processing || isSuspended || createForm.category_ids.length === 0" 
                            :class="isSuspended || createForm.category_ids.length === 0 ? 'bg-gray-300 border-gray-400 text-gray-500 cursor-not-allowed' : 'bg-orange-500 hover:bg-orange-400 text-gray-900'"
                            class="w-full sm:w-auto px-6 py-3.5 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest transition shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:scale-95 flex items-center justify-center gap-2"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            {{ isSuspended ? 'Aksi Dibatasi (Ter-suspend)' : 'Simpan Produk' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabel Daftar Produk -->
            <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-4">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">Daftar Menu Saat Ini</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Kelola daftar menu jualan yang terdaftar di sistem</p>
                    </div>
                    <span class="bg-orange-500 text-gray-900 border-2 border-gray-900 px-3 py-1 rounded-xl text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        {{ products.length }} Item
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                <th class="p-3.5 rounded-l-xl">Gambar</th>
                                <th class="p-3.5">Nama Produk</th>
                                <th class="p-3.5">Kategori</th>
                                <th class="p-3.5">Harga</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right rounded-r-xl">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-gray-100">
                            <tr v-for="product in products" :key="product.id" class="hover:bg-stone-50/50 transition">
                                <td class="py-3.5 px-3">
                                    <div class="w-12 h-12 bg-white border-2 border-gray-900 rounded-xl overflow-hidden flex items-center justify-center shrink-0">
                                        <img v-if="product.image" :src="getImageUrl(product.image)" class="w-full h-full object-cover" />
                                        <svg v-else class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-black text-xs uppercase text-gray-900">{{ product.name }}</td>
                                
                                <!-- MENAMPILKAN BANYAK KATEGORI (BADGE CHIPS) -->
                                <td class="py-3.5 px-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="cat in product.categories" 
                                            :key="cat.id" 
                                            class="bg-stone-100 border border-gray-900 text-gray-900 text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                        >
                                            {{ cat.name }}
                                        </span>
                                        <span v-if="!product.categories || product.categories.length === 0" class="text-xs font-bold text-gray-400">-</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-3 text-xs font-black text-orange-600">Rp {{ Number(product.price).toLocaleString('id-ID') }}</td>
                                <td class="py-3.5 px-3">
                                    <span 
                                        :class="{
                                            'bg-emerald-100 text-emerald-900': product.stock_status === 'ready',
                                            'bg-amber-100 text-amber-900': product.stock_status === 'pre_order',
                                            'bg-rose-100 text-rose-900': product.stock_status === 'out_of_stock'
                                        }" 
                                        class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border border-gray-900"
                                    >
                                        {{ product.stock_status === 'ready' ? 'Ready' : (product.stock_status === 'pre_order' ? 'Pre-Order' : 'Habis') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button 
                                            @click="openEditModal(product)" 
                                            :disabled="isSuspended"
                                            :class="isSuspended ? 'text-gray-400 cursor-not-allowed' : 'bg-amber-300 hover:bg-amber-200 text-gray-900'"
                                            class="p-2 border-2 border-gray-900 rounded-lg text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            title="Edit Produk"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="deleteProduct(product.id)" 
                                            :disabled="isSuspended"
                                            :class="isSuspended ? 'text-gray-400 cursor-not-allowed' : 'bg-rose-400 hover:bg-rose-300 text-gray-900'"
                                            class="p-2 border-2 border-gray-900 rounded-lg text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            title="Hapus Produk"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="products.length === 0">
                                <td colspan="6" class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada produk yang ditambahkan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>

        <!-- Modal Edit Produk Neo-Brutalism -->
        <div v-if="editingProduct" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] max-w-2xl w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-amber-400 border-2 border-gray-900 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">Edit Produk: {{ editingProduct.name }}</h3>
                    </div>
                    <button @click="closeEditModal" class="w-8 h-8 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 rounded-xl flex items-center justify-center font-black text-gray-900 transition">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitUpdate" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Nama Produk</label>
                            <input v-model="editForm.name" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0" required />
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Harga (Rp)</label>
                            <input v-model="editForm.price" type="number" step="500" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0" required />
                        </div>
                    </div>

                    <!-- MULTI-SELECT KATEGORI JAJANAN (Modal Edit) -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700">Kategori Jajanan</label>
                            <span class="text-[9px] font-black text-orange-600 uppercase">Pilih Minimal 1</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                v-for="cat in categories"
                                :key="cat.id"
                                @click="toggleCategory(editForm, cat.id)"
                                :class="editForm.category_ids.includes(cat.id) ? 'bg-amber-400 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'bg-stone-50 border-gray-900 opacity-60'"
                                class="border-2 rounded-xl p-2.5 text-center font-black text-xs uppercase cursor-pointer select-none transition flex items-center justify-between"
                            >
                                <span>{{ cat.name }}</span>
                                <span v-if="editForm.category_ids.includes(cat.id)" class="text-[10px]">✓</span>
                            </div>
                        </div>
                        <div v-if="editForm.errors.category_ids" class="text-rose-600 text-[10px] font-bold mt-1">
                            {{ editForm.errors.category_ids }}
                        </div>
                    </div>

                    <!-- Modal Edit Status Stok Cards -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Status Stok Produk</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                @click="editForm.stock_status = 'ready'"
                                :class="editForm.stock_status === 'ready' ? 'bg-emerald-300 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'bg-stone-50 border-gray-900 opacity-60'"
                                class="border-2 rounded-xl p-2.5 text-center font-black text-[10px] uppercase cursor-pointer select-none transition"
                            >
                                Ready
                            </div>
                            <div
                                @click="editForm.stock_status = 'pre_order'"
                                :class="editForm.stock_status === 'pre_order' ? 'bg-amber-300 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'bg-stone-50 border-gray-900 opacity-60'"
                                class="border-2 rounded-xl p-2.5 text-center font-black text-[10px] uppercase cursor-pointer select-none transition"
                            >
                                Pre-Order
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Ganti Foto Produk (Opsional)</label>
                        <input @input="editForm.image = $event.target.files[0]" type="file" accept="image/*" class="w-full text-xs font-bold text-gray-600 bg-stone-50 border-2 border-gray-900 rounded-xl p-2 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-2 file:border-gray-900 file:text-xs file:font-black file:bg-amber-400 hover:file:bg-amber-300" />
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Deskripsi Singkat / Varian</label>
                        <textarea v-model="editForm.description" rows="2" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t-2 border-gray-100">
                        <button type="button" @click="closeEditModal" class="px-5 py-2.5 bg-stone-100 border-2 border-gray-900 text-gray-900 rounded-xl font-black text-xs uppercase tracking-wider hover:bg-stone-200 transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="editForm.processing || editForm.category_ids.length === 0" class="px-5 py-2.5 bg-orange-500 border-2 border-gray-900 text-gray-900 rounded-xl font-black text-xs uppercase tracking-wider hover:bg-orange-400 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>