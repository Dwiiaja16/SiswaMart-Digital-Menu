<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";

const props = defineProps({
    pendingReviews: Array,
    allReviews: Array
});

// State Tab Aktif ('pending' atau 'all')
const activeTab = ref('pending');

const approveReview = (id) => {
    if(confirm('Izinkan ulasan ini tampil ke publik?')) {
        router.patch(route('admin.reviews.approve', id));
    }
};

const deleteReview = (id) => {
    if(confirm('Hapus permanen ulasan ini?')) {
        router.delete(route('admin.reviews.destroy', id));
    }
};
</script>

<template>
    <Head title="Moderasi Ulasan - Admin" />

    <AuthenticatedLayout>
        <div class="py-8 bg-stone-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- HEADER & TAB SWITCHER -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 border-4 border-gray-900 rounded-3xl shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] gap-4">
                    <div>
                        <h2 class="text-2xl font-black uppercase text-gray-900 tracking-tight">Pusat Moderasi Ulasan</h2>
                        <p class="text-sm font-bold text-gray-500 mt-1">Kelola ulasan tertahan akibat kata kasar serta pantau seluruh ulasan.</p>
                    </div>

                    <!-- TOMBOL TAB -->
                    <div class="flex items-center gap-2 bg-stone-100 p-1.5 rounded-2xl border-2 border-gray-900">
                        <button 
                            @click="activeTab = 'pending'"
                            :class="activeTab === 'pending' ? 'bg-amber-400 text-gray-900 shadow-[2px_2px_0px_#111827]' : 'text-gray-600 hover:text-gray-900'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase transition-all flex items-center gap-2"
                        >
                            Tertahan
                            <span v-if="pendingReviews.length > 0" class="bg-rose-500 text-white px-1.5 py-0.5 rounded-full text-[10px]">
                                {{ pendingReviews.length }}
                            </span>
                        </button>
                        <button 
                            @click="activeTab = 'all'"
                            :class="activeTab === 'all' ? 'bg-amber-400 text-gray-900 shadow-[2px_2px_0px_#111827]' : 'text-gray-600 hover:text-gray-900'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase transition-all"
                        >
                            Semua Ulasan ({{ allReviews.length }})
                        </button>
                    </div>
                </div>

                <!-- TAB 1: ULASAN TERTAHAN (BADWORDS) -->
                <div v-if="activeTab === 'pending'" class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4">
                    <h3 class="text-lg font-black uppercase text-gray-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500 inline-block animate-ping"></span>
                        Ulasan Tertahan (Mengandung Kata Kasar)
                    </h3>

                    <div v-if="pendingReviews.length === 0" class="text-center py-12 bg-stone-50 border-2 border-dashed border-gray-300 rounded-2xl">
                        <span class="text-gray-400 font-black uppercase text-sm">Hore! Tidak ada ulasan tertahan saat ini.</span>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="review in pendingReviews" :key="review.id" class="p-4 border-2 border-gray-900 rounded-2xl bg-stone-50 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-[10px] font-black bg-rose-100 text-rose-600 px-2 py-0.5 rounded-md uppercase border border-rose-200">Pending</span>
                                    <span class="font-black text-sm text-gray-900">{{ review.reviewer_name || review.user?.name }}</span>
                                    <span class="text-xs text-gray-500 font-bold">di produk "{{ review.product?.name }}"</span>
                                    <span class="text-xs font-black bg-amber-100 text-amber-700 px-2 py-0.5 rounded">⭐ {{ review.rating }}/5</span>
                                </div>
                                <p class="text-sm font-bold text-gray-800 italic bg-white p-3 rounded-xl border border-gray-300 shadow-inner">
                                    "{{ review.comment }}"
                                </p>
                            </div>
                            <div class="flex items-center gap-2 w-full md:w-auto">
                                <button @click="approveReview(review.id)" class="flex-1 md:flex-none bg-emerald-400 hover:bg-emerald-500 text-gray-900 text-xs font-black uppercase px-4 py-2.5 rounded-xl border-2 border-gray-900 shadow-[2px_2px_0px_#111827] transition-all">
                                    Izinkan Post
                                </button>
                                <button @click="deleteReview(review.id)" class="flex-1 md:flex-none bg-rose-500 hover:bg-rose-600 text-white text-xs font-black uppercase px-4 py-2.5 rounded-xl border-2 border-gray-900 shadow-[2px_2px_0px_#111827] transition-all">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: SEMUA ARSIP ULASAN -->
                <div v-if="activeTab === 'all'" class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-4">
                    <h3 class="text-lg font-black uppercase text-gray-900">Arsip Seluruh Ulasan Pengunjung</h3>

                    <div v-if="allReviews.length === 0" class="text-center py-12 bg-stone-50 border-2 border-dashed border-gray-300 rounded-2xl">
                        <span class="text-gray-400 font-black uppercase text-sm">Belum ada ulasan sama sekali.</span>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="review in allReviews" :key="review.id" class="p-4 border-2 border-gray-900 rounded-2xl bg-stone-50 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between shadow-[2px_2px_0px_#111827]">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span :class="review.status === 'pending' ? 'bg-rose-100 text-rose-600 border-rose-200' : 'bg-emerald-100 text-emerald-700 border-emerald-200'" class="text-[10px] font-black px-2 py-0.5 rounded-md uppercase border">
                                        {{ review.status === 'pending' ? 'Tertahan' : 'Published' }}
                                    </span>
                                    <span class="font-black text-sm text-gray-900">{{ review.reviewer_name || review.user?.name }}</span>
                                    <span class="text-xs text-gray-500 font-bold">di produk "{{ review.product?.name }}"</span>
                                    <span class="text-xs font-black bg-amber-100 text-amber-700 px-2 py-0.5 rounded">⭐ {{ review.rating }}/5</span>
                                </div>
                                <p class="text-sm font-bold text-gray-800 bg-white p-3 rounded-xl border border-gray-300 shadow-inner">
                                    "{{ review.comment }}"
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button v-if="review.status === 'pending'" @click="approveReview(review.id)" class="bg-emerald-400 text-gray-900 text-[10px] font-black uppercase px-3 py-1.5 rounded-lg border-2 border-gray-900 shadow-[2px_2px_0px_#111827]">
                                    Izinkan
                                </button>
                                <button @click="deleteReview(review.id)" class="bg-rose-500 text-white text-[10px] font-black uppercase px-3 py-1.5 rounded-lg border-2 border-gray-900 shadow-[2px_2px_0px_#111827]">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>