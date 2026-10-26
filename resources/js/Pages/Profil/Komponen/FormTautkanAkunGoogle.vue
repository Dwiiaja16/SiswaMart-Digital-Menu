<script setup>
import { usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
const errors = usePage().props.errors;
const status = usePage().props.status;
</script>

<template>
    <section class="space-y-4">
        <header class="border-b-2 border-gray-100 pb-3 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black uppercase text-gray-900 tracking-tight">
                    Integrasi Akun Google
                </h2>
                <p class="mt-1 text-xs font-bold text-gray-500">
                    Tautkan atau ganti akun Gmail untuk memudahkan login via Google OAuth.
                </p>
            </div>
            <span 
                :class="user.google_id ? 'bg-sky-100 text-sky-900 border-sky-900' : 'bg-stone-100 text-gray-600 border-gray-900'"
                class="px-3 py-1 text-[10px] font-black uppercase rounded-lg border-2"
            >
                {{ user.google_id ? 'Tertaut' : 'Belum Tertaut' }}
            </span>
        </header>

        <!-- Pesan Status / Error -->
        <div v-if="status === 'Akun Google berhasil ditautkan!'" class="text-xs font-bold text-emerald-700 bg-emerald-100 p-3 rounded-xl border-2 border-emerald-500">
            {{ status }}
        </div>
        <div v-if="errors.google" class="text-xs font-bold text-rose-700 bg-rose-100 p-3 rounded-xl border-2 border-rose-500">
            {{ errors.google }}
        </div>

        <!-- Info Email Tertaut -->
        <div class="bg-stone-50 border-2 border-gray-900 rounded-xl p-3.5 flex items-center justify-between">
            <span class="text-xs font-black uppercase text-gray-700">Email Tertaut</span>
            <span class="text-xs font-black text-orange-600">
                {{ user.email || 'Belum Ada Email' }}
            </span>
        </div>

        <!-- Tombol Aksi Tautkan / Ganti -->
        <div>
            <a 
                :href="route('google.link')" 
                class="inline-flex items-center gap-2 px-5 py-3 bg-white hover:bg-stone-100 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                {{ user.google_id ? 'Ganti Akun Google' : 'Tautkan Akun Google' }}
            </a>
        </div>
    </section>
</template>