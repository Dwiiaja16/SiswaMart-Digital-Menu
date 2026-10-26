<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    sellers: {
        type: [Array, Object],
        default: () => [],
    },
});

const search = ref("");

// Helper untuk Mengambil Nama Toko Usaha secara Fleksibel
const getStoreName = (seller) => {
    if (!seller) return '-';
    const profile = seller.dataPenjual || seller.data_penjual || seller.seller_profile || {};
    
    if (profile.nama_toko && profile.nama_toko.trim() !== '') return profile.nama_toko;
    if (profile.nama_usaha && profile.nama_usaha.trim() !== '') return profile.nama_usaha;
    if (profile.nama_toko_usaha && profile.nama_toko_usaha.trim() !== '') return profile.nama_toko_usaha;
    if (seller.shop?.name) return seller.shop.name;
    if (seller.shop?.nama_toko) return seller.shop.nama_toko;

    const sellerName = profile.nama_lengkap || profile.full_name || seller.name || seller.username;
    return sellerName ? `Toko ${sellerName}` : '-';
};

const sellerList = computed(() => {
    if (Array.isArray(props.sellers)) return props.sellers;
    return props.sellers?.data || [];
});

// Filter Pencarian Client-Side Berdasarkan AWALAN / HURUF DEPAN (startsWith)
const filteredSellers = computed(() => {
    if (!search.value || search.value.trim() === '') return sellerList.value;
    const q = search.value.toLowerCase().trim();

    return sellerList.value.filter((seller) => {
        const profile = seller.dataPenjual || seller.data_penjual || seller.seller_profile || {};
        const fullName = (profile.nama_lengkap || profile.full_name || seller.name || '').trim().toLowerCase();
        const username = (seller.username || '').trim().toLowerCase();
        const className = (profile.nama_kelas || profile.class_name || '').trim().toLowerCase();
        const nisn = (profile.nisn || '').trim().toLowerCase();
        const storeName = getStoreName(seller).trim().toLowerCase();
        const email = (profile.email || seller.email || '').trim().toLowerCase();

        return (
            fullName.startsWith(q) || 
            username.startsWith(q) || 
            className.startsWith(q) || 
            nisn.startsWith(q) || 
            storeName.startsWith(q) ||
            email.startsWith(q)
        );
    });
});

// Form Registrasi Penjual Baru
const createForm = useForm({
    username: "",
    password: "",
    email: "", // Email wajib diisi Gmail asli
    whatsapp_number: "",
    nomor_whatsapp: "",
    full_name: "",
    nama_lengkap: "",
    class_name: "",
    nama_kelas: "",
    nama_toko: "",
    nisn: "",
    photo: null,
    foto_penjual: null,
    address: "",
    alamat: "",
});

const submitCreate = () => {
    createForm.nama_lengkap = createForm.full_name;
    createForm.nama_kelas = createForm.class_name;
    createForm.nomor_whatsapp = createForm.whatsapp_number;
    createForm.alamat = createForm.address;
    createForm.foto_penjual = createForm.photo;

    const storeRoute = route().has("admin.sellers.store")
        ? route("admin.sellers.store")
        : route("admin.penjual.store");

    createForm.post(storeRoute, {
        forceFormData: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
};

// --- MODAL DETAIL (VIEW PROFIL) ---
const selectedSeller = ref(null);
const showDetailModal = ref(false);

const openDetail = (seller) => {
    selectedSeller.value = seller;
    showDetailModal.value = true;
};

const closeDetail = () => {
    showDetailModal.value = false;
    selectedSeller.value = null;
};

// --- MODAL EDIT DATA PENJUAL ---
const showEditModal = ref(false);
const editSellerIdentifier = ref(null);

const editForm = useForm({
    username: "",
    password: "",
    email: "",
    whatsapp_number: "",
    nomor_whatsapp: "",
    full_name: "",
    nama_lengkap: "",
    class_name: "",
    nama_kelas: "",
    nama_toko: "",
    nisn: "",
    photo: null,
    foto_penjual: null,
    address: "",
    alamat: "",
    _method: "PUT",
});

const openEdit = (seller) => {
    editSellerIdentifier.value = seller.username || seller.id;

    const profile = seller.dataPenjual || seller.data_penjual || seller.seller_profile || {};

    editForm.username = seller.username || "";
    editForm.password = "";
    editForm.email = profile.email || seller.email || "";
    
    const wa = profile.nomor_whatsapp || seller.whatsapp_number || "";
    editForm.whatsapp_number = wa;
    editForm.nomor_whatsapp = wa;

    const fullName = profile.nama_lengkap || profile.full_name || seller.name || "";
    editForm.full_name = fullName;
    editForm.nama_lengkap = fullName;

    const className = profile.nama_kelas || profile.class_name || "";
    editForm.class_name = className;
    editForm.nama_kelas = className;

    editForm.nama_toko = profile.nama_toko || profile.nama_usaha || "";
    editForm.nisn = profile.nisn || "";

    const addr = profile.alamat || profile.address || "";
    editForm.address = addr;
    editForm.alamat = addr;

    editForm.photo = null;
    editForm.foto_penjual = null;

    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
    editSellerIdentifier.value = null;
    editForm.reset();
};

const submitEdit = () => {
    editForm._method = 'PUT';
    editForm.nama_lengkap = editForm.full_name;
    editForm.nama_kelas = editForm.class_name;
    editForm.nomor_whatsapp = editForm.whatsapp_number;
    editForm.alamat = editForm.address;
    if (editForm.photo) {
        editForm.foto_penjual = editForm.photo;
    }

    const updateRoute = route().has("admin.sellers.update")
        ? route("admin.sellers.update", editSellerIdentifier.value)
        : route("admin.penjual.update", editSellerIdentifier.value);

    editForm.post(updateRoute, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            alert("Data penjual berhasil diperbarui!");
            closeEdit();
        },
        onError: (errors) => {
            console.error("Validation Errors:", errors);
            alert("Gagal memperbarui: " + Object.values(errors).join(", "));
        },
    });
};

// Hapus Penjual
const deleteSeller = (seller) => {
    const identifier = typeof seller === 'object' ? (seller.username || seller.id) : seller;
    if (confirm("Apakah Anda yakin ingin menghapus akun penjual ini beserta profil dan tokonya?")) {
        const deleteRoute = route().has("admin.sellers.destroy")
            ? route("admin.sellers.destroy", identifier)
            : route("admin.penjual.destroy", identifier);

        router.delete(deleteRoute, {
            preserveScroll: true,
        });
    }
};

const getPhotoUrl = (photoPath) => {
    if (!photoPath) return null;
    if (photoPath.startsWith("http")) return photoPath;
    return photoPath.startsWith("/") ? photoPath : `/${photoPath}`;
};
</script>

<template>
    <Head title="Kelola Akun Penjual - SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-8 bg-stone-50 min-h-[calc(100vh-80px)] selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">
                
                <!-- Header Card -->
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-500 border-3 sm:border-4 border-gray-900 rounded-2xl flex items-center justify-center shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black uppercase tracking-tight text-gray-900">
                            KELOLA AKUN <span class="text-orange-500">PENJUAL</span>
                        </h1>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-400 uppercase">
                            Manajemen akun siswa wirausaha SiswaMart
                        </p>
                    </div>
                </div>

                <!-- 1. FORM DAFTARKAN PENJUAL BARU -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6">
                    
                    <div class="flex items-center gap-3 border-b-3 border-gray-900 pb-4">
                        <div class="w-9 h-9 bg-amber-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <h2 class="text-lg sm:text-xl font-black uppercase tracking-tight text-gray-900">
                            DAFTARKAN PENJUAL BARU
                        </h2>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-6">
                        
                        <!-- BAGIAN 1: DATA AKUN -->
                        <div class="bg-amber-50/60 border-2 border-gray-900 rounded-2xl p-4 sm:p-5 space-y-4 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-orange-500 border-2 border-gray-900 rounded-lg text-[10px] sm:text-xs font-black text-white uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    BAGIAN 1
                                </span>
                                <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-wider">
                                    DATA AKUN & EMAIL GOOGLE PENJUAL
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">USERNAME LOGIN</label>
                                    <input v-model="createForm.username" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Contoh: dwipenjual" required />
                                    <div v-if="createForm.errors.username" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.username }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">EMAIL GOOGLE AKTIF <span class="text-rose-600">*</span></label>
                                    <input v-model="createForm.email" type="email" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Contoh: penjual@gmail.com" required />
                                    <div v-if="createForm.errors.email" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.email }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">PASSWORD AWAL AKUN</label>
                                    <input v-model="createForm.password" type="password" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Password login pertama" required />
                                    <div v-if="createForm.errors.password" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.password }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 2: DATA PROFIL -->
                        <div class="bg-stone-50 border-2 border-gray-900 rounded-2xl p-4 sm:p-5 space-y-4 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-amber-400 border-2 border-gray-900 rounded-lg text-[10px] sm:text-xs font-black text-gray-900 uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    BAGIAN 2
                                </span>
                                <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-wider">
                                    DATA PENJUAL & TOKO
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">NAMA LENGKAP PENJUAL</label>
                                    <input v-model="createForm.full_name" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Nama lengkap siswa" required />
                                    <div v-if="createForm.errors.full_name || createForm.errors.nama_lengkap" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.full_name || createForm.errors.nama_lengkap }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">NAMA TOKO USAHA (OPSIONAL)</label>
                                    <input v-model="createForm.nama_toko" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Contoh: Kedai Dwi Jaya" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">KELAS ASAL PENJUAL</label>
                                    <input v-model="createForm.class_name" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Contoh: XII RPL 1" required />
                                    <div v-if="createForm.errors.class_name || createForm.errors.nama_kelas" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.class_name || createForm.errors.nama_kelas }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">NOMOR WHATSAPP AKTIF</label>
                                    <input v-model="createForm.whatsapp_number" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="081234567890" required />
                                    <div v-if="createForm.errors.whatsapp_number || createForm.errors.nomor_whatsapp" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.whatsapp_number || createForm.errors.nomor_whatsapp }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">NISN SISWA</label>
                                    <input
                                        v-model="createForm.nisn"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="10"
                                        pattern="[0-9]{0,10}"
                                        @input="createForm.nisn = createForm.nisn.replace(/\D/g, '').slice(0, 10)"
                                        class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition"
                                        placeholder="10 digit NISN"
                                    />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">FOTO PENJUAL (OPSIONAL)</label>
                                    <input @change="e => { createForm.photo = e.target.files[0]; createForm.foto_penjual = e.target.files[0]; }" type="file" accept="image/*" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-1.5 text-xs font-bold text-gray-900 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-2 file:border-gray-900 file:text-[10px] file:font-black file:bg-amber-300 transition" />
                                </div>
                            </div>

                            <div class="space-y-1.5 pt-1">
                                <label class="block text-[11px] font-black uppercase text-gray-800">ALAMAT TEMPAT TINGGAL (OPSIONAL)</label>
                                <textarea v-model="createForm.address" rows="1" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Alamat tempat tinggal siswa"></textarea>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" :disabled="createForm.processing" class="w-full sm:w-auto px-8 py-3.5 bg-orange-500 hover:bg-orange-400 border-3 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-2">
                                <span>+ BUAT AKUN & PROFIL PENJUAL</span>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- 2. DAFTAR PENJUAL TERDAFTAR -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] space-y-4 sm:space-y-6">
                    
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b-2 border-gray-100 pb-3 sm:pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-emerald-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h2 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">
                                DAFTAR PENJUAL TERDAFTAR
                            </h2>
                        </div>

                        <!-- INPUT SEARCH BAR PENJUAL / TOKO -->
                        <div class="relative w-full sm:w-72 md:w-80 flex items-center">
                            <svg class="w-4 h-4 absolute left-3.5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                            </svg>
                            <input 
                                v-model="search" 
                                type="text" 
                                placeholder="Cari nama, email, toko, kelas..." 
                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl pl-10 pr-3.5 py-2 text-xs font-bold text-gray-900 focus:border-orange-500 focus:ring-0 transition" 
                            />
                        </div>
                    </div>

                    <!-- TABEL UTAMA -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                    <th class="p-3.5 rounded-l-xl">SISWA PENJUAL</th>
                                    <th class="p-3.5">EMAIL GOOGLE</th>
                                    <th class="p-3.5">KELAS & NISN</th>
                                    <th class="p-3.5">NAMA TOKO USAHA</th>
                                    <th class="p-3.5">WHATSAPP</th>
                                    <th class="p-3.5">STATUS AKUN</th>
                                    <th class="p-3.5 text-center rounded-r-xl">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-100">
                                <tr v-for="seller in filteredSellers" :key="seller.id || seller.user_id || seller.username" class="hover:bg-stone-50/50 transition">
                                    <td class="py-4 px-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl border-2 border-gray-900 bg-amber-200 overflow-hidden shrink-0 flex items-center justify-center">
                                                <img 
                                                    v-if="seller.dataPenjual?.foto_penjual || seller.data_penjual?.foto_penjual || seller.seller_profile?.photo_path" 
                                                    :src="getPhotoUrl(seller.dataPenjual?.foto_penjual || seller.data_penjual?.foto_penjual || seller.seller_profile?.photo_path)" 
                                                    class="w-full h-full object-cover object-center max-w-full max-h-full" 
                                                />
                                                <div v-else class="w-full h-full flex items-center justify-center font-black text-xs text-gray-700">
                                                    {{ (seller.dataPenjual?.nama_lengkap || seller.seller_profile?.full_name || seller.username).substring(0, 2).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-black text-xs uppercase text-gray-900">
                                                    {{ seller.dataPenjual?.nama_lengkap || seller.data_penjual?.nama_lengkap || seller.seller_profile?.full_name || seller.name || seller.username }}
                                                </div>
                                                <div class="text-[10px] font-bold text-orange-600">
                                                    @{{ seller.username }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-4 px-3.5 text-xs font-bold text-gray-800">
                                        {{ seller.dataPenjual?.email || seller.data_penjual?.email || seller.email || '-' }}
                                    </td>

                                    <td class="py-4 px-3.5 text-xs">
                                        <div class="font-black text-gray-800 uppercase">
                                            {{ seller.dataPenjual?.nama_kelas || seller.data_penjual?.nama_kelas || seller.seller_profile?.class_name || "-" }}
                                        </div>
                                        <div class="text-[10px] font-bold text-gray-400">
                                            NISN: {{ seller.dataPenjual?.nisn || seller.data_penjual?.nisn || "-" }}
                                        </div>
                                    </td>

                                    <td class="py-4 px-3.5 text-xs font-bold text-gray-900 uppercase">
                                        {{ getStoreName(seller) }}
                                    </td>

                                    <td class="py-4 px-3.5 text-xs font-bold text-emerald-600">
                                        {{ seller.dataPenjual?.nomor_whatsapp || seller.data_penjual?.nomor_whatsapp || seller.whatsapp_number || "-" }}
                                    </td>

                                    <td class="py-4 px-3.5">
                                        <span :class="seller.is_suspended ? 'bg-rose-200 text-rose-900 border-rose-900' : 'bg-emerald-200 text-emerald-900 border-emerald-900'" class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border-2 inline-block">
                                            {{ seller.is_suspended ? 'TER-SUSPEND' : 'AKTIF' }}
                                        </span>
                                    </td>

                                    <td class="py-4 px-3.5">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <button @click="openDetail(seller)" class="px-2.5 py-1 bg-sky-300 hover:bg-sky-200 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                DETAIL
                                            </button>

                                            <button @click="openEdit(seller)" class="px-2.5 py-1 bg-amber-300 hover:bg-amber-200 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                EDIT
                                            </button>

                                            <button @click="deleteSeller(seller)" class="px-2.5 py-1 bg-rose-400 hover:bg-rose-300 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                HAPUS
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredSellers.length === 0">
                                    <td colspan="7" class="py-8 text-center text-xs font-black uppercase text-gray-400">
                                        Tidak ada data penjual ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION NAVIGATION -->
                    <Pagination :links="sellers.links || []" :meta="sellers" />
                </div>

            </div>
        </div>

        <!-- MODAL DETAIL PROFIL PENJUAL -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-6 max-h-[90vh] overflow-y-auto">
                
                <div class="flex justify-between items-center border-b-2 border-gray-200 pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 bg-sky-300 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <h3 class="font-black text-xl text-gray-900 uppercase tracking-tight">DETAIL PROFIL PENJUAL</h3>
                    </div>
                    <button @click="closeDetail" class="w-9 h-9 bg-rose-400 hover:bg-rose-300 border-2 border-gray-900 rounded-xl font-black text-base text-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center">
                        ✕
                    </button>
                </div>

                <div v-if="selectedSeller" class="space-y-5">
                    <div class="flex flex-col sm:flex-row items-center gap-5 bg-amber-50 border-3 border-gray-900 p-5 rounded-2xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                        <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-2xl border-3 border-gray-900 bg-amber-200 overflow-hidden shrink-0 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] flex items-center justify-center">
                            <img 
                                v-if="selectedSeller.dataPenjual?.foto_penjual || selectedSeller.data_penjual?.foto_penjual || selectedSeller.seller_profile?.photo_path" 
                                :src="getPhotoUrl(selectedSeller.dataPenjual?.foto_penjual || selectedSeller.data_penjual?.foto_penjual || selectedSeller.seller_profile?.photo_path)" 
                                class="w-full h-full object-cover object-center max-w-full max-h-full" 
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center font-black text-2xl text-gray-700 bg-amber-100">
                                <span>{{ (selectedSeller.dataPenjual?.nama_lengkap || selectedSeller.seller_profile?.full_name || selectedSeller.username).substring(0, 2).toUpperCase() }}</span>
                                <span class="text-[10px] font-bold text-gray-400 mt-1">NO PHOTO</span>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-center sm:text-left flex-1">
                            <div>
                                <span class="text-[10px] font-black text-amber-800 uppercase tracking-wider block">NAMA LENGKAP PENJUAL</span>
                                <h4 class="font-black text-xl sm:text-2xl uppercase text-gray-900 leading-tight">
                                    {{ selectedSeller.dataPenjual?.nama_lengkap || selectedSeller.data_penjual?.nama_lengkap || selectedSeller.seller_profile?.full_name || selectedSeller.name || selectedSeller.username }}
                                </h4>
                            </div>
                            
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                                <span class="px-3 py-1 bg-orange-400 border-2 border-gray-900 rounded-lg text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    @{{ selectedSeller.username }}
                                </span>
                                <span class="px-3 py-1 bg-emerald-300 border-2 border-gray-900 rounded-lg text-xs font-black text-emerald-950 uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    WA: {{ selectedSeller.dataPenjual?.nomor_whatsapp || selectedSeller.data_penjual?.nomor_whatsapp || selectedSeller.whatsapp_number || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Email Google Penjual</span>
                            <span class="font-bold text-sm text-gray-900 block">{{ selectedSeller.dataPenjual?.email || selectedSeller.data_penjual?.email || selectedSeller.email || '-' }}</span>
                        </div>

                        <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Kelas Asal Penjual</span>
                            <span class="font-black text-base text-gray-900 uppercase block">{{ selectedSeller.dataPenjual?.nama_kelas || selectedSeller.data_penjual?.nama_kelas || selectedSeller.seller_profile?.class_name || '-' }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Nama Toko Usaha</span>
                            <span class="font-black text-base text-gray-900 uppercase block">{{ getStoreName(selectedSeller) }}</span>
                        </div>
                        
                        <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">NISN</span>
                            <span class="font-black text-base text-gray-900 block">
                                {{ selectedSeller.dataPenjual?.nisn || selectedSeller.data_penjual?.nisn || '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Alamat Tempat Tinggal</span>
                        <p class="font-bold text-sm text-gray-800 leading-relaxed">{{ selectedSeller.dataPenjual?.alamat || selectedSeller.data_penjual?.alamat || selectedSeller.seller_profile?.address || 'Belum diisi' }}</p>
                    </div>
                </div>

                <div class="pt-2">
                    <button @click="closeDetail" class="w-full py-3 bg-gray-900 hover:bg-gray-800 text-white font-black text-xs uppercase tracking-widest rounded-xl border-2 border-gray-900 shadow-[3px_3px_0px_0px_rgba(242,92,5,1)] active:scale-95 transition">
                        TUTUP DETAIL
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT DATA PENJUAL -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl max-w-xl w-full p-6 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                    <h3 class="font-black text-lg text-gray-900 uppercase">EDIT DATA PENJUAL</h3>
                    <button @click="closeEdit" class="text-gray-900 font-black text-xl hover:text-rose-600">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-3 text-left">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Username</label>
                            <input v-model="editForm.username" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Email Google Penjual</label>
                            <input v-model="editForm.email" type="email" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Nama Lengkap</label>
                            <input v-model="editForm.full_name" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Password Baru (Opsional)</label>
                            <input v-model="editForm.password" type="password" placeholder="Kosongkan jika tidak diganti" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Nama Toko Usaha</label>
                            <input v-model="editForm.nama_toko" type="text" placeholder="Contoh: Kedai Dwi Jaya" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Nomor WA</label>
                            <input v-model="editForm.whatsapp_number" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Kelas Asal</label>
                            <input v-model="editForm.class_name" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">NISN</label>
                            <input
                                v-model="editForm.nisn"
                                type="text"
                                inputmode="numeric"
                                maxlength="10"
                                pattern="[0-9]{0,10}"
                                @input="editForm.nisn = editForm.nisn.replace(/\D/g, '').slice(0, 10)"
                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold"
                                placeholder="10 digit NISN"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-700">Ganti Foto Penjual (Opsional)</label>
                        <input @change="e => { editForm.photo = e.target.files[0]; editForm.foto_penjual = e.target.files[0]; }" type="file" accept="image/*" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1 text-xs font-bold" />
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-700">Alamat</label>
                        <textarea v-model="editForm.address" rows="2" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold"></textarea>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="closeEdit" class="flex-1 py-2 bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase rounded-xl">
                            BATAL
                        </button>
                        <button type="submit" :disabled="editForm.processing" class="flex-1 py-2 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            SIMPAN PERUBAHAN
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>