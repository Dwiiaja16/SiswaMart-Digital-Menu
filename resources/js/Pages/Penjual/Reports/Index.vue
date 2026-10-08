<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    topViewedProducts:      { type: Array,  default: () => [] },
    newProductsLast7Days:   { type: Number, default: 0 },
    totalCommentsLast7Days: { type: Number, default: 0 },
    totalViewsAccumulated:  { type: Number, default: 0 },
    recentReviews:          { type: Array,  default: () => [] },
});

const page = usePage();
const shopName = computed(() => page.props.auth?.user?.shop?.name || 'Toko Kamu');

const chartCanvas = ref(null);
let chartInstance = null;

const chartLabels = computed(() =>
    props.topViewedProducts.map(p => p.name.length > 20 ? p.name.substring(0, 18) + '...' : p.name)
);
const chartData = computed(() =>
    props.topViewedProducts.map(p => p.views_count)
);

const renderChart = () => {
    if (!chartCanvas.value || typeof window === 'undefined') return;
    import('chart.js').then(({ Chart, CategoryScale, LinearScale, BarElement, BarController, Tooltip, Legend }) => {
        Chart.register(CategoryScale, LinearScale, BarElement, BarController, Tooltip, Legend);
        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(chartCanvas.value, {
            type: 'bar',
            data: {
                labels: chartLabels.value,
                datasets: [{
                    label: 'Tayangan',
                    data: chartData.value,
                    backgroundColor: ['#F25C05', '#F9C22E', '#70A9A1', '#362415', '#E8A87C'],
                    borderColor: '#111827',
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111827',
                        titleColor: '#F9C22E',
                        bodyColor: '#F4F0E6',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: { label: (ctx) => ' ' + ctx.parsed.y.toLocaleString('id-ID') + ' kali dilihat' },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { weight: '800', size: 11 }, color: '#362415' }, border: { color: '#362415', width: 2 } },
                    y: { beginAtZero: true, grid: { color: 'rgba(54,36,21,0.08)' }, ticks: { font: { weight: '700', size: 11 }, color: '#362415', callback: (val) => val.toLocaleString('id-ID') }, border: { color: '#362415', width: 2 } },
                },
            },
        });
    });
};

onMounted(() => { renderChart(); });

const stockLabel = (s) => s === 'ready' ? 'Ready' : s === 'pre_order' ? 'PO' : 'Habis';
const stockClass = (s) => s === 'ready' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : s === 'pre_order' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-rose-100 text-rose-800 border-rose-300';
const starsArray = (rating) => Array.from({ length: 5 }, (_, i) => i < rating);
const handlePrint = () => window.print();
const reportDate = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <Head title="Laporan Produk - SiswaMart" />
    <AuthenticatedLayout>
        <div class="space-y-6 sm:space-y-8 pb-16" id="report-container">

            <!-- HEADER -->
            <div class="print:hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-8 h-8 bg-emerald-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black uppercase tracking-tighter text-gray-900">
                            Laporan <span class="text-emerald-600">Produk</span>
                        </h2>
                    </div>
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wider pl-1">
                        Performa {{ shopName }} - {{ reportDate }}
                    </p>
                </div>
                <button
                    @click="handlePrint"
                    id="btn-print-report"
                    class="no-print inline-flex items-center gap-2 px-5 py-3 bg-gray-900 hover:bg-gray-700 text-white border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[4px_4px_0px_0px_rgba(17,24,39,0.4)] active:scale-95 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export / Cetak PDF
                </button>
            </div>

            <!-- Header Print Only -->
            <div class="print-only" style="display:none">
                <h1 style="font-size:20px;font-weight:900;text-transform:uppercase">Laporan Performa Produk - {{ shopName }}</h1>
                <p style="font-size:12px;margin-top:4px">SiswaMart - SMKN 11 Bandung - {{ reportDate }}</p>
                <hr style="border:2px solid #111827;margin-top:12px"/>
            </div>

            <!-- STAT CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                <div class="print-card print-avoid-break bg-amber-400 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col gap-2">
                    <div class="w-10 h-10 bg-white border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-900/70">Upload 7 Hari Terakhir</p>
                    <p class="text-4xl font-black text-gray-900 tracking-tighter leading-none">{{ newProductsLast7Days }}</p>
                    <p class="text-xs font-bold text-gray-900/80">produk baru ditambahkan</p>
                </div>

                <div class="print-card print-avoid-break bg-sky-300 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col gap-2">
                    <div class="w-10 h-10 bg-white border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-900/70">Ulasan 7 Hari Terakhir</p>
                    <p class="text-4xl font-black text-gray-900 tracking-tighter leading-none">{{ totalCommentsLast7Days }}</p>
                    <p class="text-xs font-bold text-gray-900/80">ulasan baru dari pembeli</p>
                </div>

                <div class="print-card print-avoid-break bg-emerald-400 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col gap-2">
                    <div class="w-10 h-10 bg-white border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-900/70">Total Views Akumulasi</p>
                    <p class="text-4xl font-black text-gray-900 tracking-tighter leading-none">{{ Number(totalViewsAccumulated).toLocaleString('id-ID') }}</p>
                    <p class="text-xs font-bold text-gray-900/80">tayangan semua produk</p>
                </div>
            </div>

            <!-- BAR CHART -->
            <div class="print-card print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827]">
                <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-4 mb-6">
                    <div class="w-10 h-10 bg-orange-500 border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Top 5 Produk Terpopuler</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Berdasarkan jumlah tayangan kumulatif</p>
                    </div>
                </div>
                <div v-if="topViewedProducts.length > 0" class="h-64 sm:h-72 print:hidden relative">
                    <canvas ref="chartCanvas" id="chart-top-products"></canvas>
                </div>
                <div v-if="topViewedProducts.length > 0" class="hidden print:block">
                    <h3 class="font-black text-sm uppercase mb-3">Rangkuman Data Terpopuler</h3>
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-gray-100 border-2 border-gray-900">
                                <th class="p-2 text-left">Produk</th>
                                <th class="p-2 text-center">Tayangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in topViewedProducts" :key="product.id" class="border-x-2 border-b-2 border-gray-900">
                                <td class="p-2 font-bold">{{ product.name }}</td>
                                <td class="p-2 text-center font-black">{{ Number(product.views_count || 0).toLocaleString('id-ID') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="h-48 flex flex-col items-center justify-center gap-3 text-gray-400">
                    <svg class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <p class="text-xs font-black uppercase">Belum ada data produk untuk ditampilkan</p>
                </div>
            </div>

            <!-- GRID BAWAH -->
            <div class="print-page-two print-page-2 grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">

                <!-- TABEL PERINGKAT -->
                <div class="print-card print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] space-y-4">
                    <div class="flex items-center gap-2 border-b-2 border-gray-100 pb-3">
                        <div class="w-8 h-8 bg-amber-400 border-2 border-gray-900 rounded-lg flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-tight text-gray-900">Peringkat Tayangan</h3>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Ranking 5 produk teratas</p>
                        </div>
                    </div>
                    <div v-if="topViewedProducts.length > 0" class="space-y-2">
                        <div
                            v-for="(product, idx) in topViewedProducts"
                            :key="product.id"
                            class="flex items-center gap-3 p-3 rounded-xl border-2 border-gray-100 hover:border-gray-900 hover:bg-stone-50 transition-all group"
                        >
                            <div
                                :class="idx === 0 ? 'bg-amber-400' : idx === 1 ? 'bg-stone-300' : idx === 2 ? 'bg-orange-300' : 'bg-stone-100'"
                                class="w-8 h-8 rounded-lg border-2 border-gray-900 flex items-center justify-center font-black text-sm text-gray-900 shrink-0 shadow-[2px_2px_0px_#111827]"
                            >{{ idx + 1 }}</div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-black text-xs text-gray-900 uppercase truncate group-hover:text-orange-600 transition">{{ product.name }}</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] font-bold text-gray-500">{{ product.category?.name || 'Umum' }}</span>
                                    <span :class="stockClass(product.stock_status)" class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded border">{{ stockLabel(product.stock_status) }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-sm font-black text-gray-900 tabular-nums">{{ Number(product.views_count).toLocaleString('id-ID') }}</span>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">views</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada produk.</div>
                </div>

                <!-- ULASAN TERBARU -->
                <div class="print-card print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] space-y-4">
                    <div class="flex items-center gap-2 border-b-2 border-gray-100 pb-3">
                        <div class="w-8 h-8 bg-sky-300 border-2 border-gray-900 rounded-lg flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-tight text-gray-900">Ulasan Terbaru</h3>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">7 hari terakhir</p>
                        </div>
                    </div>
                    <div v-if="recentReviews.length > 0" class="space-y-3">
                        <div v-for="rev in recentReviews" :key="rev.id" class="p-3.5 rounded-xl border-2 border-gray-100 hover:border-gray-900 transition-all bg-stone-50/50">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-orange-500 border-2 border-gray-900 rounded-lg flex items-center justify-center text-white font-black text-xs shadow-[1px_1px_0px_#111827]">
                                        {{ (rev.reviewer_name || 'S').charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="text-xs font-black text-gray-900 uppercase">{{ rev.reviewer_name }}</span>
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <svg v-for="(filled, i) in starsArray(rev.rating)" :key="i" :class="filled ? 'text-amber-400' : 'text-gray-200'" class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-gray-700 leading-relaxed line-clamp-2">"{{ rev.comment || 'Tidak ada komentar.' }}"</p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[10px] font-black text-orange-600 uppercase truncate max-w-[60%]">{{ rev.product?.name || 'Produk' }}</span>
                                <span class="text-[9px] font-bold text-gray-400">{{ rev.created_at }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada ulasan dalam 7 hari terakhir.</div>
                </div>
            </div>

            <!-- Footer Print -->
            <div class="print-only" style="display:none;margin-top:32px;padding-top:16px;border-top:2px solid #111827">
                <p style="font-size:11px;color:#6b7280;font-weight:700">Dicetak dari SiswaMart - {{ reportDate }} - siswamart.my.id</p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
    body, html {
        background-color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    nav, header, sidebar, .print\:hidden {
        display: none !important;
    }
    /* Mencegah card terpotong di tengah */
    .print-avoid-break {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }
    /* Memaksa bagian tabel ke Halaman 2 */
    .print-page-2 {
        break-before: page !important;
        page-break-before: always !important;
    }
}
</style>

