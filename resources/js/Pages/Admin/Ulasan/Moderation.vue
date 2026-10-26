<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, router } from "@inertiajs/vue3";

const props = defineProps({
    allReviews: {
        type: [Array, Object],
        default: () => [],
    },
    reviews: {
        type: [Array, Object],
        default: () => [],
    },
});

// State Pencarian
const searchQuery = ref('');

// Computed untuk mendapatkan array ulasan dari props fleksibel (Mendukung Array biasa maupun Paginasi)
const rawReviews = computed(() => {
    if (Array.isArray(props.allReviews)) return props.allReviews;
    if (props.allReviews?.data) return props.allReviews.data;
    if (Array.isArray(props.reviews)) return props.reviews;
    return props.reviews?.data || [];
});

const paginationData = computed(() => {
    if (props.allReviews && props.allReviews.links) return props.allReviews;
    if (props.reviews && props.reviews.links) return props.reviews;
    return null;
});

// Computed Filter Pencarian Murni Berdasarkan AWALAN / HURUF DEPAN (startsWith)
const filteredReviews = computed(() => {
    if (!searchQuery.value || searchQuery.value.trim() === '') return rawReviews.value;

    const query = searchQuery.value.toLowerCase().trim();
    return rawReviews.value.filter((review) => {
        const reviewerName = (review.nama_pengunjung || review.reviewer_name || review.user?.name || review.username || '').trim().toLowerCase();
        const productName = (review.produk?.nama_produk || review.product?.nama_produk || review.product?.name || '').trim().toLowerCase();
        const comment = (review.komentar || review.comment || '').trim().toLowerCase();

        return (
            reviewerName.startsWith(query) || 
            productName.startsWith(query) || 
            comment.startsWith(query)
        );
    });
});

// Hapus Ulasan
const deleteReview = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus ulasan ini secara permanen?')) {
        const deleteRoute = route().has('admin.reviews.destroy')
            ? route('admin.reviews.destroy', id)
            : route('admin.ulasan.destroy', id);

        router.delete(deleteRoute, { 
            preserveScroll: true,
            onSuccess: () => alert('Ulasan berhasil dihapus!')
        });
    }
};

// Helper extractor untuk fleksibilitas field database
const getId = (review) => review.id_ulasan || review.id;
const getReviewerName = (review) => review.nama_pengunjung || review.reviewer_name || review.user?.name || review.username || 'Pengunjung';
const getProductName = (review) => review.produk?.nama_produk || review.product?.nama_produk || review.product?.name || 'Produk';
const getComment = (review) => review.komentar || review.comment || '';
const getRating = (review) => review.rating || 5;
</script>

<template>
    <Head title="Moderasi Ulasan - Admin SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-8 bg-stone-50 min-h-screen text-gray-900 font-sans selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- HEADER BAR -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 border-4 border-gray-900 rounded-3xl shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-amber-400 border-3 border-gray-900 rounded-2xl flex items-center justify-center shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] shrink-0">
                            <svg class="w-6 h-6 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl sm:text-2xl font-black uppercase text-gray-900 tracking-tight">KELOLA ULASAN PUBLIK</h2>
                            <p class="text-xs font-bold text-gray-500 uppercase mt-0.5">Pantau dan hapus ulasan terpublikasi dari pengunjung SiswaMart</p>
                        </div>
                    </div>

                    <div class="bg-amber-100 border-2 border-gray-900 px-4 py-2 rounded-2xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] flex items-center gap-2 shrink-0">
                        <span class="text-xs font-black uppercase text-gray-900">Total Ulasan:</span>
                        <span class="bg-orange-500 text-white font-black text-xs px-2.5 py-0.5 rounded-lg border border-gray-900">
                            {{ rawReviews.length }}
                        </span>
                    </div>
                </div>

                <!-- SEARCH BAR & CONTAINER ULASAN -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-5">
                    
                    <!-- Search Input (Filter Huruf Depan) -->
                    <div class="relative w-full flex items-center">
                        <svg class="w-4 h-4 absolute left-4 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari berdasarkan huruf depan pengunjung, produk, atau isi komentar..." 
                            class="w-full bg-stone-50 border-2 border-gray-900 rounded-2xl pl-11 pr-4 py-3 text-xs font-bold text-gray-900 focus:ring-0 focus:border-orange-500 transition shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]" 
                        />
                    </div>

                    <!-- Judul Section -->
                    <div class="flex items-center justify-between border-b-2 border-gray-100 pb-3">
                        <h3 class="text-base font-black uppercase text-gray-900">Daftar Ulasan Terupload</h3>
                        <span v-if="searchQuery" class="text-xs font-bold text-orange-600">
                            Menampilkan {{ filteredReviews.length }} hasil
                        </span>
                    </div>

                    <!-- Tampilan Kosong -->
                    <div v-if="filteredReviews.length === 0" class="text-center py-12 bg-stone-50 border-2 border-dashed border-gray-300 rounded-2xl space-y-2">
                        <div class="text-2xl">💬</div>
                        <span class="text-gray-400 font-black uppercase text-xs block">
                            {{ searchQuery ? 'Tidak ada ulasan yang cocok dengan pencarian huruf depan Anda.' : 'Belum ada ulasan terupload saat ini.' }}
                        </span>
                    </div>

                    <!-- List Card Ulasan -->
                    <div v-else class="space-y-4">
                        <div 
                            v-for="review in filteredReviews" 
                            :key="getId(review)" 
                            class="p-4 sm:p-5 border-2 border-gray-900 rounded-2xl bg-stone-50 space-y-2.5 shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] hover:bg-amber-50/40 transition"
                        >
                            <!-- 1. HEADER INFO ULASAN (NAMA, PRODUK, RATING) -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-black bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-lg uppercase border border-emerald-900">
                                    Terpublikasi
                                </span>
                                <span class="font-black text-sm uppercase text-gray-900">{{ getReviewerName(review) }}</span>
                                <span class="text-xs text-gray-500 font-bold">
                                    pada produk <span class="text-gray-900 font-black uppercase">"{{ getProductName(review) }}"</span>
                                </span>
                                <span class="text-xs font-black bg-amber-300 text-gray-900 px-2 py-0.5 rounded-lg border border-gray-900 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                    ⭐ {{ getRating(review) }}/5
                                </span>
                            </div>

                            <!-- 2. KONTAINER KOMENTAR & TOMBOL HAPUS (SEJAJAR VERTIKAL PERSEPI / PRESISI) -->
                            <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                                <p class="flex-1 text-xs sm:text-sm font-bold text-gray-800 bg-white p-3.5 rounded-xl border-2 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] leading-relaxed min-w-0">
                                    "{{ getComment(review) }}"
                                </p>

                                <div class="w-full md:w-auto shrink-0 flex items-center justify-center">
                                    <button 
                                        @click="deleteReview(getId(review))" 
                                        class="w-full md:w-auto inline-flex items-center justify-center gap-1.5 bg-rose-500 hover:bg-rose-400 text-white text-xs font-black uppercase px-4 py-3 rounded-xl border-2 border-gray-900 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        </svg>
                                        Hapus Ulasan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAGINATION NAVIGATION -->
                    <Pagination v-if="paginationData" :links="paginationData.links || []" :meta="paginationData" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>