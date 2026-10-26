<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    topViewedProducts:      { type: Array,  default: () => [] },
    newProductsLast7Days:   { type: Number, default: 0 },
    totalCommentsLast7Days: { type: Number, default: 0 },
    totalViewsAccumulated:  { type: Number, default: 0 },
    totalProducts:          { type: Number, default: 0 },
    recentReviews:          { type: Array,  default: () => [] },
    reportProducts:         { type: Array,  default: () => [] },
});

const page = usePage();
const shopName = computed(() => page.props.auth?.user?.data_penjual?.nama_toko || page.props.auth?.user?.data_penjual?.nama_lengkap || page.props.auth?.user?.username || 'Toko Saya');

const formattedDate = computed(() => {
    const today = new Date();
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(today);
});

// Hitung Total View Khusus Top 5 Produk Populer
const totalTopViews = computed(() => {
    return props.topViewedProducts.reduce((sum, product) => sum + Number(product.views || 0), 0);
});

// State & Kontrol Dropdown Export
const showExportDropdown = ref(false);

const toggleExportDropdown = () => {
    showExportDropdown.value = !showExportDropdown.value;
};

const closeExportDropdown = () => {
    showExportDropdown.value = false;
};

// Export PDF (Print Dialog Browser)
const handlePrintPDF = () => {
    showExportDropdown.value = false;
    window.print();
};

// Export Murni Excel (.xlsx) Rapi, Presisi & Bold Konsisten
const handleExportExcel = async () => {
    showExportDropdown.value = false;

    try {
        const ExcelJS = await import('exceljs');
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Laporan Rekap');

        // Tampilkan Gridlines Excel
        worksheet.views = [{ showGridLines: true }];

        // Style Garis Border Hitam
        const thinBorder = {
            top: { style: 'thin', color: { argb: 'FF111827' } },
            left: { style: 'thin', color: { argb: 'FF111827' } },
            bottom: { style: 'thin', color: { argb: 'FF111827' } },
            right: { style: 'thin', color: { argb: 'FF111827' } }
        };

        // Lebar Kolom A, B, C
        worksheet.columns = [
            { key: 'no', width: 8 },
            { key: 'indikator', width: 52 },
            { key: 'total', width: 26 }
        ];

        // 1. JUDUL LAPORAN UTAMA (A1:C2) - Clean White Title
        worksheet.mergeCells('A1:C1');
        const titleCell = worksheet.getCell('A1');
        titleCell.value = `LAPORAN REKAPITULASI PRODUK - ${shopName.value.toUpperCase()}`;
        titleCell.font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF111827' } };
        titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
        worksheet.getRow(1).height = 24;

        worksheet.mergeCells('A2:C2');
        const subCell = worksheet.getCell('A2');
        subCell.value = `SiswaMart — Tanggal Rekap: ${formattedDate.value}`;
        subCell.font = { name: 'Arial', size: 9, italic: true, color: { argb: 'FF4B5563' } };
        subCell.alignment = { horizontal: 'center', vertical: 'middle' };
        worksheet.getRow(2).height = 18;

        // Spasi Baris 3
        worksheet.getRow(3).height = 10;

        // 2. HEADER TABEL (Baris 4)
        const headerRow = worksheet.getRow(4);
        headerRow.height = 26;
        headerRow.values = ['NO.', 'INDIKATOR REKAPITULASI', 'JUMLAH / TOTAL DATA'];

        ['A4', 'B4', 'C4'].forEach((ref) => {
            const cell = worksheet.getCell(ref);
            cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF111827' } };
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
            cell.border = thinBorder;
        });

        // Helper Baris Indikator Utama (1, 2, 3, 5)
        const addStandardRow = (rowNum, no, indikator, jumlah) => {
            worksheet.getRow(rowNum).height = 22;
            const a = worksheet.getCell(`A${rowNum}`);
            const b = worksheet.getCell(`B${rowNum}`);
            const c = worksheet.getCell(`C${rowNum}`);

            a.value = no;
            b.value = indikator;
            c.value = jumlah;

            a.alignment = { horizontal: 'center', vertical: 'middle' };
            b.alignment = { horizontal: 'left', vertical: 'middle' };
            c.alignment = { horizontal: 'right', vertical: 'middle' };

            [a, b, c].forEach(cell => {
                cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF111827' } };
                cell.border = thinBorder;
            });
        };

        // 3. POIN 1 - 3 (BOLD KONSISTEN)
        addStandardRow(5, 1, 'Upload Produk 7 Hari Terakhir', `${props.newProductsLast7Days} Produk`);
        addStandardRow(6, 2, 'Ulasan 7 Hari Terakhir', `${props.totalCommentsLast7Days} Ulasan`);
        addStandardRow(7, 3, 'Total Keseluruhan Produk Views 7 Hari Terakhir', `${Number(props.totalViewsAccumulated).toLocaleString('id-ID')} Views`);

        // 4. POIN 4 (Top 5 Produk Populer & Merging Vertikal Kolom A)
        const products = props.topViewedProducts;
        const productCount = products.length > 0 ? products.length : 1;
        const p4StartRow = 8;
        const p4EndRow = p4StartRow + productCount;

        // Merge Vertikal Kolom A untuk Nomor 4
        worksheet.mergeCells(`A${p4StartRow}:A${p4EndRow}`);
        const cellA4 = worksheet.getCell(`A${p4StartRow}`);
        cellA4.value = 4;
        cellA4.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FF111827' } };
        cellA4.alignment = { horizontal: 'center', vertical: 'middle' };

        // Header Poin 4 (B8:C8) - Soft Gray Fill
        worksheet.mergeCells(`B${p4StartRow}:C${p4StartRow}`);
        const cellB4 = worksheet.getCell(`B${p4StartRow}`);
        cellB4.value = 'Top 5 Produk Populer Yang Dijual & Total View-nya:';
        cellB4.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF111827' } };
        cellB4.alignment = { horizontal: 'left', vertical: 'middle' };

        worksheet.getRow(p4StartRow).height = 22;

        ['A', 'B', 'C'].forEach(col => {
            const cell = worksheet.getCell(`${col}${p4StartRow}`);
            cell.border = thinBorder;
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } };
        });

        // Sub-list Produk (Baris 9, 10, 11) - DIBUAT BOLD DAN RAPI
        let prodRow = p4StartRow + 1;
        if (products.length > 0) {
            products.forEach((p, idx) => {
                worksheet.getRow(prodRow).height = 20;

                const bCell = worksheet.getCell(`B${prodRow}`);
                bCell.value = `   ${idx + 1}.  ${p.nama_produk}`;
                bCell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF111827' } }; // TEBAL / BOLD
                bCell.alignment = { horizontal: 'left', vertical: 'middle' };

                const cCell = worksheet.getCell(`C${prodRow}`);
                cCell.value = `${Number(p.views || 0).toLocaleString('id-ID')} Views`;
                cCell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF111827' } }; // TEBAL / BOLD
                cCell.alignment = { horizontal: 'right', vertical: 'middle' };

                ['A', 'B', 'C'].forEach(col => {
                    const cell = worksheet.getCell(`${col}${prodRow}`);
                    cell.border = thinBorder;
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF9FAFB' } };
                });

                prodRow++;
            });
        } else {
            worksheet.getRow(prodRow).height = 20;
            const bCell = worksheet.getCell(`B${prodRow}`);
            bCell.value = '   Belum ada data produk populer.';
            bCell.font = { name: 'Arial', size: 9.5, bold: true, italic: true, color: { argb: 'FF6B7280' } };
            bCell.alignment = { horizontal: 'left', vertical: 'middle' };

            const cCell = worksheet.getCell(`C${prodRow}`);
            cCell.value = '-';
            cCell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF6B7280' } };
            cCell.alignment = { horizontal: 'center', vertical: 'middle' };

            ['A', 'B', 'C'].forEach(col => {
                const cell = worksheet.getCell(`${col}${prodRow}`);
                cell.border = thinBorder;
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF9FAFB' } };
            });
            prodRow++;
        }

        // Kunci Border pada Sel Kolom A Ter-merge
        for (let r = p4StartRow; r <= p4EndRow; r++) {
            worksheet.getCell(`A${r}`).border = thinBorder;
        }

        // 5. POIN 5 (Total View Produk Populer)
        const p5Row = p4EndRow + 1;
        addStandardRow(p5Row, 5, 'Total View Produk Populer 7 Hari Terakhir', `${Number(totalTopViews.value).toLocaleString('id-ID')} Views`);

        // Download File .xlsx
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Laporan_Rekap_Produk_${shopName.value.replace(/\s+/g, '_')}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

    } catch (err) {
        console.error("Gagal ekspor ExcelJS:", err);
        alert("Gagal ekspor. Pastikan kamu sudah jalankan 'npm install exceljs' dan restart 'npm run dev'.");
    }
};

// --- CHART.JS LOGIC ---
const chartCanvas = ref(null);
let chartInstance = null;

const chartLabels = computed(() =>
    props.topViewedProducts.map(p => (p.nama_produk && p.nama_produk.length > 20) ? p.nama_produk.substring(0, 18) + '...' : (p.nama_produk || 'Produk'))
);
const chartData = computed(() =>
    props.topViewedProducts.map(p => p.views || 0)
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

const stockLabel = (s) => s === 'ready' ? 'Ready' : s === 'pre-order' ? 'PO' : 'Habis';
const stockClass = (s) => s === 'ready' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : s === 'pre-order' ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-rose-100 text-rose-800 border-rose-300';
const starsArray = (rating) => Array.from({ length: 5 }, (_, i) => i < rating);
</script>

<template>
    <Head title="Laporan Produk - SiswaMart" />
    <AuthenticatedLayout>
        
        <!-- ================= TAMPILAN DASHBOARD WEB ================= -->
        <div class="screen-report space-y-6 sm:space-y-8 pb-16" id="report-container">

            <!-- HEADER WEB -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 bg-amber-200 border-2 border-gray-900 px-3 py-1 rounded-xl text-[10px] sm:text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_#111827] mb-2">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ formattedDate }}</span>
                    </div>

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
                        Performa {{ shopName }}
                    </p>
                </div>

                <!-- DROPDOWN EXPORT (PDF & XLSX) -->
                <div class="relative inline-block text-left print:hidden">
                    <button
                        @click="toggleExportDropdown"
                        id="btn-export-dropdown"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-gray-900 hover:bg-gray-700 text-white border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[4px_4px_0px_0px_rgba(17,24,39,0.4)] active:scale-95 transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Export Laporan</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showExportDropdown }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-if="showExportDropdown" @click="closeExportDropdown" class="fixed inset-0 z-20"></div>

                    <div
                        v-if="showExportDropdown"
                        class="absolute right-0 mt-2 w-56 bg-white border-2 sm:border-4 border-gray-900 rounded-2xl shadow-[4px_4px_0px_0px_#111827] z-30 overflow-hidden py-1 divide-y-2 divide-gray-100"
                    >
                        <button
                            @click="handlePrintPDF"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left font-black text-xs uppercase text-gray-900 hover:bg-amber-100 transition-colors"
                        >
                            <div class="w-8 h-8 bg-rose-500 border-2 border-gray-900 rounded-lg flex items-center justify-center text-white shadow-[1.5px_1.5px_0px_#111827] shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="leading-tight">Export / Cetak PDF</p>
                                <p class="text-[9px] font-bold text-gray-400 lowercase">format pdf & siap print</p>
                            </div>
                        </button>

                        <button
                            @click="handleExportExcel"
                            class="w-full flex items-center gap-3 px-4 py-3 text-left font-black text-xs uppercase text-gray-900 hover:bg-emerald-100 transition-colors"
                        >
                            <div class="w-8 h-8 bg-emerald-500 border-2 border-gray-900 rounded-lg flex items-center justify-center text-white shadow-[1.5px_1.5px_0px_#111827] shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="leading-tight">Export Spreadsheet</p>
                                <p class="text-[9px] font-bold text-gray-400 lowercase">format excel (.xlsx)</p>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STAT CARDS WEB -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                <div class="bg-amber-400 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col gap-2">
                    <div class="w-10 h-10 bg-white border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-900/70">Upload 7 Hari Terakhir</p>
                    <p class="text-4xl font-black text-gray-900 tracking-tighter leading-none">{{ newProductsLast7Days }}</p>
                    <p class="text-xs font-bold text-gray-900/80">produk baru ditambahkan</p>
                </div>

                <div class="bg-sky-300 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col gap-2">
                    <div class="w-10 h-10 bg-white border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-900/70">Ulasan 7 Hari Terakhir</p>
                    <p class="text-4xl font-black text-gray-900 tracking-tighter leading-none">{{ totalCommentsLast7Days }}</p>
                    <p class="text-xs font-bold text-gray-900/80">ulasan baru dari pembeli</p>
                </div>

                <div class="bg-emerald-400 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827] flex flex-col gap-2">
                    <div class="w-10 h-10 bg-white border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-900/70">Total Views Akumulasi</p>
                    <p class="text-4xl font-black text-gray-900 tracking-tighter leading-none">{{ Number(totalViewsAccumulated).toLocaleString('id-ID') }}</p>
                    <p class="text-xs font-bold text-gray-900/80">tayangan semua produk</p>
                </div>
            </div>

            <!-- BAR CHART WEB -->
            <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-[4px_4px_0px_0px_#111827] sm:shadow-[6px_6px_0px_0px_#111827]">
                <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-4 mb-6">
                    <div class="w-10 h-10 bg-orange-500 border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_#111827]">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Top 5 Produk Terpopuler</h3>
                        <p class="text-xs font-bold text-gray-400 uppercase">Berdasarkan jumlah tayangan kumulatif</p>
                    </div>
                </div>
                <div v-if="topViewedProducts.length > 0" class="h-64 sm:h-72 relative">
                    <canvas ref="chartCanvas" id="chart-top-products"></canvas>
                </div>
                <div v-else class="h-48 flex flex-col items-center justify-center gap-3 text-gray-400">
                    <svg class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    <p class="text-xs font-black uppercase">Belum ada data produk untuk ditampilkan</p>
                </div>
            </div>

            <!-- GRID BAWAH WEB -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
                <!-- TABEL PERINGKAT WEB -->
                <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] space-y-4">
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
                            :key="product.id_produk"
                            class="flex items-center gap-3 p-3 rounded-xl border-2 border-gray-100 hover:border-gray-900 hover:bg-stone-50 transition-all group"
                        >
                            <div
                                :class="idx === 0 ? 'bg-amber-400' : idx === 1 ? 'bg-stone-300' : idx === 2 ? 'bg-orange-300' : 'bg-stone-100'"
                                class="w-8 h-8 rounded-lg border-2 border-gray-900 flex items-center justify-center font-black text-sm text-gray-900 shrink-0 shadow-[2px_2px_0px_#111827]"
                            >{{ idx + 1 }}</div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-black text-xs text-gray-900 uppercase truncate group-hover:text-orange-600 transition">{{ product.nama_produk }}</h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] font-bold text-gray-500">{{ product.kategori?.nama_kategori || 'Umum' }}</span>
                                    <span :class="stockClass(product.status_stok)" class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded border">{{ stockLabel(product.status_stok) }}</span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-sm font-black text-gray-900 tabular-nums">{{ Number(product.views || 0).toLocaleString('id-ID') }}</span>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">views</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada produk.</div>
                </div>

                <!-- ULASAN TERBARU WEB -->
                <div class="bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_#111827] space-y-4">
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
                        <div v-for="rev in recentReviews" :key="rev.id_ulasan" class="p-3.5 rounded-xl border-2 border-gray-100 hover:border-gray-900 transition-all bg-stone-50/50">
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 bg-orange-500 border-2 border-gray-900 rounded-lg flex items-center justify-center text-white font-black text-xs shadow-[1px_1px_0px_#111827]">
                                        {{ (rev.nama_pengunjung || 'S').charAt(0).toUpperCase() }}
                                    </div>
                                    <span class="text-xs font-black text-gray-900 uppercase">{{ rev.nama_pengunjung }}</span>
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <svg v-for="(filled, i) in starsArray(rev.rating)" :key="i" :class="filled ? 'text-amber-400' : 'text-gray-200'" class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs font-bold text-gray-700 leading-relaxed line-clamp-2">"{{ rev.komentar || 'Tidak ada komentar.' }}"</p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[10px] font-black text-orange-600 uppercase truncate max-w-[60%]">{{ rev.produk?.nama_produk || 'Produk' }}</span>
                                <span class="text-[9px] font-bold text-gray-400">{{ rev.created_at }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada ulasan dalam 7 hari terakhir.</div>
                </div>
            </div>

        </div>

        <!-- ================= TAMPILAN CETAK / EXPORT PDF ================= -->
        <section class="print-report">
            <div class="excel-header">
                <h2>LAPORAN REKAPITULASI PRODUK - {{ shopName.toUpperCase() }}</h2>
                <p>SiswaMart — Tanggal Rekap: {{ formattedDate }}</p>
            </div>

            <table class="excel-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">NO.</th>
                        <th style="width: 62%;">INDIKATOR REKAPITULASI</th>
                        <th style="width: 30%;">JUMLAH / TOTAL DATA</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center font-bold">1</td>
                        <td>Upload Produk 7 Hari Terakhir</td>
                        <td class="text-right"><strong>{{ newProductsLast7Days }}</strong> Produk</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">2</td>
                        <td>Ulasan 7 Hari Terakhir</td>
                        <td class="text-right"><strong>{{ totalCommentsLast7Days }}</strong> Ulasan</td>
                    </tr>
                    <tr>
                        <td class="text-center font-bold">3</td>
                        <td>Total Keseluruhan Produk Views 7 Hari Terakhir</td>
                        <td class="text-right"><strong>{{ Number(totalViewsAccumulated).toLocaleString('id-ID') }}</strong> Views</td>
                    </tr>
                    
                    <tr>
                        <td :rowspan="(topViewedProducts.length > 0 ? topViewedProducts.length : 1) + 2" class="text-center font-bold">4</td>
                        <td colspan="2" class="font-bold">Top 5 Produk Populer Yang Dijual & Total View-nya:</td>
                    </tr>
                    <tr class="sub-header-row">
                        <td class="font-bold text-center">RANK & NAMA PRODUK POPULER</td>
                        <td class="font-bold text-center">TOTAL VIEWS</td>
                    </tr>
                    <tr v-for="(p, i) in topViewedProducts" :key="`top-${i}`">
                        <td>
                            <span class="font-bold text-center inline-block" style="width: 35px;">#{{ i + 1 }}</span>
                            <span class="font-bold">{{ p.nama_produk }}</span>
                        </td>
                        <td class="text-right"><strong>{{ Number(p.views || 0).toLocaleString('id-ID') }}</strong> Views</td>
                    </tr>
                    <tr v-if="topViewedProducts.length === 0">
                        <td colspan="2" class="text-center text-gray">Belum ada data produk populer.</td>
                    </tr>

                    <tr>
                        <td class="text-center font-bold">5</td>
                        <td>Total View Produk Populer 7 Hari Terakhir</td>
                        <td class="text-right"><strong>{{ Number(totalTopViews).toLocaleString('id-ID') }}</strong> Views</td>
                    </tr>
                </tbody>
            </table>
        </section>

    </AuthenticatedLayout>
</template>

<style>
/* Sembunyikan cetakan di layar browser biasa */
.print-report {
    display: none;
}

/* KHUSUS TAMPILAN PRINT / EXPORT PDF */
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
    
    html, body, #app, main, div, section, article {
        background-color: #ffffff !important;
        background: #ffffff !important;
        box-shadow: none !important;
        color: #111827 !important;
        font-family: Arial, sans-serif !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    nav, header, sidebar, .print\:hidden, .screen-report {
        display: none !important;
    }

    .print-report {
        display: block !important;
        width: 100%;
        background-color: #ffffff !important;
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .excel-header {
        text-align: center;
        border-bottom: 2px solid #111827;
        padding-bottom: 10px;
        margin-bottom: 16px;
        background-color: #ffffff !important;
    }

    .excel-header h2 {
        font-size: 13pt;
        font-weight: 800;
        margin: 0;
        text-transform: uppercase;
        color: #111827;
    }

    .excel-header p {
        font-size: 9pt;
        margin: 4px 0 0 0;
        color: #4b5563;
        font-weight: 600;
    }

    .excel-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
        border: 1.5px solid #111827 !important;
        background-color: #ffffff !important;
    }

    .excel-table th, 
    .excel-table td {
        border: 1px solid #111827 !important;
        padding: 8px 10px;
        vertical-align: middle;
        background-color: #ffffff !important;
        color: #111827 !important;
    }

    .excel-table th {
        font-weight: 800;
        text-transform: uppercase;
        text-align: center;
        font-size: 9pt;
        border-bottom: 1.5px solid #111827 !important;
    }

    .sub-header-row td {
        font-size: 8.5pt;
        text-transform: uppercase;
        border-bottom: 1px solid #111827 !important;
    }

    .text-center { text-align: center !important; }
    .text-right { text-align: right !important; }
    .font-bold { font-weight: 700 !important; }
    .text-gray { color: #6b7280 !important; }
}
</style>