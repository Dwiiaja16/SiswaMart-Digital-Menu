<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    product: Object,
    relatedProducts: {
        type: Array,
        default: () => [],
    },
    isOwner: Boolean,
    hasReviewed: Boolean,
    isAdmin: Boolean,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

// --- LOGIKA PARSING GAMBAR PRODUK KOMPREHENSIF ---
const isInvalidImage = (path) => {
    if (!path || typeof path !== 'string') return true;
    const lower = path.trim().toLowerCase();
    return (
        !lower ||
        lower === 'null' ||
        lower === 'undefined' ||
        lower === '[]' ||
        lower === '{}' ||
        lower === 'none' ||
        lower.includes('default-product.png') ||
        lower.includes('default_product.png') ||
        lower.includes('placeholder')
    );
};

const parsePhotos = (input) => {
    if (!input) return [];

    if (Array.isArray(input)) {
        return input.flatMap(item => parsePhotos(item)).filter(Boolean);
    }

    if (typeof input === 'object' && input !== null) {
        const candidate = input.image_path || input.url || input.path || input.foto_produk_url || input.foto_produk || input.image || input.src;
        if (candidate && candidate !== input) {
            return parsePhotos(candidate);
        }
        return [];
    }

    if (typeof input === 'string') {
        let str = input.trim();
        if (isInvalidImage(str)) return [];

        if ((str.startsWith('[') && str.endsWith(']')) || (str.startsWith('{') && str.endsWith('}'))) {
            try {
                const parsed = JSON.parse(str);
                return parsePhotos(parsed);
            } catch (e) {
                str = str.replace(/[\[\]"']/g, '').trim();
                if (isInvalidImage(str)) return [];
                return [str];
            }
        }

        if (str.includes(',') && !str.startsWith('data:image')) {
            return str.split(',').map(s => s.trim()).filter(s => !isInvalidImage(s));
        }

        return [str];
    }

    return [];
};

const getImageUrl = (input) => {
    if (!input) return null;

    if (typeof input === 'string') {
        const trimmed = input.trim();
        if (isInvalidImage(trimmed)) return null;
        if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:image')) {
            return trimmed;
        }
    }

    const photos = parsePhotos(input);
    if (photos.length === 0) return null;

    let path = photos[0];
    if (typeof path !== 'string' || isInvalidImage(path)) return null;

    path = path.trim();
    if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('data:image')) {
        return path;
    }

    path = path.replace(/^[/"']+/, '').replace(/["']+$/, '');
    if (isInvalidImage(path)) return null;

    if (path.startsWith('storage/')) {
        return '/' + path;
    }

    return '/storage/' + path;
};

const getValidImageUrl = (product) => {
    if (!product) return null;
    return getImageUrl(
        product.foto_produk_url ||
        product.foto_produk ||
        product.image ||
        product.images ||
        (Array.isArray(product.semua_foto_url) && product.semua_foto_url.length > 0 ? product.semua_foto_url[0] : null)
    );
};

const getRelatedProductImage = (item) => {
    if (!item) return null;
    return getValidImageUrl(item);
};

// Cek apakah pengguna adalah pemilik produk
const isProductOwner = computed(() => {
    if (props.isOwner) return true;
    const currentUsername = authUser.value?.username;
    const productUsername = props.product?.penjual?.username;
    return currentUsername && productUsername ? currentUsername === productUsername : false;
});

// Cek apakah pengguna adalah Admin atau Penjual
const isRestrictedUser = computed(() => {
    if (!authUser.value) return false;
    
    const role = (authUser.value.role || '').toLowerCase();
    return role === 'admin' || role === 'penjual' || isProductOwner.value;
});

const isShopClosed = computed(() => props.product?.penjual?.status_toko === 'tutup');

const reviewForm = useForm({
    nama_pengunjung: authUser.value ? authUser.value.username : "Pengunjung",
    rating: 5,
    komentar: "",
});

const ratingLabels = {
    1: "Kurang Pas",
    2: "Biasa Aja",
    3: "Cukup Enak",
    4: "Enak Banget",
    5: "Sangat Rekomendasi! 🔥",
};

// Daftar badwords untuk filter instan di Frontend
const badWordsList = [
    'anjing', 'anj', 'anjrit', 'anjir', 'babi', 'bajingan', 'bangsat',
    'bego', 'bodoh', 'brengsek', 'goblok', 'idiot', 'jancok', 'kontol',
    'monyet', 'memek', 'ngentot', 'peler', 'sialan', 'tai', 'tolol', 'fuck', 'shit'
];

const checkClientBadWords = (text) => {
    if (!text) return false;
    return badWordsList.some(word => {
        const regex = new RegExp(`\\b${word}\\b`, 'i');
        return regex.test(text);
    });
};

const submitReview = () => {
    if (isRestrictedUser.value) {
        alert("Fitur ulasan hanya untuk pembeli/pengunjung umum!");
        return;
    }

    if (checkClientBadWords(reviewForm.nama_pengunjung) || checkClientBadWords(reviewForm.komentar)) {
        alert("⚠️ Ulasan Anda terdeteksi mengandung kata-kata kasar / tidak pantas dan tidak dapat dikirim!");
        return;
    }

    reviewForm.post(route("reviews.store", props.product.id_produk), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset("komentar");
            reviewForm.rating = 5;
            alert("Terima kasih! Ulasan kamu berhasil ditambahkan.");
        },
        onError: (errors) => {
            if (errors.badwords) {
                alert(`⚠️ ${errors.badwords}`);
            }
        }
    });
};

// --- MULTI-IMAGE / GALERI SLIDER LOGIC ---
const allImages = computed(() => {
    if (!props.product) return [];

    let list = [];
    if (Array.isArray(props.product.semua_foto_url) && props.product.semua_foto_url.length > 0) {
        list = props.product.semua_foto_url.flatMap(item => parsePhotos(item));
    } else {
        const raw = props.product.foto_produk_url || props.product.foto_produk;
        list = parsePhotos(raw);
    }

    const filtered = list.filter(p => !isInvalidImage(p));
    // Deduplikasi URL agar foto yang sama tidak tampil ganda
    return [...new Set(filtered)];
});

const activeImageIndex = ref(0);

// Reset indeks slide jika produk/foto berubah
watch(() => props.product?.id_produk, () => {
    activeImageIndex.value = 0;
});

const activeImage = computed(() => {
    return allImages.value[activeImageIndex.value] || null;
});

const activeImageUrl = computed(() => {
    return activeImage.value ? getImageUrl(activeImage.value) : null;
});

const nextSlide = () => {
    if (allImages.value.length > 1) {
        activeImageIndex.value = (activeImageIndex.value + 1) % allImages.value.length;
    }
};

const prevSlide = () => {
    if (allImages.value.length > 1) {
        activeImageIndex.value = (activeImageIndex.value - 1 + allImages.value.length) % allImages.value.length;
    }
};

let touchStartX = 0;
let touchEndX = 0;

const handleTouchStart = (e) => {
    touchStartX = e.changedTouches[0].screenX;
};

const handleTouchEnd = (e) => {
    touchEndX = e.changedTouches[0].screenX;
    if (touchEndX < touchStartX - 40) {
        nextSlide();
    } else if (touchEndX > touchStartX + 40) {
        prevSlide();
    }
};

const whatsappLink = computed(() => {
    if (props.product?.wa_url) {
        return props.product.wa_url;
    }

    let phone = props.product?.penjual?.nomor_whatsapp || '081234567890';
    phone = String(phone).replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) phone = '62' + phone.slice(1);

    const penjualName = props.product?.penjual?.nama_toko || props.product?.penjual?.nama_lengkap || 'Penjual SiswaMart';
    const productName = props.product?.nama_produk || '';
    const price = Number(props.product?.harga || 0).toLocaleString('id-ID');
    const currentUrl = typeof window !== 'undefined' ? window.location.href : '';

    const rawMessage = `Halo ${penjualName}, saya mau pesan produk ini via SiswaMart:

► Produk: ${productName}
► Harga: Rp ${price}
► Tautan: ${currentUrl}

Apakah produk ini ready? Terima kasih!`;

    return `https://wa.me/${phone}?text=${encodeURIComponent(rawMessage.trim())}`;
});

const copied = ref(false);
const copyLink = async () => {
    const url = window.location.href;
    if (navigator.share) {
        try {
            await navigator.share({ title: props.product?.nama_produk || 'SiswaMart', url });
            return;
        } catch (e) {
            if (e.name === 'AbortError') return;
        }
    }
    try {
        await navigator.clipboard.writeText(url);
    } catch {
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    }
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const approveProduct = () => {
    if (!confirm('Setujui produk ini agar tayang publik?')) return;

    router.patch(route('admin.products.approve', props.product.id_produk), {}, {
        preserveScroll: true,
    });
};

const rejectProduct = () => {
    if (!confirm('Tolak produk ini?')) return;

    router.patch(route('admin.products.reject', props.product.id_produk), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="(product.nama_produk || 'Detail Produk') + ' - SiswaMart'" />

    <div class="detail-page min-h-screen text-gray-900 font-sans pb-24 selection:bg-orange-500 selection:text-white">
        <div class="ambient-grid" aria-hidden="true"></div>
        <div class="ambient-orb orb-one" aria-hidden="true"></div>
        <div class="ambient-orb orb-two" aria-hidden="true"></div>

        <!-- Sticky Header Navbar -->
        <header class="site-nav sticky top-0 z-50">
            <div class="nav-inner max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('catalog.index')" :preserve-state="false" class="brand flex items-center">
                        <span>Siswa</span><b>Mart</b>
                    </Link>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <button @click="copyLink" class="share-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        <span>{{ copied ? 'Tersalin!' : 'Bagikan' }}</span>
                    </button>

                    <template v-if="authUser">
                        <Link v-if="authUser.role === 'admin' || authUser.role === 'penjual'" :href="route('dashboard')" class="dash-btn">
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="login-btn">
                            Masuk
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto px-4 sm:px-6 pt-6 relative z-10">
            <!-- Hero Card Main -->
            <section class="hero-card reveal">
                <!-- Panel Image Galeri Slider -->
                <div class="hero-image-panel">
                    <div 
                        class="main-image-wrap group select-none"
                        @touchstart.passive="handleTouchStart"
                        @touchend.passive="handleTouchEnd"
                    >
                        <div class="image-pattern" aria-hidden="true"></div>
                        
                        <!-- 1. TAMPILKAN FOTO JIKA TERSEDIA -->
                        <img 
                            v-if="activeImageUrl" 
                            :src="activeImageUrl" 
                            :alt="product.nama_produk" 
                            loading="lazy" 
                            class="main-product-image" 
                            :class="isShopClosed ? 'grayscale-[35%] opacity-90' : ''" 
                        />
                        
                        <!-- Badge Toko Tutup di Foto Utama -->
                        <div v-if="isShopClosed && activeImageUrl" class="absolute top-3 left-3 z-30 pointer-events-none">
                            <span class="bg-rose-500 text-white px-2.5 py-1 rounded-full text-[10px] sm:text-xs font-black uppercase tracking-wider shadow-[2px_2px_0px_#362415] flex items-center gap-1.5 border-2 border-[#362415]">
                                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                                <span>TOKO TUTUP</span>
                            </span>
                        </div>

                        <!-- Indikator Angka Slide (Hanya Jika Foto > 1) -->
                        <div v-if="allImages.length > 1" class="absolute top-3 right-3 z-30 bg-[#362415]/85 text-white text-[10px] font-black uppercase px-2.5 py-1 rounded-lg border border-[#362415] shadow-sm backdrop-blur-sm pointer-events-none">
                            {{ activeImageIndex + 1 }} / {{ allImages.length }} Foto
                        </div>
                        
                        <!-- 2. JIKA TANPA FOTO SAMA SEIKALI (PLACEHOLDER NEO-BRUTALISM) -->
                        <div v-else-if="!activeImageUrl" class="relative z-10 w-full h-full flex flex-col items-center justify-center text-[#362415] font-black text-xs uppercase gap-2 bg-[#FFFDF0] p-4 text-center">
                            <div class="w-14 h-14 rounded-2xl bg-amber-100 border-2 sm:border-3 border-[#362415] flex items-center justify-center shadow-[3px_3px_0px_#362415] mb-1">
                                <svg class="w-7 h-7 text-[#362415]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <span class="tracking-wider">Tanpa Foto</span>
                        </div>

                        <!-- AREA KLIK SISI KIRI GAMBAR (Hanya Jika Foto > 1) -->
                        <div 
                            v-if="allImages.length > 1" 
                            @click="prevSlide" 
                            class="absolute left-0 top-0 bottom-8 w-1/2 z-20 cursor-pointer flex items-center justify-start pl-3 opacity-0 hover:opacity-100 transition-opacity group/left"
                            title="Klik sisi kiri untuk foto sebelumnya"
                        >
                            <div class="bg-[#362415]/80 text-amber-300 p-2 rounded-xl border border-amber-300/40 shadow-md transform -translate-x-2 group-hover/left:translate-x-0 transition-transform">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </div>
                        </div>

                        <!-- AREA KLIK SISI KANAN GAMBAR (Hanya Jika Foto > 1) -->
                        <div 
                            v-if="allImages.length > 1" 
                            @click="nextSlide" 
                            class="absolute right-0 top-0 bottom-8 w-1/2 z-20 cursor-pointer flex items-center justify-end pr-3 opacity-0 hover:opacity-100 transition-opacity group/right"
                            title="Klik sisi kanan untuk foto selanjutnya"
                        >
                            <div class="bg-[#362415]/80 text-amber-300 p-2 rounded-xl border border-amber-300/40 shadow-md transform translate-x-2 group-hover/right:translate-x-0 transition-transform">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>

                        <!-- Segmen Indikator Garis Slider (Hanya Jika Foto > 1) -->
                        <div v-if="allImages.length > 1" class="absolute bottom-3 inset-x-0 px-4 flex gap-1.5 z-30 justify-center">
                            <button
                                v-for="(img, idx) in allImages"
                                :key="idx"
                                type="button"
                                @click.stop="activeImageIndex = idx"
                                :class="[
                                    'h-1.5 rounded-full transition-all duration-300 border border-gray-900/40 cursor-pointer p-0',
                                    idx === activeImageIndex 
                                        ? 'flex-1 bg-orange-500 shadow-sm ring-1 ring-orange-400' 
                                        : 'flex-1 bg-stone-300/80 hover:bg-stone-400'
                                ]"
                                :title="`Lihat foto ${idx + 1}`"
                            ></button>
                        </div>
                    </div>

                    <!-- Status Stok & Kategori (Di bawah Foto) -->
                    <div class="flex flex-wrap items-center justify-between gap-2 mt-3.5">
                        <span :class="product.status_stok === 'ready' ? 'status-ready' : product.status_stok === 'pre-order' ? 'status-preorder' : 'status-empty'" class="status-chip m-0">
                            <span class="status-dot"></span>
                            {{ product.status_stok === "ready" ? "Ready Stock" : product.status_stok === "pre-order" ? "Pre-Order" : "Habis" }}
                        </span>

                        <div class="category-list flex flex-wrap items-center gap-1.5">
                            <span v-if="product.kategori" class="category-chip m-0">
                                {{ product.kategori.nama_kategori }}
                            </span>
                            <span v-else class="category-chip m-0">Kreatif</span>
                        </div>
                    </div>

                    <!-- THUMBNAIL SELECTOR (HANYA RENDER JIKA FOTO LEBIH DARI 1) -->
                    <div v-if="allImages.length > 1" class="gallery-thumbnails mt-3">
                        <button
                            v-for="(img, idx) in allImages"
                            :key="idx"
                            @click="activeImageIndex = idx"
                            type="button"
                            class="thumb-item"
                            :class="{ 'thumb-active': activeImageIndex === idx }"
                        >
                            <img v-if="getImageUrl(img)" :src="getImageUrl(img)" :alt="'Thumbnail ' + (idx + 1)" />
                        </button>
                    </div>
                </div>

                <!-- Info Panel Kanan (Layout Bersih & Rapi) -->
                <div class="hero-info">
                    <!-- 1. Row Atas: Tag Katalog + Stat Pills (Rating & Dilihat) -->
                    <div class="info-top-row">
                        <span class="katalog-badge">KATALOG ENTREPRENEUR</span>
                        
                        <div class="flex items-center gap-2">
                            <div class="meta-pill rating-pill" title="Rating Produk">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <span>{{ product.avg_rating || "0.0" }}</span>
                            </div>
                            <div class="meta-pill views-pill" title="Jumlah Dilihat">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>{{ Number(product.views || 0).toLocaleString("id-ID") }}x</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Judul Produk -->
                    <h1 class="product-title">{{ product.nama_produk }}</h1>

                    <!-- 3. Section Harga Pas -->
                    <div class="price-section">
                        <span class="price-label">HARGA PAS</span>
                        <div class="price-value-wrap">
                            <span class="price-currency">Rp</span>
                            <span class="price-amount">{{ Number(product.harga || 0).toLocaleString("id-ID") }}</span>
                        </div>
                    </div>

                    <!-- 4. Kartu Informasi Penjual -->
                    <div class="seller-card">
                        <div class="seller-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <div class="seller-copy flex-1 min-w-0">
                            <span class="seller-label">NAMA TOKO USAHA</span>
                            <h3 class="seller-name truncate">{{ product.penjual?.nama_toko || product.penjual?.nama_lengkap || 'Toko SiswaMart' }}</h3>
                        </div>
                    </div>

                    <!-- 5. Tombol Aksi (CTA) -->
                    <div class="cta-area w-full flex flex-col">
                        <!-- JIKA TOKO TUTUP -->
                        <template v-if="isShopClosed">
                            <button
                                disabled
                                type="button"
                                class="shop-closed-btn w-full"
                            >
                                <svg class="w-4 h-4 shrink-0 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <span>Toko Sedang Tutup</span>
                            </button>

                            <p class="mt-2 text-center text-xs font-bold text-rose-800 bg-rose-100/90 border-2 border-[#362415] rounded-xl py-2 px-3 shadow-[2px_2px_0px_#362415] leading-relaxed">
                                ⚠️ Toko ini sedang tutup sementara dan belum menerima pesanan.
                            </p>
                        </template>

                        <!-- JIKA TOKO BUKA -->
                        <template v-else>
                            <a v-if="!isRestrictedUser" :href="whatsappLink" target="_blank" rel="noopener noreferrer" class="wa-button active-wa">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                Pesan via WhatsApp
                            </a>

                            <button v-else class="owner-lock-btn w-full" disabled>
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ isProductOwner ? 'Ini Produk Tokomu Sendiri' : 'Akses Khusus Pembeli' }}</span>
                            </button>

                            <p class="cta-note">
                                {{ isRestrictedUser ? "Fitur pemesanan WhatsApp khusus untuk Pembeli / Pengunjung umum." : "Pemesanan langsung terhubung ke nomor WhatsApp penjual." }}
                            </p>
                        </template>
                    </div>
                </div>
            </section>

            <!-- Moderasi cepat hanya untuk Admin -->
            <section v-if="isAdmin === true" class="mt-6 bg-amber-100 border-4 border-gray-900 rounded-2xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-700">Moderasi Admin</p>
                        <h2 class="mt-1 text-base font-black uppercase text-gray-900">Status Produk</h2>
                        <span
                            :class="{
                                'bg-amber-100 text-amber-900': product.status_moderasi === 'pending',
                                'bg-emerald-100 text-emerald-900': product.status_moderasi === 'disetujui',
                                'bg-rose-100 text-rose-900': product.status_moderasi === 'ditolak'
                            }"
                            class="mt-2 inline-block border-2 border-gray-900 px-3 py-1 text-[10px] font-black uppercase rounded-lg shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            {{ product.status_moderasi === 'disetujui' ? 'DISETUJUI' : (product.status_moderasi === 'ditolak' ? 'DITOLAK' : 'PENDING') }}
                        </span>
                    </div>

                    <div v-if="product.status_moderasi === 'pending'" class="flex flex-wrap gap-3">
                        <button @click="approveProduct" class="bg-emerald-400 border-2 border-gray-900 px-4 py-2.5 text-xs font-black uppercase text-gray-900 rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition hover:bg-emerald-300 active:scale-95">
                            Setujui
                        </button>
                        <button @click="rejectProduct" class="bg-rose-400 border-2 border-gray-900 px-4 py-2.5 text-xs font-black uppercase text-gray-900 rounded-xl shadow-[3px_3px_0px_0px_rgba(17,24,39,1)] transition hover:bg-rose-300 active:scale-95">
                            Tolak
                        </button>
                    </div>
                    <p v-else-if="product.status_moderasi === 'disetujui'" class="text-xs font-black uppercase text-emerald-900">
                        PRODUK INI TELAH DISETUJUI &amp; TAYANG PUBLIK.
                    </p>
                    <p v-else class="text-xs font-black uppercase text-rose-900">
                        PRODUK INI TELAH DITOLAK DARI KATALOG.
                    </p>
                </div>
            </section>

            <!-- Grid Konten Bawah -->
            <section class="content-grid">
                <div class="content-main">
                    <!-- Deskripsi Card -->
                    <article class="content-card reveal">
                        <div class="section-heading">
                            <div class="heading-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p>INFORMASI</p>
                                <h2>Deskripsi Produk</h2>
                            </div>
                        </div>
                        <div class="description-box">
                            <p>{{ product.deskripsi || "Penjual belum menambahkan deskripsi detail untuk produk ini." }}</p>
                        </div>
                    </article>

                    <!-- Ulasan Card -->
                    <article class="content-card reveal">
                        <div class="review-header">
                            <div class="section-heading no-margin">
                                <div class="heading-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p>TESTIMONI</p>
                                    <h2>Ulasan Pembeli</h2>
                                </div>
                            </div>
                            <span class="review-count">{{ product.ulasan ? product.ulasan.length : 0 }} ULASAN</span>
                        </div>

                        <div class="reviews-list">
                            <div v-for="rev in product.ulasan || []" :key="rev.id_ulasan" class="review-item">
                                <div class="review-top">
                                    <div class="review-user">
                                        <div class="avatar">
                                            {{ (rev.nama_pengunjung || "S").charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <strong>{{ rev.nama_pengunjung || "Siswa Pembeli" }}</strong>
                                            <span>{{ rev.created_at || 'Baru saja' }}</span>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <svg viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ rev.rating }}/5
                                    </div>
                                </div>
                                <p class="review-comment">"{{ rev.komentar }}"</p>
                            </div>

                            <div v-if="!product.ulasan || product.ulasan.length === 0" class="empty-reviews">
                                <div class="star-icon">
                                    <svg class="w-6 h-6 text-amber-400 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                <strong>Belum ada ulasan</strong>
                                <span>Jadilah yang pertama memberikan pendapat tentang produk karya siswa ini!</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Kolom Samping (Cara Pemesanan & Form Ulasan) -->
                <aside class="content-side">
                    <!-- KARTU PANDUAN CARA PEMESANAN -->
                    <article class="content-card reveal how-to-order-card">
                        <div class="section-heading">
                            <div class="heading-icon order-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p>PANDUAN PRAKTIS</p>
                                <h2>Cara Pemesanan</h2>
                            </div>
                        </div>

                        <div class="steps-grid">
                            <div class="step-item">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h3>Klik Tombol WhatsApp</h3>
                                    <p>Tekan tombol hijau pesan langsung di atas untuk terhubung otomatis ke WhatsApp penjual.</p>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h3>Format Otomatis Terisi</h3>
                                    <p>Pesan format produk, harga, dan link halaman akan otomatis terketik di chat WhatsApp kamu.</p>
                                </div>
                            </div>

                            <div class="step-item">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h3>Atur Lokasi & COD</h3>
                                    <p>Konfirmasi ketersediaan, lalu sepakati waktu dan titik temu penyerahan (COD) di area sekolah.</p>
                                </div>
                            </div>
                        </div>

                        <div class="order-badge-footer">
                            <span>⚡ 100% Aman, Tanpa Potongan, Dukung Wirausaha Siswa!</span>
                        </div>
                    </article>

                    <!-- KARTU PERINGATAN (Khusus Penjual & Admin) -->
                    <div 
                        v-if="isRestrictedUser" 
                        class="bg-rose-50 border-2 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-5 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] flex items-start sm:items-center gap-4 sm:gap-5 transition-all"
                    >
                        <div class="w-12 h-12 sm:w-14 sm:h-14 bg-rose-400 border-2 sm:border-3 border-gray-900 rounded-xl sm:rounded-2xl flex items-center justify-center shrink-0 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]">
                            <svg class="w-7 h-7 sm:w-8 sm:h-8 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-block px-2.5 py-0.5 bg-rose-200 border border-gray-900 text-[10px] font-black uppercase tracking-wider text-rose-950 rounded-md shadow-[1px_1px_0px_0px_rgba(17,24,39,1)]">
                                    Akses Terbatas
                                </span>
                            </div>
                            <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-gray-900">
                                Akses Penjual & Admin
                            </h3>
                            <p class="text-xs font-bold text-gray-700 leading-relaxed">
                                Fitur ulasan hanya diperuntukkan bagi pembeli / pengunjung umum. Akun Penjual dan Admin tidak dapat memberikan ulasan produk.
                            </p>
                        </div>
                    </div>

                    <div v-else class="review-form-card reveal">
                        <div class="form-head">
                            <div class="form-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div class="form-title-group">
                                <p>PENDAPATMU</p>
                                <h2>Tulis Ulasan</h2>
                            </div>
                        </div>

                        <form @submit.prevent="submitReview" class="review-form">
                            <div class="field">
                                <label>Nama Kamu</label>
                                <input type="text" v-model="reviewForm.nama_pengunjung" placeholder="Nama pengulas..." required />
                            </div>

                            <div class="field">
                                <label>Rating Kualitas</label>
                                <div class="rating-box">
                                    <div class="stars">
                                        <button v-for="star in 5" :key="star" type="button" @click="reviewForm.rating = star" class="star-button">
                                            <svg viewBox="0 0 24 24" :class="star <= reviewForm.rating ? 'star-active' : 'star-inactive'">
                                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L3.69 9.901c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <strong>{{ reviewForm.rating }} / 5</strong>
                                    <span>{{ ratingLabels[reviewForm.rating] }}</span>
                                </div>
                            </div>

                            <div class="field">
                                <label>Ulasan / Saran</label>
                                <textarea v-model="reviewForm.komentar" rows="4" placeholder="Tuliskan ulasan kamu tentang rasa, kualitas, atau pelayanan..." required></textarea>
                            </div>

                            <button type="submit" :disabled="reviewForm.processing" class="submit-button">
                                <span>{{ reviewForm.processing ? "MENGIRIM..." : "KIRIM ULASAN" }}</span>
                            </button>
                        </form>
                    </div>
                </aside>
            </section>

            <!-- SEKSI REKOMENDASI PRODUK SERUPA -->
            <section v-if="relatedProducts && relatedProducts.length > 0" class="mt-12 sm:mt-16 space-y-4 sm:space-y-6">
                <!-- Section Header Banner -->
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 border-b-4 border-[#362415] pb-3 sm:pb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#F9C22E] border-2 border-[#362415] rounded-full text-[10px] sm:text-xs font-black uppercase tracking-wider text-[#362415] shadow-[2px_2px_0px_#362415] mb-2">
                            <svg class="w-3.5 h-3.5 text-[#F25C05]" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <span>Rekomendasi Terkait</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl md:text-3xl font-black text-[#362415] tracking-tight">
                            Sajian Kategori Sama
                        </h2>
                    </div>

                    <Link
                        v-if="product.kategori"
                        :href="route('catalog.categories', { category: product.kategori.id_kategori })"
                        class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-black text-[#362415] hover:text-[#F25C05] bg-white border-2 border-[#362415] px-3.5 py-1.5 sm:py-2 rounded-xl shadow-[3px_3px_0px_#362415] hover:shadow-[1px_1px_0px_#362415] hover:translate-x-[2px] hover:translate-y-[2px] transition-all self-start sm:self-auto"
                    >
                        <span>Lihat Semua {{ product.kategori.nama_kategori }}</span>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <!-- Grid Kartu Produk Serupa -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5 md:gap-6">
                    <article
                        v-for="item in relatedProducts"
                        :key="item.id_produk"
                        class="bg-[#F4F0E6] rounded-2xl sm:rounded-3xl border-2 sm:border-3 border-[#362415] shadow-[3px_3px_0px_#362415] sm:shadow-[5px_5px_0px_#362415] hover:-translate-y-1 hover:shadow-[6px_6px_0px_#362415] transition-all duration-200 flex flex-col group overflow-hidden"
                    >
                        <Link
                            :href="route('products.show', item.id_produk)"
                            class="block relative w-full aspect-[4/5] bg-[#EBE5D8] overflow-hidden border-b-2 sm:border-b-3 border-[#362415]"
                        >
                            <img
                                v-if="getRelatedProductImage(item)"
                                :src="getRelatedProductImage(item)"
                                :alt="item.nama_produk"
                                loading="lazy"
                                :class="[
                                    'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 ease-out',
                                    item.penjual?.status_toko === 'tutup' ? 'grayscale-[40%] opacity-85' : ''
                                ]"
                            />
                            <div
                                v-else
                                class="w-full h-full flex flex-col items-center justify-center text-[#362415] font-black text-[9px] sm:text-[10px] uppercase gap-1 bg-[#fffaf3] p-2 text-center"
                                :class="item.penjual?.status_toko === 'tutup' ? 'grayscale-[40%] opacity-85' : ''"
                            >
                                <div class="w-10 h-10 rounded-xl bg-amber-100 border-2 border-[#362415] flex items-center justify-center mb-0.5 shadow-[2px_2px_0px_#362415]">
                                    <svg class="w-5 h-5 text-[#362415]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span>Tanpa Foto</span>
                            </div>

                            <!-- BADGE TOKO TUTUP (Pojok Kiri Atas) -->
                            <div v-if="item.penjual?.status_toko === 'tutup'" class="absolute top-2 left-2 z-10">
                                <span class="bg-rose-500 text-white px-1.5 sm:px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider shadow-sm flex items-center gap-1 border border-[#362415]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>TOKO TUTUP</span>
                                </span>
                            </div>

                            <!-- LABEL NAMA TOKO (Pojok Kiri Bawah - Utamakan nama_toko) -->
                            <div
                                class="absolute -bottom-px left-0 bg-[#ea580c] text-white pl-2 sm:pl-3 pr-4 sm:pr-6 py-1 flex items-center gap-1 z-10 shadow-sm"
                                style="clip-path: polygon(0 0, 100% 0, 84% 100%, 0% 100%);"
                            >
                                <span class="font-extrabold text-[9px] sm:text-[11px] tracking-wide truncate max-w-[80px] sm:max-w-[110px]">
                                    {{ item.penjual?.nama_toko || item.penjual?.nama_lengkap || "Toko SiswaMart" }}
                                </span>
                            </div>

                            <div class="absolute top-2 right-2 z-10">
                                <span
                                    :class="item.status_stok === 'ready' ? 'bg-[#bbf7d0] text-[#14532d]' : 'bg-[#fde68a] text-[#78350f]'"
                                    class="px-2 py-0.5 rounded-full text-[8px] sm:text-[9px] font-black uppercase tracking-wider border border-[#362415] shadow-[1px_1px_0px_#362415] flex items-center gap-1"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="item.status_stok === 'ready' ? 'bg-[#16a34a] animate-pulse' : 'bg-[#d97706]'"
                                    ></span>
                                    <span>{{ item.status_stok === 'ready' ? 'Ready' : 'PO' }}</span>
                                </span>
                            </div>
                        </Link>

                        <div class="p-3 sm:p-4 flex flex-col flex-1 bg-white justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-1 text-[10px] sm:text-[11px] font-bold text-[#634C3C] mb-1.5">
                                    <span v-if="item.kategori" class="truncate font-black text-[#F25C05] uppercase tracking-wide">
                                        {{ item.kategori.nama_kategori }}
                                    </span>
                                    <span v-else class="text-stone-400">Umum</span>

                                    <span
                                        :class="{
                                            'bg-emerald-100 text-emerald-800 border-emerald-300': item.status_stok === 'ready',
                                            'bg-amber-100 text-amber-800 border-amber-300': item.status_stok === 'pre-order',
                                        }"
                                        class="px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] font-black uppercase border shrink-0"
                                    >
                                        {{ item.status_stok === 'ready' ? 'Ready' : 'PO' }}
                                    </span>
                                </div>

                                <Link :href="route('products.show', item.id_produk)" class="block">
                                    <h3 class="font-black text-[#362415] text-xs sm:text-sm md:text-base leading-snug group-hover:text-[#F25C05] transition-colors line-clamp-2">
                                        {{ item.nama_produk }}
                                    </h3>
                                </Link>
                            </div>

                            <div class="mt-3 pt-2.5 border-t border-[#362415]/15 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <span class="text-[#F25C05] font-black text-xs sm:text-base tracking-tight truncate">
                                    Rp {{ Number(item.harga || 0).toLocaleString('id-ID') }}
                                </span>

                                <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto">
                                    <!-- STATISTIK TOTAL VIEWS (DILIHAT) -->
                                    <div class="flex items-center gap-0.5 bg-[#F4F0E6] border border-[#362415]/30 px-1.5 py-0.5 rounded-lg text-[9px] sm:text-[10px] font-black text-[#362415]" title="Total Dilihat">
                                        <svg class="w-3 h-3 text-[#634C3C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>{{ Number(item.views || item.views_count || 0).toLocaleString('id-ID') }}</span>
                                    </div>

                                    <!-- STATISTIK RATING -->
                                    <div class="flex items-center gap-0.5 bg-[#F9C22E]/30 border border-[#362415]/30 px-1.5 py-0.5 rounded-lg text-[9px] sm:text-[10px] font-black text-[#362415]" title="Rating Produk">
                                        <svg class="w-3 h-3 text-amber-500 fill-amber-400" viewBox="0 0 24 24">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                        <span>{{ item.avg_rating || "0.0" }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* ==========================================================================
   1. MAIN PAGE & AMBIENT SETUP
   ========================================================================== */
.detail-page {
    position: relative;
    overflow: hidden;
    background: radial-gradient(circle at 10% 10%, rgba(251, 191, 36, 0.18), transparent 25rem),
                radial-gradient(circle at 90% 50%, rgba(249, 115, 22, 0.14), transparent 28rem),
                #f7f5ef;
}
.ambient-grid {
    position: fixed; inset: 0; pointer-events: none; opacity: 0.3;
    background-image: linear-gradient(rgba(17, 24, 39, 0.05) 1px, transparent 1px),
                      linear-gradient(90deg, rgba(17, 24, 39, 0.05) 1px, transparent 1px);
    background-size: 36px 36px;
}
.ambient-orb {
    position: fixed; width: 280px; height: 280px; border: 3px solid rgba(17, 24, 39, 0.06);
    border-radius: 999px; pointer-events: none; animation: drift 12s ease-in-out infinite alternate;
}
.orb-one { top: 15%; left: -140px; background: rgba(251, 191, 36, 0.15); }
.orb-two { right: -140px; top: 60%; background: rgba(249, 115, 22, 0.12); animation-delay: -5s; }

/* ==========================================================================
   2. NAVBAR STYLES
   ========================================================================== */
.site-nav {
    background: rgba(251, 191, 36, 0.95);
    border-bottom: 4px solid #362415;
    backdrop-filter: blur(10px);
}
.brand { color: #362415; text-decoration: none; font-size: 1.5rem; font-weight: 1000; letter-spacing: -0.05em; }
.brand b { color: #F25C05; }

.share-btn, .dash-btn, .login-btn {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem;
    border: 2.5px solid #362415; border-radius: 0.8rem; background: #fff; color: #362415;
    font-size: 0.72rem; font-weight: 900; text-transform: uppercase; text-decoration: none;
    box-shadow: 3px 3px 0 #362415; transition: all 0.15s ease; cursor: pointer;
}
.share-btn:hover, .dash-btn:hover, .login-btn:hover { transform: translate(-2px, -2px); box-shadow: 5px 5px 0 #362415; }
.dash-btn { background: #F9C22E; }
.login-btn { background: #F25C05; color: #fff; }

/* ==========================================================================
   3. HERO CARD (MAIN PRODUCT DISPLAY)
   ========================================================================== */
.hero-card {
    display: grid;
    grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
    background: #ffffff;
    border: 3.5px solid #362415;
    border-radius: 24px;
    box-shadow: 6px 6px 0px #362415;
    overflow: hidden;
}

/* Left Image Panel */
.hero-image-panel {
    padding: 20px;
    background: #FAF8F5;
    border-right: 3.5px solid #362415;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.status-chip, .category-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 0.4rem 0.7rem;
    border: 2px solid #362415; border-radius: 10px; font-size: 0.62rem; font-weight: 900;
    text-transform: uppercase; box-shadow: 2px 2px 0 #362415;
}
.status-ready { background: #bbf7d0; color: #14532d; }
.status-preorder { background: #fde68a; color: #78350f; }
.status-empty { background: #fecdd3; color: #881337; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; border: 1px solid #362415; }
.category-chip { background: #ffedd5; color: #9a3412; }

/* Main Image & Slider */
.main-image-wrap {
    height: 380px; position: relative; display: flex; align-items: center; justify-content: center;
    overflow: hidden; border: 3px solid #362415; border-radius: 18px; background: #e7e5e4;
}
.image-pattern {
    position: absolute; inset: 0; opacity: 0.25;
    background-image: radial-gradient(#362415 1px, transparent 1px); background-size: 16px 16px;
}
.main-product-image {
    position: relative; z-index: 1; width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.4s ease;
}
.main-image-wrap:hover .main-product-image { transform: scale(1.03); }

/* Gallery Thumbnails */
.gallery-thumbnails {
    display: flex; gap: 8px; margin-top: 12px; overflow-x: auto; padding-bottom: 4px;
}
.thumb-item {
    width: 60px; height: 60px; flex-shrink: 0;
    border: 2.5px solid #362415; border-radius: 10px; overflow: hidden;
    background: #fff; cursor: pointer; opacity: 0.6; transition: all 0.2s ease;
    padding: 0; box-shadow: 2px 2px 0 #362415;
}
.thumb-item img { width: 100%; height: 100%; object-fit: cover; }
.thumb-item:hover { opacity: 0.9; }
.thumb-active { opacity: 1; border-color: #F25C05; background: #F25C05; transform: translateY(-2px); box-shadow: 3px 3px 0 #362415; }

/* Right Info Panel */
.hero-info { 
    padding: 26px 28px; 
    display: flex; 
    flex-direction: column; 
    justify-content: space-between; 
    gap: 16px;
    background: #ffffff;
}

/* Header Info Row (Badges & Pills) */
.info-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.katalog-badge {
    font-size: 0.62rem;
    font-weight: 1000;
    letter-spacing: 0.12em;
    color: #F25C05;
    text-transform: uppercase;
}

.meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 9px;
    border: 2px solid #362415;
    border-radius: 10px;
    font-size: 0.68rem;
    font-weight: 900;
    box-shadow: 2px 2px 0px #362415;
}

.rating-pill { background: #FEF3C7; color: #362415; }
.rating-pill svg { width: 13px; height: 13px; color: #F59E0B; }

.views-pill { background: #FCFAF5; color: #362415; }
.views-pill svg { width: 13px; height: 13px; color: #F25C05; }

.product-title { 
    margin: 0; 
    font-size: clamp(1.6rem, 2.5vw, 2.3rem); 
    line-height: 1.1; 
    font-weight: 1000; 
    text-transform: uppercase; 
    color: #362415;
    letter-spacing: -0.02em;
}

/* Price Section */
.price-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: #FFF7ED;
    border: 2.5px solid #362415;
    border-radius: 16px;
    box-shadow: 3.5px 3.5px 0px #362415;
}

.price-label {
    font-size: 0.6rem;
    font-weight: 1000;
    color: #9A3412;
    letter-spacing: 0.08em;
}

.price-value-wrap {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.price-currency {
    font-size: 0.85rem;
    font-weight: 1000;
    color: #F25C05;
}

.price-amount {
    font-size: clamp(1.5rem, 2.4vw, 2rem);
    font-weight: 1000;
    color: #F25C05;
    line-height: 1;
}

/* Seller Card */
.seller-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #FCFAF5;
    border: 2.5px solid #362415;
    border-radius: 16px;
    box-shadow: 3.5px 3.5px 0px #362415;
}

.seller-icon {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    background: #F9C22E;
    border: 2px solid #362415;
    border-radius: 12px;
    flex-shrink: 0;
    box-shadow: 2px 2px 0px #362415;
}
.seller-icon svg { width: 20px; height: 20px; stroke: #362415; }

.seller-copy { display: flex; flex-direction: column; gap: 1px; }
.seller-label { color: #8C7A6B; font-size: 0.52rem; font-weight: 900; letter-spacing: 0.08em; }
.seller-name { margin: 0; font-size: 0.85rem; font-weight: 1000; text-transform: uppercase; color: #362415; }

/* CTA Buttons & Lock Button */
.cta-area { display: flex; flex-direction: column; gap: 6px; }

.wa-button {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 13px; border: 2.5px solid #362415; border-radius: 14px; font-size: 0.75rem;
    font-weight: 1000; text-transform: uppercase; box-shadow: 3.5px 3.5px 0px #362415; text-decoration: none;
}
.active-wa { background: #25D366; color: #ffffff; transition: all 0.15s ease; }
.active-wa:hover { background: #20BA5A; transform: translate(-1px, -1px); box-shadow: 5px 5px 0px #362415; }

.owner-lock-btn {
    width: 100%; padding: 13px; background: #FDE68A; border: 2.5px solid #362415; border-radius: 14px;
    font-size: 0.75rem; font-weight: 1000; text-transform: uppercase; color: #362415;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: 3.5px 3.5px 0px #362415; cursor: not-allowed; opacity: 0.95;
}

.shop-closed-btn {
    width: 100%; padding: 13px; background: #d1d5db; border: 2.5px solid #362415; border-radius: 14px;
    font-size: 0.75rem; font-weight: 1000; text-transform: uppercase; color: #374151;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 3.5px 3.5px 0px #362415; cursor: not-allowed;
}

.cta-note { margin: 2px 0 0; text-align: center; color: #8C7A6B; font-size: 0.58rem; font-weight: 700; }

/* ==========================================================================
   4. CONTENT GRID LOWER (DESKRIPSI, ULASAN, PANDUAN)
   ========================================================================== */
.content-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(300px, 0.9fr); gap: 20px; margin-top: 24px; }
.content-main, .content-side { display: flex; flex-direction: column; gap: 20px; }
.content-card, .review-form-card {
    background: #fff; border: 4px solid #362415; border-radius: 20px; padding: 20px; box-shadow: 6px 6px 0 #362415;
}

.section-heading { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.section-heading.no-margin { margin-bottom: 0; }
.heading-icon {
    width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
    background: #F9C22E; border: 2.5px solid #362415; border-radius: 10px; box-shadow: 2px 2px 0 #362415; flex-shrink: 0;
}
.section-heading p { margin: 0; color: #F25C05; font-size: 0.55rem; font-weight: 1000; letter-spacing: 0.1em; }
.section-heading h2 { margin: 0; font-size: 1.1rem; font-weight: 1000; text-transform: uppercase; color: #362415; }

.description-box { padding: 16px; background: #FCFAF5; border: 2.5px dashed #362415/30; border-radius: 14px; }
.description-box p { margin: 0; color: #362415; font-size: 0.85rem; line-height: 1.6; font-weight: 600; white-space: pre-line; }

.review-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e7e5e4; padding-bottom: 12px; margin-bottom: 14px; }
.review-count { padding: 4px 8px; background: #F25C05; color: #fff; border: 2px solid #362415; border-radius: 6px; font-size: 0.55rem; font-weight: 1000; }

.review-item { padding: 12px; background: #FCFAF5; border: 2px solid #362415; border-radius: 12px; box-shadow: 3px 3px 0 #362415; margin-bottom: 10px; }
.review-top { display: flex; align-items: center; justify-content: space-between; }
.review-user { display: flex; align-items: center; gap: 8px; }
.avatar { width: 32px; height: 32px; display: grid; place-items: center; background: #fdba74; border: 2px solid #362415; border-radius: 50%; font-weight: 1000; font-size: 0.75rem; color: #362415; }
.review-user strong { display: block; font-size: 0.68rem; font-weight: 1000; text-transform: uppercase; color: #362415; }
.review-user span { font-size: 0.5rem; color: #8C7A6B; font-weight: 700; }
.review-rating { display: flex; align-items: center; gap: 3px; padding: 4px 6px; background: #FEF3C7; border: 1.5px solid #362415; border-radius: 6px; font-size: 0.6rem; font-weight: 1000; color: #362415; }
.review-rating svg { width: 12px; height: 12px; color: #F59E0B; }
.review-comment { margin: 8px 0 0; padding-top: 6px; border-top: 1px solid #e7e5e4; font-size: 0.75rem; color: #362415; font-weight: 600; }

.empty-reviews { padding: 24px 10px; text-align: center; color: #8C7A6B; }
.empty-reviews .star-icon { font-size: 1.8rem; color: #F25C05; line-height: 1; }
.empty-reviews strong { display: block; margin-top: 4px; font-size: 0.75rem; text-transform: uppercase; color: #362415; }
.empty-reviews span { font-size: 0.6rem; }

/* How To Order Card */
.how-to-order-card {
    background: linear-gradient(135deg, #fffbeb 0%, #fff 100%);
    border: 4px solid #362415;
}
.order-icon {
    background: #F25C05 !important; 
    color: #fff !important;    
}
.steps-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 14px;
}
.step-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px;
    background: #fff;
    border: 2px solid #362415;
    border-radius: 10px;
    box-shadow: 2.5px 2.5px 0 #362415;
    transition: transform 0.15s ease;
}
.step-item:hover {
    transform: translate(-2px, -2px);
    box-shadow: 4px 4px 0 #362415;
}
.step-number {
    width: 24px;
    height: 24px;
    min-width: 24px;
    display: grid;
    place-items: center;
    background: #F9C22E;
    border: 2px solid #362415;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 1000;
    color: #362415;
}
.step-content h3 {
    margin: 0 0 2px 0;
    font-size: 0.72rem;
    font-weight: 1000;
    text-transform: uppercase;
    color: #362415;
}
.step-content p {
    margin: 0;
    font-size: 0.65rem;
    color: #57534e;
    font-weight: 600;
    line-height: 1.35;
}
.order-badge-footer {
    margin-top: 12px;
    padding: 7px 8px;
    background: #ffedd5;
    border: 2px solid #362415;
    border-radius: 8px;
    text-align: center;
    font-size: 0.58rem;
    font-weight: 1000;
    color: #c2410c;
}

/* Form Ulasan Side Panel */
.content-side { position: sticky; top: 80px; }

.form-head {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    margin-bottom: 18px !important;
    padding-bottom: 14px !important;
    border-bottom: 3px solid #362415 !important;
}

.form-icon {
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #F9C22E !important;
    border: 2.5px solid #362415 !important;
    border-radius: 12px !important;
    box-shadow: 2.5px 2.5px 0 #362415 !important;
    flex-shrink: 0 !important;
}
.form-icon svg { width: 22px !important; height: 22px !important; stroke: #362415; }

.form-title-group {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}
.form-title-group p {
    margin: 0 !important;
    color: #F25C05 !important;
    font-size: 0.6rem !important;
    font-weight: 1000 !important;
    letter-spacing: 0.12em !important;
    line-height: 1.1 !important;
}
.form-title-group h2 {
    margin: 2px 0 0 0 !important;
    font-size: 1.15rem !important;
    font-weight: 1000 !important;
    text-transform: uppercase !important;
    color: #362415 !important;
    line-height: 1.1 !important;
}

.review-form { display: flex; flex-direction: column; gap: 12px; }
.field label { display: block; margin-bottom: 4px; font-size: 0.55rem; font-weight: 1000; text-transform: uppercase; color: #362415; }
.field input, .field textarea {
    width: 100%; box-sizing: border-box; background: #FCFAF5; border: 2px solid #362415;
    border-radius: 8px; padding: 8px 10px; font-size: 0.75rem; font-weight: 700; outline: none; color: #362415;
}
.field input:focus, .field textarea:focus { border-color: #F25C05; background: #fff; box-shadow: 2px 2px 0 #F25C05; }

.rating-box { padding: 8px; background: #FCFAF5; border: 2px solid #362415; border-radius: 8px; text-align: center; }
.stars { display: flex; justify-content: center; gap: 2px; }
.star-button { background: none; border: none; cursor: pointer; padding: 2px; transition: transform 0.1s ease; }
.star-button:hover { transform: scale(1.15); }
.star-button svg { width: 22px; height: 22px; stroke: #362415; stroke-width: 1.5; }
.star-active { fill: #F9C22E; }
.star-inactive { fill: #e7e5e4; }
.rating-box strong { display: block; font-size: 0.7rem; color: #F25C05; margin-top: 2px; }
.rating-box span { font-size: 0.5rem; font-weight: 900; text-transform: uppercase; color: #8C7A6B; }

.submit-button {
    width: 100%; padding: 11px; background: #F25C05; color: #ffffff; border: 2.5px solid #362415;
    border-radius: 10px; box-shadow: 3px 3px 0 #362415; font-size: 0.65rem; font-weight: 1000; cursor: pointer;
}
.submit-button:hover { background: #fb923c; transform: translate(-1px, -1px); box-shadow: 5px 5px 0 #362415; }

/* ==========================================================================
   5. ANIMATIONS & RESPONSIVE BREAKPOINTS
   ========================================================================== */
.reveal { animation: rise-in 0.4s ease both; }
@keyframes rise-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
@keyframes drift { from { transform: translate3d(0, 0, 0); } to { transform: translate3d(20px, -15px, 0); } }

@media (max-width: 900px) {
    .hero-card { grid-template-columns: 1fr; }
    .hero-image-panel { border-right: none; border-bottom: 3.5px solid #362415; }
    .content-grid { grid-template-columns: 1fr; }
    .content-side { position: static; }
}

@media (max-width: 768px) {
    .detail-page { padding-bottom: 2rem; }
    .brand { font-size: 1.25rem; }
    .share-btn, .dash-btn, .login-btn {
        padding: 0.35rem 0.65rem;
        font-size: 0.65rem;
        border-radius: 0.6rem;
        box-shadow: 2px 2px 0 #362415;
    }
    .hero-image-panel { padding: 14px; }
    .category-list { display: flex; flex-wrap: wrap; gap: 4px; }
    .main-image-wrap { height: 260px; border-radius: 14px; }
    .hero-info { padding: 18px 16px; gap: 14px; }
    .product-title { font-size: 1.4rem; line-height: 1.1; }
    .price-amount { font-size: 1.6rem; }
    .price-section { padding: 10px 14px; }
    .seller-card { padding: 10px 12px; }
    .seller-icon { width: 36px; height: 36px; }
    .seller-icon svg { width: 18px; height: 18px; }
    .wa-button, .owner-lock-btn, .shop-closed-btn { padding: 11px; font-size: 0.68rem; border-radius: 12px; }
    .content-grid { margin-top: 16px; gap: 16px; }
    .content-card, .review-form-card { padding: 16px; border-radius: 16px; box-shadow: 4px 4px 0 #362415; }
    .heading-icon { width: 34px; height: 34px; }
    .heading-icon svg { width: 18px; height: 18px; }
    .section-heading h2 { font-size: 0.95rem; }
    .form-head { margin-bottom: 12px !important; padding-bottom: 10px !important; }
    .form-icon { width: 36px !important; height: 36px !important; min-width: 36px !important; }
    .form-icon svg { width: 18px !important; height: 18px !important; }
    .form-title-group h2 { font-size: 1rem !important; }
    .field input, .field textarea { font-size: 0.8rem; padding: 7px 9px; }
}
</style>
