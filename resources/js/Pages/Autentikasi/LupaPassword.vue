<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    username_or_email: '',
});

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password - SiswaMart" />

        <div class="mb-6 text-center">
            <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight">Lupa Kata Sandi?</h2>
            <p class="mt-2 text-xs sm:text-sm text-gray-600 font-medium leading-relaxed">
                Masukkan username atau email terdaftar. Kami akan mengirimkan tautan reset ke email Anda.
            </p>
        </div>

        <!-- Alert Status Berhasil (Success) -->
        <div 
            v-if="status" 
            class="mb-5 p-3.5 rounded-xl border-2 border-emerald-800 bg-emerald-100 text-emerald-950 font-medium text-xs sm:text-sm shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-all flex items-start gap-3"
        >
            <div class="p-1 rounded-lg bg-white/80 border border-emerald-800 shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="flex-1 leading-relaxed text-left">
                <p class="font-black uppercase tracking-wider text-[10px] sm:text-[11px] mb-0.5">
                    Berhasil Dikirim
                </p>
                <p class="font-semibold text-xs leading-normal">
                    {{ status }}
                </p>
            </div>
        </div>

        <!-- Alert Error General (Sistem / Validation Back-end Error) -->
        <div 
            v-if="$page.props.errors.username_or_email && !form.errors.username_or_email" 
            class="mb-5 p-3.5 rounded-xl border-2 border-rose-800 bg-rose-100 text-rose-950 font-medium text-xs sm:text-sm shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] transition-all flex items-start gap-3"
        >
            <div class="p-1 rounded-lg bg-white/80 border border-rose-800 shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1 leading-relaxed text-left">
                <p class="font-black uppercase tracking-wider text-[10px] sm:text-[11px] mb-0.5">
                    Gagal Diproses
                </p>
                <p class="font-semibold text-xs leading-normal">
                    {{ $page.props.errors.username_or_email }}
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="username_or_email" value="USERNAME / EMAIL" class="text-xs font-bold uppercase tracking-wider text-gray-700" />

                <TextInput
                    id="username_or_email"
                    type="text"
                    class="mt-1 block w-full rounded-xl border-2 border-black bg-amber-50/30 p-3 font-semibold text-black placeholder-gray-400 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)] focus:border-black focus:ring-2 focus:ring-amber-400"
                    v-model="form.username_or_email"
                    placeholder="Masukkan username atau email..."
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.username_or_email" />
            </div>

            <div class="mt-6 flex flex-col items-center gap-3">
                <PrimaryButton
                    class="w-full justify-center py-3.5 rounded-xl border-2 border-black bg-orange-500 font-extrabold text-white shadow-[3px_3px_0px_0px_rgba(0,0,0,1)] hover:bg-orange-600 active:translate-x-[1px] active:translate-y-[1px] active:shadow-none transition-all disabled:opacity-50"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'MENGIRIM...' : 'KIRIM TAUTAN RESET' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>