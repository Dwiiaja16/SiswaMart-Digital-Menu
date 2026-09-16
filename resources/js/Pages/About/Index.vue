<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";

const mobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};
</script>

<template>
    <Head title="Tentang Kami - SiswaMart SMKN 11 Bandung" />

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
                <!-- LOGO -->
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

                <!-- DESKTOP NAV MENU -->
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
                    <Link
                        :href="route('how-to-order')"
                        class="text-sm font-bold text-[#362415] hover:text-[#F25C05] transition-colors"
                    >
                        Cara Memesan
                    </Link>
                    <span
                        class="text-sm font-black text-[#F25C05] px-3 py-1 bg-[#F25C05]/10 rounded-full border border-[#F25C05]/20"
                    >
                        Tentang Kami
                    </span>
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
                        <Link
                            :href="route('how-to-order')"
                            @click="mobileMenuOpen = false"
                            class="px-3 py-2 hover:bg-[#F9C22E]/30 rounded-xl transition-colors"
                        >
                            Cara Memesan
                        </Link>
                        <span class="px-3 py-2 bg-[#F25C05]/15 text-[#F25C05] font-black rounded-xl">
                            Tentang Kami
                        </span>
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>MENGENAL SISWAMART</span>
                </div>

                <!-- TITLE -->
                <h1
                    class="text-3xl sm:text-6xl lg:text-7xl font-black text-[#362415] tracking-tight leading-tight"
                >
                    Wadah Karya & Wirausaha <br />
                    <span class="text-[#F25C05]">Siswa SMKN 11 Bandung.</span>
                </h1>

                <!-- SUBTITLE -->
                <p
                    class="text-[#634C3C] text-xs sm:text-base md:text-lg font-semibold max-w-2xl mx-auto leading-relaxed px-2"
                >
                    Platform digital katalog kuliner dan kreasi siswa yang menghubungkan teman-teman penjual dengan seluruh warga sekolah secara cepat, ramah, dan mandiri.
                </p>
            </section>

            <!-- CERITA KAMI (OUR STORY) -->
            <section
                class="bg-[#FCFAF5] border-2 border-[#362415] rounded-2xl sm:rounded-[36px] p-5 sm:p-12 shadow-[3px_3px_0px_#362415] sm:shadow-[8px_8px_0px_#362415] space-y-4 sm:space-y-6"
            >
                <div class="flex items-center gap-2 text-[10px] sm:text-xs font-black uppercase tracking-wider text-[#F25C05]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Kisah di Balik Layar</span>
                </div>

                <h2 class="text-xl sm:text-3xl font-black text-[#362415] tracking-tight">
                    Mengapa SiswaMart Ada?
                </h2>

                <div class="space-y-3 sm:space-y-4 text-xs sm:text-base text-[#634C3C] font-semibold leading-relaxed">
                    <p>
                        Di SMKN 11 Bandung, kami melihat banyak sekali potensi hebat dari teman-teman siswa. Mulai dari keahlian membuat aneka jajanan gurih, minuman segar penyemangat belajar, hingga produk karya kreatif yang luar biasa.
                    </p>
                    <p>
                        Sebelumnya, proses promosi sering kali terbatas hanya dari mulut ke mulut atau status WhatsApp yang cepat tertimbun. SiswaMart hadir sebagai solusi katalog digital modern agar setiap karya siswa memiliki etalase yang rapi, jelas informasinya, dan mudah dipesan oleh siswa lain maupun bapak/ibu guru.
                    </p>
                    <p>
                        Dengan sistem pemesanan langsung ke WhatsApp penjual, transaksi berlangsung santai, aman, tanpa biaya perantara, serta melatih interaksi komunikasi bisnis yang nyata bagi siswa.
                    </p>
                </div>
            </section>

            <!-- 4 NILAI UTAMA (VALUES) -->
            <section class="space-y-4 sm:space-y-6">
                <div class="flex items-center justify-between border-b-2 border-[#362415]/15 pb-2.5 sm:pb-3">
                    <h2 class="text-lg sm:text-2xl font-black text-[#362415] tracking-tight flex items-center gap-1.5 sm:gap-2">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#F25C05]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Nilai Utama Kami</span>
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <!-- VALUE 1 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] space-y-2 sm:space-y-3"
                    >
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#F9C22E] border-2 border-[#362415] flex items-center justify-center text-[#362415] shadow-sm">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="font-black text-base sm:text-xl text-[#362415]">Kreativitas & Inovasi</h3>
                        <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                            Mendorong siswa untuk terus berinovasi dalam mengolah rasa, menciptakan packaging menarik, dan menghadirkan karya yang disukai teman-teman sebaya.
                        </p>
                    </div>

                    <!-- VALUE 2 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] space-y-2 sm:space-y-3"
                    >
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#F25C05] border-2 border-[#362415] flex items-center justify-center text-white shadow-sm">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="font-black text-base sm:text-xl text-[#362415]">Kejujuran & Harga Pelajar</h3>
                        <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                            Semua harga tertera apa adanya tanpa mark-up atau biaya komisi aplikasi, menjunjung tinggi transparansi dalam berjual-beli.
                        </p>
                    </div>

                    <!-- VALUE 3 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] space-y-2 sm:space-y-3"
                    >
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#70A9A1] border-2 border-[#362415] flex items-center justify-center text-white shadow-sm">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="font-black text-base sm:text-xl text-[#362415]">Solidaritas Satu Almamater</h3>
                        <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                            Membangun budaya saling mendukung usaha teman satu sekolah. Dari siswa, oleh siswa, dan untuk seluruh keluarga besar SMKN 11 Bandung.
                        </p>
                    </div>

                    <!-- VALUE 4 -->
                    <div
                        class="bg-[#FCFAF5] p-4 sm:p-7 rounded-2xl sm:rounded-[32px] border-2 border-[#362415] shadow-[2px_2px_0px_#362415] sm:shadow-[6px_6px_0px_#362415] space-y-2 sm:space-y-3"
                    >
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-[#F7A399] border-2 border-[#362415] flex items-center justify-center text-[#362415] shadow-sm">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="font-black text-base sm:text-xl text-[#362415]">Kemandirian Sejak Dini</h3>
                        <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                            Memberikan ruang pembelajaran praktik wirausaha langsung, mulai dari manajemen persediaan, melayani pembeli, hingga menerima ulasan.
                        </p>
                    </div>
                </div>
            </section>

            <!-- TENTANG SMKN 11 BANDUNG -->
            <section
                class="bg-white border-2 border-[#362415] rounded-2xl sm:rounded-[36px] p-5 sm:p-10 shadow-[3px_3px_0px_#362415] sm:shadow-[8px_8px_0px_#362415] flex flex-col sm:flex-row items-center gap-5 sm:gap-8"
            >
                <div class="w-16 h-16 sm:w-28 sm:h-28 rounded-2xl sm:rounded-3xl bg-[#F9C22E] border-2 border-[#362415] flex items-center justify-center text-[#362415] shrink-0 shadow-sm">
                    <svg class="w-9 h-9 sm:w-12 sm:h-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>

                <div class="space-y-1 sm:space-y-2 text-center sm:text-left">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-[#F25C05]">SMKN 11 Bandung</span>
                    <h3 class="text-base sm:text-2xl font-black text-[#362415]">
                        Sekolah Pusat Keunggulan Berjiwa Wirausaha
                    </h3>
                    <p class="text-xs sm:text-sm text-[#634C3C] font-semibold leading-relaxed">
                        SMKN 11 Bandung terus berkomitmen mencetak generasi muda yang kompeten, berkarakter, dan siap berwirausaha secara mandiri di era digital. SiswaMart merupakan implementasi nyata dalam memfasilitasi kreativitas siswa di lingkungan sekolah.
                    </p>
                </div>
            </section>

            <!-- CTA BANNER -->
            <section
                class="bg-[#F25C05] text-[#FCFAF5] border-2 border-[#362415] rounded-3xl sm:rounded-[40px] p-6 sm:p-12 text-center space-y-4 sm:space-y-6 shadow-[5px_5px_0px_#362415] sm:shadow-[10px_10px_0px_#362415] relative overflow-hidden"
            >
                <div class="relative z-10 max-w-xl mx-auto space-y-3 sm:space-y-4">
                    <span
                        class="inline-block px-3 py-1 rounded-full bg-[#F9C22E] text-[#362415] text-[10px] sm:text-xs font-black uppercase tracking-widest border border-[#362415] shadow-sm rotate-[-2deg]"
                    >
                        Ayo Dukung Temanmu!
                    </span>
                    <h2 class="text-2xl sm:text-5xl font-black text-white tracking-tight sm:tracking-tighter leading-tight">
                        Siap Mencicipi Karya Siswa Hari Ini?
                    </h2>
                    <p class="text-white/90 text-xs sm:text-base font-semibold sm:font-bold">
                        Banyak pilihan menu istimewa yang siap menemani jam istirahatmu di sekolah.
                    </p>

                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5 sm:gap-4">
                        <Link
                            :href="route('catalog.index')"
                            class="w-full sm:w-auto px-8 sm:px-10 py-3 sm:py-4 bg-[#362415] text-[#F4F0E6] rounded-full font-black text-xs uppercase tracking-wider hover:bg-white hover:text-[#362415] border-2 border-[#362415] transition-all"
                        >
                            Mulai Eksplor Menu
                        </Link>
                        <Link
                            :href="route('catalog.categories')"
                            class="w-full sm:w-auto px-8 sm:px-10 py-3 sm:py-4 bg-white text-[#362415] rounded-full font-black text-xs uppercase tracking-wider hover:bg-[#F9C22E] border-2 border-[#362415] transition-all"
                        >
                            Filter Kategori & Budget
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
