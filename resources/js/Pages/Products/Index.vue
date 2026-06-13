<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { Package, RotateCcw, Search, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
})

const filterForm = reactive({
    search: props.filters.search ?? '',
    category: props.filters.category ?? '',
})
const { confirmAction } = useConfirm()

function applyFilters() {
    router.get(route('products.index'), filterForm, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''
    filterForm.category = ''
    applyFilters()
}

function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

async function destroyProduct(product) {
    const confirmed = await confirmAction({
        title: 'Hapus produk?',
        message: `Produk ${product.name} dan varian produknya akan dihapus.`,
        confirmLabel: 'Ya, hapus produk',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('products.destroy', product.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Produk" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master data"
            title="Produk"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('product-categories.index')" variant="secondary">
                    Kategori
                </Button>
                <Button :href="route('products.create')">
                    <Package :size="18" />
                    Tambah produk
                </Button>
            </template>
        </PageHeader>

        <form class="grid gap-3 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_260px_auto]" @submit.prevent="applyFilters">
            <Input
                v-model="filterForm.search"
                placeholder="Cari nama atau kode produk"
                type="search"
            />
            <select
                v-model="filterForm.category"
                class="min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
            >
                <option value="">Semua kategori</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
            <div class="flex gap-2">
                <Button type="submit">
                    <Search :size="17" />
                    Filter
                </Button>
                <Button variant="secondary" type="button" @click="resetFilters">
                    <RotateCcw :size="17" />
                    Reset
                </Button>
            </div>
        </form>

        <div class="grid gap-3 md:hidden">
            <Link
                v-for="product in products.data"
                :key="product.id"
                :href="route('products.show', product.id)"
                class="flex min-h-28 items-center gap-4 rounded-3xl border border-white/80 bg-white p-3 transition hover:bg-white/80"
            >
                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-xs font-bold text-diamond-soft">
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                    >
                    <span v-else>Foto</span>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="line-clamp-2 text-base font-bold leading-6 text-diamond-text">{{ product.name }}</p>
                    <p class="mt-1 truncate text-sm text-diamond-muted">{{ product.category?.name || 'Tanpa kategori' }}</p>
                    <p class="mt-2 text-sm font-bold text-diamond-primary">{{ formatMoney(product.base_rental_price) }}</p>
                </div>
            </Link>
            <EmptyState v-if="products.data.length === 0" title="Tidak ada produk sesuai filter." />
        </div>

        <div class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white md:block">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Produk</th>
                        <th class="px-4 py-4 font-bold">Foto</th>
                        <th class="px-4 py-4 font-bold">Kategori</th>
                        <th class="px-4 py-4 font-bold">Harga default</th>
                        <th class="px-4 py-4 font-bold">Varian</th>
                        <th class="px-4 py-4 font-bold">Status</th>
                        <th class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="product in products.data" :key="product.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <Link :href="route('products.show', product.id)" class="font-bold text-diamond-text hover:text-diamond-primary">
                                {{ product.name }}
                            </Link>
                            <p class="mt-1 text-xs text-diamond-muted">{{ product.code || 'Tanpa kode' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-xs font-bold text-diamond-soft">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="h-full w-full object-cover"
                                >
                                <span v-else>Foto</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-diamond-muted">{{ product.category?.name || '-' }}</td>
                        <td class="px-4 py-4 font-semibold text-diamond-text">{{ formatMoney(product.base_rental_price) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ product.variants_count }}</td>
                        <td class="px-4 py-4">
                            <StatusBadge :value="product.is_active ? 'active' : 'inactive'" type="user" />
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('products.edit', product.id)"
                                    class="rounded-xl border border-diamond-border px-3 py-2 text-sm font-semibold text-diamond-text transition hover:bg-diamond-surface-soft"
                                >
                                    Edit
                                </Link>
                                <button
                                    class="flex items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                    type="button"
                                    @click="destroyProduct(product)"
                                >
                                    <Trash2 :size="15" />
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td class="px-6 py-8 text-center text-diamond-muted" colspan="7">Tidak ada produk sesuai filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :paginator="products" />
    </section>
</template>
