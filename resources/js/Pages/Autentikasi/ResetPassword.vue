<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, required: true },
});

const showPassword = ref(false);
const showConfirmation = ref(false);

const form = ref({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
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

                <!-- Action diubah ke action="/reset-password" agar menembak URL yang tepat -->
                <form method="POST" action="/reset-password" class="mt-7 space-y-5">
                    <input type="hidden" name="_token" :value="$page.props.csrf_token">
                    <input type="hidden" name="token" :value="form.token">
                    <input type="hidden" name="email" :value="form.email">

                    <div>
                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest">Password Baru</label>
                        <div class="relative">
                            <input v-model="form.password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="new-password" required class="w-full rounded-xl border-2 border-gray-900 bg-[#f7f5ef] px-4 py-3 pr-12 text-sm font-bold focus:border-[#f97316] focus:outline-none focus:ring-4 focus:ring-[#f97316]/20" placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 px-4 font-black text-gray-600" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword">{{ showPassword ? '◉' : '◌' }}</button>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-[10px] font-black uppercase tracking-widest">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <input v-model="form.password_confirmation" name="password_confirmation" :type="showConfirmation ? 'text' : 'password'" autocomplete="new-password" required class="w-full rounded-xl border-2 border-gray-900 bg-[#f7f5ef] px-4 py-3 pr-12 text-sm font-bold focus:border-[#f97316] focus:outline-none focus:ring-4 focus:ring-[#f97316]/20" placeholder="••••••••">
                            <button type="button" class="absolute inset-y-0 right-0 px-4 font-black text-gray-600" :aria-label="showConfirmation ? 'Sembunyikan password' : 'Tampilkan password'" @click="showConfirmation = !showConfirmation">{{ showConfirmation ? '◉' : '◌' }}</button>
                        </div>
                    </div>

                    <div v-if="$page.props.errors && Object.keys($page.props.errors).length > 0" class="p-3 rounded-xl border-2 border-gray-900 bg-rose-100 text-rose-950 font-bold text-xs shadow-[2px_2px_0px_0px_#111827]">
                        <p v-for="(err, key) in $page.props.errors" :key="key">{{ err }}</p>
                    </div>

                    <button type="submit" class="w-full rounded-xl border-2 border-gray-900 bg-[#fbbf24] py-3.5 text-sm font-black uppercase tracking-widest shadow-[4px_4px_0_#111827] transition hover:translate-x-1 hover:translate-y-1 hover:bg-yellow-300 hover:shadow-none">Reset Kata Sandi</button>
                </form>

                <Link :href="route('login')" class="mt-6 block text-center text-xs font-black uppercase tracking-widest text-[#f97316] hover:underline">Kembali ke masuk</Link>
            </div>
        </div>
    </div>
</template>