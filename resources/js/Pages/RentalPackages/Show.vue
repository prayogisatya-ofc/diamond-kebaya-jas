<script setup>
import { Head } from '@inertiajs/vue3'
import { Pencil, Tag } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

defineOptions({
    layout: AppLayout,
})

defineProps({
    rentalPackage: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
})

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
</script>

<template>
    <Head :title="rentalPackage.name" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Detail paket"
            :title="rentalPackage.name"
            :subtitle="rentalPackage.description || 'Tanpa deskripsi'"
        >
            <template #actions>
                <Button :href="route('rental-packages.index')" variant="secondary">
                    Kembali
                </Button>
                <Button :href="route('rental-packages.edit', rentalPackage.id)">
                    <Pencil :size="18" />
                    Edit paket
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <p class="text-sm font-semibold text-diamond-muted">Harga paket</p>
                <p class="mt-3 text-2xl font-bold text-diamond-text">{{ formatMoney(rentalPackage.package_price) }}</p>
            </Card>
            <Card>
                <p class="text-sm font-semibold text-diamond-muted">Jumlah item</p>
                <p class="mt-3 text-2xl font-bold text-diamond-text">{{ items.length }}</p>
            </Card>
            <Card>
                <p class="text-sm font-semibold text-diamond-muted">Status</p>
                <div class="mt-3">
                    <StatusBadge :value="rentalPackage.is_active ? 'active' : 'inactive'" type="user" />
                </div>
            </Card>
        </div>

        <section class="grid gap-3">
            <div>
                <h2 class="text-lg font-bold text-diamond-text">Item paket</h2>
            </div>

            <div class="grid gap-3 md:hidden">
                <article
                    v-for="item in items"
                    :key="item.id"
                    class="rounded-3xl border border-white/80 bg-white p-4"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                            <Tag :size="20" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="line-clamp-2 font-bold leading-6 text-diamond-text">{{ item.product?.name }}</p>
                                <span class="shrink-0 rounded-xl px-3 py-1 text-xs font-bold" :class="item.is_optional ? 'bg-orange-50 text-diamond-accent' : 'bg-emerald-50 text-emerald-700'">
                                    {{ item.is_optional ? 'Opsional' : 'Wajib' }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-diamond-muted">
                                {{ item.product_variant?.name || 'Tanpa varian khusus' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-diamond-surface-soft p-3">
                            <p class="text-diamond-soft">Qty</p>
                            <p class="mt-1 font-bold text-diamond-text">{{ item.quantity }}</p>
                        </div>
                        <div class="rounded-2xl bg-diamond-surface-soft p-3">
                            <p class="text-diamond-soft">Harga default</p>
                            <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(item.default_item_price) }}</p>
                        </div>
                    </div>

                    <p v-if="item.notes" class="mt-3 rounded-2xl bg-diamond-surface-soft p-3 text-sm leading-6 text-diamond-muted">{{ item.notes }}</p>
                </article>

                <EmptyState v-if="items.length === 0" title="Belum ada item paket." />
            </div>

            <div class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white md:block">
                <table class="w-full min-w-[920px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                        <tr>
                            <th class="px-6 py-4 font-bold">Produk</th>
                            <th class="px-4 py-4 font-bold">Varian</th>
                            <th class="px-4 py-4 font-bold">Qty</th>
                            <th class="px-4 py-4 font-bold">Harga default</th>
                            <th class="px-4 py-4 font-bold">Status item</th>
                            <th class="px-6 py-4 font-bold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="item in items" :key="item.id" class="transition hover:bg-diamond-surface-soft">
                            <td class="px-6 py-4">
                                <p class="font-bold text-diamond-text">{{ item.product?.name }}</p>
                                <p class="mt-1 text-xs text-diamond-muted">{{ item.product?.code || 'Tanpa kode' }}</p>
                            </td>
                            <td class="px-4 py-4 text-diamond-muted">{{ item.product_variant?.name || '-' }}</td>
                            <td class="px-4 py-4 font-semibold text-diamond-text">{{ item.quantity }}</td>
                            <td class="px-4 py-4 text-diamond-muted">{{ formatMoney(item.default_item_price) }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-xl px-3 py-1 text-xs font-bold" :class="item.is_optional ? 'bg-orange-50 text-diamond-accent' : 'bg-emerald-50 text-emerald-700'">
                                    {{ item.is_optional ? 'Opsional' : 'Wajib' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-diamond-muted">{{ item.notes || '-' }}</td>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td class="px-6 py-8" colspan="6">
                                <EmptyState title="Belum ada item paket." />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>
