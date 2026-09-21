<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

const props = defineProps({
    products: Array,
    categories: Array,
    filters: Object,
});

// Otomatis refresh data produk ketika kembali ke Beranda/Dashboard
onMounted(() => {
    router.reload({ only: ["products"] });
});

const search = ref(props.filters?.search || "");
const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const handleSearch = () => {
    if (search.value.trim()) {
        router.get(route("catalog.categories"), { search: search.value.trim() });
    } else {
        router.get(route("catalog.index"));
    }
};
</script>

<template>
    <Head title="Katalog Digital SiswaMart - SMKN 11 Bandung" />

    <div class="min-h-screen bg-[#F4F0E6] text-[#362415] font-sans selection:bg-[#F25C05] selection:text-[#F4F0E6] pb-20 sm:pb-24 overflow-x-hidden relative">
        
        <!-- BACKGROUND BLOBS OATSIDE STYLE -->
        <div class="fixed inset-0 z-0 pointer-events-none opacity-40 sm:opacity-60 overflow-hidden">
            <div class="blob-shape bg-[#F25C05]/15 sm:bg-[#F25C05]/20 w-72 sm:w-[450px] h-72 sm:h-[450px] absolute -top-10 sm:-top-20 -left-10 sm:-left-20 animate-blob-spin"></div>
            <div class="blob-shape bg-[#F9C22E]/20 sm:bg-[#F9C22E]/25 w-64 sm:w-[400px] h-64 sm:h-[400px] absolute top-1/3 -right-10 sm:-right-20 animate-blob-spin-reverse"></div>
            <div class="blob-shape bg-[#70A9A1]/15 sm:bg-[#70A9A1]/20 w-80 sm:w-[520px] h-80 sm:h-[520px] absolute -bottom-20 sm:-bottom-36 left-1/4 animate-blob-spin"></div>
        </div>

        <!-- HEADER STICKY RESPONSIVE DENGAN MOBILE DRAWER -->
        <header class="sticky top-0 z-50 px-3 sm:px-6 py-2.5 sm:py-3 transition-all duration-200">
            <div class="max-w-5xl mx-auto bg-[#F4F0E6]/95 sm:bg-[#F4F0E6]/90 backdrop-blur-md border-2 border-[#362415] rounded-2xl sm:rounded-full px-3.5 sm:px-6 py-2 sm:py-2.5 flex items-center justify-between gap-2 sm:gap-6 shadow-sm sm:shadow-[0_8px_20px_rgba(54,36,21,0.06)]">
                <!-- LOGO -->
                <Link :href="route('catalog.index')" class="flex items-center gap-2 group shrink-0">
                    <div class="text-lg sm:text-2xl font-black tracking-tighter text-[#362415] group-hover:text-[#F25C05] transition-colors">
                        Siswa<span class="text-[#F25C05]">Mart</span>
                    </div>
                </Link>

                <!-- DESKTOP NAV MENU -->
                <nav class="hidden md:flex items-center gap-6">
                    <span class="text-sm font-black text-[#F25C05] px-3 py-1 bg-[#F25C05]/10 rounded-full border border-[#F25C05]/20">
                        Beranda
                    </span>
                    <Link :href="route('catalog.categories')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                        Kategori
                    </Link>
                    <Link :href="route('how-to-order')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                        Cara Memesan
                    </Link>
                    <Link :href="route('about')" class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors">
                        Tentang Kami
                    </Link>
                </nav>

                <!-- DESKTOP ACTION BUTTON -->
                <div class="hidden md:flex items-center gap-2 shrink-0">
                    <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="px-5 py-2 rounded-full bg-[#362415] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#F25C05] transition-colors duration-200">
                        Dashboard
                    </Link>
                    <Link v-else :href="route('login')" class="px-5 py-2 rounded-full bg-[#F25C05] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#362415] transition-colors duration-200">
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
                <div
                    v-if="mobileMenuOpen"
                    class="md:hidden mt-2 bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl p-4 shadow-md space-y-2.5 max-w-5xl mx-auto"
                >
                    <div class="flex flex-col space-y-1 text-sm font-bold text-[#362415]">
                        <span class="px-3 py-2 bg-[#F25C05]/15 text-[#F25C05] font-black rounded-xl">
                            Beranda
                        </span>
                        <Link
                            :href="route('catalog.categories')"
                            @click="mobileMenuOpen = false"
                            class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors"
                        >
                            Kategori & Budget
                        </Link>
                        <Link
                            :href="route('how-to-order')"
                            @click="mobileMenuOpen = false"
                            class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors"
                        >
                            Cara Memesan
                        </Link>
                        <Link
                            :href="route('about')"
                            @click="mobileMenuOpen = false"
                            class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors"
                        >
                            Tentang Kami
                        </Link>
                    </div>
                </div>
            </transition>
        </header>

        <!-- HERO SECTION (RESPONSIF MOBILE & DESKTOP) -->
        <section class="relative pt-6 sm:pt-16 pb-10 sm:pb-16 px-4 sm:px-6 lg:px-8 flex flex-col items-center justify-center text-center">
            <div class="relative z-10 max-w-4xl mx-auto space-y-4 sm:space-y-6 flex flex-col items-center w-full">
                <!-- BADGE STIKER -->
                <div class="inline-flex items-center gap-1.5 sm:gap-2 bg-[#F9C22E] border-2 border-[#362415] text-[#362415] px-3 sm:px-4 py-1 sm:py-1.5 rounded-full text-[10px] sm:text-xs font-black uppercase tracking-widest rotate-[-2deg] shadow-[2px_2px_0px_#362415]">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#362415]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                    <span>SMKN 11 BANDUNG PUNYA!</span>
                </div>

                <!-- MAIN HEADLINE -->
                <h1 class="text-3xl sm:text-6xl lg:text-7xl font-black text-[#362415] tracking-tight leading-[1.05] sm:leading-[0.95]">
                    Karya Rasa <br/>
                    <span class="text-[#F25C05]">Luar Biasa.</span>
                </h1>

                <!-- SUBTITLE -->
                <p class="text-[#634C3C] text-xs sm:text-base md:text-lg font-semibold sm:font-bold max-w-xl mx-auto leading-relaxed px-2">
                    Pesan makanan, minuman dingin, dan karya kreatif langsung dari siswa SMKN 11 Bandung. Enak, praktis, dan dukung jiwa wirausaha!
                </p>

                <!-- SEARCH CAPSULE -->
                <div class="w-full max-w-xl pt-1 sm:pt-2 px-1">
                    <div class="flex items-center bg-[#FCFAF5] border-2 border-[#362415] rounded-full p-1 sm:p-1.5 shadow-[3px_3px_0px_#362415] focus-within:border-[#F25C05] transition-all">
                        <div class="pl-3 sm:pl-4 text-[#362415]">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            @keyup.enter="handleSearch"
                            type="text"
                            placeholder="Cari jajanan kesukaanmu..."
                            class="w-full text-[#362415] font-bold border-none focus:ring-0 px-2 sm:px-4 text-xs sm:text-base bg-transparent placeholder-[#362415]/40"
                        />
                        <button
                            @click="handleSearch"
                            class="bg-[#362415] hover:bg-[#F25C05] text-[#F4F0E6] px-4 sm:px-8 py-2.5 sm:py-3.5 rounded-full font-black text-[11px] sm:text-xs uppercase tracking-wider transition-colors shrink-0"
                        >
                            Cari
                        </button>
                    </div>
                </div>

                <!-- VALUE PILLS TICKER -->
                <div class="flex flex-wrap items-center justify-center gap-1.5 sm:gap-2 pt-1 text-[11px] sm:text-xs font-black text-[#362415]">
                    <div class="flex items-center gap-1.5 bg-white border border-[#362415] sm:border-2 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full shadow-sm">
                        <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Direct WhatsApp</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-white border border-[#362415] sm:border-2 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full shadow-sm">
                        <svg class="w-3.5 h-3.5 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span>COD di Sekolah</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-white border border-[#362415] sm:border-2 px-2.5 sm:px-3.5 py-1 sm:py-1.5 rounded-full shadow-sm">
                        <svg class="w-3.5 h-3.5 text-[#70A9A1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>100% Siswa</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- MAIN FEED CONTENT CONTAINER -->
        <main class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 space-y-10 sm:space-y-16 relative z-10">
            
            <!-- SECTION 1: SHORTCUT BENTO KATEGORI POPULER -->
            <section class="space-y-4 sm:space-y-6">
                <div class="flex items-end justify-between gap-2 border-b-2 border-[#362415]/15 pb-3 sm:pb-4">
                    <div>
                        <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-[#F25C05] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                            <span>Jelajahi Menu</span>
                        </div>
                        <h2 class="text-lg sm:text-2xl md:text-3xl font-black text-[#362415] tracking-tight">
                            Kategori Terpopuler
                        </h2>
                    </div>

                    <Link
                        :href="route('catalog.categories')"
                        class="text-[11px] sm:text-xs font-black uppercase tracking-wider bg-[#F9C22E] text-[#362415] px-3 sm:px-4 py-1.5 sm:py-2 rounded-full border-2 border-[#362415] shadow-sm hover:bg-[#362415] hover:text-[#F4F0E6] transition-colors shrink-0"
                    >
                        Semua Kategori
                    </Link>
                </div>

                <!-- BENTO SHORTCUTS GRID (2 KOLOM DI MOBILE) -->
                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4">
                    <Link
                        v-for="cat in (categories?.slice(0, 4) || [])"
                        :key="cat.id"
                        :href="route('catalog.categories', { category: cat.id })"
                        class="bg-[#FCFAF5] p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[4px_4px_0px_#362415] hover:-translate-y-0.5 transition-all flex flex-col justify-between group"
                    >
                        <div class="flex items-center justify-between mb-2 sm:mb-4">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-white border border-[#362415] sm:border-2 flex items-center justify-center text-[#362415] shadow-sm">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-black text-sm sm:text-base md:text-lg text-[#362415] leading-snug group-hover:text-[#F25C05] transition-colors truncate">
                                {{ cat.name }}
                            </h3>
                            <p class="text-[10px] sm:text-[11px] font-bold text-[#634C3C] mt-0.5 sm:mt-1 truncate">
                                Lihat menu & harga
                            </p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- SECTION 2: HIGHLIGHT SAJIAN PILIHAN SISWA -->
            <section class="space-y-4 sm:space-y-6">
                <div class="flex items-end justify-between gap-2 border-b-2 border-[#362415]/15 pb-3 sm:pb-4">
                    <div>
                        <div class="text-[10px] sm:text-[11px] font-black uppercase tracking-wider text-[#70A9A1] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                            </svg>
                            <span>Rekomendasi Hari Ini</span>
                        </div>
                        <h2 class="text-lg sm:text-2xl md:text-3xl font-black text-[#362415] tracking-tight">
                            Sajian Segar & Hits
                        </h2>
                    </div>

                    <Link
                        :href="route('catalog.categories')"
                        class="text-[11px] sm:text-xs font-black text-[#F25C05] hover:text-[#362415] transition-colors shrink-0"
                    >
                        Filter Budget
                    </Link>
                </div>

                <!-- GRID PRODUK (2 KOLOM DI MOBILE, 3-4 KOLOM DI DESKTOP) -->
                <div
                    v-if="products && products.length > 0"
                    class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-5"
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
                                <svg class="w-5 h-5 sm:w-6 sm:h-6 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
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

                            <!-- BADGE STATUS BUKA/TUTUP -->
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
                            <!-- Judul Produk -->
                            <Link :href="route('products.show', product.id)" class="block mb-1.5 sm:mb-2">
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
                                <span class="text-[#F25C05] font-black text-xs sm:text-base tracking-tight truncate">
                                    Rp {{ Number(product.price).toLocaleString("id-ID") }}
                                </span>

                                <div class="flex items-center gap-1 shrink-0">
                                    <!-- Badge Total Views (Baru) -->
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
                    class="text-center py-12 sm:py-16 bg-[#FCFAF5] rounded-3xl sm:rounded-[36px] border-2 border-dashed border-[#F9C22E] px-4 space-y-3"
                >
                    <div class="w-12 h-12 rounded-full bg-[#F9C22E]/30 border-2 border-[#362415] flex items-center justify-center text-[#362415] mx-auto">
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <h3 class="text-lg sm:text-2xl font-black text-[#362415]">
                        Belum Ada Menu Aktif
                    </h3>
                    <p class="text-[#634C3C] font-semibold text-xs sm:text-sm max-w-sm mx-auto">
                        Siswa sedang menyiapkan sajian terbaiknya. Cek lagi nanti atau buka halaman kategori!
                    </p>
                </div>
            </section>

            <!-- SECTION 3: 3 VALUE CARDS (KENAPA JAJAN DI SISWAMART) -->
            <section class="bg-[#FCFAF5] border-2 border-[#362415] rounded-3xl sm:rounded-[36px] p-5 sm:p-10 shadow-[4px_4px_0px_#362415] sm:shadow-[8px_8px_0px_#362415] space-y-6">
                <div class="text-center space-y-1.5 max-w-md mx-auto">
                    <span class="inline-block bg-[#F25C05] text-[#FCFAF5] px-3 py-0.5 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-widest">
                        Kenapa SiswaMart?
                    </span>
                    <h3 class="text-xl sm:text-3xl font-black text-[#362415] tracking-tight">
                        Jajan Praktis, Dukung Teman
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-5">
                    <div class="bg-white p-4 sm:p-6 rounded-2xl border-2 border-[#362415] shadow-sm space-y-2">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#F9C22E] border-2 border-[#362415] flex items-center justify-center text-[#362415]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="font-black text-sm sm:text-base text-[#362415]">Tanpa Biaya Tambahan</h4>
                        <p class="text-xs text-[#634C3C] font-semibold leading-relaxed">
                            Harga murni dari siswa tanpa potongan aplikasi, jadi lebih hemat untuk saku pelajar.
                        </p>
                    </div>

                    <div class="bg-white p-4 sm:p-6 rounded-2xl border-2 border-[#362415] shadow-sm space-y-2">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#70A9A1] border-2 border-[#362415] flex items-center justify-center text-white">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h4 class="font-black text-sm sm:text-base text-[#362415]">Chat WA Instan</h4>
                        <p class="text-xs text-[#634C3C] font-semibold leading-relaxed">
                            Format pesanan terisi otomatis, langsung ngobrol dan atur titik temu di area sekolah.
                        </p>
                    </div>

                    <div class="bg-white p-4 sm:p-6 rounded-2xl border-2 border-[#362415] shadow-sm space-y-2">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#F7A399] border-2 border-[#362415] flex items-center justify-center text-[#362415]">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="font-black text-sm sm:text-base text-[#362415]">Dukung Wirausaha</h4>
                        <p class="text-xs text-[#634C3C] font-semibold leading-relaxed">
                            Setiap pembelian langsung membantu temanmu mengasah kemampuan berbisnis nyata.
                        </p>
                    </div>
                </div>
            </section>

            <!-- SECTION 4: CALL TO ACTION BANNER -->
            <section class="bg-[#F25C05] text-[#FCFAF5] border-2 border-[#362415] rounded-3xl sm:rounded-[40px] p-6 sm:p-12 text-center space-y-4 sm:space-y-6 shadow-[5px_5px_0px_#362415] sm:shadow-[10px_10px_0px_#362415] relative overflow-hidden">
                <div class="relative z-10 max-w-xl mx-auto space-y-3 sm:space-y-4">
                    <span class="inline-block px-3 py-1 rounded-full bg-[#F9C22E] text-[#362415] text-[10px] sm:text-xs font-black uppercase tracking-widest border border-[#362415] shadow-sm rotate-[-2deg]">
                        Cari yang Pas di Dompet?
                    </span>
                    <h2 class="text-2xl sm:text-5xl font-black text-white tracking-tight sm:tracking-tighter leading-tight">
                        Atur Budget Jajanmu di Halaman Kategori!
                    </h2>
                    <p class="text-white/90 text-xs sm:text-sm font-semibold sm:font-bold">
                        Pilih menu sesuai kantong mulai dari di bawah Rp 5.000 hingga paket lengkap lezat.
                    </p>

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5 sm:gap-3">
                        <Link
                            :href="route('catalog.categories')"
                            class="w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-3.5 bg-[#362415] text-[#F4F0E6] rounded-full font-black text-xs uppercase tracking-wider hover:bg-white hover:text-[#362415] border-2 border-[#362415] transition-all flex items-center justify-center"
                        >
                            <span>Buka Halaman Kategori</span>
                        </Link>
                        <Link
                            :href="route('how-to-order')"
                            class="w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-3.5 bg-white text-[#362415] rounded-full font-black text-xs uppercase tracking-wider hover:bg-[#F9C22E] border-2 border-[#362415] transition-all"
                        >
                            Cara Memesan
                        </Link>
                    </div>
                </div>
            </section>

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
.blob-shape {
    border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
    filter: blur(40px);
}

@keyframes blob-spin {
    0% { transform: rotate(0deg) scale(1); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; }
    50% { transform: rotate(180deg) scale(1.1); border-radius: 60% 40% 30% 70% / 50% 60% 40% 50%; }
    100% { transform: rotate(360deg) scale(1); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%; }
}

@keyframes blob-spin-reverse {
    0% { transform: rotate(360deg) scale(1); border-radius: 50% 50% 30% 70% / 60% 40% 60% 40%; }
    50% { transform: rotate(180deg) scale(1.2); border-radius: 30% 70% 70% 30% / 40% 60% 40% 60%; }
    100% { transform: rotate(0deg) scale(1); border-radius: 50% 50% 30% 70% / 60% 40% 60% 40%; }
}

.animate-blob-spin {
    animation: blob-spin 15s infinite linear;
}

.animate-blob-spin-reverse {
    animation: blob-spin-reverse 18s infinite linear;
}
</style>