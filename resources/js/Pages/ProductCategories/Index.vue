<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { Edit3, FolderPlus, Package, RotateCcw, Search, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
})

const page = usePage()
const search = ref('')
const { confirmAction } = useConfirm()

const categoryError = computed(() => page.props.errors.category)

const filteredCategories = computed(() => {
    const keyword = search.value.trim().toLowerCase()

    if (!keyword) {
        return props.categories
    }

    return props.categories.filter((category) => {
        return [category.name, category.slug]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(keyword))
    })
})

function productLabel(count) {
    return `${count} produk`
}

function resetSearch() {
    search.value = ''
}

async function destroyCategory(category) {
    if (category.products_count > 0) {
        return
    }

    const confirmed = await confirmAction({
        title: 'Hapus kategori?',
        message: `Kategori ${category.name} akan dihapus permanen dari master data.`,
        confirmLabel: 'Ya, hapus kategori',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('product-categories.destroy', category.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Kategori Produk" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master data"
            title="Kategori produk"
        >
            <template #actions>
                <Button :href="route('products.index')" variant="secondary">
                    <Package :size="18" />
                    Produk
                </Button>
                <Button :href="route('product-categories.create')">
                    <FolderPlus :size="18" />
                    Tambah kategori
                </Button>
            </template>
        </PageHeader>

        <div v-if="categoryError" class="rounded-3xl border border-red-100 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700">
            {{ categoryError }}
        </div>

        <form class="grid gap-3 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_auto]" @submit.prevent>
            <Input
                v-model="search"
                placeholder="Cari nama kategori atau slug"
                type="search"
            />
            <div class="flex gap-2">
                <Button type="button">
                    <Search :size="17" />
                    Cari
                </Button>
                <Button type="button" variant="secondary" @click="resetSearch">
                    <RotateCcw :size="17" />
                    Reset
                </Button>
            </div>
        </form>

        <div v-if="filteredCategories.length > 0" class="grid gap-3 lg:hidden">
            <article
                v-for="category in filteredCategories"
                :key="category.id"
                class="rounded-3xl border border-white/80 bg-white p-4"
            >
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-diamond-surface-soft text-diamond-primary">
                        <FolderPlus :size="24" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-diamond-text">{{ category.name }}</p>
                                <p class="mt-1 truncate text-sm text-diamond-muted">{{ category.slug }}</p>
                            </div>
                            <StatusBadge :value="category.is_active ? 'active' : 'inactive'" type="user" />
                        </div>
                        <p class="mt-2 text-sm font-bold text-diamond-primary">{{ productLabel(category.products_count) }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <Button :href="route('product-categories.edit', category.id)" variant="secondary" full>
                        <Edit3 :size="16" />
                        Edit
                    </Button>
                    <button
                        class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition focus:outline-none focus:ring-4"
                        :class="category.products_count > 0 ? 'cursor-not-allowed border-diamond-border bg-diamond-surface-soft text-diamond-soft' : 'border-red-200 bg-white text-red-700 hover:bg-red-50 focus:ring-red-100'"
                        :disabled="category.products_count > 0"
                        type="button"
                        @click="destroyCategory(category)"
                    >
                        <Trash2 :size="16" />
                        Hapus
                    </button>
                </div>
            </article>
        </div>

        <div v-if="filteredCategories.length > 0" class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                        <tr>
                            <th class="px-6 py-4 font-bold">Kategori</th>
                            <th class="px-6 py-4 font-bold">Produk</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="category in filteredCategories" :key="category.id" class="transition hover:bg-diamond-surface-soft">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                                        <FolderPlus :size="20" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-diamond-text">{{ category.name }}</p>
                                        <p class="mt-1 text-xs text-diamond-muted">{{ category.slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('product-categories.show', category.id)"
                                    class="inline-flex rounded-xl bg-diamond-surface-soft px-3 py-2 text-xs font-bold text-diamond-primary transition hover:bg-diamond-primary-soft"
                                >
                                    {{ productLabel(category.products_count) }}
                                </Link>
                            </td>
                            <td class="px-6 py-4">
                                <StatusBadge :value="category.is_active ? 'active' : 'inactive'" type="user" />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <Button :href="route('product-categories.edit', category.id)" variant="secondary">
                                        <Edit3 :size="16" />
                                        Edit
                                    </Button>
                                    <button
                                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold transition focus:outline-none focus:ring-4"
                                        :class="category.products_count > 0 ? 'cursor-not-allowed border-diamond-border bg-diamond-surface-soft text-diamond-soft' : 'border-red-200 bg-white text-red-700 hover:bg-red-50 focus:ring-red-100'"
                                        :disabled="category.products_count > 0"
                                        type="button"
                                        @click="destroyCategory(category)"
                                    >
                                        <Trash2 :size="16" />
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <EmptyState
            v-else
            :description="search ? 'Coba gunakan kata kunci lain.' : 'Tambahkan kategori pertama untuk mulai merapikan produk rental.'"
            :title="search ? 'Kategori tidak ditemukan' : 'Belum ada kategori produk'"
        />
    </section>
</template>
