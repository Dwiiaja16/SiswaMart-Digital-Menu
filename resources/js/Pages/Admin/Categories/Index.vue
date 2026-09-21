<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    categories: Array,
});

// Form Tambah Kategori
const form = useForm({
    name: '',
});

const editingCategory = ref(null);
const editForm = useForm({
    name: '',
});

// Tambah Kategori
const submitCreate = () => {
    form.post(route('categories.store'), {
        onSuccess: () => form.reset(),
    });
};

// Mode Edit
const startEdit = (category) => {
    editingCategory.value = category.id;
    editForm.name = category.name;
};

const cancelEdit = () => {
    editingCategory.value = null;
    editForm.reset();
};

// Update Kategori
const submitUpdate = (id) => {
    editForm.put(route('categories.update', id), {
        onSuccess: () => cancelEdit(),
    });
};

// Hapus Kategori
const deleteCategory = (id) => {
    if (confirm('Yakin ingin menghapus kategori ini?')) {
        useForm({}).delete(route('categories.destroy', id));
    }
};
</script>

<template>
    <Head title="Kelola Kategori - Admin SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-8 bg-stone-100 min-h-screen text-gray-900 font-sans selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                
                <!-- 1. Header Card (Neo-Brutalism Style) -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    <div>
                        <h1 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-gray-900">
                            Kelola Kategori Produk
                        </h1>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase mt-0.5 sm:mt-1">
                            Atur Label & Kelompok Jenis Makanan/Minuman SiswaMart
                        </p>
                    </div>
                    
                    <Link 
                        :href="route('dashboard')" 
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 px-3.5 py-2 rounded-xl text-[11px] sm:text-xs font-black uppercase tracking-wider shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                    >
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Kembali ke Dashboard
                    </Link>
                </div>

                <!-- 2. Form Tambah Kategori -->
                <div class="p-4 sm:p-6 bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-3 sm:space-y-4">
                    <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 border-b-2 border-gray-100 pb-2.5 sm:pb-3">
                        Tambah Kategori Baru
                    </h3>
                    
                    <form @submit.prevent="submitCreate" class="flex flex-col sm:flex-row gap-2.5 sm:gap-3">
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: Makanan Berat, Minuman, Snacking..."
                            class="flex-1 bg-stone-50 border-2 border-gray-900 rounded-xl px-3.5 py-2.5 text-xs font-bold text-gray-900 placeholder-gray-400 focus:border-orange-500 focus:ring-0"
                            required
                        />
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 bg-orange-500 hover:bg-orange-400 border-2 border-gray-900 text-gray-900 font-black text-xs uppercase tracking-wider rounded-xl shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition disabled:opacity-50"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5v14"/>
                            </svg>
                            Simpan Kategori
                        </button>
                    </form>
                </div>

                <!-- 3. Daftar Kategori Terdaftar -->
                <div class="p-4 sm:p-6 bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-3 sm:space-y-4">
                    <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 border-b-2 border-gray-100 pb-2.5 sm:pb-3">
                        Daftar Kategori Terdaftar
                    </h3>

                    <!-- TAMPILAN MOBILE: CARD LIST -->
                    <div class="block sm:hidden space-y-3">
                        <div 
                            v-for="category in categories" 
                            :key="category.id"
                            class="bg-stone-50 border-2 border-gray-900 rounded-xl p-3.5 space-y-2.5 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <!-- Mode Edit Inline Mobile -->
                            <template v-if="editingCategory === category.id">
                                <div class="space-y-2">
                                    <input
                                        v-model="editForm.name"
                                        type="text"
                                        class="w-full bg-white border-2 border-gray-900 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-900 focus:border-orange-500 focus:ring-0"
                                    />
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="submitUpdate(category.id)" 
                                            class="flex-1 py-1.5 bg-emerald-400 hover:bg-emerald-300 border-2 border-gray-900 text-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                        >
                                            Simpan
                                        </button>
                                        <button 
                                            @click="cancelEdit" 
                                            class="flex-1 py-1.5 bg-stone-200 hover:bg-stone-300 border-2 border-gray-900 text-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Mode Normal Mobile -->
                            <template v-else>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="font-black text-xs uppercase text-gray-900">
                                        {{ category.name }}
                                    </div>
                                    <span class="bg-amber-100 text-amber-900 border border-gray-900 px-2 py-0.5 rounded-md text-[9px] font-black tracking-wider uppercase shrink-0">
                                        {{ category.slug }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 border-t border-gray-200 pt-2.5">
                                    <button 
                                        @click="startEdit(category)" 
                                        class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        Edit
                                    </button>
                                    <button 
                                        @click="deleteCategory(category.id)" 
                                        class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18"/>
                                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div v-if="categories.length === 0" class="py-6 text-center text-xs font-bold uppercase text-gray-400">
                            Belum ada kategori terdaftar.
                        </div>
                    </div>

                    <!-- TAMPILAN DESKTOP & TABLET: TABEL -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-4 border-gray-900 text-xs font-black uppercase tracking-wider text-gray-500 pb-3">
                                    <th class="py-3 px-4">Nama Kategori</th>
                                    <th class="py-3 px-4">Slug (URL Route)</th>
                                    <th class="py-3 px-4 text-right">Aksi Manajemen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-200">
                                <tr v-for="category in categories" :key="category.id" class="hover:bg-stone-50 transition">
                                    
                                    <!-- Mode Edit Inline Desktop -->
                                    <template v-if="editingCategory === category.id">
                                        <td class="py-3 px-4" colspan="2">
                                            <input
                                                v-model="editForm.name"
                                                type="text"
                                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:border-orange-500 focus:ring-0"
                                            />
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button 
                                                @click="submitUpdate(category.id)" 
                                                class="px-3 py-1.5 bg-emerald-400 hover:bg-emerald-300 border-2 border-gray-900 text-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                Simpan
                                            </button>
                                            <button 
                                                @click="cancelEdit" 
                                                class="px-3 py-1.5 bg-stone-200 hover:bg-stone-300 border-2 border-gray-900 text-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                Batal
                                            </button>
                                        </td>
                                    </template>

                                    <!-- Mode Normal Desktop -->
                                    <template v-else>
                                        <td class="py-3 px-4 font-black text-xs uppercase text-gray-900">
                                            {{ category.name }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="bg-amber-100 text-amber-900 border border-gray-900 px-2 py-0.5 rounded-md text-[10px] font-black tracking-wider uppercase">
                                                {{ category.slug }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button 
                                                @click="startEdit(category)" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                </svg>
                                                Edit
                                            </button>
                                            <button 
                                                @click="deleteCategory(category.id)" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 6h18"/>
                                                    <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                                    <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                                </svg>
                                                Hapus
                                            </button>
                                        </td>
                                    </template>

                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="3" class="py-8 text-center text-xs font-bold uppercase text-gray-400">
                                        Belum ada kategori terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>