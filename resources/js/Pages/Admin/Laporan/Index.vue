<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    sellers: { type: [Array, Object], default: () => [] },
    chartShops: { type: Array, default: () => [] },
    shops: { type: Array, default: () => [] },
    stats: {
        type: Object,
        default: () => ({
            total_active: 0,
            total_passive: 0,
            total_suspended: 0,
            total_penjual: 0,
            new_products_week: 0,
            deleted_products_week: 0,
        }),
    },
});

// Format Hari, Tanggal, dan Tahun Hari Ini (Bahasa Indonesia)
const formattedDate = computed(() => {
    const today = new Date();
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(today);
});

const normalizeShop = (item) => ({
    ...item,
    id: item.user_id || item.id_penjual || item.id,
    nama_toko: item.nama_toko || 'TOKO ' + (item.username || '').toUpperCase(),
    nama_lengkap: item.nama_lengkap || item.username || 'Penjual',
    owner: item.owner || { username: item.username || '-' },
    status: item.status || (item.activity_badge === 'suspended' ? 'suspended' : 'active'),
    is_open: item.is_open ?? true,
    last_status_change_at: item.last_status_change_at || '7 hari terakhir',
    total_products_count: item.total_produk_count ?? item.total_products_count ?? 0,
    total_reviews_count: item.total_reviews_count ?? 0,
    new_products_count: item.produk_baru_count ?? item.new_products_count ?? 0,
    deleted_products_count: item.produk_dihapus_count ?? item.deleted_products_count ?? 0,
    activity_badge: item.activity_badge || 'active',
});

// Normalisasi list data toko / penjual dari Controller (Halaman saat ini untuk tabel)
const shopsList = computed(() => {
    let raw = [];
    if (Array.isArray(props.sellers)) {
        raw = props.sellers;
    } else if (props.sellers?.data) {
        raw = props.sellers.data;
    } else if (props.shops && props.shops.length > 0) {
        raw = props.shops;
    }
    return raw.map(normalizeShop);
});

// Normalisasi untuk grafik perbandingan produk per toko
const chartShopsList = computed(() => {
    if (props.chartShops && props.chartShops.length > 0) {
        return props.chartShops.map(normalizeShop);
    }
    return shopsList.value;
});

// State & Kontrol Dropdown Export
const showExportDropdown = ref(false);

const toggleExportDropdown = () => {
    showExportDropdown.value = !showExportDropdown.value;
};

const closeExportDropdown = () => {
    showExportDropdown.value = false;
};

// Export PDF (Print Dialog)
const handlePrintPDF = () => {
    showExportDropdown.value = false;
    window.print();
};

// Export Spreadsheet (.xlsx) lewat ExcelJS (Rapi & Ber-border)
const handleExportExcel = async () => {
    showExportDropdown.value = false;

    try {
        const ExcelJS = await import('exceljs');
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Rekap Toko');

        // Tampilkan Gridlines Excel
        worksheet.views = [{ showGridLines: true }];

        // Style Garis Border Hitam
        const thinBorder = {
            top: { style: 'thin', color: { argb: 'FF111827' } },
            left: { style: 'thin', color: { argb: 'FF111827' } },
            bottom: { style: 'thin', color: { argb: 'FF111827' } },
            right: { style: 'thin', color: { argb: 'FF111827' } }
        };

        // Lebar Kolom A-G
        worksheet.columns = [
            { key: 'no', width: 8 },
            { key: 'nama_lengkap', width: 28 },
            { key: 'nama_toko', width: 28 },
            { key: 'total_produk', width: 16 },
            { key: 'total_ulasan', width: 16 },
            { key: 'produk_baru', width: 20 },
            { key: 'produk_dihapus', width: 22 }
        ];

        // 1. JUDUL LAPORAN UTAMA (A1:G1)
        worksheet.mergeCells('A1:G1');
        const titleCell = worksheet.getCell('A1');
        titleCell.value = 'LAPORAN REKAPITULASI TOKO & PENJUAL';
        titleCell.font = { name: 'Arial', size: 12, bold: true, color: { argb: 'FF111827' } };
        titleCell.alignment = { horizontal: 'center', vertical: 'middle' };
        worksheet.getRow(1).height = 24;

        // Subtitle (A2:G2)
        worksheet.mergeCells('A2:G2');
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
        headerRow.values = [
            'NO.',
            'NAMA PENJUAL',
            'NAMA TOKO',
            'TOTAL PRODUK',
            'TOTAL ULASAN',
            'PRODUK BARU (7HR)',
            'PRODUK DIHAPUS (7HR)'
        ];

        ['A4', 'B4', 'C4', 'D4', 'E4', 'F4', 'G4'].forEach((ref) => {
            const cell = worksheet.getCell(ref);
            cell.font = { name: 'Arial', size: 10, bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF111827' } };
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
            cell.border = thinBorder;
        });

        // 3. ISI DATA TOKO
        const list = chartShopsList.value;
        if (list.length > 0) {
            list.forEach((shop, idx) => {
                const rowNum = 5 + idx;
                const row = worksheet.getRow(rowNum);
                row.height = 20;

                const cA = row.getCell(1);
                const cB = row.getCell(2);
                const cC = row.getCell(3);
                const cD = row.getCell(4);
                const cE = row.getCell(5);
                const cF = row.getCell(6);
                const cG = row.getCell(7);

                cA.value = idx + 1;
                cB.value = shop.nama_lengkap;
                cC.value = shop.nama_toko;
                cD.value = Number(shop.total_products_count || 0);
                cE.value = Number(shop.total_reviews_count || 0);
                cF.value = Number(shop.new_products_count || 0);
                cG.value = Number(shop.deleted_products_count || 0);

                cA.alignment = { horizontal: 'center', vertical: 'middle' };
                cB.alignment = { horizontal: 'left', vertical: 'middle' };
                cC.alignment = { horizontal: 'left', vertical: 'middle' };
                cD.alignment = { horizontal: 'right', vertical: 'middle' };
                cE.alignment = { horizontal: 'right', vertical: 'middle' };
                cF.alignment = { horizontal: 'right', vertical: 'middle' };
                cG.alignment = { horizontal: 'right', vertical: 'middle' };

                [cA, cB, cC, cD, cE, cF, cG].forEach(cell => {
                    cell.font = { name: 'Arial', size: 9.5, bold: true, color: { argb: 'FF111827' } };
                    cell.border = thinBorder;
                });
            });
        } else {
            const row = worksheet.getRow(5);
            row.height = 22;
            worksheet.mergeCells('A5:G5');
            const emptyCell = row.getCell(1);
            emptyCell.value = 'Belum ada data toko.';
            emptyCell.font = { name: 'Arial', size: 9.5, italic: true, color: { argb: 'FF6B7280' } };
            emptyCell.alignment = { horizontal: 'center', vertical: 'middle' };
            ['A5', 'B5', 'C5', 'D5', 'E5', 'F5', 'G5'].forEach(ref => {
                worksheet.getCell(ref).border = thinBorder;
            });
        }

        // Download File .xlsx
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Laporan_Rekap_Toko_SiswaMart.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

    } catch (err) {
        console.error("Gagal ekspor ExcelJS:", err);
        alert("Gagal ekspor. Pastikan kamu sudah jalankan 'npm install exceljs' dan restart 'npm run dev'.");
    }
};

// Konfirmasi suspend
const confirmShop = ref(null);
const isSubmitting = ref(false);

const openConfirm = (shop) => { confirmShop.value = shop; };
const closeConfirm = () => { confirmShop.value = null; };

const doToggleSuspend = () => {
    if (!confirmShop.value || isSubmitting.value) return;
    
    isSubmitting.value = true;
    const targetUsername = confirmShop.value.username;

    router.patch(`/admin/recap/${encodeURIComponent(targetUsername)}/toggle-suspend`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false;
            closeConfirm();
        },
        onError: (errors) => {
            console.error("Gagal memperbarui status toko:", errors);
            isSubmitting.value = false;
            closeConfirm();
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const chartCanvas = ref(null);
let chartInstance = null;
const chartLabels = computed(() => chartShopsList.value.map((shop) => shop.nama_toko));
const chartData = computed(() => chartShopsList.value.map((shop) => shop.total_products_count || 0));

const renderChart = async () => {
    if (!chartCanvas.value || typeof window === 'undefined') return;

    const { Chart, CategoryScale, LinearScale, BarElement, BarController, Tooltip, Legend } = await import('chart.js');
    Chart.register(CategoryScale, LinearScale, BarElement, BarController, Tooltip, Legend);
    chartInstance?.destroy();
    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: chartLabels.value,
            datasets: [{
                label: 'Jumlah Produk',
                data: chartData.value,
                backgroundColor: '#38bdf8',
                borderColor: '#111827',
                borderWidth: 2,
                borderRadius: 7,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#111827', font: { weight: '700', size: 10 } }, border: { color: '#111827', width: 2 } },
                y: { beginAtZero: true, ticks: { precision: 0, color: '#111827', font: { weight: '700' } }, border: { color: '#111827', width: 2 } },
            },
        },
    });
};

onMounted(renderChart);
onBeforeUnmount(() => chartInstance?.destroy());
</script>

<template>
    <Head title="Rekap Keaktifan Toko — SiswaMart Admin" />

    <AuthenticatedLayout>
        <div class="screen-report py-6 sm:py-8 bg-[#f7f5ef] min-h-[calc(100vh-80px)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

                <!-- Page Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <!-- TAMPILAN WAKTU & TANGGAL -->
                        <div class="inline-flex items-center gap-2 bg-amber-200 border-2 border-gray-900 px-3 py-1 rounded-xl text-[10px] sm:text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_#111827] mb-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ formattedDate }}</span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-gray-900">
                            Rekap Toko
                        </h1>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                            Pantau keaktifan & kelola status toko penjual
                        </p>
                    </div>

                    <!-- DROPDOWN EXPORT (PDF & XLSX) -->
                    <div class="relative inline-block text-left print:hidden">
                        <button
                            @click="toggleExportDropdown"
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

                <!-- Summary Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                    <!-- Toko Aktif -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Toko Aktif</span>
                            <div class="w-7 h-7 bg-emerald-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-emerald-600 tracking-tight">{{ stats.total_active || 0 }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Toko berjalan normal</p>
                    </div>

                    <!-- Toko Pasif -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Perlu Ditinjau</span>
                            <div class="w-7 h-7 bg-amber-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-amber-600 tracking-tight">{{ stats.total_passive || 0 }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Tidak aktif 7 hari</p>
                    </div>

                    <!-- Total Penjual -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Total Penjual</span>
                            <div class="w-7 h-7 bg-rose-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-rose-600 tracking-tight">{{ stats.total_penjual ?? stats.total_suspended ?? 0 }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Terdaftar di platform</p>
                    </div>

                    <!-- Produk Baru -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Produk Baru (Acc)</span>
                            <div class="w-7 h-7 bg-sky-300 border-2 border-gray-900 rounded-lg flex items-center justify-center">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-sky-600 tracking-tight">{{ stats.new_products_week || 0 }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Disetujui 7 hari ini</p>
                    </div>
                </div>

                <!-- Grafik Produk per Toko -->
                <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827]">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-3 mb-4">
                        <div class="w-8 h-8 bg-sky-300 border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_0px_#111827]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l3-4 3 2 5-7" /></svg>
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Produk per Toko Penjual</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Perbandingan total produk setiap toko</p>
                        </div>
                    </div>
                    <div v-if="shopsList.length" class="relative h-64 sm:h-72 print:hidden">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                    <p v-if="!shopsList.length" class="py-8 text-center text-xs font-black uppercase text-gray-400">Belum ada data toko.</p>
                </div>

                <!-- Tabel Rekap Toko -->
                <div class="print-page-2 print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827]">
                    <div class="flex items-center gap-3 border-b-2 border-gray-100 pb-3 mb-4">
                        <div class="w-8 h-8 bg-[#fbbf24] border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_0px_#111827]">
                            <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Keaktifan Toko</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Data rekap 7 hari terakhir</p>
                        </div>
                    </div>

                    <!-- Table wrapper -->
                    <div class="overflow-x-auto print:overflow-visible -mx-4 sm:mx-0 px-4 sm:px-0">
                        <table class="w-full text-left border-collapse min-w-[780px] print:min-w-0 print:w-full">
                            <thead>
                                <tr class="bg-[#f7f5ef] border-b-2 border-gray-900 text-[10px] font-black uppercase tracking-widest text-gray-600">
                                    <th class="px-3 py-3 rounded-l-xl">Toko & Pemilik</th>
                                    <th class="px-3 py-3">Status Toko</th>
                                    <th class="px-3 py-3">Terakhir Aktif</th>
                                    <th class="px-3 py-3 text-center">Total Produk</th>
                                    <th class="px-3 py-3 text-center">Total Ulasan</th>
                                    <th class="px-3 py-3 text-center">Produk Baru (7hr)</th>
                                    <th class="px-3 py-3 text-center">Dihapus (7hr)</th>
                                    <th class="px-3 py-3 text-center">Keaktifan</th>
                                    <th class="print:hidden px-3 py-3 text-center rounded-r-xl">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="shop in shopsList" :key="shop.id" class="hover:bg-[#fdf9f0] transition">
                                    <td class="px-3 py-3.5">
                                        <div class="font-black text-xs text-gray-900 uppercase">{{ shop.nama_toko }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-0.5">{{ shop.nama_lengkap }}</div>
                                    </td>

                                    <td class="px-3 py-3.5">
                                        <span v-if="shop.status === 'suspended'" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border-2 border-gray-900 bg-rose-100 text-rose-800 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]">
                                            Suspended
                                        </span>
                                        <span v-else-if="shop.is_open" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border-2 border-gray-900 bg-emerald-100 text-emerald-800 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                            Buka
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border-2 border-gray-900 bg-gray-100 text-gray-600 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                            Tutup
                                        </span>
                                    </td>

                                    <td class="px-3 py-3.5 text-xs font-bold text-gray-600">
                                        {{ shop.last_status_change_at }}
                                    </td>

                                    <td class="px-3 py-3.5 text-center text-sm font-black text-sky-600">
                                        {{ shop.total_products_count }}
                                    </td>

                                    <td class="px-3 py-3.5 text-center text-sm font-black text-amber-600">
                                        {{ shop.total_reviews_count }}
                                    </td>

                                    <!-- Produk Baru (7 Hari) -->
                                    <td class="px-3 py-3.5 text-center">
                                        <span class="text-sm font-black" :class="shop.new_products_count > 0 ? 'text-sky-600' : 'text-gray-400'">
                                            {{ shop.new_products_count }}
                                        </span>
                                    </td>

                                    <!-- Produk Dihapus (7 Hari) -->
                                    <td class="px-3 py-3.5 text-center">
                                        <span class="text-sm font-black" :class="shop.deleted_products_count > 0 ? 'text-rose-600' : 'text-gray-400'">
                                            {{ shop.deleted_products_count }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3.5 text-center">
                                        <span
                                            class="inline-block px-2.5 py-1 rounded-lg border-2 border-gray-900 font-black text-[9px] uppercase shadow-[1px_1px_0px_0px_#111827]"
                                            :class="{
                                                'bg-emerald-200 text-emerald-900': shop.activity_badge === 'active',
                                                'bg-gray-200 text-gray-800': shop.activity_badge === 'closed',
                                                'bg-amber-200 text-amber-900': shop.activity_badge === 'passive',
                                                'bg-rose-200 text-rose-900': shop.activity_badge === 'suspended',
                                                'bg-blue-100 text-blue-800 border-blue-900': shop.activity_badge === 'new',
                                            }"
                                        >
                                            <span v-if="shop.activity_badge === 'active'">Aktif</span>
                                            <span v-else-if="shop.activity_badge === 'closed'">Toko Tutup</span>
                                            <span v-else-if="shop.activity_badge === 'passive'">Perlu Ditinjau</span>
                                            <span v-else-if="shop.activity_badge === 'new'">Toko Baru</span>
                                            <span v-else>Suspended</span>
                                        </span>
                                    </td>

                                    <td class="print:hidden px-3 py-3.5 text-center">
                                        <button
                                            @click="openConfirm(shop)"
                                            class="px-3 py-1.5 border-2 border-gray-900 rounded-lg font-black text-[10px] uppercase shadow-[2px_2px_0px_0px_#111827] hover:shadow-none hover:translate-x-[2px] hover:translate-y-[2px] transition-all active:scale-95"
                                            :class="shop.status === 'suspended'
                                                ? 'bg-emerald-400 hover:bg-emerald-300 text-gray-900'
                                                : 'bg-rose-400 hover:bg-rose-300 text-gray-900'"
                                        >
                                            {{ shop.status === 'suspended' ? 'Aktifkan' : 'Suspend' }}
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="shopsList.length === 0">
                                    <td colspan="9" class="py-10 text-center text-xs font-black uppercase text-gray-400">
                                        Belum ada data toko.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION NAVIGATION -->
                    <Pagination :links="sellers.links || []" :meta="sellers" />
                </div>

            </div>
        </div>

        <!-- PRINT REPORT (PRESISI SAMA DENGAN EXCEL - HITAM PUTIH RESMI) -->
        <section class="print-report">
            <div class="print-report__header">
                <h1>LAPORAN REKAPITULASI TOKO & PENJUAL</h1>
                <p>SiswaMart — Tanggal Rekap: {{ formattedDate }}</p>
            </div>

            <table class="print-report__table">
                <thead>
                    <tr>
                        <th style="width: 5%;">NO.</th>
                        <th style="width: 25%;">NAMA PENJUAL</th>
                        <th style="width: 22%;">NAMA TOKO</th>
                        <th style="width: 12%;">TOTAL PRODUK</th>
                        <th style="width: 12%;">TOTAL ULASAN</th>
                        <th style="width: 12%;">PRODUK BARU (7HR)</th>
                        <th style="width: 12%;">PRODUK DIHAPUS (7HR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(shop, index) in chartShopsList" :key="`print-${shop.id}`">
                        <td class="text-center">{{ index + 1 }}</td>
                        <td>{{ shop.nama_lengkap }}</td>
                        <td>{{ shop.nama_toko }}</td>
                        <td class="text-right">{{ shop.total_products_count }}</td>
                        <td class="text-right">{{ shop.total_reviews_count }}</td>
                        <td class="text-right">{{ shop.new_products_count }}</td>
                        <td class="text-right">{{ shop.deleted_products_count }}</td>
                    </tr>
                    <tr v-if="chartShopsList.length === 0">
                        <td colspan="7" class="text-center">Belum ada data toko.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Konfirmasi Dialog Suspend -->
        <Teleport to="body">
            <div
                v-if="confirmShop"
                class="fixed inset-0 z-[999] flex items-center justify-center p-4 print:hidden"
                @click.self="closeConfirm"
            >
                <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

                <div class="relative bg-white border-4 border-gray-900 rounded-3xl p-6 sm:p-8 w-full max-w-sm shadow-[8px_8px_0px_0px_#111827]">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl border-2 border-gray-900 flex items-center justify-center shadow-[3px_3px_0px_0px_#111827]"
                        :class="confirmShop.status === 'suspended' ? 'bg-emerald-300' : 'bg-rose-300'"
                    >
                        <svg class="w-7 h-7 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path v-if="confirmShop.status !== 'suspended'" stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-lg font-black uppercase text-center text-gray-900 tracking-tight">
                        {{ confirmShop.status === 'suspended' ? 'Aktifkan Toko?' : 'Suspend Toko?' }}
                    </h3>
                    <p class="text-xs font-bold text-gray-500 text-center mt-2 leading-relaxed">
                        Toko <span class="font-black text-gray-900">{{ confirmShop.nama_toko }}</span>
                        <span v-if="confirmShop.status !== 'suspended'">
                            akan diblokir dari katalog publik. Produknya tidak akan muncul sampai diaktifkan kembali.
                        </span>
                        <span v-else>
                            akan diaktifkan kembali dan produknya akan kembali muncul di katalog publik.
                        </span>
                    </p>

                    <div class="flex gap-3 mt-6">
                        <button
                            @click="closeConfirm"
                            :disabled="isSubmitting"
                            class="flex-1 py-2.5 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest bg-white hover:bg-stone-100 shadow-[3px_3px_0px_0px_#111827] hover:shadow-none hover:translate-x-[3px] hover:translate-y-[3px] transition-all disabled:opacity-50"
                        >
                            Batal
                        </button>
                        <button
                            @click="doToggleSuspend"
                            :disabled="isSubmitting"
                            class="flex-1 py-2.5 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[3px_3px_0px_0px_#111827] hover:shadow-none hover:translate-x-[3px] hover:translate-y-[3px] transition-all disabled:opacity-50"
                            :class="confirmShop.status === 'suspended' ? 'bg-emerald-400 hover:bg-emerald-300' : 'bg-rose-400 hover:bg-rose-300'"
                        >
                            {{ isSubmitting ? 'Memproses...' : (confirmShop.status === 'suspended' ? 'Ya, Aktifkan' : 'Ya, Suspend') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style>
.print-report {
    display: none;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }

    /* Paksa background murni putih */
    html, 
    body, 
    #app, 
    main, 
    div {
        background-color: #ffffff !important;
        background: #ffffff !important;
        color: #000000 !important;
        box-shadow: none !important;
    }

    /* Sembunyikan navigasi, sidebar, dan elemen UI layar biasa */
    nav, 
    header:not(.print-report__header), 
    sidebar, 
    footer, 
    .screen-report, 
    .print\:hidden {
        display: none !important;
    }

    /* Tampilan Laporan Cetak PDF (Hitam Putih Presisi) */
    .print-report {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background-color: #ffffff !important;
        font-family: Arial, sans-serif !important;
    }

    /* Judul Laporan & Subtitle Tanggal (Tampil Rapi di Atas Tabel) */
    .print-report__header {
        display: block !important;
        text-align: center;
        margin-bottom: 16px;
        padding-bottom: 4px;
    }

    .print-report__header h1 {
        font-size: 13pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 0 0 4px 0;
        color: #000000 !important;
    }

    .print-report__header p {
        font-size: 9pt;
        font-style: italic;
        margin: 0;
        color: #374151 !important;
    }

    /* Tabel Hitam Putih Rapi Ala Laporan Resmi */
    .print-report__table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
        margin-top: 8px;
    }

    .print-report__table th, 
    .print-report__table td {
        border: 1px solid #000000 !important;
        padding: 6px 8px;
        vertical-align: middle;
        color: #000000 !important;
        background-color: #ffffff !important;
    }

    /* Header Tabel Hitam Teks Putih */
    .print-report__table th {
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
        font-size: 8.5pt;
        background-color: #111827 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-report__table td.text-center {
        text-align: center;
    }

    .print-report__table td.text-right {
        text-align: right;
    }

    .print-report__table tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>