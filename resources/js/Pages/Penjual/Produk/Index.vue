<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    products: [Array, Object],
    categories: Array,
    seller: Object,
    filters: { type: Object, default: () => ({}) },
});

const selectedCategory = ref(props.filters?.kategori_id || '');
const searchQuery = ref(props.filters?.search || props.filters?.q || '');
const isCategoryOpen = ref(false);

const toggleCategoryDropdown = () => {
    isCategoryOpen.value = !isCategoryOpen.value;
};

const selectCategory = (catId) => {
    selectedCategory.value = catId;
    isCategoryOpen.value = false;
    filterByCategory();
};

const selectedCategoryLabel = computed(() => {
    if (!selectedCategory.value) return 'SEMUA KATEGORI';
    const found = props.categories?.find(c => String(c.id_kategori) === String(selectedCategory.value));
    return found ? found.nama_kategori.toUpperCase() : 'SEMUA KATEGORI';
});

// Mengirimkan request pencarian & filter ke Controller Laravel dengan Reset ke Page 1
const filterByCategory = () => {
    router.get(safeRoute('penjual.products.index'), {
        kategori_id: selectedCategory.value || undefined,
        search: searchQuery.value || undefined,
        page: 1, // WAJIB: Reset ke halaman 1 tiap kali pencarian / filter berubah
    }, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

// Mengambil list produk langsung dari response pagination server
const productList = computed(() => {
    if (Array.isArray(props.products)) {
        return props.products;
    }
    return props.products?.data || [];
});

const totalProductCount = computed(() => {
    if (props.products?.total !== undefined) return props.products.total;
    return productList.value.length;
});

const page = usePage();
const dataPenjual = computed(() => props.seller || page.props.auth.user?.data_penjual || page.props.auth.user?.dataPenjual);

// Pengecekan Nama Toko
const hasShopName = computed(() => {
    const name = dataPenjual.value?.nama_toko;
    return name && typeof name === 'string' && name.trim() !== '' && name.trim() !== '-';
});

const shopName = computed(() => {
    if (hasShopName.value) {
        return dataPenjual.value.nama_toko;
    }
    return dataPenjual.value?.nama_lengkap || page.props.auth.user?.username || 'Toko Belum Dinamai';
});

const isOpen = computed(() => dataPenjual.value?.status_toko === 'buka');
const isSuspended = computed(() => Boolean(page.props.auth.user?.is_suspended));

const showRequireShopModal = ref(false);

const shopForm = useForm({
    username: page.props.auth.user?.username || '',
    nama_lengkap: dataPenjual.value?.nama_lengkap || page.props.auth.user?.name || '',
    nama_toko: '',
});

const submitShopName = () => {
    let updateProfileRoute = '/profile';
    try {
        if (typeof route === 'function') {
            updateProfileRoute = route('profile.update');
        }
    } catch (e) {
        updateProfileRoute = '/profile';
    }

    shopForm.patch(updateProfileRoute, {
        preserveScroll: true,
        onSuccess: () => {
            showRequireShopModal.value = false;
            shopForm.reset();
            alert('Berhasil menyimpan Nama Toko! Silakan klik "Simpan Produk" kembali.');
        },
        onError: (errors) => {
            alert('Gagal menyimpan Nama Toko:\n' + Object.values(errors).join('\n'));
        }
    });
};

// --- LOGIKA PARSING GAMBAR PRODUK ---
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

const safeRoute = (name, params = {}) => {
    try {
        return route(name, params);
    } catch (e) {
        console.warn(`Route [${name}] tidak ditemukan:`, e);
        return '#';
    }
};

// --- FORM TAMBAH PRODUK ---
const createForm = useForm({
    id_kategori: null,
    nama_produk: '',
    deskripsi: '',
    harga: '',
    status_stok: 'ready',
    foto_produk: null,
    images: [],
});

const handleCreateGalleryChange = (e) => {
    createForm.images = Array.from(e.target.files);
};

const submitCreate = () => {
    if (isSuspended.value) {
        alert('Akun kamu sedang dinonaktifkan oleh Admin.');
        return;
    }

    if (!hasShopName.value) {
        showRequireShopModal.value = true;
        return;
    }

    if (!createForm.id_kategori) {
        alert('Silakan pilih 1 Kategori Jajanan terlebih dahulu!');
        return;
    }

    if (Number(createForm.harga) < 0) {
        alert('Harga produk tidak boleh bernilai negatif!');
        return;
    }
    
    let storeRoute = '/penjual/products';
    try {
        storeRoute = route('penjual.products.store');
    } catch (e1) {
        try {
            storeRoute = route('penjual.produk.store');
        } catch (e2) {
            storeRoute = '/penjual/products';
        }
    }

    createForm.post(storeRoute, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            createForm.reset();
            createForm.clearErrors();
            createForm.id_kategori = null;
            createForm.status_stok = 'ready';
            createForm.foto_produk = null;
            createForm.images = [];
            alert('Produk berhasil ditambahkan ke etalase!');
        },
        onError: (errors) => {
            console.error('Error Validasi:', errors);
            if (errors.nama_toko) {
                showRequireShopModal.value = true;
            } else {
                alert('Gagal menyimpan produk:\n' + Object.values(errors).join('\n'));
            }
        }
    });
};

// --- MODAL & FORM EDIT PRODUK ---
const editingProduct = ref(null);
const editForm = useForm({
    _method: 'PUT',
    id_kategori: null,
    nama_produk: '',
    deskripsi: '',
    harga: '',
    status_stok: 'ready',
    foto_produk: null,
    images: [],
    retained_photos: [],
});

const handleEditGalleryChange = (e) => {
    editForm.images = Array.from(e.target.files);
};

const openEditModal = (product) => {
    if (isSuspended.value) return;
    editingProduct.value = product;
    editForm.clearErrors();
    editForm.id_kategori = product.id_kategori ?? null;
    editForm.nama_produk = product.nama_produk ?? '';
    editForm.deskripsi = product.deskripsi || '';
    editForm.harga = product.harga ?? '';
    editForm.status_stok = product.status_stok ?? 'ready';
    editForm.foto_produk = null;
    editForm.images = [];
    editForm.retained_photos = parsePhotos(product.foto_produk);
};

const removeExistingPhoto = (idx) => {
    if (confirm('Yakin ingin menghapus foto ini dari produk?')) {
        editForm.retained_photos.splice(idx, 1);
    }
};

const closeEditModal = () => {
    editingProduct.value = null;
    editForm.clearErrors();
    editForm.reset();
};

const submitUpdate = () => {
    if (isSuspended.value) return;

    if (Number(editForm.harga) < 0) {
        alert('Harga produk tidak boleh bernilai negatif!');
        return;
    }

    let updateRoute = `/penjual/products/${editingProduct.value.id_produk}`;
    try {
        updateRoute = route('penjual.products.update', editingProduct.value.id_produk);
    } catch (e1) {
        try {
            updateRoute = route('penjual.produk.update', editingProduct.value.id_produk);
        } catch (e2) {
            updateRoute = `/penjual/products/${editingProduct.value.id_produk}`;
        }
    }

    editForm.transform((data) => ({
        ...data,
        retained_photos: JSON.stringify(data.retained_photos),
    })).post(updateRoute, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            alert('Produk berhasil diperbarui!');
            closeEditModal();
        },
        onError: (errors) => {
            console.error('Error Edit:', errors);
            alert('Gagal memperbarui produk:\n' + Object.values(errors).join('\n'));
        }
    });
};

const deleteProduct = (id_produk) => {
    if (isSuspended.value) return;
    if (confirm('Yakin ingin menghapus produk ini dari menu digital?')) {
        router.delete(safeRoute('penjual.products.destroy', id_produk), {
            preserveScroll: true,
            onSuccess: () => alert('Produk berhasil dihapus!'),
            onError: (errors) => alert('Gagal menghapus produk: ' + Object.values(errors).join(', '))
        });
    }
};

const toggleStatus = () => {
    if (isSuspended.value) return;
    router.post(safeRoute('penjual.shop.toggle-status'), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kelola Menu Produk - SiswaMart" />

    <AuthenticatedLayout>
        <div class="space-y-6 sm:space-y-8 pb-16 selection:bg-orange-500 selection:text-white">

            <!-- Judul Halaman -->
            <div class="flex items-center gap-3">
                <h2 class="text-xl sm:text-2xl font-black uppercase tracking-tighter text-gray-900">
                    Kelola Menu <span class="text-orange-500">Produk</span>
                </h2>
                <span class="bg-amber-100 border-2 border-gray-900 text-gray-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-md shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                    Wirausaha Siswa
                </span>
            </div>
            
            <!-- Banner Warning Suspend -->
            <div 
                v-if="isSuspended" 
                class="bg-rose-100 border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] flex items-start gap-4"
            >
                <div class="w-10 h-10 bg-rose-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-black text-gray-900 text-sm uppercase tracking-wide">Akun / Toko Anda Sedang Di-Suspend</h4>
                    <p class="text-xs font-bold text-gray-800 mt-1">
                        Akses pengelolaan produk dan status toko sementara dinonaktifkan oleh Admin.
                    </p>
                </div>
            </div>

            <!-- Card Status Operasional Toko -->
            <div class="bg-amber-400 border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest bg-gray-900 text-white px-2.5 py-1 rounded-md shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                        Status Toko Wirausaha
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-gray-900 mt-2">
                        {{ shopName }}
                    </h1>
                    <p class="text-xs font-bold text-gray-900 mt-1 opacity-90">
                        Atur status tokomu agar pembeli tahu kapan jajanan siap dipesan via WhatsApp.
                    </p>
                </div>

                <button
                    @click="toggleStatus"
                    :disabled="isSuspended"
                    :class="isSuspended ? 'bg-gray-300 border-gray-500 text-gray-500 cursor-not-allowed' : (isOpen ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-rose-400 hover:bg-rose-300')"
                    class="px-6 py-3.5 border-3 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-2xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-2 shrink-0"
                >
                    <span class="w-3 h-3 rounded-full border border-gray-900" :class="isOpen ? 'bg-emerald-900 animate-pulse' : 'bg-rose-900'"></span>
                    Status Toko: {{ isSuspended ? 'DIKUNCI' : (isOpen ? 'BUKA' : 'TUTUP') }}
                </button>
            </div>

            <!-- Form Tambah Produk -->
            <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6" :class="{ 'opacity-60': isSuspended }">
                <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-4">
                    <div class="w-10 h-10 bg-orange-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Tambah Produk Baru</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Isi formulir untuk memasukkan jajanan baru ke etalase</p>
                    </div>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Nama Produk</label>
                            <input v-model="createForm.nama_produk" :disabled="isSuspended" type="text" placeholder="Contoh: Es Teh Solo, Risoles Mayu..." class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0 disabled:bg-gray-100" required />
                            <div v-if="createForm.errors.nama_produk" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                                {{ createForm.errors.nama_produk }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Harga (Rp)</label>
                            <input 
                                v-model="createForm.harga" 
                                :disabled="isSuspended" 
                                type="number" 
                                min="0" 
                                step="500" 
                                placeholder="5000" 
                                @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()"
                                @input="createForm.harga = createForm.harga < 0 ? 0 : createForm.harga"
                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0 disabled:bg-gray-100" 
                                required 
                            />
                            <div v-if="createForm.errors.harga" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                                {{ createForm.errors.harga }}
                            </div>
                        </div>
                    </div>

                    <!-- KATEGORI JAJANAN -->
                    <div>
                        <div class="flex justify-between items-center mb-2.5">
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700">
                                Kategori Jajanan
                            </label>
                            <span class="text-[10px] font-black text-emerald-700 bg-emerald-100 border border-gray-900 px-2 py-0.5 rounded-md uppercase">
                                Pilih 1 Kategori Utama
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                            <div
                                v-for="cat in categories"
                                :key="cat.id_kategori"
                                @click="!isSuspended && (createForm.id_kategori = cat.id_kategori)"
                                :class="[
                                    createForm.id_kategori === cat.id_kategori 
                                        ? 'bg-amber-400 border-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] translate-x-[-2px] translate-y-[-2px]' 
                                        : 'bg-stone-50 border-gray-900 hover:bg-stone-100 opacity-80',
                                    isSuspended ? 'cursor-not-allowed' : 'cursor-pointer'
                                ]"
                                class="border-2 rounded-2xl p-3.5 flex items-center justify-between transition-all select-none group"
                            >
                                <span class="font-black text-xs uppercase tracking-tight text-gray-900">
                                    {{ cat.nama_kategori }}
                                </span>
                                
                                <div 
                                    :class="createForm.id_kategori === cat.id_kategori ? 'bg-gray-900 text-white' : 'bg-stone-200 text-gray-400 group-hover:bg-stone-300'"
                                    class="w-5 h-5 rounded-lg border border-gray-900 flex items-center justify-center shrink-0 transition"
                                >
                                    <svg v-if="createForm.id_kategori === cat.id_kategori" class="w-3.5 h-3.5 stroke-current" viewBox="0 0 24 24" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div v-if="createForm.errors.id_kategori" class="text-rose-600 text-[11px] font-black uppercase mt-1.5">
                            {{ createForm.errors.id_kategori }}
                        </div>
                    </div>

                    <!-- Status Stok Produk -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2.5">
                            Status Stok Produk
                        </label>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            <div
                                @click="!isSuspended && (createForm.status_stok = 'ready')"
                                :class="[
                                    createForm.status_stok === 'ready' 
                                        ? 'bg-emerald-300 border-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' 
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
                                @click="!isSuspended && (createForm.status_stok = 'pre-order')"
                                :class="[
                                    createForm.status_stok === 'pre-order' 
                                        ? 'bg-amber-300 border-gray-900 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]' 
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

                    <!-- UPLOAD FOTO UTAMA & TAMBAHAN -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">
                                Foto Utama Produk (Cover)
                            </label>
                            <input 
                                @input="createForm.foto_produk = $event.target.files[0]" 
                                :disabled="isSuspended" 
                                type="file" 
                                accept="image/*" 
                                class="w-full text-xs font-bold text-gray-600 bg-stone-50 border-2 border-gray-900 rounded-xl p-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-gray-900 file:text-xs file:font-black file:bg-amber-400 hover:file:bg-amber-300 disabled:opacity-50" 
                            />
                            <div v-if="createForm.errors.foto_produk" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                                {{ createForm.errors.foto_produk }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2 flex justify-between items-center">
                                <span>Foto Tambahan / Lainnya</span>
                                <span class="text-[10px] text-orange-600 font-bold bg-orange-100 border border-gray-900 px-1.5 py-0.5 rounded">
                                    Bisa Pilih Banyak
                                </span>
                            </label>
                            <input 
                                @change="handleCreateGalleryChange" 
                                multiple 
                                :disabled="isSuspended" 
                                type="file" 
                                accept="image/*" 
                                class="w-full text-xs font-bold text-gray-600 bg-stone-50 border-2 border-gray-900 rounded-xl p-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-2 file:border-gray-900 file:text-xs file:font-black file:bg-orange-400 hover:file:bg-orange-300 disabled:opacity-50" 
                            />
                            <p class="text-[10px] text-gray-500 font-bold mt-1">
                                Tekan & tahan tombol Ctrl / Cmd untuk memilih beberapa foto sekaligus.
                            </p>
                        </div>
                    </div>

                    <!-- DESKRIPSI -->
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">
                            Deskripsi Singkat / Varian <span class="text-rose-600">*</span>
                        </label>
                        <textarea 
                            v-model="createForm.deskripsi" 
                            :disabled="isSuspended" 
                            rows="3" 
                            required 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold focus:border-orange-500 focus:ring-0 disabled:bg-gray-100" 
                            placeholder="Contoh: Level pedas 1-5, topping keju gratis, varian isi cokelat/keju..."
                        ></textarea>
                        <div v-if="createForm.errors.deskripsi" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                            {{ createForm.errors.deskripsi }}
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            :disabled="createForm.processing || isSuspended || !createForm.id_kategori" 
                            :class="isSuspended || !createForm.id_kategori ? 'bg-gray-300 border-gray-400 text-gray-500 cursor-not-allowed' : 'bg-orange-500 hover:bg-orange-400 text-gray-900'"
                            class="w-full sm:w-auto px-6 py-3.5 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest transition shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 flex items-center justify-center gap-2"
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
            <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Daftar Menu Saat Ini</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Kelola daftar menu jualan yang terdaftar di sistem</p>
                    </div>
                    <span class="bg-orange-500 text-gray-900 border-2 border-gray-900 px-3 py-1 rounded-xl text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        {{ totalProductCount }} Item
                    </span>
                </div>

                <!-- SEARCH & FILTER KATEGORI -->
                <div class="bg-white border-2 border-gray-900 rounded-2xl p-3 sm:p-4 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] flex flex-col sm:flex-row items-stretch sm:items-center gap-3 relative">
                    <!-- Input Search (Huruf Depan Produk) -->
                    <div class="relative flex-1 flex items-center">
                        <svg class="w-4 h-4 absolute left-3.5 text-gray-900 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            v-model="searchQuery"
                            @input="filterByCategory"
                            type="text"
                            placeholder="CARI BERDASARKAN HURUF DEPAN PRODUK..."
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl pl-10 pr-4 py-2.5 text-xs font-black uppercase text-gray-900 placeholder-gray-400 focus:border-orange-500 focus:ring-0 transition"
                        />
                    </div>

                    <!-- Custom Dropdown Button & Options -->
                    <div class="relative shrink-0">
                        <button
                            type="button"
                            @click="toggleCategoryDropdown"
                            class="w-full sm:w-auto min-w-[200px] bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-black uppercase text-gray-900 flex items-center justify-between gap-3 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                        >
                            <span>{{ selectedCategoryLabel }}</span>
                            <svg class="w-4 h-4 text-gray-900 transition-transform duration-200" :class="{ 'rotate-180': isCategoryOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-if="isCategoryOpen" @click="isCategoryOpen = false" class="fixed inset-0 z-40"></div>

                        <div
                            v-if="isCategoryOpen"
                            class="absolute right-0 top-full mt-2 z-50 w-full sm:w-64 bg-white border-2 border-gray-900 rounded-xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] overflow-hidden py-1"
                        >
                            <div
                                @click="selectCategory('')"
                                class="px-4 py-2.5 text-xs font-black uppercase flex items-center justify-between cursor-pointer transition select-none"
                                :class="!selectedCategory ? 'bg-orange-500 text-white' : 'text-gray-900 hover:bg-amber-100'"
                            >
                                <span>SEMUA KATEGORI</span>
                                <span v-if="!selectedCategory" class="font-black text-sm">✓</span>
                            </div>
                            <div
                                v-for="category in categories"
                                :key="category.id_kategori"
                                @click="selectCategory(category.id_kategori)"
                                class="px-4 py-2.5 text-xs font-black uppercase flex items-center justify-between cursor-pointer transition select-none"
                                :class="String(selectedCategory) === String(category.id_kategori) ? 'bg-orange-500 text-white' : 'text-gray-900 hover:bg-amber-100'"
                            >
                                <span>{{ category.nama_kategori }}</span>
                                <span v-if="String(selectedCategory) === String(category.id_kategori)" class="font-black text-sm">✓</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tampilan kartu untuk perangkat mobile -->
                <div class="grid gap-3 md:hidden">
                    <div
                        v-for="product in productList"
                        :key="product.id_produk"
                        class="bg-stone-50 border-2 border-gray-900 rounded-2xl p-3.5 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]"
                    >
                        <div class="flex gap-3">
                            <div class="relative w-14 h-14 bg-amber-50 border-2 border-gray-900 rounded-xl overflow-hidden flex items-center justify-center shrink-0">
                                <img v-if="getValidImageUrl(product)" :src="getValidImageUrl(product)" :alt="product.nama_produk" class="w-full h-full object-cover" />
                                <div v-else class="text-[7px] font-black uppercase text-gray-700 text-center">NO PHOTO</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-xs font-black uppercase text-gray-900 truncate">{{ product.nama_produk }}</h4>
                                <p class="mt-1 text-xs font-black text-orange-600">Rp {{ Number(product.harga).toLocaleString('id-ID') }}</p>
                                <span v-if="product.kategori" class="mt-1.5 inline-block bg-stone-100 border border-gray-900 text-gray-900 text-[9px] font-black uppercase px-2 py-0.5 rounded-md">
                                    {{ product.kategori.nama_kategori }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap items-center gap-2 border-t-2 border-gray-200 pt-3">
                            <span
                                :class="{
                                    'bg-emerald-100 text-emerald-900': product.status_stok === 'ready',
                                    'bg-amber-100 text-amber-900': product.status_stok === 'pre-order',
                                    'bg-rose-100 text-rose-900': product.status_stok === 'habis'
                                }"
                                class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border border-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                            >
                                {{ product.status_stok === 'ready' ? 'Ready' : (product.status_stok === 'pre-order' ? 'Pre-Order' : 'Habis') }}
                            </span>
                            <span
                                :class="{
                                    'bg-amber-100 text-amber-900': product.status_moderasi === 'pending',
                                    'bg-emerald-100 text-emerald-900': product.status_moderasi === 'disetujui',
                                    'bg-rose-100 text-rose-900': product.status_moderasi === 'ditolak'
                                }"
                                class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border border-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                            >
                                {{ product.status_moderasi === 'disetujui' ? 'DISETUJUI' : (product.status_moderasi === 'ditolak' ? 'DITOLAK' : 'PENDING') }}
                            </span>
                            <div class="ml-auto flex items-center gap-2">
                                <button @click="openEditModal(product)" :disabled="isSuspended" :class="isSuspended ? 'text-gray-400 cursor-not-allowed' : 'bg-amber-300 hover:bg-amber-200 text-gray-900'" class="p-2 border-2 border-gray-900 rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition" title="Edit Produk">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button @click="deleteProduct(product.id_produk)" :disabled="isSuspended" :class="isSuspended ? 'text-gray-400 cursor-not-allowed' : 'bg-rose-400 hover:bg-rose-300 text-gray-900'" class="p-2 border-2 border-gray-900 rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition" title="Hapus Produk">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="productList.length === 0" class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada produk yang ditambahkan.</div>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                <th class="p-3.5 rounded-l-xl">Gambar</th>
                                <th class="p-3.5">Nama Produk</th>
                                <th class="p-3.5">Kategori</th>
                                <th class="p-3.5">Harga</th>
                                <th class="p-3.5">Stok</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right rounded-r-xl">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-gray-100">
                            <tr v-for="product in productList" :key="product.id_produk" class="hover:bg-stone-50/50 transition">
                                <td class="py-3.5 px-3">
                                    <div class="relative w-12 h-12 bg-amber-50 border-2 border-gray-900 rounded-xl overflow-hidden flex items-center justify-center shrink-0">
                                        <img 
                                            v-if="getValidImageUrl(product)" 
                                            :src="getValidImageUrl(product)" 
                                            :alt="product.nama_produk"
                                            class="w-full h-full object-cover" 
                                        />
                                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-[7px] font-black uppercase text-gray-700 bg-amber-50 leading-none text-center p-0.5">
                                            <svg class="w-4 h-4 mb-0.5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span>NO PHOTO</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-3 font-black text-xs uppercase text-gray-900">{{ product.nama_produk }}</td>
                                
                                <td class="py-3.5 px-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-if="product.kategori"
                                            class="bg-stone-100 border border-gray-900 text-gray-900 text-[9px] font-black uppercase px-2 py-0.5 rounded-md"
                                        >
                                            {{ product.kategori.nama_kategori }}
                                        </span>
                                        <span v-else class="text-xs font-bold text-gray-400">-</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-3 text-xs font-black text-orange-600">Rp {{ Number(product.harga).toLocaleString('id-ID') }}</td>
                                <td class="py-3.5 px-3">
                                    <span 
                                        :class="{
                                            'bg-emerald-100 text-emerald-900': product.status_stok === 'ready',
                                            'bg-amber-100 text-amber-900': product.status_stok === 'pre-order',
                                            'bg-rose-100 text-rose-900': product.status_stok === 'habis'
                                        }" 
                                        class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border border-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                                    >
                                        {{ product.status_stok === 'ready' ? 'Ready' : (product.status_stok === 'pre-order' ? 'Pre-Order' : 'Habis') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3">
                                    <span
                                        :class="{
                                            'bg-amber-100 text-amber-900': product.status_moderasi === 'pending',
                                            'bg-emerald-100 text-emerald-900': product.status_moderasi === 'disetujui',
                                            'bg-rose-100 text-rose-900': product.status_moderasi === 'ditolak'
                                        }"
                                        class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border border-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]"
                                    >
                                        {{ product.status_moderasi === 'disetujui' ? 'DISETUJUI' : (product.status_moderasi === 'ditolak' ? 'DITOLAK' : 'PENDING') }}
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
                                            @click="deleteProduct(product.id_produk)" 
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
                            <tr v-if="productList.length === 0">
                                <td colspan="7" class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada produk yang ditambahkan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION NAVIGATION -->
                <Pagination :links="products.links || []" :meta="products" />
            </div>

        </div>

        <!-- MODAL POP-UP WAJIB ISI NAMA TOKO -->
        <div v-if="showRequireShopModal" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] max-w-md w-full p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-3.5">
                    <div class="w-10 h-10 bg-amber-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        <svg class="w-6 h-6 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black uppercase text-gray-900 leading-tight">Isi Nama Toko Dulu!</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Syarat sebelum menjual produk</p>
                    </div>
                </div>

                <p class="text-xs font-bold text-gray-800 leading-relaxed bg-amber-50 border-2 border-amber-300 p-3.5 rounded-2xl flex items-center gap-2.5">
                    <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>
                        Kamu wajib mengisi <span class="font-black text-gray-900">Nama Toko Usaha</span> terlebih dahulu agar nama tokomu tertera dengan jelas di katalog publik saat produk di-upload!
                    </span>
                </p>

                <form @submit.prevent="submitShopName" class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-gray-900 mb-1.5">
                            Nama Toko Usaha <span class="text-rose-600">*</span>
                        </label>
                        <input
                            v-model="shopForm.nama_toko"
                            type="text"
                            placeholder="Contoh: Toko Snack Berkah, Dapur Yuta..."
                            required
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl p-3 text-xs font-bold focus:ring-0 focus:border-orange-500"
                        />
                        <div v-if="shopForm.errors.nama_toko" class="text-[11px] font-black uppercase text-rose-600 mt-1">
                            {{ shopForm.errors.nama_toko }}
                        </div>
                    </div>

                    <div class="flex gap-2 justify-end pt-2">
                        <button
                            type="button"
                            @click="showRequireShopModal = false"
                            class="px-4 py-2.5 bg-stone-100 border-2 border-gray-900 rounded-xl text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-stone-200 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="shopForm.processing"
                            class="px-5 py-2.5 bg-orange-500 text-gray-900 border-2 border-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] hover:bg-orange-400 active:scale-95 transition disabled:opacity-50"
                        >
                            {{ shopForm.processing ? 'Menyimpan...' : 'Simpan & Lanjut' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Produk -->
        <div v-if="editingProduct" class="fixed inset-0 z-50 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] max-w-2xl w-full p-6 sm:p-8 space-y-6 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-amber-400 border-2 border-gray-900 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">Edit Produk: {{ editingProduct.nama_produk }}</h3>
                    </div>
                    <button @click="closeEditModal" class="w-8 h-8 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 rounded-xl flex items-center justify-center font-black text-gray-900 transition">
                        &times;
                    </button>
                </div>

                <form @submit.prevent="submitUpdate" class="space-y-4">
                    <div v-if="editForm.retained_photos && editForm.retained_photos.length > 0" class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700">
                                Foto Tersimpan ({{ editForm.retained_photos.length }} Foto)
                            </label>
                            <span class="text-[10px] font-bold text-gray-500">Klik ikon sampah untuk menghapus foto</span>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 bg-stone-50 border-2 border-gray-900 rounded-2xl p-3">
                            <div 
                                v-for="(photo, idx) in editForm.retained_photos" 
                                :key="idx" 
                                class="relative group aspect-square rounded-xl border-2 border-gray-900 overflow-hidden bg-white shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                            >
                                <img v-if="getImageUrl(photo)" :src="getImageUrl(photo)" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center text-[7px] font-black uppercase text-gray-700 bg-amber-50 leading-none text-center p-0.5">
                                    <svg class="w-4 h-4 mb-0.5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span>NO PHOTO</span>
                                </div>
                                <span 
                                    :class="idx === 0 ? 'bg-amber-400 text-gray-900' : 'bg-gray-900 text-white'"
                                    class="absolute top-1 left-1 text-[8px] font-black uppercase px-1.5 py-0.5 rounded border border-gray-900"
                                >
                                    {{ idx === 0 ? 'Cover' : 'Pendukung' }}
                                </span>
                                <button 
                                    type="button" 
                                    @click="removeExistingPhoto(idx)" 
                                    class="absolute top-1 right-1 bg-rose-500 hover:bg-rose-600 text-white p-1 rounded-lg border border-gray-900 shadow transition cursor-pointer"
                                    title="Hapus foto ini"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Nama Produk</label>
                            <input v-model="editForm.nama_produk" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0" required />
                            <div v-if="editForm.errors.nama_produk" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                                {{ editForm.errors.nama_produk }}
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Harga (Rp)</label>
                            <input 
                                v-model="editForm.harga" 
                                type="number" 
                                min="0" 
                                step="500" 
                                @keydown="['-', 'e', 'E', '+'].includes($event.key) && $event.preventDefault()"
                                @input="editForm.harga = editForm.harga < 0 ? 0 : editForm.harga"
                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0" 
                                required 
                            />
                            <div v-if="editForm.errors.harga" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                                {{ editForm.errors.harga }}
                            </div>
                        </div>
                    </div>

                    <!-- KATEGORI JAJANAN -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700">Kategori Jajanan</label>
                            <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 border border-gray-900 px-2 py-0.5 rounded-md uppercase">Pilih 1 Kategori Utama</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                v-for="cat in categories"
                                :key="cat.id_kategori"
                                @click="editForm.id_kategori = cat.id_kategori"
                                :class="editForm.id_kategori === cat.id_kategori ? 'bg-amber-400 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] translate-x-[-1px] translate-y-[-1px]' : 'bg-stone-50 border-gray-900 opacity-60 hover:opacity-80'"
                                class="border-2 rounded-xl p-2.5 text-center font-black text-xs uppercase cursor-pointer select-none transition flex items-center justify-between"
                            >
                                <span>{{ cat.nama_kategori }}</span>
                                <span v-if="editForm.id_kategori === cat.id_kategori" class="text-[10px]">✓</span>
                            </div>
                        </div>
                        <div v-if="editForm.errors.id_kategori" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                            {{ editForm.errors.id_kategori }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Status Stok Produk</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <div
                                @click="editForm.status_stok = 'ready'"
                                :class="editForm.status_stok === 'ready' ? 'bg-emerald-300 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'bg-stone-50 border-gray-900 opacity-60'"
                                class="border-2 rounded-xl p-2.5 text-center font-black text-[10px] uppercase cursor-pointer select-none transition"
                            >
                                Ready
                            </div>
                            <div
                                @click="editForm.status_stok = 'pre-order'"
                                :class="editForm.status_stok === 'pre-order' ? 'bg-amber-300 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]' : 'bg-stone-50 border-gray-900 opacity-60'"
                                class="border-2 rounded-xl p-2.5 text-center font-black text-[10px] uppercase cursor-pointer select-none transition"
                            >
                                Pre-Order
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">
                                Ganti Foto Utama (Cover)
                            </label>
                            <input 
                                @input="editForm.foto_produk = $event.target.files[0]" 
                                type="file" 
                                accept="image/*" 
                                class="w-full text-xs font-bold text-gray-600 bg-stone-50 border-2 border-gray-900 rounded-xl p-2 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-2 file:border-gray-900 file:text-xs file:font-black file:bg-amber-400 hover:file:bg-amber-300" 
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">
                                Tambah Foto Pendukung Baru
                            </label>
                            <input 
                                @change="handleEditGalleryChange" 
                                multiple 
                                type="file" 
                                accept="image/*" 
                                class="w-full text-xs font-bold text-gray-600 bg-stone-50 border-2 border-gray-900 rounded-xl p-2 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-2 file:border-gray-900 file:text-xs file:font-black file:bg-orange-400 hover:file:bg-orange-300" 
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">
                            Deskripsi Singkat / Varian <span class="text-rose-600">*</span>
                        </label>
                        <textarea 
                            v-model="editForm.deskripsi" 
                            rows="2" 
                            required 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold focus:border-orange-500 focus:ring-0"
                        ></textarea>
                        <div v-if="editForm.errors.deskripsi" class="text-rose-600 text-[11px] font-black uppercase mt-1">
                            {{ editForm.errors.deskripsi }}
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t-2 border-gray-100">
                        <button type="button" @click="closeEditModal" class="px-5 py-2.5 bg-stone-100 border-2 border-gray-900 text-gray-900 rounded-xl font-black text-xs uppercase tracking-wider hover:bg-stone-200 transition">
                            Batal
                        </button>
                        <button type="submit" :disabled="editForm.processing || !editForm.id_kategori" class="px-5 py-2.5 bg-orange-500 border-2 border-gray-900 text-gray-900 rounded-xl font-black text-xs uppercase tracking-wider hover:bg-orange-400 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>