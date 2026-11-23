<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="w-full bg-[#2C1E16] text-[#FAF6EE] border-t border-[#2C1E16]/20">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 md:grid-cols-3">
            <!-- Kolom 1 (Identitas) -->
            <section>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-extrabold tracking-tight text-[#FAF6EE]">
                        Siswa<span class="text-orange-400">Mart</span>
                    </h2>
                    <span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-3 py-0.5 text-[11px] font-semibold text-amber-300">
                        Digital Menu
                    </span>
                </div>
                <p class="mt-4 text-sm font-semibold text-amber-300/90">
                    Etalase Menu & Karya Wirausaha Siswa
                </p>
                <p class="mt-2 max-w-sm text-xs leading-relaxed text-[#D6C7B8]">
                    Jelajahi beragam pilihan jajanan, minuman, dan produk kreatif siswa dalam satu katalog digital. Pilih menu favoritmu dan pesan langsung ke penjual via WhatsApp.
                </p>
            </section>

            <!-- Kolom 2 (Navigasi Cepat) -->
            <section>
                <h2 class="text-xs font-bold uppercase tracking-wider text-amber-400">
                    Navigasi Cepat
                </h2>
                <nav class="mt-4 flex flex-col items-start gap-2.5 text-xs font-medium text-[#E7DDD3]">
                    <Link href="/" class="transition hover:text-orange-400">Beranda</Link>
                    <Link href="/kategori" class="transition hover:text-orange-400">Katalog Menu</Link>
                    <Link href="/cara-memesan" class="transition hover:text-orange-400">Cara Pesan</Link>
                    <Link href="/login" class="transition hover:text-orange-400">Masuk Penjual / Admin</Link>
                </nav>
            </section>

            <!-- Kolom 3 (Aplikasi Mobile & Bantuan) -->
            <section>
                <h2 class="text-xs font-bold uppercase tracking-wider text-amber-400">
                    Aplikasi Mobile &amp; Bantuan
                </h2>
                <p class="mt-4 max-w-sm text-xs leading-relaxed text-[#D6C7B8]">
                    Akses katalog <strong class="font-semibold text-[#FAF6EE]">SiswaMart Digital Menu</strong> lebih praktis langsung dari perangkat Android.
                </p>
                <div class="mt-3.5">
                    <a
                        href="https://github.com/Dwiiaja16/SiswaMart-Digital-Menu-Mobile/releases"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-xl border border-amber-400/30 bg-amber-400/10 px-4 py-2 text-xs font-semibold text-amber-300 transition-all duration-200 hover:border-orange-500 hover:bg-orange-500 hover:text-white"
                    >
                        <svg class="h-4 w-4 shrink-0 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                        </svg>
                        <span>Unduh APK Android (GitHub)</span>
                    </a>
                </div>
                <p class="mt-3.5 max-w-sm text-xs leading-relaxed text-[#D6C7B8]">
                    Butuh bantuan atau mengalami kendala? Klik ikon WhatsApp di pojok kanan bawah.
                </p>
            </section>
        </div>

        <!-- Bar Copyright Paling Bawah -->
        <div class="bg-[#211610] border-t border-[#443024] text-[#B8A696] px-4 py-4 text-center text-xs font-medium">
            © 2026 SiswaMart Digital Menu. All rights reserved.
        </div>
    </footer>
</template>
