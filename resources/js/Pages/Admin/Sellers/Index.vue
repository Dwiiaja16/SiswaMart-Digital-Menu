<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";

const props = defineProps({
    sellers: {
        type: Array,
        default: () => [],
    },
});

// Form Registrasi Penjual Baru (Manual Password Only)
const createForm = useForm({
    username: "",
    password: "",
    whatsapp_number: "",
});

const submitCreate = () => {
    createForm.post(route("sellers.store"), {
        onSuccess: () => {
            createForm.reset();
        },
    });
};

// Toggle Suspend Account
const toggleSuspend = (sellerId) => {
    router.patch(route("sellers.toggle-suspend", sellerId), {}, {
        preserveScroll: true,
    });
};

// Hapus Penjual
const deleteSeller = (sellerId) => {
    if (confirm("Apakah Anda yakin ingin menghapus akun penjual ini beserta lapaknya?")) {
        router.delete(route("sellers.destroy", sellerId), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Kelola Akun Penjual - SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-8 bg-stone-50 min-h-[calc(100vh-80px)] selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header Card -->
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-orange-500 border-4 border-gray-900 rounded-2xl flex items-center justify-center shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] shrink-0">
                        <svg class="w-6 h-6 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-black uppercase tracking-tight text-gray-900">
                            KELOLA AKUN <span class="text-orange-500">PENJUAL</span>
                        </h1>
                        <p class="text-xs font-bold text-gray-400 uppercase">
                            Manajemen akun siswa wirausaha SiswaMart
                        </p>
                    </div>
                </div>

                <!-- 1. FORM DAFTARKAN PENJUAL BARU -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-4">
                        <div class="w-8 h-8 bg-amber-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-black uppercase tracking-tight text-gray-900">
                            DAFTARKAN PENJUAL BARU
                        </h2>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Username Login -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-black uppercase text-gray-700">USERNAME LOGIN PENJUAL</label>
                                <input 
                                    v-model="createForm.username" 
                                    type="text" 
                                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:ring-0 focus:border-orange-500 transition" 
                                    placeholder="Contoh: dwipenjual" 
                                    required 
                                />
                                <div v-if="createForm.errors.username" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.username }}</div>
                            </div>

                            <!-- Password Awal Akun -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-black uppercase text-gray-700">PASSWORD AWAL AKUN</label>
                                <input 
                                    v-model="createForm.password" 
                                    type="password" 
                                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:ring-0 focus:border-orange-500 transition" 
                                    placeholder="Buat password login pertama siswa" 
                                    required 
                                />
                                <div v-if="createForm.errors.password" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.password }}</div>
                            </div>
                        </div>

                        <!-- Nomor WhatsApp Active -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-black uppercase text-gray-700">NOMOR WHATSAPP AKTIF</label>
                            <input 
                                v-model="createForm.whatsapp_number" 
                                type="text" 
                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-900 focus:bg-white focus:ring-0 focus:border-orange-500 transition" 
                                placeholder="081234567890" 
                                required 
                            />
                            <div v-if="createForm.errors.whatsapp_number" class="text-rose-600 text-[10px] font-bold">{{ createForm.errors.whatsapp_number }}</div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="pt-2">
                            <button 
                                type="submit" 
                                :disabled="createForm.processing"
                                class="px-6 py-3 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition flex items-center gap-2"
                            >
                                <span>+ BUAT AKUN PENJUAL</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- 2. TABEL DAFTAR PENJUAL TERDAFTAR -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-4">
                        <div class="w-8 h-8 bg-emerald-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-black uppercase tracking-tight text-gray-900">
                            DAFTAR PENJUAL TERDAFTAR
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                    <th class="p-3.5 rounded-l-xl">SISWA PENJUAL</th>
                                    <th class="p-3.5">NAMA LAPAK USAHA</th>
                                    <th class="p-3.5">WHATSAPP</th>
                                    <th class="p-3.5">METODE LOGIN</th>
                                    <th class="p-3.5">STATUS AKUN</th>
                                    <th class="p-3.5 text-center rounded-r-xl">AKSI</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-100">
                                <tr 
                                    v-for="seller in sellers" 
                                    :key="seller.id" 
                                    class="hover:bg-stone-50/50 transition"
                                >
                                    <!-- SISWA PENJUAL -->
                                    <td class="py-4 px-3.5">
                                        <div class="font-black text-xs uppercase text-gray-900">
                                            {{ seller.username }}
                                        </div>
                                        <div v-if="seller.email" class="text-[10px] font-bold text-gray-400 lowercase">
                                            @{{ seller.email }}
                                        </div>
                                    </td>

                                    <!-- NAMA LAPAK -->
                                    <td class="py-4 px-3.5 text-xs font-bold text-gray-700 uppercase">
                                        {{ seller.shop ? seller.shop.name : "-" }}
                                    </td>

                                    <!-- WHATSAPP -->
                                    <td class="py-4 px-3.5 text-xs font-bold text-emerald-600">
                                        {{ seller.whatsapp_number }}
                                    </td>

                                    <!-- METODE LOGIN -->
                                    <td class="py-4 px-3.5">
                                        <span 
                                            :class="seller.google_id ? 'bg-sky-100 text-sky-900 border-sky-900' : 'bg-amber-100 text-amber-900 border-amber-900'"
                                            class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border-2 inline-block"
                                        >
                                            {{ seller.google_id ? 'GOOGLE' : 'PASSWORD' }}
                                        </span>
                                    </td>

                                    <!-- STATUS AKUN -->
                                    <td class="py-4 px-3.5">
                                        <span 
                                            :class="seller.is_suspended ? 'bg-rose-200 text-rose-900 border-rose-900' : 'bg-emerald-200 text-emerald-900 border-emerald-900'"
                                            class="px-2.5 py-1 text-[9px] font-black uppercase rounded-lg border-2 inline-block"
                                        >
                                            {{ seller.is_suspended ? 'TER-SUSPEND' : 'AKTIF' }}
                                        </span>
                                    </td>

                                    <!-- AKSI (HANYA SUSPEND & HAPUS) -->
                                    <td class="py-4 px-3.5">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Suspend / Aktifkan -->
                                            <button 
                                                @click="toggleSuspend(seller.id)" 
                                                :class="seller.is_suspended ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-amber-400 hover:bg-amber-300'"
                                                class="px-3 py-1 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                {{ seller.is_suspended ? 'AKTIFKAN' : 'SUSPEND' }}
                                            </button>

                                            <!-- Hapus -->
                                            <button 
                                                @click="deleteSeller(seller.id)" 
                                                class="px-3 py-1 bg-rose-400 hover:bg-rose-300 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
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
    </AuthenticatedLayout>
</template>