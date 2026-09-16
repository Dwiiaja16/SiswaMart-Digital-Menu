<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";

const activeFaq = ref(null);
const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const toggleFaq = (index) => {
    activeFaq.value = activeFaq.value === index ? null : index;
};

const faqs = [
    {
        q: "Gimana cara bayarnya? Bisa pakai QRIS?",
        a: "Bisa banget! Cara pembayaran (Tunai/Cash pas ketemuan atau transfer QRIS) disepakati langsung saat kamu chat WhatsApp dengan penjualnya.",
    },
    {
        q: "Apakah pesanan bisa diantar ke kelas?",
        a: "Tergantung kesepakatan dengan teman penjual saat chat WA! Kebanyakan bisa COD di area sekolah seperti kantin, depan kelas, gazebo, atau lobi SMKN 11 Bandung.",
    },
    {
        q: "Apa bedanya menu 'Ready' sama 'Pre-Order (PO)'?",
        a: "Menu 'Ready' berarti stok sudah tersedia dan bisa langsung diambil. Sedangkan 'Pre-Order' berarti sajian baru dibuat fresh setelah kamu pesan (biasanya dikirim di hari berikutnya).",
    },
    {
        q: "Format chat WhatsApp-nya nanti ngetik sendiri?",
        a: "Gak perlu repot! Ketika kamu klik tombol 'Pesan via WhatsApp' di halaman produk, SiswaMart otomatis bikinin template pesan yang rapi lengkap dengan nama produk dan harganya.",
    },
    {
        q: "Saya siswa SMKN 11 dan mau jualan juga, gimana caranya?",
        a: "Keren banget! Kamu bisa daftar akun, lalu hubungi Admin sekolah untuk verifikasi tokomu agar bisa mulai memajang produk kreatifmu di SiswaMart.",
    },
];
</script>

<template>
    <Head title="Cara Memesan - SiswaMart" />

    <div
        class="min-h-screen bg-[#F4F0E6] text-[#362415] font-sans selection:bg-[#F25C05] selection:text-[#F4F0E6] pb-20 sm:pb-24 overflow-x-hidden relative"
    >
        <!-- BACKGROUND BLOBS OATSIDE STYLE -->
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
        <header
            class="sticky top-0 z-50 px-3 sm:px-6 py-2.5 sm:py-3 transition-all duration-200"
        >
            <div
                class="max-w-5xl mx-auto bg-[#F4F0E6]/95 sm:bg-[#F4F0E6]/90 backdrop-blur-md border-2 border-[#362415] rounded-2xl sm:rounded-full px-3.5 sm:px-6 py-2 sm:py-2.5 flex items-center justify-between gap-2 sm:gap-6 shadow-sm sm:shadow-[0_8px_20px_rgba(54,36,21,0.06)]"
            >
                <Link
                    :href="route('catalog.index')"
                    class="flex items-center gap-2 group shrink-0"
                >
                    <div
                        class="text-lg sm:text-2xl font-black tracking-tighter text-[#362415] group-hover:text-[#F25C05] transition-colors"
                    >
                        Siswa<span class="text-[#F25C05]">Mart</span>
                    </div>
                </Link>

                <!-- DESKTOP NAV -->
                <nav class="hidden md:flex items-center gap-6">
                    <Link
                        :href="route('catalog.index')"
                        class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors"
                    >
                        Beranda
                    </Link>
                    <Link
                        :href="route('catalog.categories')"
                        class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors"
                    >
                        Kategori
                    </Link>
                    <span
                        class="text-sm font-black text-[#F25C05] px-3 py-1 bg-[#F25C05]/10 rounded-full border border-[#F25C05]/20"
                    >
                        Cara Memesan
                    </span>
                    <Link
                        :href="route('about')"
                        class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors"
                    >
                        Tentang Kami
                    </Link>
                </nav>

                <!-- DESKTOP ACTION BUTTON -->
                <div class="hidden md:flex items-center gap-2 shrink-0">
                    <Link
                        :href="route('catalog.index')"
                        class="px-5 py-2 rounded-full bg-[#362415] text-[#F4F0E6] text-xs sm:text-sm font-bold hover:bg-[#F25C05] transition-colors duration-200"
                    >
                        Kembali Belanja
                    </Link>
                </div>

                <!-- MOBILE HAMBURGER TOGGLE -->
                <div class="flex md:hidden items-center gap-2">
                    <Link
                        :href="route('catalog.index')"
                        class="px-3 py-1.5 rounded-full bg-[#362415] text-[#F4F0E6] text-xs font-bold"
                    >
                        Belanja
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
                        <Link
                            :href="route('catalog.index')"
                            @click="mobileMenuOpen = false"
                            class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors"
                        >
                            Beranda
                        </Link>
                        <Link
                            :href="route('catalog.categories')"
                            @click="mobileMenuOpen = false"
                            class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors"
                        >
                            Kategori & Budget
                        </Link>
                        <span class="px-3 py-2 bg-[#F25C05]/15 text-[#F25C05] font-black rounded-xl">
                            Cara Memesan
                        </span>
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

        <!-- MAIN CONTAINER -->
        <main class="relative z-10 max-w-5xl mx-auto px-3.5 sm:px-6 lg:px-8 pt-4 sm:pt-10 space-y-10 sm:space-y-16">
            <!-- HERO SECTION -->
            <section class="text-center space-y-4 sm:space-y-6 flex flex-col items-center">
                <!-- BADGE STIKER OATSIDE -->
                <div
                    class="inline-flex items-center gap-1.5 sm:gap-2 bg-[#F9C22E] border-2 border-[#362415] text-[#362415] px-3 sm:px-4 py-1 sm:py-1.5 rounded-full text-[10px] sm:text-xs font-black uppercase tracking-widest rotate-[-2deg] shadow-sm"
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>GAMPANG & ANTI RIBET!</span>
                </div>

                <!-- TITLE -->
                <h1
                    class="text-3xl sm:text-6xl lg:text-7xl font-black text-[#362415] tracking-tight leading-tight"
                >
                    Mau Jajan di Sekolah? <br />
                    <span class="text-[#F25C05]">Gini Nih Caranya!</span>
                </h1>

                <!-- SUBTITLE -->
                <p
                    class="text-[#634C3C] text-xs sm:text-base md:text-lg font-semibold max-w-2xl mx-auto leading-relaxed px-2"
                >
                    Pesan makanan, minuman segar, dan karya buatan teman sekolahmu
                    langsung via WhatsApp. Praktis, cepat, dan menyenangkan.
                </p>
            </section>

            <!-- 4 STEP CARDS (2 KOLOM DI DESKTOP, 1 KOLOM DI MOBILE) -->
            <section class="space-y-4 sm:space-y-6">
                <div class="flex items-center justify-between border-b-2 border-[#362415]/15 pb-2.5 sm:pb-3">
                    <h2 class="text-lg sm:text-2xl font-black text-[#362415] tracking-tight flex items-center gap-1.5 sm:gap-2">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>4 Langkah Santai Jajan</span>
                    </h2>
                    <span class="text-[10px] sm:text-xs font-bold text-[#70A9A1] uppercase tracking-wider bg-[#70A9A1]/10 px-2.5 py-0.5 rounded-full border border-[#70A9A1]/30">
                        Direct WA
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <!-- CARD 1 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] flex flex-col justify-between group"
                    >
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex items-center justify-between">
                                <div
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#F9C22E] border-2 border-[#362415] text-[#362415] flex items-center justify-center font-black text-lg sm:text-xl shadow-sm"
                                >
                                    01
                                </div>
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border-2 border-[#362415] flex items-center justify-center text-[#362415]">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <h3 class="font-black text-base sm:text-xl text-[#362415]">
                                    Pilih Menu Favoritmu
                                </h3>
                                <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                                    Buka halaman utama SiswaMart, intip deretan jajanan lezat, minuman dingin,
                                    snack gurih, atau produk kreatif buatan siswa SMKN 11 Bandung.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 sm:mt-6 pt-3 sm:pt-4 border-t border-[#362415]/10 flex items-center gap-2 text-[11px] sm:text-xs font-bold text-[#362415]/70">
                            <span class="inline-block w-2 h-2 rounded-full bg-[#F9C22E]"></span>
                            <span>Bisa filter berdasarkan kategori & harga</span>
                        </div>
                    </div>

                    <!-- CARD 2 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] flex flex-col justify-between group"
                    >
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex items-center justify-between">
                                <div
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#F25C05] border-2 border-[#362415] text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-sm"
                                >
                                    02
                                </div>
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border-2 border-[#362415] flex items-center justify-center text-[#362415]">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <h3 class="font-black text-base sm:text-xl text-[#362415]">
                                    Klik Pesan via WhatsApp
                                </h3>
                                <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                                    Buka detail produk, tentukan jumlah porsi/item, lalu pencet tombol hijau WA.
                                    Template chat sudah otomatis terformat rapi.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 sm:mt-6 bg-[#E8F5E9] border border-[#A5D6A7] rounded-xl p-2.5 sm:p-3 text-[11px] sm:text-xs font-mono text-[#2E7D32] flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-[#2E7D32] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span class="truncate">"Halo kak! Mau pesan Risol Mayo..."</span>
                        </div>
                    </div>

                    <!-- CARD 3 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] flex flex-col justify-between group"
                    >
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex items-center justify-between">
                                <div
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#70A9A1] border-2 border-[#362415] text-white flex items-center justify-center font-black text-lg sm:text-xl shadow-sm"
                                >
                                    03
                                </div>
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border-2 border-[#362415] flex items-center justify-center text-[#362415]">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#70A9A1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                    </svg>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <h3 class="font-black text-base sm:text-xl text-[#362415]">
                                    Janjian Ambil & Bayar
                                </h3>
                                <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                                    Konfirmasi titik temu di area SMKN 11 Bandung (bisa di kelas, lobi, atau kantin).
                                    Bayar fleksibel pakai Tunai / Cash atau QRIS.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 sm:mt-6 pt-3 sm:pt-4 border-t border-[#362415]/10 flex items-center gap-2 text-[11px] sm:text-xs font-bold text-[#362415]/70">
                            <span class="inline-block w-2 h-2 rounded-full bg-[#70A9A1]"></span>
                            <span>Khusus area lingkungan sekolah SMKN 11</span>
                        </div>
                    </div>

                    <!-- CARD 4 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] flex flex-col justify-between group"
                    >
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex items-center justify-between">
                                <div
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#F7A399] border-2 border-[#362415] text-[#362415] flex items-center justify-center font-black text-lg sm:text-xl shadow-sm"
                                >
                                    04
                                </div>
                                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-white border-2 border-[#362415] flex items-center justify-center text-[#362415]">
                                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500 fill-amber-400" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                </div>
                            </div>

                            <div class="space-y-1 sm:space-y-2">
                                <h3 class="font-black text-base sm:text-xl text-[#362415]">
                                    Nikmati & Kasih Bintang 5!
                                </h3>
                                <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                                    Makan sambil nongkrong bareng teman. Jangan lupa tinggalkan ulasan
                                    dan rating bintang 5 di SiswaMart untuk dukung karya wirausaha siswa.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 sm:mt-6 flex items-center gap-1.5 text-[11px] sm:text-xs font-black text-amber-600 bg-amber-50 border border-amber-200/80 px-2.5 py-1 rounded-full w-fit">
                            <div class="flex items-center text-amber-500">
                                <svg v-for="i in 5" :key="i" class="w-3 h-3 sm:w-3.5 sm:h-3.5 fill-amber-400" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                            </div>
                            <span class="text-[#362415] font-bold">Dukung Siswa!</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- KODE STATUS & TIPS OATSIDE -->
            <section
                class="bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl sm:rounded-[36px] p-4 sm:p-10 shadow-[3px_3px_0px_#362415] sm:shadow-[8px_8px_0px_#362415] space-y-6"
            >
                <div class="text-center space-y-1.5">
                    <div
                        class="inline-block bg-[#F25C05] text-[#FCFAF5] px-3 py-0.5 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-widest"
                    >
                        Kamus SiswaMart
                    </div>
                    <h3 class="text-xl sm:text-3xl font-black text-[#362415] tracking-tight">
                        Pahami Status Produk & Toko
                    </h3>
                    <p class="text-xs sm:text-sm font-semibold text-[#634C3C] max-w-md mx-auto">
                        Biar gak salah paham waktu pesan, perhatikan label-label ini ya.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 sm:gap-5">
                    <div
                        class="bg-white p-4 sm:p-5 rounded-xl sm:rounded-2xl border-2 border-[#362415] shadow-sm space-y-2"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping"></span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-black text-[10px] sm:text-xs border border-emerald-300 uppercase">
                                Ready Stock
                            </span>
                        </div>
                        <h4 class="font-extrabold text-xs sm:text-sm text-[#362415]">Langsung Siap Ambil</h4>
                        <p class="text-[11px] sm:text-xs text-[#634C3C] font-semibold leading-relaxed">
                            Stoknya sudah tersedia hari ini. Kamu bisa langsung janjian ambil sekarang juga.
                        </p>
                    </div>

                    <div
                        class="bg-white p-4 sm:p-5 rounded-xl sm:rounded-2xl border-2 border-[#362415] shadow-sm space-y-2"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-black text-[10px] sm:text-xs border border-amber-300 uppercase">
                                Pre-Order (PO)
                            </span>
                        </div>
                        <h4 class="font-extrabold text-xs sm:text-sm text-[#362415]">Dibuat Sesuai Pesanan</h4>
                        <p class="text-[11px] sm:text-xs text-[#634C3C] font-semibold leading-relaxed">
                            Siswa butuh waktu memasak/membuat. Pesan sekarang untuk pengantaran jadwal kesepakatan.
                        </p>
                    </div>

                    <div
                        class="bg-white p-4 sm:p-5 rounded-xl sm:rounded-2xl border-2 border-[#362415] shadow-sm space-y-2"
                    >
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                            <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-black text-[10px] sm:text-xs border border-rose-300 uppercase">
                                Lapak Tutup
                            </span>
                        </div>
                        <h4 class="font-extrabold text-xs sm:text-sm text-[#362415]">Sedang Istirahat</h4>
                        <p class="text-[11px] sm:text-xs text-[#634C3C] font-semibold leading-relaxed">
                            Teman penjual sedang belajar atau stok hari ini sudah habis. Cek lagi nanti ya.
                        </p>
                    </div>
                </div>
            </section>

            <!-- FAQ SECTION -->
            <section class="space-y-4 sm:space-y-6">
                <div class="text-center space-y-1.5">
                    <div
                        class="inline-block bg-[#70A9A1] text-white px-3 py-0.5 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-widest"
                    >
                        Paling Sering Ditanyain
                    </div>
                    <h3 class="text-xl sm:text-3xl font-black text-[#362415] tracking-tight">
                        Ada Pertanyaan? Kami Punya Jawabannya!
                    </h3>
                </div>

                <div class="space-y-2.5 max-w-3xl mx-auto">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-[#FCFAF5] border-2 border-[#362415] rounded-xl sm:rounded-2xl overflow-hidden shadow-sm transition-all"
                    >
                        <button
                            @click="toggleFaq(index)"
                            type="button"
                            class="w-full p-3.5 sm:p-5 text-left font-black text-sm sm:text-lg text-[#362415] flex items-center justify-between gap-3 hover:bg-[#F9C22E]/20 transition-colors"
                        >
                            <span>{{ faq.q }}</span>
                            <span
                                class="w-6 h-6 sm:w-8 sm:h-8 rounded-full border border-[#362415] sm:border-2 bg-white flex items-center justify-center text-xs font-black shrink-0 transition-transform duration-200"
                                :class="{ 'rotate-180 bg-[#F9C22E]': activeFaq === index }"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>

                        <div
                            v-show="activeFaq === index"
                            class="px-3.5 sm:px-5 pb-3.5 sm:pb-5 pt-1 text-xs sm:text-sm font-semibold text-[#634C3C] leading-relaxed border-t border-[#362415]/10 bg-white/70"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA BANNER -->
            <section
                class="bg-[#F25C05] text-[#FCFAF5] border-2 border-[#362415] rounded-3xl sm:rounded-[40px] p-6 sm:p-14 text-center space-y-4 sm:space-y-6 shadow-[5px_5px_0px_#362415] sm:shadow-[10px_10px_0px_#362415] relative overflow-hidden"
            >
                <div class="relative z-10 max-w-xl mx-auto space-y-3 sm:space-y-4">
                    <span
                        class="inline-block px-3 py-1 rounded-full bg-[#F9C22E] text-[#362415] text-[10px] sm:text-xs font-black uppercase tracking-widest border border-[#362415] shadow-sm rotate-[-2deg]"
                    >
                        Perut Kenyang, Hati Senang!
                    </span>
                    <h2 class="text-2xl sm:text-5xl font-black text-white tracking-tight sm:tracking-tighter leading-tight">
                        Udah Paham Kan? <br />
                        Yuk Langsung Berburu Jajanan!
                    </h2>
                    <p class="text-white/90 text-xs sm:text-base font-semibold sm:font-bold">
                        Banyak pilihan menu lezat dan karya menarik dari siswa SMKN 11 Bandung yang siap kamu coba sekarang.
                    </p>

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <Link
                            :href="route('catalog.index')"
                            class="w-full sm:w-auto px-8 py-3.5 bg-[#362415] text-[#F4F0E6] rounded-full font-black text-xs uppercase tracking-wider hover:bg-white hover:text-[#362415] border-2 border-[#362415] transition-all"
                        >
                            Buka Katalog Menu
                        </Link>
                    </div>
                </div>
            </section>

            <!-- FOOTER NOTE -->
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
    animation: blob-spin 16s infinite linear;
}

.animate-blob-spin-reverse {
    animation: blob-spin-reverse 20s infinite linear;
}
</style>
