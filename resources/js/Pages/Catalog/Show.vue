<script setup>
import { computed, ref } from "vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({
    product: Object,
    isOwner: Boolean,
    hasReviewed: Boolean,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const isProductOwner = computed(() => {
    if (props.isOwner) return true;
    const userShopId = authUser.value?.shop?.id;
    const productShopId = props.product?.shop?.id || props.product?.shop_id;
    return userShopId && productShopId ? Number(userShopId) === Number(productShopId) : false;
});

const reviewForm = useForm({
    reviewer_name: authUser.value ? authUser.value.name || authUser.value.username : "Pengunjung",
    rating: 5,
    comment: "",
});

const ratingLabels = {
    1: "Kurang Pas",
    2: "Biasa Aja",
    3: "Cukup Enak",
    4: "Enak Banget",
    5: "Sangat Rekomendasi! 🔥",
};

const submitReview = () => {
    if (isProductOwner.value) {
        alert("Kamu tidak dapat memberikan ulasan pada produk sendiri!");
        return;
    }
    reviewForm.post(route("reviews.store", props.product.id), {
        preserveScroll: true,
        onSuccess: () => {
            reviewForm.reset("comment");
            reviewForm.rating = 5;
        },
    });
};

const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    if (imagePath.startsWith("http")) return imagePath;
    return imagePath.startsWith("/") ? imagePath : `/${imagePath}`;
};

const whatsappLink = computed(() => {
    let phone = props.product.shop?.user?.whatsapp_number || props.product.shop?.whatsapp_number || '081234567890';
    phone = String(phone).replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) phone = '62' + phone.slice(1);

    const shopName = props.product.shop?.name || 'Lapak Siswa';
    const productName = props.product.name;
    const price = Number(props.product.price).toLocaleString('id-ID');
    const currentUrl = typeof window !== 'undefined' ? window.location.href : '';

    const messageLines = [
        `Halo ${shopName}, saya mau pesan produk ini via SiswaMart:`,
        ``,
        `📌 Produk: ${productName}`,
        `💰 Harga: Rp ${price}`,
        `🔗 Tautan: ${currentUrl}`,
        ``,
        `Apakah produk ini ready? Terima kasih!`
    ];

    const encodedMessage = messageLines.map(line => encodeURIComponent(line)).join('%0A');
    return `https://wa.me/${phone}?text=${encodedMessage}`;
});

const copied = ref(false);
const copyLink = async () => {
    const url = window.location.href;
    if (navigator.share) {
        try {
            await navigator.share({ title: props.product?.name || 'SiswaMart', url });
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
</script>

<template>
    <Head :title="product.name + ' - SiswaMart'" />

    <div class="detail-page min-h-screen text-gray-900 font-sans pb-24 selection:bg-orange-500 selection:text-white">
        <div class="ambient-grid" aria-hidden="true"></div>
        <div class="ambient-orb orb-one" aria-hidden="true"></div>
        <div class="ambient-orb orb-two" aria-hidden="true"></div>

        <!-- Sticky Header Navbar -->
        <header class="site-nav sticky top-0 z-50">
            <div class="nav-inner max-w-6xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('catalog.index')" class="brand flex items-center">
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
                <!-- Panel Image -->
                <div class="hero-image-panel">
                    <div class="image-topbar">
                        <span :class="product.stock_status === 'ready' ? 'status-ready' : product.stock_status === 'pre_order' ? 'status-preorder' : 'status-empty'" class="status-chip">
                            <span class="status-dot"></span>
                            {{ product.stock_status === "ready" ? "Ready Stock" : product.stock_status === "pre_order" ? "Pre-Order" : "Habis" }}
                        </span>

                        <div class="category-list">
                            <template v-if="product.categories && product.categories.length > 0">
                                <span v-for="cat in product.categories" :key="cat.id" class="category-chip">
                                    {{ cat.name }}
                                </span>
                            </template>
                            <span v-else class="category-chip">
                                {{ product.category?.name || product.category || "Kreatif" }}
                            </span>
                        </div>
                    </div>

                    <div class="main-image-wrap group">
                        <div class="image-pattern" aria-hidden="true"></div>
                        <img v-if="product.image" :src="getImageUrl(product.image)" :alt="product.name" loading="lazy" class="main-product-image" />
                        <div v-else class="image-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            <span>Belum Ada Foto</span>
                        </div>
                        <div class="image-sticker">SMKN 11<br />KARYA SISWA★</div>
                    </div>
                </div>

                <!-- Info Panel -->
                <div class="hero-info">
                    <div class="badge-row">
                        <span class="product-label">KATALOG ENTREPRENEUR</span>
                        <div class="rating-badge-top">
                            <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <span>4.9 / 5.0</span>
                        </div>
                    </div>

                    <h1 class="product-title">{{ product.name }}</h1>

                    <div class="price-box">
                        <div>
                            <p class="price-label">HARGA SATUAN</p>
                            <p class="price">
                                Rp {{ Number(product.price).toLocaleString("id-ID") }}
                            </p>
                        </div>
                    </div>

                    <!-- Lapak Info -->
                    <div class="seller-card">
                        <div class="seller-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <div class="seller-copy">
                            <p class="seller-label">PEMILIK LAPAK</p>
                            <h2>{{ product.shop?.name || "Lapak Entrepreneur Siswa" }}</h2>
                            <p :class="product.shop?.is_open !== false ? 'shop-open' : 'shop-closed'">
                                <span></span>
                                Lapak {{ product.shop?.is_open !== false ? "Buka (Siap Melayani)" : "Sedang Tutup" }}
                            </p>
                        </div>
                    </div>

                    <!-- Call To Action -->
                    <div class="cta-area">
                        <a v-if="!isProductOwner && product.shop?.is_open !== false" :href="whatsappLink" target="_blank" rel="noopener noreferrer" class="wa-button active-wa">
                            <svg viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            Pesan Langsung via WhatsApp
                        </a>

                        <button v-else-if="isProductOwner" disabled class="owner-lock-btn">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>Ini Produk Lapakmu Sendiri</span>
                        </button>

                        <button v-else disabled class="wa-button disabled-wa">
                            Lapak Sedang Tutup
                        </button>

                        <p class="cta-note">
                            {{ isProductOwner ? "Kelola stok dan ulasan produk ini di Dashboard Penjual." : "Pemesanan langsung terhubung ke nomor WhatsApp resmi penjual." }}
                        </p>
                    </div>
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
                            <p>{{ product.description || "Penjual belum menambahkan deskripsi detail untuk produk ini." }}</p>
                        </div>
                    </article>

                    <!-- Ulasan Card -->
                    <article class="content-card reveal">
                        <div class="review-header">
                            <div class="section-heading no-margin">
                                <div class="heading-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                </div>
                                <div>
                                    <p>TESTIMONI</p>
                                    <h2>Ulasan Pembeli</h2>
                                </div>
                            </div>
                            <span class="review-count">{{ product.reviews ? product.reviews.length : 0 }} ULASAN</span>
                        </div>

                        <div class="reviews-list">
                            <div v-for="rev in product.reviews || []" :key="rev.id" class="review-item">
                                <div class="review-top">
                                    <div class="review-user">
                                        <div class="avatar">
                                            {{ (rev.user?.name || rev.reviewer_name || "S").charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <strong>{{ rev.user?.name || rev.reviewer_name || "Siswa Pembeli" }}</strong>
                                            <span>Siswa SMKN 11</span>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        {{ rev.rating }}/5
                                    </div>
                                </div>
                                <p class="review-comment">“{{ rev.comment }}”</p>
                            </div>

                            <div v-if="!product.reviews || product.reviews.length === 0" class="empty-reviews">
                                <div class="star-icon">★</div>
                                <strong>Belum ada ulasan</strong>
                                <span>Jadilah yang pertama memberikan pendapat tentang produk karya siswa ini!</span>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Form Ulasan Side Panel -->
                <aside class="content-side">
                    <div v-if="isProductOwner" class="notice-card owner-notice reveal">
                        <div class="notice-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p>AKSES PEMILIK</p>
                            <h3>Lapak Kamu</h3>
                            <span>Kamu tidak dapat mengisi ulasan pada produk di toko kamu sendiri.</span>
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
                                <input type="text" v-model="reviewForm.reviewer_name" placeholder="Nama pengulas..." required />
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
                                <textarea v-model="reviewForm.comment" rows="4" placeholder="Tuliskan ulasan kamu tentang rasa, kualitas, atau pelayanan..." required></textarea>
                            </div>

                            <button type="submit" :disabled="reviewForm.processing" class="submit-button">
                                <span>{{ reviewForm.processing ? "MENGIRIM..." : "KIRIM ULASAN" }}</span>
                            </button>
                        </form>
                    </div>
                </aside>
            </section>
        </main>
    </div>
</template>

<style scoped>
/* =========================================================
   SISWAMART — DETAIL PRODUCT PAGE STYLING
   ========================================================= */

/* Main Page Setup */
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

/* Navbar Improvements */
.site-nav {
    background: rgba(251, 191, 36, 0.95);
    border-bottom: 4px solid #111827;
    backdrop-filter: blur(10px);
}
.brand { color: #111827; text-decoration: none; font-size: 1.5rem; font-weight: 1000; letter-spacing: -0.05em; }
.brand b { color: #ea580c; }

.back-button, .share-btn, .dash-btn, .login-btn {
    display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem;
    border: 2px solid #111827; border-radius: 0.8rem; background: #fff; color: #111827;
    font-size: 0.72rem; font-weight: 900; text-transform: uppercase; text-decoration: none;
    box-shadow: 3px 3px 0 #111827; transition: all 0.15s ease; cursor: pointer;
}
.back-button:hover, .share-btn:hover { transform: translate(-2px, -2px); box-shadow: 5px 5px 0 #111827; }
.dash-btn { background: #fbbf24; }
.login-btn { background: #f97316; color: #fff; }

/* Hero Card Layout */
.hero-card {
    display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(340px, 0.95fr);
    background: #fff; border: 4px solid #111827; border-radius: 24px;
    box-shadow: 8px 8px 0 #111827; overflow: hidden;
}
.hero-image-panel {
    padding: 20px; background: linear-gradient(145deg, #fafaf9 0%, #fff7ed 100%);
    border-right: 4px solid #111827;
}
.image-topbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 14px; }
.status-chip, .category-chip {
    display: inline-flex; align-items: center; gap: 6px; padding: 0.4rem 0.7rem;
    border: 2px solid #111827; border-radius: 10px; font-size: 0.62rem; font-weight: 900;
    text-transform: uppercase; box-shadow: 2px 2px 0 #111827;
}
.status-ready { background: #bbf7d0; color: #14532d; }
.status-preorder { background: #fde68a; color: #78350f; }
.status-empty { background: #fecdd3; color: #881337; }
.status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; border: 1px solid #111827; }
.category-chip { background: #ffedd5; color: #9a3412; }

.main-image-wrap {
    height: 380px; position: relative; display: flex; align-items: center; justify-content: center;
    overflow: hidden; border: 3px solid #111827; border-radius: 18px; background: #e7e5e4;
}
.image-pattern {
    position: absolute; inset: 0; opacity: 0.25;
    background-image: radial-gradient(#111827 1px, transparent 1px); background-size: 16px 16px;
}
.main-product-image {
    position: relative; z-index: 1; width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.4s ease;
}
.main-image-wrap:hover .main-product-image { transform: scale(1.05); }
.image-sticker {
    position: absolute; z-index: 2; left: 14px; bottom: 14px; padding: 0.5rem 0.7rem;
    background: #fbbf24; border: 2.5px solid #111827; border-radius: 10px;
    font-size: 0.6rem; font-weight: 1000; transform: rotate(-4deg); box-shadow: 3px 3px 0 #111827;
}

/* Info Section Setup */
.hero-info { padding: 26px; display: flex; flex-direction: column; gap: 12px; justify-content: center; }
.badge-row { display: flex; align-items: center; justify-content: space-between; }
.product-label { font-size: 0.65rem; font-weight: 1000; letter-spacing: 0.15em; color: #ea580c; }
.rating-badge-top {
    display: flex; align-items: center; gap: 4px; padding: 3px 8px; background: #fef3c7;
    border: 2px solid #111827; border-radius: 8px; font-size: 0.65rem; font-weight: 1000;
}
.rating-badge-top svg { width: 14px; height: 14px; color: #f59e0b; }

.product-title { margin: 0; font-size: clamp(1.8rem, 3.2vw, 2.6rem); line-height: 1; font-weight: 1000; text-transform: uppercase; }

.price-box {
    display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem;
    padding: 12px 0; border-top: 3px solid #111827; border-bottom: 3px solid #111827;
}
.price-label { margin: 0 0 2px; font-size: 0.58rem; font-weight: 900; color: #78716c; }
.price { margin: 0; font-size: clamp(1.6rem, 2.8vw, 2.2rem); font-weight: 1000; color: #ea580c; }
.unit-badge { padding: 0.4rem 0.6rem; background: #111827; color: #fff; border-radius: 6px; font-size: 0.55rem; font-weight: 900; }

.seller-card {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px; background: #fafaf9;
    border: 3px solid #111827; border-radius: 14px; box-shadow: 4px 4px 0 #111827;
}
.seller-icon {
    width: 42px; height: 42px; display: grid; place-items: center; background: #fb923c;
    border: 2px solid #111827; border-radius: 10px; flex-shrink: 0;
}
.seller-icon svg { width: 22px; height: 22px; }
.seller-label { margin: 0; color: #78716c; font-size: 0.52rem; font-weight: 900; }
.seller-copy h2 { margin: 0; font-size: 0.85rem; font-weight: 1000; text-transform: uppercase; }
.shop-open { color: #059669; font-size: 0.6rem; font-weight: 900; display: flex; align-items: center; gap: 4px; }
.shop-open span { width: 6px; height: 6px; background: #10b981; border-radius: 50%; }

/* Buttons WA & Disabled */
.wa-button {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 13px; border: 3px solid #111827; border-radius: 14px; font-size: 0.72rem;
    font-weight: 1000; text-transform: uppercase; box-shadow: 4px 4px 0 #111827; text-decoration: none;
}
.active-wa { background: #25d366; color: #111827; transition: all 0.15s ease; }
.active-wa:hover { background: #20ba5a; transform: translate(-2px, -2px); box-shadow: 6px 6px 0 #111827; }
.owner-lock-btn {
    width: 100%; padding: 12px; background: #fde68a; border: 3px solid #111827; border-radius: 14px;
    font-size: 0.7rem; font-weight: 1000; text-transform: uppercase; display: flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: 3px 3px 0 #111827; cursor: not-allowed;
}
.cta-note { margin: 6px 0 0; text-align: center; color: #78716c; font-size: 0.58rem; font-weight: 700; }

/* Content Grid Lower */
.content-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(300px, 0.9fr); gap: 20px; margin-top: 24px; }
.content-main, .content-side { display: flex; flex-direction: column; gap: 20px; }
.content-card, .review-form-card, .notice-card {
    background: #fff; border: 4px solid #111827; border-radius: 20px; padding: 20px; box-shadow: 6px 6px 0 #111827;
}

/* Section Headings */
.section-heading { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.section-heading.no-margin { margin-bottom: 0; }
.heading-icon {
    width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
    background: #fbbf24; border: 2.5px solid #111827; border-radius: 10px; box-shadow: 2px 2px 0 #111827; flex-shrink: 0;
}
.section-heading p { margin: 0; color: #ea580c; font-size: 0.55rem; font-weight: 1000; letter-spacing: 0.1em; }
.section-heading h2 { margin: 0; font-size: 1.1rem; font-weight: 1000; text-transform: uppercase; }

.description-box { padding: 16px; background: #fafaf9; border: 2.5px dashed #a8a29e; border-radius: 14px; }
.description-box p { margin: 0; color: #292524; font-size: 0.85rem; line-height: 1.6; font-weight: 600; white-space: pre-line; }

.review-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #e7e5e4; padding-bottom: 12px; margin-bottom: 14px; }
.review-count { padding: 4px 8px; background: #f97316; border: 2px solid #111827; border-radius: 6px; font-size: 0.55rem; font-weight: 1000; }

.review-item { padding: 12px; background: #fafaf9; border: 2px solid #111827; border-radius: 12px; box-shadow: 3px 3px 0 #111827; margin-bottom: 10px; }
.review-top { display: flex; align-items: center; justify-content: space-between; }
.review-user { display: flex; align-items: center; gap: 8px; }
.avatar { width: 32px; height: 32px; display: grid; place-items: center; background: #fdba74; border: 2px solid #111827; border-radius: 50%; font-weight: 1000; font-size: 0.75rem; }
.review-user strong { display: block; font-size: 0.68rem; font-weight: 1000; text-transform: uppercase; }
.review-user span { font-size: 0.5rem; color: #78716c; font-weight: 700; }
.review-rating { display: flex; align-items: center; gap: 3px; padding: 4px 6px; background: #fef3c7; border: 1.5px solid #111827; border-radius: 6px; font-size: 0.6rem; font-weight: 1000; }
.review-rating svg { width: 12px; height: 12px; color: #f59e0b; }
.review-comment { margin: 8px 0 0; padding-top: 6px; border-top: 1px solid #e7e5e4; font-size: 0.75rem; color: #44403c; font-weight: 600; }

.empty-reviews { padding: 24px 10px; text-align: center; color: #78716c; }
.empty-reviews .star-icon { font-size: 1.8rem; color: #f97316; line-height: 1; }
.empty-reviews strong { display: block; margin-top: 4px; font-size: 0.75rem; text-transform: uppercase; color: #111827; }
.empty-reviews span { font-size: 0.6rem; }

/* =========================================================
   FORM ULASAN SIDE PANEL (FIX NUMPUK / DEMPET)
   ========================================================= */
.content-side { position: sticky; top: 80px; }

.form-head {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    margin-bottom: 18px !important;
    padding-bottom: 14px !important;
    border-bottom: 3px solid #111827 !important;
}

.form-icon {
    width: 44px !important;
    height: 44px !important;
    min-width: 44px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    background: #fbbf24 !important;
    border: 2.5px solid #111827 !important;
    border-radius: 12px !important;
    box-shadow: 2.5px 2.5px 0 #111827 !important;
    flex-shrink: 0 !important;
}

.form-icon svg {
    width: 22px !important;
    height: 22px !important;
}

.form-title-group {
    display: flex !important;
    flex-direction: column !important;
    justify-content: center !important;
}

.form-title-group p {
    margin: 0 !important;
    color: #ea580c !important;
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
    color: #111827 !important;
    line-height: 1.1 !important;
}

.review-form { display: flex; flex-direction: column; gap: 12px; }
.field label { display: block; margin-bottom: 4px; font-size: 0.55rem; font-weight: 1000; text-transform: uppercase; }
.field input, .field textarea {
    width: 100%; box-sizing: border-box; background: #fafaf9; border: 2px solid #111827;
    border-radius: 8px; padding: 8px 10px; font-size: 0.75rem; font-weight: 700; outline: none;
}
.field input:focus, .field textarea:focus { border-color: #f97316; background: #fff; box-shadow: 2px 2px 0 #f97316; }

.rating-box { padding: 8px; background: #fafaf9; border: 2px solid #111827; border-radius: 8px; text-align: center; }
.stars { display: flex; justify-content: center; gap: 2px; }
.star-button { background: none; border: none; cursor: pointer; padding: 2px; transition: transform 0.1s ease; }
.star-button:hover { transform: scale(1.15); }
.star-button svg { width: 22px; height: 22px; stroke: #111827; stroke-width: 1.5; }
.star-active { fill: #fbbf24; }
.star-inactive { fill: #e7e5e4; }
.rating-box strong { display: block; font-size: 0.7rem; color: #ea580c; margin-top: 2px; }
.rating-box span { font-size: 0.5rem; font-weight: 900; text-transform: uppercase; color: #78716c; }

.submit-button {
    width: 100%; padding: 11px; background: #f97316; color: #111827; border: 2.5px solid #111827;
    border-radius: 10px; box-shadow: 3px 3px 0 #111827; font-size: 0.65rem; font-weight: 1000; cursor: pointer;
}
.submit-button:hover { background: #fb923c; transform: translate(-1px, -1px); box-shadow: 5px 5px 0 #111827; }

/* Animations & Responsive */
.reveal { animation: rise-in 0.4s ease both; }
@keyframes rise-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
@keyframes drift { from { transform: translate3d(0, 0, 0); } to { transform: translate3d(20px, -15px, 0); } }

@media (max-width: 900px) {
    .hero-card { grid-template-columns: 1fr; }
    .hero-image-panel { border-right: none; border-bottom: 4px solid #111827; }
    .content-grid { grid-template-columns: 1fr; }
    .content-side { position: static; }
}

/* Styling Tombol Navbar */
.back-button, .share-btn, .dash-btn, .login-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.45rem 0.9rem;
    border: 2px solid #111827;
    border-radius: 0.8rem;
    background: #fff;
    color: #111827;
    font-size: 0.72rem;
    font-weight: 900;
    text-transform: uppercase;
    text-decoration: none;
    box-shadow: 3px 3px 0 #111827;
    transition: all 0.15s ease;
    cursor: pointer;
}

/* Animasi Hover (Naik ke atas-kiri & bayangan meluas) */
.back-button:hover, 
.share-btn:hover, 
.dash-btn:hover, 
.login-btn:hover { 
    transform: translate(-2px, -2px); 
    box-shadow: 5px 5px 0 #111827; 
}

/* Animasi Click/Active (Mentok ke bawah-kanan) */
.back-button:active, 
.share-btn:active, 
.dash-btn:active, 
.login-btn:active { 
    transform: translate(1px, 1px); 
    box-shadow: 2px 2px 0 #111827; 
}

/* Warna Spesifik Tiap Tombol */
.dash-btn { 
    background: #fbbf24; 
}

.login-btn { 
    background: #f97316; 
    color: #111827; /* Atau #fff kalau mau teks putih */
}
</style>