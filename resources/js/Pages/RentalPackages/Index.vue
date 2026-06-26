<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Boxes, PackagePlus, Pencil, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

defineProps({
    packages: {
        type: Array,
        required: true,
    },
})

const { confirmAction } = useConfirm()

function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

async function destroyPackage(rentalPackage) {
    const confirmed = await confirmAction({
        title: 'Hapus paket rental?',
        message: `Paket ${rentalPackage.name} akan dihapus dari master data.`,
        confirmLabel: 'Ya, hapus paket',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('rental-packages.destroy', rentalPackage.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Paket Rental" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master data"
            title="Paket"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('rental-packages.create')">
                    <PackagePlus :size="18" />
                    Tambah paket
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3 lg:hidden">
            <Link
                v-for="rentalPackage in packages"
                :key="rentalPackage.id"
                :href="route('rental-packages.show', rentalPackage.id)"
                class="grid gap-4 rounded-3xl border border-white/80 bg-white p-4 transition hover:bg-white/80"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <Boxes :size="26" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p class="line-clamp-2 font-bold leading-6 text-diamond-text">{{ rentalPackage.name }}</p>
                            <StatusBadge :value="rentalPackage.is_active ? 'active' : 'inactive'" type="user" />
                        </div>
                        <p class="mt-1 line-clamp-1 text-sm text-diamond-muted">{{ rentalPackage.description || 'Tanpa deskripsi' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-diamond-surface-soft p-3">
                        <p class="text-xs font-semibold text-diamond-soft">Harga paket</p>
                        <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(rentalPackage.package_price) }}</p>
                    </div>
                    <div class="rounded-2xl bg-diamond-surface-soft p-3">
                        <p class="text-xs font-semibold text-diamond-soft">Item</p>
                        <p class="mt-1 font-bold text-diamond-text">{{ rentalPackage.items_count }}</p>
                    </div>
                </div>
            </Link>

            <EmptyState v-if="packages.length === 0" title="Belum ada paket rental." description="Buat paket pertama untuk mempercepat input transaksi rental." />
        </div>

        <div class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Paket</th>
                        <th class="px-4 py-4 font-bold">Harga</th>
                        <th class="px-4 py-4 font-bold">Item</th>
                        <th class="px-4 py-4 font-bold">Status</th>
                        <th class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="rentalPackage in packages" :key="rentalPackage.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <Link :href="route('rental-packages.show', rentalPackage.id)" class="font-bold text-diamond-text hover:text-diamond-primary">
                                {{ rentalPackage.name }}
                            </Link>
                            <p class="mt-1 line-clamp-1 text-xs text-diamond-muted">{{ rentalPackage.description || 'Tanpa deskripsi' }}</p>
                        </td>
                        <td class="px-4 py-4 font-semibold text-diamond-text">{{ formatMoney(rentalPackage.package_price) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ rentalPackage.items_count }} item</td>
                        <td class="px-4 py-4">
                            <StatusBadge :value="rentalPackage.is_active ? 'active' : 'inactive'" type="user" />
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Button :href="route('rental-packages.edit', rentalPackage.id)" variant="secondary">
                                    <Pencil :size="15" />
                                    Edit
                                </Button>
                                <Button variant="danger" type="button" @click="destroyPackage(rentalPackage)">
                                    <Trash2 :size="15" />
                                    Hapus
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="packages.length === 0">
                        <td class="px-6 py-8" colspan="5">
                            <EmptyState title="Belum ada paket rental." description="Buat paket pertama untuk mempercepat input transaksi rental." />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
