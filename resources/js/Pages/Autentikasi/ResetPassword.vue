<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, required: true },
});

const showPassword = ref(false);
const showConfirmation = ref(false);
const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post(route('password.update'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
});
</script>

<template>
    <Head title="Reset Kata Sandi — SiswaMart" />
    <div class="min-h-screen flex items-center justify-center bg-[#f7f5ef] p-4 text-gray-900">
        <div class="w-full max-w-md">
            <div class="mb-4 inline-flex rounded-full border-3 border-gray-900 bg-[#fbbf24] px-4 py-2 text-xs font-black uppercase tracking-widest shadow-[3px_3px_0_#111827]" style="border-width:3px">SiswaMart Security</div>
            <div class="rounded-3xl border-4 border-gray-900 bg-white p-7 shadow-[8px_8px_0_#111827] sm:p-9">
                <h1 class="text-3xl font-black uppercase tracking-tight">Buat Password Baru</h1>
                <p class="mt-2 text-sm font-bold text-gray-600">Masukkan kata sandi baru untuk <span class="text-[#f97316]">{{ email }}</span>.</p>

                <form class="mt-7 space-y-5" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest">Password Baru</label>
                        <div class="relative">
                            <input v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required class="w-full rounded-xl border-2 border-gray-900 bg-[#f7f5ef] px-4 py-3 pr-12 text-sm font-bold focus:border-[#f97316] focus:outline-none focus:ring-4 focus:ring-[#f97316]/20" placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 px-4 font-black text-gray-600" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">{{ showPassword ? '◉' : '◌' }}</button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs font-bold text-rose-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <input v-model="form.password_confirmation" :type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" required class="w-full rounded-xl border-2 border-gray-900 bg-[#f7f5ef] px-4 py-3 pr-12 text-sm font-bold focus:border-[#f97316] focus:outline-none focus:ring-4 focus:ring-[#f97316]/20" placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 px-4 font-black text-gray-600" :aria-label="showConfirmation ? 'Sembunyikan password' : 'Tampilkan password'" @click="showConfirmation = !showConfirmation">{{ showConfirmation ? '◉' : '◌' }}</button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs font-bold text-rose-600">{{ form.errors.password_confirmation }}</p>
                    </div>
                    <p v-if="form.errors.email" class="text-xs font-bold text-rose-600">{{ form.errors.email }}</p>
                    <button type="submit" :disabled="form.processing" class="w-full rounded-xl border-2 border-gray-900 bg-[#fbbf24] py-3.5 text-sm font-black uppercase tracking-widest shadow-[4px_4px_0_#111827] transition hover:translate-x-1 hover:translate-y-1 hover:bg-yellow-300 hover:shadow-none disabled:opacity-60">{{ form.processing ? 'Menyimpan...' : 'Reset Kata Sandi' }}</button>
                </form>
                <Link :href="route('login')" class="mt-6 block text-center text-xs font-black uppercase tracking-widest text-[#f97316] hover:underline">Kembali ke masuk</Link>
            </div>
        </div>
    </div>
</template>
