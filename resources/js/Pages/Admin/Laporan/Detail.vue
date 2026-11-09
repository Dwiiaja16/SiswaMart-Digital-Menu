<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    seller: {
        type: Object,
        default: () => ({}),
    },
    products: {
        type: [Array, Object],
        default: () => ({ data: [], links: [] }),
    },
    allProducts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_products: 0,
            approved_products: 0,
            pending_products: 0,
            rejected_products: 0,
            compliance_rate: 0,
            total_reviews: 0,
            avg_shop_rating: "0.0",
        }),
    },
    ratingDistribution: {
        type: Object,
        default: () => ({ 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 }),
    },
});

// State Pencarian Nama Produk di Tabel
const searchQuery = ref("");

// Format Hari & Tanggal Indonesia
const formattedDate = computed(() => {
    const today = new Date();
    return new Intl.DateTimeFormat("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(today);
});

// Helper Format Tanggal & Jam
const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

// Seluruh data produk lifetime (untuk Export Excel, Cetak PDF, dan Global Search)
const allProductsList = computed(() => {
    if (props.allProducts && props.allProducts.length > 0) {
        return props.allProducts;
    }
    if (Array.isArray(props.products)) {
        return props.products;
    }
    return props.products?.data || [];
});

// Normalisasi list data produk untuk tabel (Pencarian Awalan Huruf Nama Produk)
const currentProductsList = computed(() => {
    const isSearching = searchQuery.value.trim() !== "";
    const source = isSearching
        ? allProductsList.value
        : (Array.isArray(props.products) ? props.products : (props.products?.data || []));

    if (!isSearching) return source;

    const q = searchQuery.value.toLowerCase().trim();
    return source.filter((p) => {
        return p.nama_produk?.toLowerCase().startsWith(q);
    });
});

// Helper Penomoran Baris Tabel Berdasarkan Halaman Paginasi
const getItemNumber = (index) => {
    if (searchQuery.value.trim() !== "") {
        return index + 1;
    }
    if (props.products?.from) {
        return props.products.from + index;
    }
    return index + 1;
};

// Name & Shop getters
const shopName = computed(() => {
    return (
        props.seller?.data_penjual?.nama_toko ||
        props.seller?.dataPenjual?.nama_toko ||
        ("TOKO " + (props.seller?.username || "").toUpperCase())
    ).toUpperCase();
});

const sellerFullName = computed(() => {
    return (
        props.seller?.data_penjual?.nama_lengkap ||
        props.seller?.dataPenjual?.nama_lengkap ||
        props.seller?.username ||
        "-"
    );
});

const sellerClass = computed(() => {
    return (
        props.seller?.data_penjual?.nama_kelas ||
        props.seller?.dataPenjual?.nama_kelas ||
        "-"
    );
});

// State & Function Export
const showExportDropdown = ref(false);

const toggleExportDropdown = () => {
    showExportDropdown.value = !showExportDropdown.value;
};

const closeExportDropdown = () => {
    showExportDropdown.value = false;
};

// Export PDF (Print Dialog)
const handlePrintPDF = () => {
    closeExportDropdown();
    window.print();
};

// Export Excel (ExcelJS) untuk Lembar Rekap Detail
const handleExportExcel = async () => {
    closeExportDropdown();

    try {
        const ExcelJS = await import("exceljs");
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet("Rekap Detail Toko");

        worksheet.views = [{ showGridLines: true }];

        const thinBorder = {
            top: { style: "thin", color: { argb: "FF000000" } },
            left: { style: "thin", color: { argb: "FF000000" } },
            bottom: { style: "thin", color: { argb: "FF000000" } },
            right: { style: "thin", color: { argb: "FF000000" } },
        };

        worksheet.columns = [
            { key: "no", width: 8 },
            { key: "col2", width: 32 },
            { key: "col3", width: 22 },
            { key: "col4", width: 24 },
            { key: "col5", width: 22 },
            { key: "col6", width: 14 },
            { key: "col7", width: 16 },
        ];

        // 1. HEADER DOKUMEN (JUDUL UTAMA)
        worksheet.mergeCells("A1:G1");
        const titleCell = worksheet.getCell("A1");
        titleCell.value = `LAPORAN REKAPITULASI DETAIL TOKO — ${shopName.value}`;
        titleCell.font = { name: "Arial", size: 12, bold: true, color: { argb: "FF000000" } };
        titleCell.alignment = { horizontal: "center", vertical: "middle" };
        worksheet.getRow(1).height = 24;

        worksheet.mergeCells("A2:G2");
        const subCell = worksheet.getCell("A2");
        subCell.value = `SiswaMart Admin Audit — Tanggal Rekap: ${formattedDate.value} | Penjual: ${sellerFullName.value} (@${props.seller.username}) - Kelas: ${sellerClass.value}`;
        subCell.font = { name: "Arial", size: 9, italic: true, color: { argb: "FF4B5563" } };
        subCell.alignment = { horizontal: "center", vertical: "middle" };
        worksheet.getRow(2).height = 18;

        // 2. TABEL RINGKASAN INDIKATOR (JUDUL SEKSI I)
        worksheet.getRow(4).height = 24;
        worksheet.mergeCells("A4:G4");
        const section1 = worksheet.getCell("A4");
        section1.value = "I. INDIKATOR PERFORMA & AUDIT TOKO (LIFETIME)";
        section1.font = { name: "Arial", size: 10, bold: true, color: { argb: "FF000000" } }; // TEKS HITAM
        section1.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE5E7EB" } }; // ABU-ABU TERANG #E5E7EB
        section1.alignment = { horizontal: "left", vertical: "middle" };

        const summaryRows = [
            ["1", "Total Produk Diunggah (Lifetime)", `${props.stats.total_products || 0} Produk`],
            ["2", "Produk Disetujui / Masuk Katalog Website", `${props.stats.approved_products || 0} Produk (Kelayakan ${props.stats.compliance_rate || 0}%)`],
            ["3", "Produk Pending / Ditolak Moderasi", `${props.stats.pending_products || 0} Pending / ${props.stats.rejected_products || 0} Ditolak`],
            ["4", "Total Ulasan & Rata-rata Rating Toko", `${props.stats.total_reviews || 0} Ulasan (Rating ★ ${props.stats.avg_shop_rating || "0.0"})`],
        ];

        summaryRows.forEach((sRow, idx) => {
            const rNum = 5 + idx;
            const row = worksheet.getRow(rNum);
            row.height = 20;

            row.getCell(1).value = sRow[0];
            worksheet.mergeCells(`B${rNum}:E${rNum}`);
            row.getCell(2).value = sRow[1];
            worksheet.mergeCells(`F${rNum}:G${rNum}`);
            row.getCell(6).value = sRow[2];

            row.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
            row.getCell(2).alignment = { horizontal: "left", vertical: "middle" };
            row.getCell(6).alignment = { horizontal: "right", vertical: "middle" };

            [1, 2, 3, 4, 5, 6, 7].forEach((col) => {
                const cell = row.getCell(col);
                cell.font = { name: "Arial", size: 9.5, bold: true, color: { argb: "FF000000" } };
                cell.border = thinBorder;
            });
        });

        // 3. TABEL DETAIL PRODUK (JUDUL SEKSI II & HEADER KOLOM)
        const startRowProd = 10;
        worksheet.getRow(startRowProd).height = 24;
        worksheet.mergeCells(`A${startRowProd}:G${startRowProd}`);
        const section2 = worksheet.getCell(`A${startRowProd}`);
        section2.value = "II. RINCIAN PRODUK, TANGGAL APPROVE KATALOG & RATING";
        section2.font = { name: "Arial", size: 10, bold: true, color: { argb: "FF000000" } }; // TEKS HITAM
        section2.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE5E7EB" } }; // ABU-ABU TERANG #E5E7EB
        section2.alignment = { horizontal: "left", vertical: "middle" };

        const headerRow = worksheet.getRow(startRowProd + 1);
        headerRow.height = 26;
        headerRow.values = ["NO.", "NAMA PRODUK", "KATEGORI", "TANGGAL UPLOAD", "TGL MASUK KATALOG (APPROVE)", "RATING", "TOTAL ULASAN"];

        [1, 2, 3, 4, 5, 6, 7].forEach((col) => {
            const cell = headerRow.getCell(col);
            cell.font = { name: "Arial", size: 9.5, bold: true, color: { argb: "FF000000" } }; // TEKS HITAM
            cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE5E7EB" } }; // ABU-ABU TERANG #E5E7EB
            cell.alignment = { horizontal: col === 1 ? "center" : col >= 4 ? "center" : "left", vertical: "middle" };
            cell.border = thinBorder;
        });

        const list = allProductsList.value;
        if (list.length > 0) {
            list.forEach((p, idx) => {
                const rowNum = startRowProd + 2 + idx;
                const row = worksheet.getRow(rowNum);
                row.height = 20;

                row.getCell(1).value = idx + 1;
                row.getCell(2).value = `${p.nama_produk} (Rp ${Number(p.harga || 0).toLocaleString("id-ID")})`;
                row.getCell(3).value = p.kategori?.nama_kategori || "-";
                row.getCell(4).value = formatDate(p.created_at);
                row.getCell(5).value = p.status_moderasi === "disetujui" ? formatDate(p.updated_at) : `[${(p.status_moderasi || "").toUpperCase()}]`;
                row.getCell(6).value = `★ ${p.ulasan_avg_rating ? Number(p.ulasan_avg_rating).toFixed(1) : "0.0"}`;
                row.getCell(7).value = `${p.ulasan_count || 0} Ulasan`;

                row.getCell(1).alignment = { horizontal: "center", vertical: "middle" };
                row.getCell(2).alignment = { horizontal: "left", vertical: "middle" };
                row.getCell(3).alignment = { horizontal: "left", vertical: "middle" };
                row.getCell(4).alignment = { horizontal: "center", vertical: "middle" };
                row.getCell(5).alignment = { horizontal: "center", vertical: "middle" };
                row.getCell(6).alignment = { horizontal: "center", vertical: "middle" };
                row.getCell(7).alignment = { horizontal: "center", vertical: "middle" };

                [1, 2, 3, 4, 5, 6, 7].forEach((col) => {
                    const cell = row.getCell(col);
                    cell.font = { name: "Arial", size: 9, bold: true, color: { argb: "FF000000" } };
                    cell.border = thinBorder;
                });
            });
        }

        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = `Laporan_Rekap_Detail_${sellerFullName.value.replace(/\s+/g, "_")}.xlsx`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (err) {
        console.error("Gagal ekspor ExcelJS:", err);
        alert("Gagal ekspor Excel. Pastikan library exceljs terpasang.");
    }
};
</script>

<template>
    <Head :title="'Laporan Detail ' + shopName + ' — Admin SiswaMart'" />

    <AuthenticatedLayout>
        <div class="screen-report py-6 sm:py-8 bg-[#f7f5ef] min-h-[calc(100vh-80px)] selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 sm:space-y-8">

                <!-- Header & Top Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <!-- BADGE TANGGAL -->
                        <div class="inline-flex items-center gap-2 bg-amber-200 border-2 border-gray-900 px-3 py-1 rounded-xl text-[10px] sm:text-xs font-black uppercase text-gray-900 shadow-[2px_2px_0px_0px_#111827] mb-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ formattedDate }}</span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black uppercase tracking-tight text-gray-900">
                            Laporan Detail Toko
                        </h1>
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-0.5">
                            {{ shopName }} &bull; {{ sellerFullName }} <span class="text-orange-600 font-bold">(@{{ seller.username }})</span> &bull; Kelas {{ sellerClass }}
                        </p>
                    </div>

                    <!-- ACTION BUTTONS: KEMBALI & EXPORT LAPORAN -->
                    <div class="flex items-center gap-3 print:hidden">
                        <!-- TOMBOL KEMBALI KE REKAP -->
                        <Link
                            :href="route('admin.recap')"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 sm:py-3 bg-white hover:bg-stone-100 text-gray-900 border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[4px_4px_0px_0px_rgba(17,24,39,0.4)] active:scale-95 transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Kembali ke Rekap</span>
                        </Link>

                        <!-- DROPDOWN EXPORT LAPORAN -->
                        <div class="relative inline-block text-left">
                            <button
                                @click="toggleExportDropdown"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 sm:py-3 bg-gray-900 hover:bg-gray-700 text-white border-2 border-gray-900 rounded-xl font-black text-xs uppercase tracking-widest shadow-[4px_4px_0px_0px_rgba(17,24,39,0.4)] active:scale-95 transition-all cursor-pointer"
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
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left font-black text-xs uppercase text-gray-900 hover:bg-amber-100 transition-colors cursor-pointer"
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
                                    class="w-full flex items-center gap-3 px-4 py-3 text-left font-black text-xs uppercase text-gray-900 hover:bg-emerald-100 transition-colors cursor-pointer"
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
                </div>

                <!-- Summary Metric Cards Grid (4 Kartu Neobrutalism Pastel) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
                    <!-- Card 1: Total Produk Diunggah -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Total Produk</span>
                            <div class="w-7 h-7 bg-sky-300 border-2 border-gray-900 rounded-lg flex items-center justify-center shadow-[1.5px_1.5px_0px_0px_#111827]">
                                <svg class="w-3.5 h-3.5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-sky-600 tracking-tight">{{ stats.total_products || 0 }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Katalog lifetime penjual</p>
                    </div>

                    <!-- Card 2: Produk Disetujui (Tayang di Web) -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Disetujui Admin</span>
                            <div class="w-7 h-7 bg-emerald-300 border-2 border-gray-900 rounded-lg flex items-center justify-center shadow-[1.5px_1.5px_0px_0px_#111827]">
                                <svg class="w-3.5 h-3.5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-emerald-600 tracking-tight">{{ stats.approved_products || 0 }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">Kelayakan {{ stats.compliance_rate || 0 }}% tayang</p>
                    </div>

                    <!-- Card 3: Pending & Ditolak Moderasi -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Pending / Ditolak</span>
                            <div class="w-7 h-7 bg-rose-300 border-2 border-gray-900 rounded-lg flex items-center justify-center shadow-[1.5px_1.5px_0px_0px_#111827]">
                                <svg class="w-3.5 h-3.5 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-rose-600 tracking-tight">
                            {{ stats.pending_products || 0 }} <span class="text-base text-gray-400 font-bold">/ {{ stats.rejected_products || 0 }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">{{ stats.pending_products || 0 }} pending, {{ stats.rejected_products || 0 }} ditolak</p>
                    </div>

                    <!-- Card 4: Rata-Rata Rating & Ulasan -->
                    <div class="print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-5 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827] space-y-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-widest text-gray-400">Rating Toko</span>
                            <div class="w-7 h-7 bg-amber-300 border-2 border-gray-900 rounded-lg flex items-center justify-center shadow-[1.5px_1.5px_0px_0px_#111827]">
                                <svg class="w-3.5 h-3.5 text-gray-900" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-3xl sm:text-4xl font-black text-amber-500 tracking-tight">★ {{ stats.avg_shop_rating || '0.0' }}</div>
                        <p class="text-[10px] font-bold text-gray-500 uppercase">{{ stats.total_reviews || 0 }} total ulasan pembeli</p>
                    </div>
                </div>

                <!-- Tabel Rincian Katalog Produk (Card Style Neobrutalism) -->
                <div class="print-page-2 print-avoid-break bg-white border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[3px_3px_0px_0px_#111827] sm:shadow-[5px_5px_0px_0px_#111827]">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b-2 border-gray-100 pb-4 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-400 border-2 border-gray-900 rounded-xl flex items-center justify-center shadow-[2px_2px_0px_0px_#111827]">
                                <svg class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">Rincian Katalog Produk</h2>
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Daftar produk, status moderasi, tanggal approve, dan rating ulasan</p>
                            </div>
                        </div>

                        <!-- INPUT SEARCH BAR PRODUK -->
                        <div class="w-full sm:w-72 print:hidden">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari Awalan Nama Produk..."
                                    class="w-full bg-white border-2 border-gray-900 rounded-xl pl-9 pr-3 py-2 text-xs font-bold text-gray-900 shadow-[2px_2px_0px_0px_#111827] focus:ring-0 focus:border-orange-500"
                                />
                                <svg class="w-4 h-4 text-gray-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Table wrapper -->
                    <div class="overflow-x-auto print:overflow-visible -mx-4 sm:mx-0 px-4 sm:px-0">
                        <table class="w-full text-left border-collapse min-w-[780px] print:min-w-0 print:w-full">
                            <thead>
                                <tr class="bg-[#f7f5ef] border-b-2 border-gray-900 text-[10px] font-black uppercase tracking-widest text-gray-600">
                                    <th class="px-3 py-3 rounded-l-xl text-center w-12">No.</th>
                                    <th class="px-3 py-3">Nama Produk</th>
                                    <th class="px-3 py-3">Kategori</th>
                                    <th class="px-3 py-3 text-center">Status Moderasi</th>
                                    <th class="px-3 py-3 text-center">Tgl Upload</th>
                                    <th class="px-3 py-3 text-center">Tgl Approve Katalog</th>
                                    <th class="px-3 py-3 text-center rounded-r-xl">Rating & Ulasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(product, idx) in currentProductsList"
                                    :key="product.id_produk"
                                    class="hover:bg-[#fdf9f0] transition"
                                >
                                    <!-- NO -->
                                    <td class="px-3 py-3.5 text-center font-black text-xs text-gray-600">
                                        {{ getItemNumber(idx) }}
                                    </td>

                                    <!-- NAMA PRODUK & HARGA -->
                                    <td class="px-3 py-3.5">
                                        <div class="font-black text-xs text-gray-900 uppercase">
                                            {{ product.nama_produk }}
                                        </div>
                                        <div class="text-[10px] font-black text-orange-600 mt-0.5">
                                            Rp {{ Number(product.harga || 0).toLocaleString('id-ID') }}
                                        </div>
                                    </td>

                                    <!-- KATEGORI -->
                                    <td class="px-3 py-3.5 text-xs font-bold uppercase text-gray-700">
                                        <span class="inline-block px-2 py-0.5 bg-stone-100 border border-gray-300 rounded text-[10px] font-bold">
                                            {{ product.kategori?.nama_kategori || '-' }}
                                        </span>
                                    </td>

                                    <!-- STATUS MODERASI -->
                                    <td class="px-3 py-3.5 text-center">
                                        <span
                                            v-if="product.status_moderasi === 'disetujui'"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border-2 border-gray-900 bg-emerald-200 text-emerald-950 font-black text-[9px] uppercase shadow-[1.5px_1.5px_0px_0px_#111827]"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 inline-block"></span>
                                            Disetujui
                                        </span>
                                        <span
                                            v-else-if="product.status_moderasi === 'pending'"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border-2 border-gray-900 bg-amber-200 text-amber-950 font-black text-[9px] uppercase shadow-[1.5px_1.5px_0px_0px_#111827]"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600 inline-block animate-pulse"></span>
                                            Pending
                                        </span>
                                        <span
                                            v-else-if="product.status_moderasi === 'ditolak'"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border-2 border-gray-900 bg-rose-200 text-rose-950 font-black text-[9px] uppercase shadow-[1.5px_1.5px_0px_0px_#111827]"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block"></span>
                                            Ditolak
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border-2 border-gray-900 bg-gray-200 text-gray-800 font-black text-[9px] uppercase shadow-[1.5px_1.5px_0px_0px_#111827]"
                                        >
                                            {{ product.status_moderasi || '-' }}
                                        </span>
                                    </td>

                                    <!-- TANGGAL UPLOAD -->
                                    <td class="px-3 py-3.5 text-center text-xs font-bold text-gray-600">
                                        {{ formatDate(product.created_at) }}
                                    </td>

                                    <!-- TANGGAL APPROVE KATALOG -->
                                    <td class="px-3 py-3.5 text-center text-xs font-bold" :class="product.status_moderasi === 'disetujui' ? 'text-emerald-700 font-black' : 'text-gray-400'">
                                        {{ product.status_moderasi === 'disetujui' ? formatDate(product.updated_at) : ('[' + (product.status_moderasi || '').toUpperCase() + ']') }}
                                    </td>

                                    <!-- RATING & ULASAN -->
                                    <td class="px-3 py-3.5 text-center">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="text-xs font-black text-amber-500 flex items-center gap-1">
                                                ★ {{ product.ulasan_avg_rating ? Number(product.ulasan_avg_rating).toFixed(1) : '0.0' }}
                                            </span>
                                            <span class="text-[9px] font-bold text-gray-400 uppercase">
                                                {{ product.ulasan_count || 0 }} ulasan
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="currentProductsList.length === 0">
                                    <td colspan="7" class="py-10 text-center text-xs font-black uppercase text-gray-400">
                                        Tidak ada data produk ditemukan.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION NAVIGATION COMPONENT (Sembunyi otomatis saat user sedang search) -->
                    <Pagination v-if="!searchQuery.trim()" :links="products.links || []" :meta="products" />
                </div>

            </div>
        </div>

        <!-- PRINT REPORT PDF (FORMAT CETAK A4 PRESISI) -->
        <section class="print-report">
            <div class="print-report__header">
                <h1>LAPORAN REKAPITULASI PRODUK — {{ shopName }}</h1>
                <p>SiswaMart — Tanggal Rekap: {{ formattedDate }}</p>
                <p style="font-size: 8pt; margin-top: 2px;">Pemilik: {{ sellerFullName }} (@{{ seller.username }}) | Kelas: {{ sellerClass }}</p>
            </div>

            <!-- TABEL I PRINT -->
            <div class="print-report__section" style="margin-bottom: 24px;">
                <h4 style="font-size: 9pt; font-weight: bold; margin-bottom: 6px; text-transform: uppercase; color: #000;">
                    I. INDIKATOR REKAPITULASI AUDIT (LIFETIME)
                </h4>
                <table class="print-report__table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">NO.</th>
                            <th style="width: 62%;">INDIKATOR REKAPITULASI</th>
                            <th style="width: 30%; text-align: right;">JUMLAH / TOTAL DATA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">1</td>
                            <td>Total Produk Diunggah (Lifetime)</td>
                            <td class="text-right">{{ stats.total_products || 0 }} Produk</td>
                        </tr>
                        <tr>
                            <td class="text-center">2</td>
                            <td>Produk Disetujui (Tayang di Katalog Website)</td>
                            <td class="text-right">{{ stats.approved_products || 0 }} Produk ({{ stats.compliance_rate || 0 }}%)</td>
                        </tr>
                        <tr>
                            <td class="text-center">3</td>
                            <td>Produk Pending / Ditolak Moderasi Admin</td>
                            <td class="text-right">{{ stats.pending_products || 0 }} Pending / {{ stats.rejected_products || 0 }} Ditolak</td>
                        </tr>
                        <tr>
                            <td class="text-center">4</td>
                            <td>Total Ulasan & Rata-Rata Rating Toko</td>
                            <td class="text-right">★ {{ stats.avg_shop_rating || '0.0' }} ({{ stats.total_reviews || 0 }} Ulasan)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TABEL II PRINT -->
            <div class="print-report__section" style="margin-top: 20px;">
                <h4 style="font-size: 9pt; font-weight: bold; margin-bottom: 6px; text-transform: uppercase; color: #000;">
                    II. RINCIAN KATALOG PRODUK & RATING (SELURUH DATA)
                </h4>
                <table class="print-report__table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">NO.</th>
                            <th style="width: 27%;">NAMA PRODUK</th>
                            <th style="width: 15%;">KATEGORI</th>
                            <th style="width: 17%;">TANGGAL UPLOAD</th>
                            <th style="width: 16%;">TGL MASUK KATALOG</th>
                            <th style="width: 10%;">RATING</th>
                            <th style="width: 10%;">TOTAL ULASAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, idx) in allProductsList" :key="'print-' + p.id_produk">
                            <td class="text-center">{{ idx + 1 }}</td>
                            <td>{{ p.nama_produk }}</td>
                            <td>{{ p.kategori?.nama_kategori || '-' }}</td>
                            <td class="text-center">{{ formatDate(p.created_at) }}</td>
                            <td class="text-center">{{ p.status_moderasi === 'disetujui' ? formatDate(p.updated_at) : (p.status_moderasi || '').toUpperCase() }}</td>
                            <td class="text-center">★ {{ p.ulasan_avg_rating ? Number(p.ulasan_avg_rating).toFixed(1) : '0.0' }}</td>
                            <td class="text-center">{{ p.ulasan_count || 0 }} Ulasan</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </AuthenticatedLayout>
</template>

<style>
.print-report {
    display: none;
}

@media print {
    @page {
        size: A4 portrait;
        margin: 12mm 10mm;
    }

    html, body, #app, main, div {
        background-color: #ffffff !important;
        background: #ffffff !important;
        color: #000000 !important;
        box-shadow: none !important;
    }

    nav, header, sidebar, footer, .screen-report, .print\:hidden {
        display: none !important;
    }

    .print-report {
        display: block !important;
        width: 100% !important;
        background-color: #ffffff !important;
        font-family: Arial, sans-serif !important;
    }

    .print-report__header {
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000000;
        padding-bottom: 8px;
    }

    .print-report__header h1 {
        font-size: 13pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 0 0 3px 0;
        color: #000000 !important;
    }

    .print-report__header p {
        font-size: 8.5pt;
        font-style: italic;
        margin: 0;
        color: #374151 !important;
    }

    .print-report__table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5pt;
        margin-top: 4px;
    }

    .print-report__table th, .print-report__table td {
        border: 1px solid #000000 !important;
        padding: 5px 6px;
        vertical-align: middle;
        color: #000000 !important;
    }

    .print-report__table th {
        font-weight: bold;
        text-transform: uppercase;
        background-color: #e5e7eb !important;
        color: #000000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-report__table td.text-center { text-align: center; }
    .print-report__table td.text-right { text-align: right; }
}
</style>
