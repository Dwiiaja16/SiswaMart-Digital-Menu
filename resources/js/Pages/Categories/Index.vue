<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    products: Array,
    categories: Array,
    priceStats: Object,
    filters: Object,
});

// Otomatis refresh data produk ketika kembali ke halaman Kategori
onMounted(() => {
    router.reload({ only: ["products"] });
});

const search = ref(props.filters.search || "");
const selectedCategory = ref(props.filters.category ? String(props.filters.category) : "");
const minPrice = ref(props.filters.min_price || "");
const maxPrice = ref(props.filters.max_price || "");
const selectedSort = ref(props.filters.sort || "latest");
const selectedStatus = ref(props.filters.status || "");
const showMobileFilters = ref(false);
const mobileMenuOpen = ref(false);
const sortDropdownOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

// Opsi Urutan Katalog
const sortOptions = [
    { value: "latest", label: "Paling Baru Ditambahkan" },
    { value: "views_desc", label: "Paling Populer (Banyak Dilihat)" },
    { value: "price_asc", label: "Harga: Termurah" },
    { value: "price_desc", label: "Harga: Termahal" },
    { value: "rating", label: "Rating Tertinggi" },
];

const selectedSortLabel = computed(() => {
    const found = sortOptions.find((opt) => opt.value === selectedSort.value);
    return found ? found.label : "Urutkan Menu";
});

const selectSort = (value) => {
    selectedSort.value = value;
    sortDropdownOpen.value = false;
    applyFilters();
};

// Preset Rentang Harga Pelajar (Bersih Tanpa Tanda > / <)
const pricePresets = [
    { label: "Semua Harga", min: "", max: "", type: "all" },
    { label: "Maksimal Rp 5.000", min: "", max: 5000, type: "hemat" },
    { label: "Rp 5.000 - Rp 10.000", min: 5000, max: 10000, type: "populer" },
    { label: "Rp 10.000 - Rp 20.000", min: 10000, max: 20000, type: "kenyang" },
    { label: "Di Atas Rp 20.000", min: 20000, max: "", type: "sultan" },
];

const activePreset = computed(() => {
    const min = minPrice.value === "" ? "" : Number(minPrice.value);
    const max = maxPrice.value === "" ? "" : Number(maxPrice.value);

    if (min === "" && max === "") return 0;
    if (min === "" && max === 5000) return 1;
    if (min === 5000 && max === 10000) return 2;
    if (min === 10000 && max === 20000) return 3;
    if (min === 20000 && max === "") return 4;
    return -1; // custom
});

const applyFilters = () => {
    router.get(
        route("catalog.categories"),
        {
            search: search.value,
            category: selectedCategory.value,
            min_price: minPrice.value,
            max_price: maxPrice.value,
            sort: selectedSort.value,
            status: selectedStatus.value,
        },
        { preserveState: true, replace: true }
    );
};

const selectCategory = (id) => {
    selectedCategory.value = selectedCategory.value === String(id) ? "" : String(id);
    applyFilters();
};

const selectPricePreset = (preset) => {
    minPrice.value = preset.min;
    maxPrice.value = preset.max;
    applyFilters();
};

const resetAllFilters = () => {
    search.value = "";
    selectedCategory.value = "";
    minPrice.value = "";
    maxPrice.value = "";
    selectedSort.value = "latest";
    selectedStatus.value = "";
    applyFilters();
};

const hasActiveFilters = computed(() => {
    return (
        search.value !== "" ||
        selectedCategory.value !== "" ||
        minPrice.value !== "" ||
        maxPrice.value !== "" ||
        selectedSort.value !== "latest" ||
        selectedStatus.value !== ""
    );
});

const currentCategoryName = computed(() => {
    if (!selectedCategory.value) return "Semua Menu";
    const found = props.categories.find((c) => String(c.id) === String(selectedCategory.value));
    return found ? found.name : "Kategori";
});
</script>

<template>
    <Head title="Kategori & Setting Harga - SiswaMart" />

    <div
        class="min-h-screen bg-[#F4F0E6] text-[#362415] font-sans selection:bg-[#F25C05] selection:text-[#F4F0E6] pb-20 sm:pb-24 overflow-x-hidden relative"
    >
        <!-- BACKGROUND BLOBS OATSIDE -->
        <div class="fixed inset-0 z-0 pointer-events-none opacity-40 sm:opacity-50 overflow-hidden">
            <div
                class="blob-shape bg-[#F25C05]/15 w-72 sm:w-[450px] h-72 sm:h-[450px] absolute -top-10 sm:-top-20 -left-10 sm:-left-20 animate-blob-spin"
            ></div>
            <div
                class="blob-shape bg-[#F9C22E]/20 sm:bg-[#F9C22E]/25 w-64 sm:w-[380px] h-64 sm:h-[380px] absolute top-1/3 -right-10 sm:-right-24 animate-blob-spin-reverse"
            ></div>
            <div
                class="blob-shape bg-[#70A9A1]/15 sm:bg-[#70A9A1]/20 w-80 sm:w-[500px] h-80 sm:h-[500px] absolute -bottom-20 sm:-bottom-32 left-1/4 animate-blob-spin"
            ></div>
        </div>

        <!-- HEADER STICKY RESPONSIVE DENGAN MOBILE DRAWER -->
        <header class="sticky top-0 z-50 px-3 sm:px-6 py-2.5 sm:py-3 transition-all duration-200">
            <div
                class="max-w-5xl mx-auto bg-[#F4F0E6]/95 sm:bg-[#F4F0E6]/90 backdrop-blur-md border-2 border-[#362415] rounded-2xl sm:rounded-full px-3.5 sm:px-6 py-2 sm:py-2.5 flex items-center justify-between gap-2 sm:gap-6 shadow-sm sm:shadow-[0_8px_20px_rgba(54,36,21,0.06)]"
            >
                <!-- BRAND LOGO -->
                <Link :href="route('catalog.index')" class="flex items-center gap-2 group shrink-0">
                    <div class="text-lg sm:text-2xl font-black tracking-tighter text-[#362415] group-hover:text-[#F25C05] transition-colors">
                        Siswa<span class="text-[#F25C05]">Mart</span>
                    </div>
                </Link>

                <!-- DESKTOP NAV MENU -->
                <nav class="hidden md:flex items-center gap-6">
                    <Link :href="route('catalog.index')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                        Beranda
                    </Link>
                    <span class="text-sm font-black text-[#F25C05] px-3 py-1 bg-[#F25C05]/10 rounded-full border border-[#F25C05]/20">
                        Kategori
                    </span>
                    <Link :href="route('how-to-order')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                        Cara Memesan
                    </Link>
                    <Link :href="route('about')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                        Tentang Kami
                    </Link>
                </nav>

                <!-- DESKTOP ACTION BUTTON -->
                <div class="hidden md:flex items-center gap-2 shrink-0">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="px-5 py-2 rounded-full bg-[#362415] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#F25C05] transition-colors duration-200"
                    >
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="px-5 py-2 rounded-full bg-[#F25C05] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#362415] transition-colors duration-200"
                    >
                        Login
                    </Link>
                </div>

                <!-- MOBILE HAMBURGER TOGGLE -->
                <div class="flex md:hidden items-center gap-2">
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="px-3 py-1.5 rounded-full bg-[#362415] text-[#F4F0E6] text-xs font-bold">
                        Dashboard
                    </Link>
                    <Link v-else :href="route('login')" class="px-3 py-1.5 rounded-full bg-[#F25C05] text-[#F4F0E6] text-xs font-bold">
                        Login
                    </Link>
                    <button
                        @click="toggleMobileMenu"
                        type="button"
                        class="p-2 rounded-xl border-2 border-[#362415] bg-white text-[#362415] hover:bg-[#F9C22E] transition-colors focus:outline-none"
                        aria-label="Menu"
                    >
                        <svg v-if="!mobileMenuOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- MOBILE SLIDE-DOWN DRAWER -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <div v-if="mobileMenuOpen" class="md:hidden mt-2 bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl p-4 shadow-md space-y-2.5 max-w-5xl mx-auto">
                    <div class="flex flex-col space-y-1 text-sm font-bold text-[#362415]">
                        <Link :href="route('catalog.index')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                            Beranda
                        </Link>
                        <span class="px-3 py-2 bg-[#F25C05]/15 text-[#F25C05] font-black rounded-xl">
                            Kategori & Setting Harga
                        </span>
                        <Link :href="route('how-to-order')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                            Cara Memesan
                        </Link>
                        <Link :href="route('about')" @click="mobileMenuOpen = false" class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors">
                            Tentang Kami
                        </Link>
                    </div>
                </div>
            </transition>
        </header>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="relative z-10 max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 pt-4 sm:pt-10 space-y-6 sm:space-y-8">
            <!-- HERO HEADER BANNER -->
            <section class="bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl sm:rounded-[36px] p-5 sm:p-10 shadow-[3px_3px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] relative overflow-hidden">
                <div class="max-w-3xl space-y-3 sm:space-y-4">
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 bg-[#F9C22E] border-2 border-[#362415] text-[#362415] px-3 sm:px-3.5 py-1 rounded-full text-[10px] sm:text-xs font-black uppercase tracking-widest rotate-[-1.5deg] shadow-sm">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Eksplorasi Menu & Setting Harga</span>
                    </div>

                    <h1 class="text-2xl sm:text-5xl lg:text-6xl font-black text-[#362415] tracking-tight leading-tight">
                        Pilih Kategori & <br class="hidden sm:inline" />
                        <span class="text-[#F25C05]">Atur Sesuai Kantongmu!</span>
                    </h1>

                    <p class="text-[#634C3C] text-xs sm:text-sm md:text-base font-semibold leading-relaxed max-w-xl">
                        Filter jajanan lezat dan karya siswa SMKN 11 Bandung berdasarkan kategori favorit dan rentang harga yang pas buat kamu.
                    </p>
                </div>

                <!-- Decorative Badge (Desktop) -->
                <div class="hidden md:flex absolute right-8 top-1/2 -translate-y-1/2 flex-col items-center justify-center w-32 h-32 rounded-full bg-[#70A9A1]/20 border-2 border-dashed border-[#362415] text-center p-2 rotate-6">
                    <span class="text-2xl font-black text-[#362415]">{{ products.length }}</span>
                    <span class="text-[9px] font-black uppercase tracking-wider text-[#362415]">Menu Tersedia</span>
                </div>
            </section>

            <!-- HORIZONTAL CATEGORY PILLS -->
            <section class="space-y-2.5 sm:space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5 text-[11px] sm:text-xs font-black uppercase tracking-wider text-[#362415]">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                        <span>Kategori Menu</span>
                    </div>
                    <span class="text-[11px] sm:text-xs font-bold text-[#634C3C]">
                        Aktif: <strong class="text-[#F25C05]">{{ currentCategoryName }}</strong>
                    </span>
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar">
                    <button
                        @click="selectCategory('')"
                        :class="
                            !selectedCategory
                                ? 'bg-[#362415] text-[#F4F0E6] shadow-sm scale-105'
                                : 'bg-white text-[#362415] hover:bg-[#F9C22E] border-2 border-[#362415]'
                        "
                        class="px-4 sm:px-5 py-2 rounded-full font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap shrink-0 flex items-center gap-1.5"
                    >
                        <span>Semua</span>
                    </button>

                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        @click="selectCategory(cat.id)"
                        :class="
                            selectedCategory === String(cat.id)
                                ? 'bg-[#362415] text-[#F4F0E6] shadow-sm scale-105'
                                : 'bg-white text-[#362415] hover:bg-[#F9C22E] border-2 border-[#362415]'
                        "
                        class="px-4 sm:px-5 py-2 rounded-full font-black text-xs uppercase tracking-wider transition-all whitespace-nowrap shrink-0 flex items-center gap-1.5"
                    >
                        <span>{{ cat.name }}</span>
                        <span
                            v-if="cat.products_count !== undefined"
                            :class="selectedCategory === String(cat.id) ? 'bg-[#F25C05] text-white' : 'bg-[#362415]/10 text-[#362415]'"
                            class="text-[9px] sm:text-[10px] px-1.5 py-0.2 rounded-full font-extrabold"
                        >
                            {{ cat.products_count }}
                        </span>
                    </button>
                </div>
            </section>

            <!-- MOBILE TOGGLE BUTTON UNTUK FILTER SIDEBAR -->
            <div class="block lg:hidden">
                <button
                    @click="showMobileFilters = !showMobileFilters"
                    type="button"
                    class="w-full py-2.5 px-4 bg-white border-2 border-[#362415] rounded-2xl font-black text-xs uppercase tracking-wider flex items-center justify-between shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span>{{ showMobileFilters ? 'Sembunyikan Panel Filter' : 'Atur Pilihan Pas Kantong & Filter' }}</span>
                    </div>
                    <span class="w-6 h-6 rounded-full bg-[#F9C22E] border border-[#362415] flex items-center justify-center text-[10px]">
                        {{ showMobileFilters ? '▲' : '▼' }}
                    </span>
                </button>
            </div>

            <!-- 2-COLUMN MAIN CONTENT: STICKY FILTER SIDEBAR + PRODUCT FEED -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">
                
                <!-- LEFT COLUMN: FILTER SIDEBAR -->
                <aside :class="showMobileFilters ? 'block' : 'hidden lg:block'" class="lg:col-span-4 space-y-4 sm:space-y-6">
                    <div class="bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl sm:rounded-[32px] p-4 sm:p-6 shadow-[3px_3px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] space-y-4 sm:space-y-5">
                        <!-- SIDEBAR HEADER -->
                        <div class="flex items-center justify-between pb-3 border-b-2 border-[#362415]/10">
                            <div class="flex items-center gap-2 font-black text-sm sm:text-base text-[#362415]">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                                <span>Filter & Pilihan Harga</span>
                            </div>

                            <button
                                v-if="hasActiveFilters"
                                @click="resetAllFilters"
                                class="text-[10px] sm:text-[11px] font-black text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full transition-colors flex items-center gap-1"
                            >
                                <span>Reset</span>
                            </button>
                        </div>

                        <!-- 1. PILIHAN PAS KANTONG (BAHASA INDONESIA & TANPA TANDA > / <) -->
                        <div class="space-y-2.5">
                            <label class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-[#362415] flex items-center gap-1.5">
                                <span>Pilihan Pas Kantong</span>
                            </label>

                            <div class="grid grid-cols-1 gap-1.5">
                                <button
                                    v-for="(preset, idx) in pricePresets"
                                    :key="idx"
                                    @click="selectPricePreset(preset)"
                                    :class="
                                        activePreset === idx
                                            ? 'bg-[#F25C05] text-white border-2 border-[#362415] shadow-sm font-black'
                                            : 'bg-white text-[#362415] border border-[#362415]/40 hover:border-[#362415] hover:bg-[#FFF9E6] font-bold'
                                    "
                                    class="w-full px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition-all"
                                >
                                    <span>{{ preset.label }}</span>
                                    <svg v-if="activePreset === idx" class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- 2. CUSTOM RENTANG HARGA -->
                        <div class="space-y-2.5 pt-3 border-t-2 border-[#362415]/10">
                            <label class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-[#362415] flex items-center justify-between">
                                <span>Atur Harga Sendiri</span>
                                <span class="text-[9px] sm:text-[10px] font-bold text-[#634C3C]">Maks. Rp {{ Number(priceStats?.max || 50000).toLocaleString('id-ID') }}</span>
                            </label>

                            <div class="space-y-2">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-[#362415]/50">Rp</span>
                                    <input
                                        v-model="minPrice"
                                        @keyup.enter="applyFilters"
                                        type="number"
                                        placeholder="Min (0)"
                                        class="w-full pl-8 pr-3 py-1.5 sm:py-2 bg-white border-2 border-[#362415] rounded-xl text-xs font-bold text-[#362415] placeholder-[#362415]/40 focus:ring-0 focus:border-[#F25C05]"
                                    />
                                </div>

                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-[#362415]/50">Rp</span>
                                    <input
                                        v-model="maxPrice"
                                        @keyup.enter="applyFilters"
                                        type="number"
                                        placeholder="Max (misal: 15000)"
                                        class="w-full pl-8 pr-3 py-1.5 sm:py-2 bg-white border-2 border-[#362415] rounded-xl text-xs font-bold text-[#362415] placeholder-[#362415]/40 focus:ring-0 focus:border-[#F25C05]"
                                    />
                                </div>

                                <button
                                    @click="applyFilters"
                                    class="w-full py-2 px-3 bg-[#362415] hover:bg-[#F25C05] text-[#F4F0E6] rounded-xl font-black text-xs uppercase tracking-wider border-2 border-[#362415] transition-colors"
                                >
                                    Terapkan Harga
                                </button>
                            </div>
                        </div>

                        <!-- 3. STATUS STOK -->
                        <div class="space-y-2 pt-3 border-t-2 border-[#362415]/10">
                            <label class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-[#362415]">
                                Status Stok
                            </label>

                            <div class="grid grid-cols-3 gap-1">
                                <button
                                    @click="selectedStatus = ''; applyFilters();"
                                    :class="selectedStatus === '' ? 'bg-[#362415] text-white' : 'bg-white text-[#362415] border border-[#362415]/30'"
                                    class="py-1.5 rounded-lg text-[10px] font-black transition-colors"
                                >
                                    Semua
                                </button>
                                <button
                                    @click="selectedStatus = 'ready'; applyFilters();"
                                    :class="selectedStatus === 'ready' ? 'bg-emerald-600 text-white' : 'bg-white text-emerald-800 border border-emerald-300'"
                                    class="py-1.5 rounded-lg text-[10px] font-black transition-colors"
                                >
                                    Ready
                                </button>
                                <button
                                    @click="selectedStatus = 'pre_order'; applyFilters();"
                                    :class="selectedStatus === 'pre_order' ? 'bg-amber-600 text-white' : 'bg-white text-amber-800 border border-amber-300'"
                                    class="py-1.5 rounded-lg text-[10px] font-black transition-colors"
                                >
                                    PO
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- RIGHT COLUMN: SEARCH, CUSTOM SORT DROPDOWN & PRODUCT FEED -->
                <section class="lg:col-span-8 space-y-4 sm:space-y-6">
                    <!-- SEARCH & CUSTOM SORT BAR -->
                    <div class="bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl sm:rounded-[28px] p-3 sm:p-4 shadow-sm space-y-2.5">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3">
                            <!-- Search Input -->
                            <div class="sm:col-span-6">
                                <div class="relative">
                                    <input
                                        v-model="search"
                                        @keyup.enter="applyFilters"
                                        type="text"
                                        placeholder="Cari jajanan, minuman..."
                                        class="w-full pl-8 pr-3 py-2 bg-white border-2 border-[#362415] rounded-xl text-xs sm:text-sm font-bold text-[#362415] placeholder-[#362415]/40 focus:ring-0 focus:border-[#F25C05]"
                                    />
                                    <svg
                                        class="w-3.5 h-3.5 sm:w-4 sm:h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-[#362415]"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- CUSTOM SORT DROPDOWN (Ganti bawaan <select> yang kaku) -->
                            <div class="sm:col-span-6 relative">
                                <button
                                    @click="sortDropdownOpen = !sortDropdownOpen"
                                    type="button"
                                    class="w-full py-2 px-3 bg-white border-2 border-[#362415] rounded-xl text-xs font-bold text-[#362415] flex items-center justify-between gap-2 shadow-xs hover:border-[#F25C05] transition-colors"
                                >
                                    <div class="flex items-center gap-1.5 truncate">
                                        <svg class="w-3.5 h-3.5 text-[#F25C05] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                                        </svg>
                                        <span class="truncate">{{ selectedSortLabel }}</span>
                                    </div>
                                    <svg
                                        class="w-4 h-4 text-[#362415] transition-transform duration-200 shrink-0"
                                        :class="{ 'rotate-180': sortDropdownOpen }"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Backdrop Click Away -->
                                <div v-if="sortDropdownOpen" @click="sortDropdownOpen = false" class="fixed inset-0 z-20"></div>

                                <!-- Custom Dropdown Menu -->
                                <transition
                                    enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="transform scale-95 opacity-0"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-75 ease-in"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0"
                                >
                                    <div
                                        v-if="sortDropdownOpen"
                                        class="absolute right-0 left-0 mt-1.5 bg-white border-2 border-[#362415] rounded-xl shadow-[4px_4px_0px_#362415] py-1.5 z-30 space-y-0.5 overflow-hidden"
                                    >
                                        <button
                                            v-for="option in sortOptions"
                                            :key="option.value"
                                            @click="selectSort(option.value)"
                                            :class="
                                                selectedSort === option.value
                                                    ? 'bg-[#F25C05]/15 text-[#F25C05] font-black'
                                                    : 'text-[#362415] hover:bg-[#FFF9E6] font-bold'
                                            "
                                            class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between transition-colors"
                                        >
                                            <span>{{ option.label }}</span>
                                            <svg v-if="selectedSort === option.value" class="w-3.5 h-3.5 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </transition>
                            </div>
                        </div>

                        <!-- Active Filter Chips -->
                        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-1.5 pt-1.5 border-t border-[#362415]/10 text-[10px] sm:text-xs">
                            <span class="font-bold text-[#634C3C]">Filter:</span>

                            <span
                                v-if="selectedCategory"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#F9C22E] border border-[#362415] font-black text-[#362415]"
                            >
                                {{ currentCategoryName }}
                                <button @click="selectCategory('')" class="hover:text-rose-600 font-bold ml-0.5">✕</button>
                            </span>

                            <span
                                v-if="minPrice || maxPrice"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-[#70A9A1]/30 border border-[#362415] font-black text-[#362415]"
                            >
                                {{ minPrice ? 'Rp ' + Number(minPrice).toLocaleString('id-ID') : 'Rp 0' }} -
                                {{ maxPrice ? 'Rp ' + Number(maxPrice).toLocaleString('id-ID') : 'Max' }}
                                <button @click="minPrice = ''; maxPrice = ''; applyFilters();" class="hover:text-rose-600 font-bold ml-0.5">✕</button>
                            </span>

                            <span
                                v-if="search"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-[#362415] font-black text-[#362415]"
                            >
                                "{{ search }}"
                                <button @click="search = ''; applyFilters();" class="hover:text-rose-600 font-bold ml-0.5">✕</button>
                            </span>

                            <span
                                v-if="selectedStatus"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-[#362415] font-black text-[#362415]"
                            >
                                {{ selectedStatus === 'ready' ? 'Ready' : 'PO' }}
                                <button @click="selectedStatus = ''; applyFilters();" class="hover:text-rose-600 font-bold ml-0.5">✕</button>
                            </span>
                        </div>
                    </div>

                    <!-- PRODUCTS GRID FEED (2 KOLOM DI MOBILE) -->
                    <div
                        v-if="products && products.length > 0"
                        class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-5"
                    >
                        <div
                            v-for="product in products"
                            :key="product.id"
                            class="bg-white rounded-2xl border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[5px_5px_0px_#362415] hover:-translate-y-0.5 transition-all duration-200 flex flex-col group overflow-hidden"
                        >
                            <!-- AREA GAMBAR RASIO 4/5 DENGAN LAZY LOADING -->
                            <Link
                                :href="route('products.show', product.id)"
                                class="block relative w-full aspect-[4/5] bg-stone-100 overflow-hidden"
                            >
                                <img
                                    v-if="product.image"
                                    :src="product.image"
                                    :alt="product.name"
                                    loading="lazy"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 ease-out"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex flex-col items-center justify-center text-stone-400 font-bold text-[9px] sm:text-[10px] uppercase gap-1 bg-[#fffaf3]"
                                >
                                    <svg
                                        class="w-5 h-5 sm:w-6 sm:h-6 opacity-40"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    Tanpa Foto
                                </div>

                                <!-- BADGE NAMA TOKO (MIRING ORANYE) -->
                                <div
                                    class="absolute bottom-0 left-0 bg-[#ea580c]/95 text-white pl-2 sm:pl-3 pr-4 sm:pr-6 py-1 flex items-center gap-1 z-10 shadow-sm"
                                    style="clip-path: polygon(0 0, 100% 0, 84% 100%, 0% 100%);"
                                >
                                    <span class="font-extrabold text-[9px] sm:text-[11px] tracking-wide truncate max-w-[80px] sm:max-w-[110px]">
                                        {{ product.shop?.name || "Lapak Siswa" }}
                                    </span>
                                </div>

                                <!-- Badge Status Buka/Tutup -->
                                <div class="absolute top-2 right-2 z-10">
                                    <span
                                        :class="product.shop?.is_open ? 'bg-emerald-400 text-stone-900' : 'bg-rose-400 text-stone-900'"
                                        class="px-1.5 sm:px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider shadow-sm flex items-center gap-1 bg-white/95 border border-stone-200/50"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="product.shop?.is_open ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500'"
                                        ></span>
                                        <span class="hidden xs:inline sm:inline">{{ product.shop?.is_open ? "Buka" : "Tutup" }}</span>
                                    </span>
                                </div>
                            </Link>

                            <!-- INFORMASI PRODUK RINGKAS -->
                            <div class="p-2.5 sm:p-3.5 flex flex-col flex-1 bg-white">
                                <!-- Judul -->
                                <Link :href="route('products.show', product.id)" class="block mb-1 sm:mb-2">
                                    <h3 class="font-black text-[#362415] text-xs sm:text-base leading-snug group-hover:text-[#F25C05] transition-colors line-clamp-2">
                                        {{ product.name }}
                                    </h3>
                                </Link>

                                <!-- Kategori & Status Stok -->
                                <div class="flex items-center gap-1 text-[10px] sm:text-[11px] text-[#634C3C] font-semibold mb-2 sm:mb-3">
                                    <span v-for="cat in product.categories?.slice(0, 1)" :key="cat.id" class="truncate">{{ cat.name }}</span>
                                    <span v-if="product.categories?.length && product.stock_status" class="text-stone-300">•</span>
                                    <span :class="{
                                        'text-emerald-700 font-bold': product.stock_status === 'ready',
                                        'text-amber-700 font-bold': product.stock_status === 'pre_order',
                                        'text-rose-700 font-bold': product.stock_status === 'out_of_stock'
                                    }" class="shrink-0">
                                        {{ product.stock_status === 'ready' ? 'Ready' : (product.stock_status === 'pre_order' ? 'PO' : 'Habis') }}
                                    </span>
                                </div>

                                <!-- Harga, Total Views & Rating Footer -->
                                <div class="mt-auto pt-2 sm:pt-3 border-t border-[#362415]/10 flex items-center justify-between gap-1">
                                    <!-- Harga Produk -->
                                    <span class="text-[#F25C05] font-black text-xs sm:text-base tracking-tight truncate">
                                        Rp {{ Number(product.price).toLocaleString("id-ID") }}
                                    </span>

                                    <div class="flex items-center gap-1 shrink-0">
                                        <!-- Badge Total Views -->
                                        <div class="flex items-center gap-0.5 bg-stone-100 border border-stone-300 px-1.5 py-0.5 rounded-lg text-[9px] sm:text-[10px] font-extrabold text-[#362415]" title="Total Dilihat">
                                            <svg class="w-3 h-3 text-stone-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>{{ Number(product.views_count || 0).toLocaleString("id-ID") }}</span>
                                        </div>

                                        <!-- Badge Rating -->
                                        <div class="flex items-center gap-0.5 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-lg text-[9px] sm:text-[10px] font-black text-[#362415]">
                                            <svg class="w-3 h-3 text-amber-500 fill-amber-400" viewBox="0 0 24 24">
                                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                            </svg>
                                            <span>{{ product.avg_rating || "0.0" }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EMPTY STATE -->
                    <div
                        v-else
                        class="text-center py-12 sm:py-16 bg-[#FCFAF5] rounded-3xl border-2 border-dashed border-[#F9C22E] px-4 space-y-3"
                    >
                        <div class="w-12 h-12 rounded-full bg-[#F9C22E]/30 border-2 border-[#362415] flex items-center justify-center text-[#362415] mx-auto">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-2xl font-black text-[#362415]">
                            Menu Belum Ditemukan
                        </h3>
                        <p class="text-[#634C3C] font-semibold text-xs sm:text-sm max-w-sm mx-auto">
                            Coba sesuaikan lagi pilihan pas kantongmu atau pilih kategori lainnya ya.
                        </p>
                        <button
                            @click="resetAllFilters"
                            class="px-5 py-2.5 bg-[#F25C05] text-[#FCFAF5] rounded-full font-black text-xs uppercase tracking-wider hover:bg-[#362415] border-2 border-[#362415] transition-all flex items-center gap-1.5 mx-auto"
                        >
                            <span>Reset Semua Filter</span>
                        </button>
                    </div>
                </section>
            </div>

            <!-- FOOTER -->
            <footer class="text-center pt-6 sm:pt-8 pb-4 text-[11px] sm:text-xs font-bold text-[#947d6b] space-y-1">
                <p>SISWAMART • SMKN 11 BANDUNG</p>
                <p class="text-[10px] sm:text-[11px] font-semibold text-[#947d6b]/80">
                    Platform Digital Wirausaha Siswa Kreatif & Berdaya.
                </p>
            </footer>
        </main>
    </div>
</template>

<style scoped>
.no-scrollbar {
    scrollbar-width: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.blob-shape {
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    filter: blur(40px);
}

@keyframes blob-spin {
    0% {
        transform: rotate(0deg) scale(1);
        border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    }
    50% {
        transform: rotate(180deg) scale(1.1);
        border-radius: 60% 40% 30% 70% / 50% 60% 40% 50%;
    }
    100% {
        transform: rotate(360deg) scale(1);
        border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    }
}

@keyframes blob-spin-reverse {
    0% {
        transform: rotate(360deg) scale(1);
        border-radius: 50% 50% 30% 70% / 60% 40% 60% 40%;
    }
    50% {
        transform: rotate(180deg) scale(1.2);
        border-radius: 30% 70% 70% 30% / 40% 60% 40% 60%;
    }
    100% {
        transform: rotate(0deg) scale(1);
        border-radius: 50% 50% 30% 70% / 60% 40% 60% 40%;
    }
}

.animate-blob-spin {
    animation: blob-spin 16s infinite linear;
}

.animate-blob-spin-reverse {
    animation: blob-spin-reverse 20s infinite linear;
}
</style>