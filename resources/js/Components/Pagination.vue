<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => null,
    },
});

const paginationLinks = computed(() => {
    if (props.links && props.links.length > 0) {
        return props.links;
    }
    if (props.meta && props.meta.links) {
        return props.meta.links;
    }
    return [];
});

const paginationInfo = computed(() => {
    const source = props.meta || (props.links && props.links.meta ? props.links.meta : null);
    if (!source) return null;
    return {
        from: source.from ?? null,
        to: source.to ?? null,
        total: source.total ?? null,
        currentPage: source.current_page ?? null,
        lastPage: source.last_page ?? null,
    };
});

const cleanLabel = (label) => {
    if (!label) return '';
    if (label.includes('&laquo;') || label.toLowerCase().includes('previous')) {
        return '« Prev';
    }
    if (label.includes('&raquo;') || label.toLowerCase().includes('next')) {
        return 'Next »';
    }
    return label;
};
</script>

<template>
    <div v-if="paginationLinks.length > 3 || (paginationInfo && paginationInfo.total > 0)" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t-2 border-gray-100 mt-2">
        <!-- Informasi Jumlah Data -->
        <div v-if="paginationInfo && paginationInfo.total !== null" class="text-xs font-bold uppercase text-gray-500 text-center sm:text-left">
            Menampilkan 
            <span class="font-black text-gray-900">{{ paginationInfo.from ?? 0 }}</span> 
            - 
            <span class="font-black text-gray-900">{{ paginationInfo.to ?? 0 }}</span> 
            dari 
            <span class="font-black text-orange-600">{{ paginationInfo.total }}</span> 
            data
        </div>
        <div v-else class="hidden sm:block"></div>

        <!-- Tombol / Navigasi Halaman -->
        <nav v-if="paginationLinks.length > 3" aria-label="Pagination" class="flex flex-wrap items-center justify-center gap-1.5">
            <template v-for="(link, index) in paginationLinks" :key="index">
                <!-- Disabled Link (url null) -->
                <span
                    v-if="!link.url"
                    class="px-3 py-1.5 text-xs font-bold uppercase rounded-lg border-2 border-gray-300 bg-gray-100 text-gray-400 cursor-not-allowed select-none"
                    v-html="cleanLabel(link.label)"
                />

                <!-- Active / Clickable Link -->
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :class="[
                        'px-3 py-1.5 text-xs rounded-lg border-2 border-gray-900 transition uppercase select-none',
                        link.active
                            ? 'bg-orange-500 text-gray-900 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]'
                            : 'bg-white hover:bg-stone-100 text-gray-800 font-black shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95'
                    ]"
                    v-html="cleanLabel(link.label)"
                />
            </template>
        </nav>
    </div>
</template>
