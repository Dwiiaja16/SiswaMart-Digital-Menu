<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section class="space-y-4">
        <form @submit.prevent="updatePassword" class="space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-900">KATA SANDI SAAT INI</label>
                <input 
                    ref="currentPasswordInput"
                    v-model="form.current_password" 
                    type="password" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    autocomplete="current-password"
                />
                <div v-if="form.errors.current_password" class="text-rose-600 text-[10px] font-bold">{{ form.errors.current_password }}</div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-900">KATA SANDI BARU</label>
                <input 
                    ref="passwordInput"
                    v-model="form.password" 
                    type="password" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password" class="text-rose-600 text-[10px] font-bold">{{ form.errors.password }}</div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-900">KONFIRMASI KATA SANDI BARU</label>
                <input 
                    v-model="form.password_confirmation" 
                    type="password" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    autocomplete="new-password"
                />
                <div v-if="form.errors.password_confirmation" class="text-rose-600 text-[10px] font-bold">{{ form.errors.password_confirmation }}</div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-6 py-3 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                >
                    PERBARUI KATA SANDI
                </button>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-xs font-black text-emerald-600 uppercase">Kata Sandi Diperbarui!</p>
                </Transition>
            </div>
        </form>
    </section>
</template>