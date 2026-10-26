<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    categories: {
        type: [Array, Object],
        default: () => [],
    },
});

const categoryList = computed(() => {
    if (Array.isArray(props.categories)) return props.categories;
    return props.categories?.data || [];
});

// Helper Ambil ID Kategori secara Fleksibel
const getCategoryId = (category) => {
    if (!category) return null;
    if (typeof category === 'object') {
        return category.id_kategori ?? category.id ?? category.kategori_id ?? null;
    }
    return category;
};

// Form Tambah Kategori
const form = useForm({
    nama_kategori: '',
});

const editingCategory = ref(null);
const editForm = useForm({
    nama_kategori: '',
});

// Tambah Kategori
const submitCreate = () => {
    let storeRoute = '/admin/categories';
    try {
        if (route().has('admin.categories.store')) storeRoute = route('admin.categories.store');
        else if (route().has('admin.kategori.store')) storeRoute = route('admin.kategori.store');
    } catch (e) {
        console.warn('Ziggy route error:', e);
    }

    form.post(storeRoute, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Kategori berhasil ditambahkan!');
            form.reset();
        },
        onError: (errors) => {
            console.error("Validation Errors:", errors);
            alert("Gagal menyimpan kategori: " + Object.values(errors).join(", "));
        }
    });
};

// Mode Edit
const startEdit = (category) => {
    editingCategory.value = getCategoryId(category);
    editForm.nama_kategori = category.nama_kategori || category.name || '';
};

const cancelEdit = () => {
    editingCategory.value = null;
    editForm.reset();
};

// Update Kategori
const submitUpdate = (category) => {
    const id = getCategoryId(category);
    if (!id) {
        alert("ID Kategori tidak ditemukan!");
        return;
    }

    let updateRoute = `/admin/categories/${id}`;
    try {
        if (route().has('admin.categories.update')) updateRoute = route('admin.categories.update', id);
        else if (route().has('admin.kategori.update')) updateRoute = route('admin.kategori.update', id);
    } catch (e) {
        console.warn('Ziggy route error:', e);
    }

    editForm.put(updateRoute, {
        preserveScroll: true,
        onSuccess: () => {
            alert('Kategori berhasil diperbarui!');
            cancelEdit();
        },
        onError: (errors) => {
            console.error("Update Errors:", errors);
            alert("Gagal memperbarui kategori: " + Object.values(errors).join(", "));
        }
    });
};

// Hapus Kategori
const deleteCategory = (category) => {
    const id = getCategoryId(category);
    if (!id) {
        alert("ID Kategori tidak ditemukan!");
        return;
    }

    if (confirm('Yakin ingin menghapus kategori ini?')) {
        let deleteRoute = `/admin/categories/${id}`;
        try {
            if (route().has('admin.categories.destroy')) deleteRoute = route('admin.categories.destroy', id);
            else if (route().has('admin.kategori.destroy')) deleteRoute = route('admin.kategori.destroy', id);
        } catch (e) {
            console.warn('Ziggy route error:', e);
        }

        router.delete(deleteRoute, {
            preserveScroll: true,
            onSuccess: () => {
                alert('Kategori berhasil dihapus!');
            },
            onError: (errors) => {
                console.error("Delete Errors:", errors);
                const msg = errors.error || Object.values(errors).join(", ");
                alert("Gagal menghapus kategori: " + msg);
            }
        });
    }
};
</script>

<template>
    <Head title="Kelola Kategori - Admin SiswaMart" />

    <AuthenticatedLayout>
        <div class="py-4 sm:py-8 bg-stone-100 min-h-screen text-gray-900 font-sans selection:bg-orange-500 selection:text-white">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                
                <!-- 1. Header Card -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)]">
                    <div>
                        <h1 class="text-lg sm:text-2xl font-black uppercase tracking-tight text-gray-900">
                            Kelola Kategori Produk
                        </h1>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-500 uppercase mt-0.5 sm:mt-1">
                            Atur Label & Kelompok Jenis Makanan/Minuman SiswaMart
                        </p>
                    </div>
                </div>

                <!-- 2. Form Tambah Kategori -->
                <div class="p-4 sm:p-6 bg-white border-3 sm:border-4 border-gray-900 rounded-2xl sm:rounded-3xl shadow-[4px_4px_0px_0px_rgba(17,24,39,1)] sm:shadow-[6px_6px_0px_0px_rgba(17,24,39,1)] space-y-3 sm:space-y-4">
                    <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 border-b-2 border-gray-100 pb-2.5 sm:pb-3">
                        Tambah Kategori Baru
                    </h3>
                    
                    <form @submit.prevent="submitCreate" class="space-y-2">
                        <div class="flex flex-col sm:flex-row gap-2.5 sm:gap-3">
                            <input
                                v-model="form.nama_kategori"
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
                        </div>
                        <div v-if="form.errors.nama_kategori" class="text-rose-600 text-[11px] font-black uppercase pl-1">
                            {{ form.errors.nama_kategori }}
                        </div>
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
                            v-for="category in categoryList" 
                            :key="getCategoryId(category)"
                            class="bg-stone-50 border-2 border-gray-900 rounded-xl p-3.5 space-y-2.5 shadow-[2px_2px_0px_0px_rgba(17,24,39,1)]"
                        >
                            <!-- Mode Edit Inline Mobile -->
                            <template v-if="editingCategory === getCategoryId(category)">
                                <div class="space-y-2">
                                    <input
                                        v-model="editForm.nama_kategori"
                                        type="text"
                                        class="w-full bg-white border-2 border-gray-900 rounded-lg px-3 py-1.5 text-xs font-bold text-gray-900 focus:border-orange-500 focus:ring-0"
                                    />
                                    <div class="flex items-center gap-2">
                                        <button 
                                            @click="submitUpdate(category)" 
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
                                        {{ category.nama_kategori || category.name }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 border-t border-gray-200 pt-2.5">
                                    <button 
                                        @click="startEdit(category)" 
                                        class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        @click="deleteCategory(category)" 
                                        class="flex-1 inline-flex items-center justify-center gap-1 py-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 rounded-lg text-[10px] font-black uppercase shadow-[1px_1px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div v-if="!categoryList || categoryList.length === 0" class="py-6 text-center text-xs font-bold uppercase text-gray-400">
                            Belum ada kategori terdaftar.
                        </div>
                    </div>

                    <!-- TAMPILAN DESKTOP: TABEL -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b-4 border-gray-900 text-xs font-black uppercase tracking-wider text-gray-500 pb-3">
                                    <th class="py-3 px-4">Nama Kategori</th>
                                    <th class="py-3 px-4 text-right">Aksi Manajemen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-200">
                                <tr v-for="category in categoryList" :key="getCategoryId(category)" class="hover:bg-stone-50 transition">
                                    
                                    <!-- Mode Edit Inline Desktop -->
                                    <template v-if="editingCategory === getCategoryId(category)">
                                        <td class="py-3 px-4">
                                            <input
                                                v-model="editForm.nama_kategori"
                                                type="text"
                                                class="w-full bg-stone-50 border-2 border-gray-900 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 focus:border-orange-500 focus:ring-0"
                                            />
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button 
                                                @click="submitUpdate(category)" 
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
                                            {{ category.nama_kategori || category.name }}
                                        </td>
                                        <td class="py-3 px-4 text-right space-x-2">
                                            <button 
                                                @click="startEdit(category)" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-400 hover:bg-amber-300 border-2 border-gray-900 text-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                Edit
                                            </button>
                                            <button 
                                                @click="deleteCategory(category)" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-500 hover:bg-rose-400 text-white border-2 border-gray-900 rounded-xl text-xs font-black uppercase shadow-[2px_2px_0px_0px_rgba(17,24,39,1)] active:scale-95 transition"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </template>

                                </tr>
                                <tr v-if="!categoryList || categoryList.length === 0">
                                    <td colspan="2" class="py-8 text-center text-xs font-bold uppercase text-gray-400">
                                        Belum ada kategori terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION NAVIGATION -->
                    <Pagination :links="categories.links || []" :meta="categories" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>