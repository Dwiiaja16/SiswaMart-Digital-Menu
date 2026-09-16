<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.reset();
};
</script>

<template>
    <section class="space-y-4">
        <button 
            type="button"
            @click="confirmUserDeletion"
            class="px-6 py-3 bg-rose-500 hover:bg-rose-400 border-2 border-gray-900 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
        >
            HAPUS AKUN ADMINISTRATOR
        </button>

        <!-- Modal Konfirmasi Hapus Akun -->
        <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white border-4 border-gray-900 rounded-3xl w-full max-w-md p-6 sm:p-8 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] space-y-5">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-3">
                    <h3 class="text-lg font-black uppercase text-rose-600">KONFIRMASI HAPUS AKUN</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-900 font-black text-sm">✕</button>
                </div>

                <p class="text-xs font-bold text-gray-600">
                    Apakah Anda yakin ingin menghapus akun ini? Masukkan kata sandi Anda untuk mengonfirmasi.
                </p>

                <form @submit.prevent="deleteUser" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-black uppercase text-gray-900">KATA SANDI</label>
                        <input 
                            ref="passwordInput"
                            v-model="form.password" 
                            type="password" 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-4 py-3 text-xs font-bold text-gray-900 focus:bg-white focus:border-rose-500 transition" 
                            placeholder="Masukkan kata sandi"
                            required
                        />
                        <div v-if="form.errors.password" class="text-rose-600 text-[10px] font-bold">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 py-3 bg-stone-100 hover:bg-stone-200 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl transition"
                        >
                            BATAL
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-1 py-3 bg-rose-500 hover:bg-rose-400 border-2 border-gray-900 text-white font-black text-xs uppercase tracking-widest rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition"
                        >
                            HAPUS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>