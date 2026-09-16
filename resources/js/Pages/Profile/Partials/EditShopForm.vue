<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['close']);

const user = usePage().props.auth.user;
const shop = usePage().props.auth.shop;

const form = useForm({
    username: user.username || '',
    whatsapp_number: user.whatsapp_number || '',
    shop_name: shop ? shop.name : '',
});

const closeModal = () => {
    emit('close');
};

const submitUpdate = () => {
    form.patch(route('profile.update'), {
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <!-- Modal Container -->
    <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-6">
        
        <!-- Header Modal -->
        <div class="flex justify-between items-center border-b-4 border-gray-900 pb-4">
            <h3 class="text-xl font-black uppercase tracking-tight text-gray-900">
                EDIT PROFIL & <span class="text-orange-500">LAPAK</span>
            </h3>
            <button 
                type="button" 
                @click="closeModal" 
                class="text-gray-400 hover:text-gray-900 font-bold text-xl transition"
            >
                ✕
            </button>
        </div>

        <form @submit.prevent="submitUpdate" class="space-y-4">
            <!-- 1. USERNAME PENJUAL -->
            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-700">
                    USERNAME PENJUAL
                </label>
                <input 
                    v-model="form.username" 
                    type="text" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    placeholder="Masukkan username"
                    required 
                />
                <div v-if="form.errors.username" class="text-rose-600 text-[10px] font-bold">{{ form.errors.username }}</div>
            </div>

            <!-- 2. NAMA LAPAK USAHA -->
            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-700">
                    NAMA LAPAK USAHA
                </label>
                <input 
                    v-model="form.shop_name" 
                    type="text" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    placeholder="Contoh: Lapak Snack Dwi"
                    required 
                />
                <div v-if="form.errors.shop_name" class="text-rose-600 text-[10px] font-bold">{{ form.errors.shop_name }}</div>
            </div>

            <!-- 3. NOMOR WHATSAPP AKTIF -->
            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-700">
                    NOMOR WHATSAPP AKTIF
                </label>
                <input 
                    v-model="form.whatsapp_number" 
                    type="text" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    placeholder="081234567890"
                    required 
                />
                <div v-if="form.errors.whatsapp_number" class="text-rose-600 text-[10px] font-bold">{{ form.errors.whatsapp_number }}</div>
            </div>

            <!-- 4. AKUN GOOGLE TERTAUT (STATUS & AKSES CEPAT) -->
            <div class="bg-stone-100 border-2 border-gray-900 rounded-2xl p-4 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-black uppercase text-gray-900">AKUN GMAIL GOOGLE</span>
                    <span 
                        :class="user.google_id ? 'bg-sky-200 text-sky-900 border-sky-900' : 'bg-amber-200 text-amber-900 border-amber-900'"
                        class="px-2.5 py-0.5 text-[9px] font-black uppercase rounded-md border-2"
                    >
                        {{ user.google_id ? 'TERTAUT' : 'BELUM TERTAUT' }}
                    </span>
                </div>
                
                <p class="text-[10px] font-bold text-gray-500">
                    {{ user.email ? 'Email: ' + user.email : 'Belum ada email Google yang dihubungkan.' }}
                </p>

                <a 
                    :href="route('google.link')" 
                    class="inline-flex items-center gap-2 mt-1 px-3 py-2 bg-white hover:bg-stone-50 border-2 border-gray-900 text-gray-900 font-black text-[10px] uppercase rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    {{ user.google_id ? 'GANTI AKUN GOOGLE' : 'TAUTKAN AKUN GOOGLE' }}
                </a>
            </div>

            <!-- Tombol Aksi Simpan & Batal -->
            <div class="flex gap-3 pt-2">
                <button 
                    type="button" 
                    @click="closeModal" 
                    class="w-1/2 py-3.5 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase rounded-xl transition"
                >
                    BATAL
                </button>
                <button 
                    type="submit" 
                    :disabled="form.processing" 
                    class="w-1/2 py-3.5 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-widest rounded-xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                >
                    SIMPAN PERUBAHAN
                </button>
            </div>
        </form>

    </div>
</template>