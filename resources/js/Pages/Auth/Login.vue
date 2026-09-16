<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

// State untuk toggle password mata
const showPassword = ref(false);

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in Wirausaha" />

    <!-- Latar Belakang Krem Hangat (stone-50) -->
    <div class="min-h-screen flex items-center justify-center bg-stone-50 p-4 text-gray-900 selection:bg-orange-500 selection:text-white">
        
        <!-- Kotak Login bergaya Brutalist Cerah (Shadow tegas warna Orange) -->
        <div class="w-full max-w-md bg-white border-4 border-gray-900 rounded-3xl p-8 sm:p-10 relative shadow-[8px_8px_0px_0px_rgba(249,115,22,1)]">
            
            <!-- Branding Aplikasi -->
            <div class="text-center mb-8">
                <h1 class="text-5xl font-black text-gray-900 uppercase tracking-tighter drop-shadow-sm">
                    Siswa<span class="text-orange-500">Mart</span>
                </h1>
                <p class="text-gray-500 text-xs font-black mt-2 uppercase tracking-[0.3em]">
                    Portal Wirausaha SMKN 11
                </p>
            </div>

            <!-- Flash Error Notification (Hanya untuk Error Umum / Suspended) -->
            <div
                v-if="$page.props.flash?.error"
                class="mb-6 overflow-hidden rounded-2xl border-2 border-gray-900 bg-rose-400 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]"
            >
                <div class="flex items-stretch">
                    <div class="flex w-12 shrink-0 items-center justify-center border-r-2 border-gray-900 bg-rose-500">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg border-2 border-gray-900 bg-amber-300 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                            <svg
                                class="h-4 w-4 text-gray-900"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8v4m0 4h.01M10.29 3.86l-7.36 12.73A2 2 0 004.66 19.6h14.68a2 2 0 001.73-3.01L13.71 3.86a2 2 0 00-3.42 0z"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="flex flex-1 items-center px-3 py-3">
                        <p class="text-xs font-black uppercase leading-relaxed tracking-wide text-gray-900">
                            {{ $page.props.flash.error }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="status" class="mb-4 font-bold text-sm text-emerald-700 bg-emerald-100 p-3 rounded-xl border-2 border-emerald-500 text-center">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <!-- Input Username / Email -->
                <div class="mb-5">
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">
                        Username / Email
                    </label>
                    <input 
                        v-model="form.username" 
                        type="text" 
                        class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3.5 text-gray-900 font-bold focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all" 
                        placeholder="Masukkan username atau email" 
                        required 
                        autofocus 
                    />
                    <div v-if="form.errors.username" class="text-rose-600 text-xs font-bold mt-2">{{ form.errors.username }}</div>
                </div>

                <!-- Input Password + Toggle Mata Presisi -->
                <div class="mb-6">
                    <label class="block text-xs font-black text-gray-700 uppercase tracking-widest mb-2">
                        Password 
                    </label>

                    <div class="relative flex items-center">
                        <input 
                            v-model="form.password" 
                            :type="showPassword ? 'text' : 'password'" 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl pl-4 pr-12 py-3.5 text-gray-900 font-bold focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all tracking-widest" 
                            placeholder="••••••••" 
                            required 
                        />
                        
                        <!-- TOMBOL MATA INTERAKTIF -->
                        <button 
                            type="button" 
                            @click="showPassword = !showPassword"
                            class="absolute right-3.5 flex items-center justify-center p-1 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-stone-200 active:scale-90 transition-all duration-150"
                            :title="showPassword ? 'Sembunyikan Password' : 'Tampilkan Password'"
                        >
                            <!-- Ikon Mata Terbuka -->
                            <svg 
                                v-if="showPassword" 
                                class="w-5 h-5" 
                                viewBox="0 0 24 24" 
                                fill="none" 
                                stroke="currentColor" 
                                stroke-width="2" 
                                stroke-linecap="round" 
                                stroke-linejoin="round"
                            >
                                <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                            </svg>

                            <!-- Ikon Mata Coret -->
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                <circle cx="12" cy="12" r="2.5" />
                                <path d="M3 3l18 18" />
                            </svg>
                        </button>
                    </div>
                    
                    <div v-if="form.errors.password" class="text-rose-600 text-xs font-bold mt-2">{{ form.errors.password }}</div>
                </div>

                <!-- Ingat Saya & Lupa Password -->
                <div class="flex items-center justify-between mb-8">
                    <label class="flex items-center cursor-pointer group">
                        <input 
                            v-model="form.remember" 
                            type="checkbox" 
                            class="rounded border-gray-900 bg-white text-orange-500 focus:ring-orange-500 focus:ring-offset-stone-50 h-5 w-5 transition" 
                        />
                        <span class="ml-2 text-sm font-bold text-gray-600 group-hover:text-gray-900 transition">Ingat Saya</span>
                    </label>

                    <Link 
                        v-if="canResetPassword" 
                        :href="route('password.request')" 
                        class="text-sm font-black text-orange-600 hover:text-orange-500 transition-colors"
                    >
                        Lupa Sandi?
                    </Link>
                </div>

                <!-- Tombol Login Utama -->
                <button 
                    type="submit" 
                    :disabled="form.processing" 
                    class="w-full bg-orange-500 hover:bg-orange-400 text-gray-900 font-black uppercase tracking-widest py-4 rounded-xl transition-all active:scale-95 border-2 border-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] hover:shadow-none hover:translate-y-1 hover:translate-x-1 flex justify-center items-center text-sm disabled:opacity-50"
                >
                    Masuk 
                </button>
            </form>

            <!-- Garis Pemisah -->
            <div class="mt-8 flex items-center justify-center space-x-3 opacity-60">
                <span class="h-0.5 bg-gray-300 flex-1 rounded"></span>
                <span class="text-xs font-black text-gray-500 uppercase tracking-widest">ATAU</span>
                <span class="h-0.5 bg-gray-300 flex-1 rounded"></span>
            </div>

            <!-- Tombol Login Google -->
            <div class="mt-6">
                <a 
                    :href="route('google.login')" 
                    class="w-full bg-white hover:bg-stone-100 text-gray-900 font-black uppercase tracking-widest py-3.5 rounded-xl transition-all active:scale-95 border-2 border-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] hover:shadow-none hover:translate-y-1 hover:translate-x-1 flex justify-center items-center gap-3 text-sm"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google 
                </a>
            </div>

        </div>
    </div>
</template>