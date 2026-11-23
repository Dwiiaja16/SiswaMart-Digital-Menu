<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
const page = usePage();
const phone = computed(() => String(page.props.app?.cs_whatsapp || '').replace(/\D/g, ''));
const href = computed(() => `https://wa.me/${phone.value}?text=${encodeURIComponent('Halo Admin SiswaMart SMKN 11 Bandung, saya ingin bertanya / membutuhkan bantuan terkait layanan SiswaMart.')}`);
</script>
<template><a v-if="phone" :href="href" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp CS SiswaMart" title="Hubungi CS SiswaMart" class="fixed bottom-5 right-5 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-b from-[#2EE574] to-[#1EBE5D] text-white ring-1 ring-black/10 shadow-[0_10px_25px_-5px_rgba(37,211,102,0.55),0_4px_10px_-2px_rgba(0,0,0,0.12)] transition-all duration-200 hover:-translate-y-0.5 hover:brightness-105"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.9 11.9 0 0 0 12.05 0C5.48 0 .13 5.35.13 11.92c0 2.1.55 4.15 1.6 5.95L0 24l6.3-1.65a11.92 11.92 0 0 0 5.74 1.46h.01c6.57 0 11.92-5.35 11.92-11.92 0-3.18-1.24-6.16-3.45-8.41ZM12.05 21.8a9.9 9.9 0 0 1-5.05-1.38l-.36-.21-3.74.98 1-3.65-.24-.38a9.9 9.9 0 1 1 8.39 4.64Z" /></svg></a></template>
