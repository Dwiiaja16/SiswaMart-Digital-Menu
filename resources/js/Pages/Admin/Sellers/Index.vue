<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    sellers: {
        type: Array,
        default: () => [],
    },
});

// Form Registrasi Penjual Baru
const createForm = useForm({
    username: "",
    password: "",
    whatsapp_number: "",
    full_name: "",
    class_name: "",
    nisn: "",
    photo: null,
    address: "",
});

const submitCreate = () => {
    createForm.post(route("admin.sellers.store"), {
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
const editSellerId = ref(null);

const editForm = useForm({
    username: "",
    password: "",
    whatsapp_number: "",
    full_name: "",
    class_name: "",
    nisn: "",
    photo: null,
    address: "",
    _method: "PUT",
});

const openEdit = (seller) => {
    editSellerId.value = seller.id;
    editForm.username = seller.username || "";
    editForm.password = "";
    editForm.whatsapp_number = seller.whatsapp_number || "";
    editForm.full_name = seller.seller_profile?.full_name || seller.name || "";
    editForm.class_name = seller.seller_profile?.class_name || "";
    editForm.nisn = seller.seller_profile?.nisn || "";
    editForm.address = seller.seller_profile?.address || "";
    editForm.photo = null;

    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
    editSellerId.value = null;
    editForm.reset();
};

const submitEdit = () => {
    // 1. Pastikan _method diisi 'PUT' secara eksplisit
    editForm._method = 'PUT';

    // 2. Kirim menggunakan .post() ke route update
    editForm.post(route("admin.sellers.update", editSellerId.value), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            alert("Data penjual berhasil diperbarui!");
            closeEdit();
        },
        onError: (errors) => {
            // Tampilkan error di console & alert jika ada validasi yang gagal (misal: NISN/Username sudah dipakai)
            console.error("Validation Errors:", errors);
            alert("Gagal memperbarui: " + Object.values(errors).join(", "));
        },
    });
};

// Toggle Suspend Account
const toggleSuspend = (sellerId) => {
    router.patch(route("admin.sellers.toggle-suspend", sellerId), {}, {
        preserveScroll: true,
    });
};

// Hapus Penjual
const deleteSeller = (sellerId) => {
    if (confirm("Apakah Anda yakin ingin menghapus akun penjual ini beserta profil dan lapaknya?")) {
        router.delete(route("admin.sellers.destroy", sellerId), {
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
                    
                    <!-- Header Form Utama -->
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
                        
                        <!-- ================= BAGIAN 1: DATA AKUN ================= -->
                        <div class="bg-amber-50/60 border-2 border-gray-900 rounded-2xl p-4 sm:p-5 space-y-4 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-orange-500 border-2 border-gray-900 rounded-lg text-[10px] sm:text-xs font-black text-white uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    BAGIAN 1
                                </span>
                                <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-wider">
                                    DATA AKUN PENJUAL
                                </h3>
                            </div>

                            <!-- Grid 3 Kolom Sejajar untuk Akun -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">USERNAME LOGIN</label>
                                    <input v-model="createForm.username" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Contoh: dwipenjual" required />
                                    <div v-if="createForm.errors.username" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.username }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">PASSWORD AWAL AKUN</label>
                                    <input v-model="createForm.password" type="password" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Password login pertama" required />
                                    <div v-if="createForm.errors.password" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.password }}</div>
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">NOMOR WHATSAPP AKTIF</label>
                                    <input v-model="createForm.whatsapp_number" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="081234567890" required />
                                    <div v-if="createForm.errors.whatsapp_number" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.whatsapp_number }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- ================= BAGIAN 2: DATA PROFIL ================= -->
                        <div class="bg-stone-50 border-2 border-gray-900 rounded-2xl p-4 sm:p-5 space-y-4 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-amber-400 border-2 border-gray-900 rounded-lg text-[10px] sm:text-xs font-black text-gray-900 uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    BAGIAN 2
                                </span>
                                <h3 class="text-xs sm:text-sm font-black text-gray-900 uppercase tracking-wider">
                                    DATA PENJUAL
                                </h3>
                            </div>

                            <!-- Baris 1: Informasi Akademik (3 Kolom) -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">NAMA LENGKAP PENJUAL</label>
                                    <input v-model="createForm.full_name" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Nama lengkap siswa" required />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">KELAS ASAL PENJUAL</label>
                                    <input v-model="createForm.class_name" type="text" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Contoh: XII RPL 1" required />
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
                            </div>

                            <!-- Baris 2: Berkas & Alamat (2 Kolom) -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">FOTO PENJUAL (OPSIONAL)</label>
                                    <input @change="e => createForm.photo = e.target.files[0]" type="file" accept="image/*" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-1.5 text-xs font-bold text-gray-900 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-2 file:border-gray-900 file:text-[10px] file:font-black file:bg-amber-300 transition" />
                                </div>

                                <div class="space-y-1.5">
                                    <label class="block text-[11px] font-black uppercase text-gray-800">ALAMAT TEMPAT TINGGAL</label>
                                    <textarea v-model="createForm.address" rows="1" class="w-full bg-white border-2 border-gray-900 rounded-xl px-3.5 py-2 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition" placeholder="Alamat tempat tinggal siswa"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Submit Utama -->
                        <div class="pt-2 flex justify-end">
                            <button type="submit" :disabled="createForm.processing" class="w-full sm:w-auto px-8 py-3.5 bg-orange-500 hover:bg-orange-400 border-3 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center justify-center gap-2">
                                <span>+ BUAT AKUN & PROFIL PENJUAL</span>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- 2. DAFTAR PENJUAL TERDAFTAR -->
                <div class="bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-8 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] space-y-4 sm:space-y-6">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-3 sm:pb-4">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-emerald-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h2 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">
                            DAFTAR PENJUAL TERDAFTAR
                        </h2>
                    </div>

                    <!-- TABEL UTAMA -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                    <th class="p-3.5 rounded-l-xl">SISWA PENJUAL</th>
                                    <th class="p-3.5">KELAS & NISN</th>
                                    <th class="p-3.5">NAMA LAPAK USAHA</th>
                                    <th class="p-3.5">WHATSAPP</th>
                                    <th class="p-3.5">STATUS AKUN</th>
                                    <th class="p-3.5 text-center rounded-r-xl">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-100">
                                <tr v-for="seller in sellers" :key="seller.id" class="hover:bg-stone-50/50 transition">
                                    <td class="py-4 px-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl border-2 border-gray-900 bg-amber-200 overflow-hidden shrink-0 flex items-center justify-center">
                                                <img 
                                                    v-if="seller.seller_profile?.photo_path" 
                                                    :src="getPhotoUrl(seller.seller_profile.photo_path)" 
                                                    class="w-full h-full object-cover object-center max-w-full max-h-full" 
                                                />
                                                <div v-else class="w-full h-full flex items-center justify-center font-black text-xs text-gray-700">
                                                    {{ (seller.seller_profile?.full_name || seller.username).substring(0, 2).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-black text-xs uppercase text-gray-900">
                                                    {{ seller.seller_profile?.full_name || seller.name || seller.username }}
                                                </div>
                                                <div class="text-[10px] font-bold text-orange-600">
                                                    @{{ seller.username }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-4 px-3.5 text-xs">
                                        <div class="font-black text-gray-800 uppercase">
                                            {{ seller.seller_profile?.class_name || "-" }}
                                        </div>
                                        <div class="text-[10px] font-bold text-gray-400">
                                            NISN: {{ seller.seller_profile?.nisn || "-" }}
                                        </div>
                                    </td>

                                    <td class="py-4 px-3.5 text-xs font-bold text-gray-700 uppercase">
                                        {{ seller.shop ? seller.shop.name : "-" }}
                                    </td>

                                    <td class="py-4 px-3.5 text-xs font-bold text-emerald-600">
                                        {{ seller.whatsapp_number }}
                                    </td>

                                    <td class="py-4 px-3.5">
                                        <span :class="seller.is_suspended ? 'bg-rose-200 text-rose-900 border-rose-900' : 'bg-emerald-200 text-emerald-900 border-emerald-900'" class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border-2 inline-block">
                                            {{ seller.is_suspended ? 'TER-SUSPEND' : 'AKTIF' }}
                                        </span>
                                    </td>

                                    <td class="py-4 px-3.5">
                                        <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                            <!-- Tombol VIEW DETAIL -->
                                            <button @click="openDetail(seller)" class="px-2.5 py-1 bg-sky-300 hover:bg-sky-200 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                DETAIL
                                            </button>

                                            <!-- Tombol EDIT -->
                                            <button @click="openEdit(seller)" class="px-2.5 py-1 bg-amber-300 hover:bg-amber-200 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                EDIT
                                            </button>

                                            <!-- Tombol HAPUS -->
                                            <button @click="deleteSeller(seller.id)" class="px-2.5 py-1 bg-rose-400 hover:bg-rose-300 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition">
                                                HAPUS
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="sellers.length === 0">
                                    <td colspan="6" class="py-8 text-center text-xs font-black uppercase text-gray-400">
                                        Belum ada data penjual terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL DETAIL PROFIL PENJUAL -->
        <div v-if="showDetailModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-6 max-h-[90vh] overflow-y-auto">
                
                <!-- Modal Header -->
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
                    <!-- Foto Profil Utama -->
                    <div class="flex flex-col sm:flex-row items-center gap-5 bg-amber-50 border-3 border-gray-900 p-5 rounded-2xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)]">
                        <div class="w-32 h-32 sm:w-40 sm:h-40 rounded-2xl border-3 border-gray-900 bg-amber-200 overflow-hidden shrink-0 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] flex items-center justify-center">
                            <img 
                                v-if="selectedSeller.seller_profile?.photo_path" 
                                :src="getPhotoUrl(selectedSeller.seller_profile.photo_path)" 
                                class="w-full h-full object-cover object-center max-w-full max-h-full" 
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center font-black text-2xl text-gray-700 bg-amber-100">
                                <span>{{ (selectedSeller.seller_profile?.full_name || selectedSeller.username).substring(0, 2).toUpperCase() }}</span>
                                <span class="text-[10px] font-bold text-gray-400 mt-1">NO PHOTO</span>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-center sm:text-left flex-1">
                            <div>
                                <span class="text-[10px] font-black text-amber-800 uppercase tracking-wider block">NAMA LENGKAP PENJUAL</span>
                                <h4 class="font-black text-xl sm:text-2xl uppercase text-gray-900 leading-tight">
                                    {{ selectedSeller.seller_profile?.full_name || selectedSeller.name || selectedSeller.username }}
                                </h4>
                            </div>
                            
                            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                                <span class="px-3 py-1 bg-orange-400 border-2 border-gray-900 rounded-lg text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    @{{ selectedSeller.username }}
                                </span>
                                <span class="px-3 py-1 bg-emerald-300 border-2 border-gray-900 rounded-lg text-xs font-black text-emerald-950 uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                                    WA: {{ selectedSeller.whatsapp_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Detail Kelas & NISN -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Kelas Asal Penjual</span>
                            <span class="font-black text-base text-gray-900 uppercase block">{{ selectedSeller.seller_profile?.class_name || '-' }}</span>
                        </div>
                        
                        <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                            <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">NISN Siswa</span>
                            <span class="font-black text-base text-gray-900 block">{{ selectedSeller.seller_profile?.nisn || '-' }}</span>
                        </div>
                    </div>

                    <!-- Lapak Usaha -->
                    <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Nama Lapak Usaha</span>
                        <span class="font-black text-base text-gray-900 uppercase block">{{ selectedSeller.shop?.name || '-' }}</span>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="bg-stone-50 border-2 border-gray-900 p-4 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] space-y-1">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-wider">Alamat Tempat Tinggal</span>
                        <p class="font-bold text-sm text-gray-800 leading-relaxed">{{ selectedSeller.seller_profile?.address || 'Belum diisi' }}</p>
                    </div>
                </div>

                <!-- Footer Modal -->
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
                            <label class="block text-[10px] font-black uppercase text-gray-700">Password Baru (Opsional)</label>
                            <input v-model="editForm.password" type="password" placeholder="Kosongkan jika tidak diganti" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Nomor WA</label>
                            <input v-model="editForm.whatsapp_number" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-700">Nama Lengkap</label>
                            <input v-model="editForm.full_name" type="text" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1.5 text-xs font-bold" required />
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
                        <input @change="e => editForm.photo = e.target.files[0]" type="file" accept="image/*" class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-1 text-xs font-bold" />
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