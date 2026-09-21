<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

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
    <Head title="Masuk — SiswaMart Portal Wirausaha" />

    <div class="min-h-screen flex items-center justify-center bg-[#f7f5ef] p-4 text-gray-900 selection:bg-orange-500 selection:text-white overflow-hidden relative">

        <!-- Decorative Background Shapes -->
        <div class="absolute top-[-80px] right-[-80px] w-64 h-64 bg-[#fbbf24] border-4 border-gray-900 rounded-full opacity-30 rotate-12 pointer-events-none" aria-hidden="true"></div>
        <div class="absolute bottom-[-60px] left-[-60px] w-48 h-48 bg-[#f97316] border-4 border-gray-900 rounded-full opacity-20 pointer-events-none" aria-hidden="true"></div>
        <div class="absolute top-1/2 left-8 w-12 h-12 bg-emerald-300 border-2 border-gray-900 rounded-xl opacity-40 rotate-6 pointer-events-none" aria-hidden="true"></div>

        <div class="w-full max-w-md relative z-10">

            <!-- Badge Sticker -->
            <div class="flex justify-center mb-4">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#fbbf24] border-3 border-gray-900 rounded-full shadow-[3px_3px_0px_0px_#111827] rotate-[-2deg]" style="border-width:3px">
                    <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                    <span class="text-xs font-black uppercase tracking-widest text-gray-900">SiswaMart Official Login</span>
                </div>
            </div>

            <!-- Main Login Card -->
            <div class="bg-white border-4 border-gray-900 rounded-3xl p-7 sm:p-9 shadow-[8px_8px_0px_0px_#111827]">

                <!-- Branding -->
                <div class="text-center mb-7">
                    <h1 class="text-5xl font-black text-gray-900 uppercase tracking-tighter leading-none">
                        Siswa<span class="text-[#f97316]">Mart</span>
                    </h1>
                    <div class="flex items-center justify-center gap-2 mt-2.5">
                        <span class="h-0.5 w-8 bg-gray-300 rounded"></span>
                        <p class="text-[10px] font-black text-gray-500 uppercase tracking-[0.25em]">Portal Wirausaha SMKN 11</p>
                        <span class="h-0.5 w-8 bg-gray-300 rounded"></span>
                    </div>
                </div>

                <!-- Flash Error -->
                <div
                    v-if="$page.props.flash?.error"
                    class="mb-5 flex items-stretch overflow-hidden rounded-2xl border-2 border-gray-900 bg-rose-400 shadow-[3px_3px_0px_0px_#111827]"
                >
                    <div class="flex w-11 shrink-0 items-center justify-center border-r-2 border-gray-900 bg-rose-500">
                        <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M10.29 3.86l-7.36 12.73A2 2 0 004.66 19.6h14.68a2 2 0 001.73-3.01L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <p class="flex-1 px-3 py-2.5 text-xs font-black uppercase leading-relaxed tracking-wide text-gray-900">
                        {{ $page.props.flash.error }}
                    </p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-5 rounded-xl border-2 border-emerald-600 bg-emerald-100 p-3 text-center text-xs font-black text-emerald-800 uppercase tracking-wide">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Username / Email -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-700 uppercase tracking-widest mb-1.5">
                            Username / Email
                        </label>
                        <input
                            v-model="form.username"
                            type="text"
                            class="w-full bg-[#f7f5ef] border-2 border-gray-900 rounded-xl px-4 py-3 text-gray-900 font-bold text-sm placeholder:text-gray-400 focus:outline-none focus:border-[#f97316] focus:ring-4 focus:ring-[#f97316]/20 transition-all"
                            placeholder="Masukkan username atau email"
                            required
                            autofocus
                        />
                        <p v-if="form.errors.username" class="text-rose-600 text-xs font-bold mt-1.5">{{ form.errors.username }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-[10px] font-black text-gray-700 uppercase tracking-widest mb-1.5">
                            Password
                        </label>
                        <div class="relative flex items-center">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full bg-[#f7f5ef] border-2 border-gray-900 rounded-xl pl-4 pr-12 py-3 text-gray-900 font-bold text-sm placeholder:text-gray-400 focus:outline-none focus:border-[#f97316] focus:ring-4 focus:ring-[#f97316]/20 transition-all tracking-widest"
                                placeholder="••••••••"
                                required
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 flex items-center justify-center p-1 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-stone-200 active:scale-90 transition-all duration-150"
                                :title="showPassword ? 'Sembunyikan' : 'Tampilkan'"
                            >
                                <!-- Eye open -->
                                <svg v-if="showPassword" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" />
                                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" />
                                </svg>
                                <!-- Eye crossed -->
                                <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="2.5" />
                                    <path d="M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-rose-600 text-xs font-bold mt-1.5">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-2 border-gray-900 bg-white text-[#f97316] focus:ring-[#f97316] focus:ring-offset-[#f7f5ef]"
                            />
                            <span class="text-xs font-bold text-gray-600 group-hover:text-gray-900 transition">Ingat Saya</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs font-black text-[#f97316] hover:underline"
                        >
                            Lupa Sandi?
                        </Link>
                    </div>

                    <!-- Submit Button (Yellow Neobrutalism) -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#fbbf24] hover:bg-yellow-300 text-gray-900 font-black uppercase tracking-widest py-3.5 rounded-xl border-2 border-gray-900 shadow-[4px_4px_0px_0px_#111827] hover:shadow-none hover:translate-x-1 hover:translate-y-1 active:translate-x-[4px] active:translate-y-[4px] active:shadow-none transition-all text-sm disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        {{ form.processing ? 'Masuk...' : 'Masuk Sekarang' }}
                    </button>
                </form>

                <!-- Divider -->
                <div class="my-6 flex items-center gap-3 opacity-60">
                    <span class="h-0.5 bg-gray-300 flex-1 rounded"></span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">atau lanjut dengan</span>
                    <span class="h-0.5 bg-gray-300 flex-1 rounded"></span>
                </div>

                <!-- Google Login Button -->
                <a
                    :href="route('google.login')"
                    class="w-full bg-white hover:bg-stone-50 text-gray-900 font-black uppercase tracking-widest py-3 rounded-xl border-2 border-gray-900 shadow-[4px_4px_0px_0px_#111827] hover:shadow-none hover:translate-x-1 hover:translate-y-1 transition-all text-xs flex items-center justify-center gap-3"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Google
                </a>

            </div>

            <!-- Footer note -->
            <p class="text-center text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-5">
                Hanya untuk Penjual & Admin SMKN 11 Bandung
            </p>
        </div>
    </div>
</template>

<style scoped>
/* =========================================================
   MOBILE RESPONSIVE FIXES — LOGIN PAGE
   ========================================================= */

@media (max-width: 640px) {
    /* Mencegah layar terpotong & beri ruang scroll yang cukup */
    .min-h-screen {
        padding: 1.25rem 0.85rem !important;
        align-items: flex-start !important;
        padding-top: 2rem !important;
        padding-bottom: 2.5rem !important;
    }

    /* Kecilkan ukuran judul SiswaMart di HP */
    h1 {
        font-size: 2.75rem !important;
    }

    /* Perkecil padding Card agar form lebih luas */
    .bg-white.border-4 {
        padding: 1.25rem 1rem !important;
        border-radius: 1.25rem !important;
        box-shadow: 5px 5px 0px 0px #111827 !important;
    }

    /* Penyesuaian Input Field */
    input[type="text"],
    input[type="password"] {
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
        font-size: 0.8rem !important;
        border-radius: 0.75rem !important;
    }

    /* Penyesuaian Tombol Masuk & Google */
    button[type="submit"],
    a[href*="google"] {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
        font-size: 0.7rem !important;
        border-radius: 0.75rem !important;
        box-shadow: 3px 3px 0px 0px #111827 !important;
    }

    /* Kecilkan hiasan background di HP agar tidak menutupi form */
    .absolute.w-64 {
        width: 8rem !important;
        height: 8rem !important;
        top: -2rem !important;
        right: -2rem !important;
    }
    .absolute.w-48 {
        width: 6rem !important;
        height: 6rem !important;
        bottom: -1.5rem !important;
        left: -1.5rem !important;
    }
}
</style>