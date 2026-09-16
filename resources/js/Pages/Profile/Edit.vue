<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Pengaturan Profil Admin - SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-8 bg-stone-50 min-h-screen selection:bg-orange-500 selection:text-white">
            <div class="mx-auto max-w-4xl space-y-8 px-4 sm:px-6 lg:px-8">

                <!-- Header Card Info Admin -->
                <div class="bg-orange-500 border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[8px_8px_0px_0px_rgba(17,24,39,1)] flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-white">
                    <div class="space-y-1">
                        <span class="text-[10px] font-black uppercase tracking-widest bg-gray-900 text-amber-400 px-3 py-1 rounded-md border border-gray-900 inline-block">
                            Akun Terverifikasi
                        </span>
                        <h1 class="text-3xl font-black uppercase tracking-tight text-gray-900">
                            {{ user.username }}
                        </h1>
                    </div>

                    <!-- Avatar Initial Admin -->
                    <div class="w-16 h-16 bg-white border-3 border-gray-900 rounded-2xl flex items-center justify-center font-black text-3xl text-gray-900 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] shrink-0">
                        {{ user.username ? user.username.charAt(0).toUpperCase() : 'A' }}
                    </div>
                </div>

                <!-- 1. Informasi Profile (Username) -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4">
                    <div class="border-b-2 border-gray-100 pb-3">
                        <h3 class="text-base font-black uppercase text-gray-900 tracking-tight">
                            Informasi Identitas
                        </h3>
                        <p class="text-xs font-bold text-gray-400">
                            Perbarui username login admin utama kamu.
                        </p>
                    </div>
                    
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- 2. Ganti Password Admin -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4">
                    <div class="border-b-2 border-gray-100 pb-3">
                        <h3 class="text-base font-black uppercase text-gray-900 tracking-tight">
                            Keamanan & Kata Sandi
                        </h3>
                        <p class="text-xs font-bold text-gray-400">
                            Pastikan akun administrator menggunakan kombinasi password yang kuat.
                        </p>
                    </div>

                    <UpdatePasswordForm />
                </div>

                <!-- 3. Hapus Akun Admin -->
                <div class="bg-rose-50 border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4">
                    <div class="border-b-2 border-rose-200 pb-3">
                        <h3 class="text-base font-black uppercase text-rose-900 tracking-tight">
                            Zona Bahaya
                        </h3>
                        <p class="text-xs font-bold text-rose-600">
                            Tindakan menghapus akun admin bersifat permanen.
                        </p>
                    </div>

                    <DeleteUserForm />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>