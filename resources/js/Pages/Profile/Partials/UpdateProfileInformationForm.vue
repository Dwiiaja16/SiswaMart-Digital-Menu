<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    username: user.username || '',
    email: user.email || '',
});

const submit = () => {
    form.patch(route('profile.update'));
};
</script>

<template>
    <section class="space-y-4">
        <form @submit.prevent="submit" class="space-y-4">
            <!-- Username Input -->
            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-900">USERNAME ADMINISTRATOR</label>
                <input 
                    v-model="form.username" 
                    type="text" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                    required 
                />
                <div v-if="form.errors.username" class="text-rose-600 text-[10px] font-bold">{{ form.errors.username }}</div>
            </div>

            <!-- Email Input -->
            <div class="space-y-1.5">
                <label class="block text-xs font-black uppercase text-gray-900">EMAIL ADMIN (OPSIONAL)</label>
                <input 
                    v-model="form.email" 
                    type="email" 
                    class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:ring-4 focus:ring-orange-500/20 focus:border-orange-500 transition" 
                />
                <div v-if="form.errors.email" class="text-rose-600 text-[10px] font-bold">{{ form.errors.email }}</div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="px-6 py-3 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                >
                    SIMPAN PROFIL
                </button>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-xs font-black text-emerald-600 uppercase">Tersimpan!</p>
                </Transition>
            </div>
        </form>
    </section>
</template>