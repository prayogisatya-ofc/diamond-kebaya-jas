<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronRight, PackagePlus, Pencil, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    variants: {
        type: Array,
        required: true,
    },
})
const { confirmAction } = useConfirm()

function formatMoney(value) {
    if (value === null || value === undefined || value === '') {
        return '-'
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value))
}

async function destroyVariant(variant) {
    const confirmed = await confirmAction({
        title: 'Hapus varian?',
        message: `Varian ${variant.name} akan dihapus dari produk ini.`,
        confirmLabel: 'Ya, hapus varian',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('product-variants.destroy', variant.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="product.name" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Detail produk"
            :title="product.name"
            :subtitle="`${product.category?.name || 'Tanpa kategori'} · ${product.code || 'Tanpa kode'}`"
        >
            <template #actions>
                <Button :href="route('products.index')" variant="secondary">
                    Kembali
                </Button>
                <Button :href="route('products.edit', product.id)" variant="secondary">
                    <Pencil :size="18" />
                    Edit produk
                </Button>
                <Button :href="route('products.variants.create', product.id)">
                    <PackagePlus :size="18" />
                    Tambah varian
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
            <Card>
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-3xl bg-diamond-surface-soft">
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                    >
                    <span v-else class="text-sm font-semibold text-diamond-soft">Belum ada foto</span>
                </div>
            </Card>

            <div class="grid gap-4 md:grid-cols-3 lg:self-start">
                <Card>
                    <p class="text-sm font-semibold text-diamond-muted">Harga default</p>
                    <p class="mt-3 text-2xl font-bold text-diamond-text">{{ formatMoney(product.base_rental_price) }}</p>
                </Card>
                <Card>
                    <p class="text-sm font-semibold text-diamond-muted">Jumlah varian</p>
                    <p class="mt-3 text-2xl font-bold text-diamond-text">{{ variants.length }}</p>
                </Card>
                <Card>
                    <p class="text-sm font-semibold text-diamond-muted">Status</p>
                    <div class="mt-3">
                        <StatusBadge :value="product.is_active ? 'active' : 'inactive'" type="user" />
                    </div>
                </Card>

                <Card class="md:col-span-3">
                    <p class="text-sm font-semibold text-diamond-muted">Deskripsi</p>
                    <p class="mt-3 text-sm leading-6 text-diamond-text">{{ product.description || 'Belum ada deskripsi.' }}</p>
                </Card>
            </div>
        </div>

        <section class="grid gap-3">
            <div>
                <h2 class="text-lg font-bold text-diamond-text">Varian produk</h2>
                <p class="mt-1 text-sm text-diamond-muted">Stok dan harga opsional per varian.</p>
            </div>

            <div class="grid gap-3 md:hidden">
                <Link
                    v-for="variant in variants"
                    :key="variant.id"
                    :href="route('product-variants.edit', variant.id)"
                    class="flex items-center gap-3 rounded-3xl border border-white/80 bg-white p-4 transition hover:bg-white/80"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="line-clamp-1 font-bold text-diamond-text">{{ variant.name }}</p>
                            <StatusBadge :value="variant.is_active ? 'active' : 'inactive'" type="user" />
                        </div>
                        <p class="mt-1 truncate text-sm text-diamond-muted">
                            {{ variant.color || 'Tanpa warna' }} · Stok {{ variant.stock_quantity }}
                        </p>
                        <p class="mt-2 text-sm font-bold text-diamond-primary">{{ formatMoney(variant.rental_price) }}</p>
                    </div>

                    <ChevronRight class="shrink-0 text-diamond-soft" :size="20" />
                </Link>

                <EmptyState v-if="variants.length === 0" title="Belum ada varian produk." description="Tambahkan ukuran, warna, stok, dan harga khusus jika dibutuhkan." />
            </div>

            <div class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white md:block">
                <table class="w-full min-w-[880px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                        <tr>
                            <th class="px-6 py-4 font-bold">Varian</th>
                            <th class="px-4 py-4 font-bold">SKU</th>
                            <th class="px-4 py-4 font-bold">Size</th>
                            <th class="px-4 py-4 font-bold">Warna</th>
                            <th class="px-4 py-4 font-bold">Stok</th>
                            <th class="px-4 py-4 font-bold">Harga</th>
                            <th class="px-4 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="variant in variants" :key="variant.id" class="transition hover:bg-diamond-surface-soft">
                            <td class="px-6 py-4 font-bold text-diamond-text">{{ variant.name }}</td>
                            <td class="px-4 py-4 text-diamond-muted">{{ variant.sku || '-' }}</td>
                            <td class="px-4 py-4 text-diamond-muted">{{ variant.size || '-' }}</td>
                            <td class="px-4 py-4 text-diamond-muted">{{ variant.color || '-' }}</td>
                            <td class="px-4 py-4 font-semibold text-diamond-text">{{ variant.stock_quantity }}</td>
                            <td class="px-4 py-4 text-diamond-muted">{{ formatMoney(variant.rental_price) }}</td>
                            <td class="px-4 py-4">
                                <StatusBadge :value="variant.is_active ? 'active' : 'inactive'" type="user" />
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <Link
                                        :href="route('product-variants.edit', variant.id)"
                                        class="rounded-xl border border-diamond-border px-3 py-2 text-sm font-semibold text-diamond-text transition hover:bg-diamond-surface-soft"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        class="flex items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                        type="button"
                                        @click="destroyVariant(variant)"
                                    >
                                        <Trash2 :size="15" />
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="variants.length === 0">
                            <td class="px-6 py-8" colspan="8">
                                <EmptyState title="Belum ada varian produk." description="Tambahkan ukuran, warna, stok, dan harga khusus jika dibutuhkan." />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>
