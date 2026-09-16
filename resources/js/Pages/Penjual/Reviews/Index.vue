<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    }
});

// Hitung Statistik Ulasan secara Reaktif
const totalReviews = computed(() => props.reviews ? props.reviews.length : 0);
const avgRating = computed(() => {
    if (!props.reviews || props.reviews.length === 0) return '0.0';
    const sum = props.reviews.reduce((acc, rev) => acc + Number(rev.rating), 0);
    return (sum / props.reviews.length).toFixed(1);
});

// Helper Format Tanggal Indonesia
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', { 
        day: 'numeric', 
        month: 'short', 
        year: 'numeric' 
    });
};
</script>

<template>
    <Head title="Laporan Ulasan Pembeli - SiswaMart" />

    <div class="min-h-screen bg-stone-50 text-gray-900 font-sans pb-16 selection:bg-orange-500 selection:text-white">

        <!-- Header Page -->
        <header class="bg-white border-b-4 border-gray-900 px-6 py-4 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-black uppercase tracking-tighter text-gray-900">
                        Laporan <span class="text-orange-500">Ulasan</span> Pembeli
                    </h2>
                    <span class="bg-amber-100 border-2 border-gray-900 text-gray-900 text-[10px] font-black uppercase px-2.5 py-1 rounded-md shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                        Reputasi Lapak
                    </span>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-6 mt-8 space-y-8">

            <!-- Banner & Ringkasan Statistik Rating -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <!-- Card Info Transparansi -->
                <div class="md:col-span-2 bg-amber-400 border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-3">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest bg-gray-900 text-white px-2.5 py-1 rounded-md">
                            Transparansi Feedback
                        </span>
                        <h3 class="text-xl sm:text-2xl font-black uppercase tracking-tight text-gray-900 mt-2">
                            Feedback Transparan & Real-Time
                        </h3>
                        <p class="text-xs font-bold text-gray-900 mt-1 opacity-90 leading-relaxed">
                            Seluruh testimoni dan rating dari siswa tampil secara terbuka di katalog produk untuk menjaga kualitas jajanan dan kepercayaan pembeli di SiswaMart.
                        </p>
                    </div>
                </div>

                <!-- Card Stat Ringkasan Rating -->
                <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] flex flex-col justify-between space-y-4">
                    <div class="flex justify-between items-start">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Rata-rata Rating</span>
                        <span class="bg-orange-500 text-gray-900 border-2 border-gray-900 px-2.5 py-0.5 rounded-lg text-xs font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            {{ totalReviews }} Ulasan
                        </span>
                    </div>

                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-amber-500 flex items-center gap-2">
                            <svg class="w-8 h-8 fill-amber-400 stroke-gray-900" stroke-width="1.5" viewBox="0 0 24 24">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            {{ avgRating }}
                        </span>
                        <span class="text-xs font-black text-gray-400 uppercase">/ 5.0</span>
                    </div>

                    <p class="text-[11px] font-black text-gray-500 uppercase border-t-2 border-gray-100 pt-2">
                        Performa Ulasan Produk Jajanan
                    </p>
                </div>

            </div>

            <!-- Tabel Laporan Ulasan -->
            <div class="bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-6">
                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8-1.284 0-2.503-.24-3.605-.671L3 21l1.395-4.32C3.512 15.443 3 13.775 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tight text-gray-900">Daftar Feedback Masuk</h3>
                            <p class="text-xs font-bold text-gray-400 uppercase">Ulasan asli yang diberikan oleh pembeli di katalog</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 border-gray-900 bg-stone-100 text-[10px] font-black uppercase tracking-wider text-gray-700">
                                <th class="p-3.5 rounded-l-xl">Produk</th>
                                <th class="p-3.5">Nama Pembeli</th>
                                <th class="p-3.5">Rating</th>
                                <th class="p-3.5">Komentar / Ulasan</th>
                                <th class="p-3.5 text-right rounded-r-xl">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y-2 divide-gray-100">
                            <tr v-for="review in reviews" :key="review.id" class="hover:bg-stone-50/50 transition">
                                <td class="py-4 px-3.5 font-black text-xs uppercase text-gray-900">
                                    {{ review.product ? review.product.name : '-' }}
                                </td>
                                <td class="py-4 px-3.5 text-xs font-bold text-gray-700 uppercase">
                                    {{ review.reviewer_name }}
                                </td>
                                <td class="py-4 px-3.5">
                                    <span class="bg-amber-100 text-amber-900 border border-gray-900 text-[10px] font-black px-2.5 py-1 rounded-md inline-flex items-center gap-1 shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                        <svg class="w-3 h-3 fill-amber-500 stroke-gray-900" stroke-width="1" viewBox="0 0 24 24">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                                        </svg>
                                        {{ review.rating }}/5
                                    </span>
                                </td>
                                <td class="py-4 px-3.5 text-xs font-medium text-gray-700 italic max-w-xs leading-relaxed">
                                    "{{ review.comment }}"
                                </td>
                                <td class="py-4 px-3.5 text-right text-[10px] font-black text-gray-400 uppercase">
                                    {{ formatDate(review.created_at) }}
                                </td>
                            </tr>

                            <tr v-if="!reviews || reviews.length === 0">
                                <td colspan="5" class="py-12 text-center text-xs font-black uppercase text-gray-400">
                                    Belum ada ulasan masuk dari pembeli.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</template>