<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { CalendarClock, MessageCircle, Pencil, ReceiptText, WalletCards } from '@lucide/vue'
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
    customer: {
        type: Object,
        required: true,
    },
    rentalHistory: {
        type: Array,
        required: true,
    },
    hasRentalHistory: {
        type: Boolean,
        required: true,
    },
})

function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

function formatDate(value) {
    if (!value) {
        return '-'
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
}

function initials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
}

function whatsappUrl(number) {
    const digits = String(number || '').replace(/\D/g, '')
    const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits

    return normalized ? `https://wa.me/${normalized}` : '#'
}
</script>

<template>
    <Head :title="customer.name" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Detail customer"
            :title="customer.name"
            :subtitle="customer.whatsapp_number"
        >
            <template #actions>
                <Button :href="route('customers.index')" variant="secondary">
                    Kembali
                </Button>
                <Button :href="route('customers.edit', customer.id)">
                    <Pencil :size="18" />
                    Edit customer
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
            <Card>
                <div class="flex items-start gap-4">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-diamond-primary text-xl font-bold text-white">
                        {{ initials(customer.name) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-lg font-bold text-diamond-text">{{ customer.name }}</p>
                        <a :href="whatsappUrl(customer.whatsapp_number)" target="_blank" class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-diamond-primary">
                            <MessageCircle :size="17" />
                            {{ customer.whatsapp_number }}
                        </a>
                    </div>
                </div>

                <div class="mt-6 rounded-3xl bg-diamond-surface-soft p-4">
                    <p class="text-sm font-semibold text-diamond-muted">Catatan</p>
                    <p class="mt-2 text-sm leading-6 text-diamond-text">{{ customer.notes || 'Belum ada catatan customer.' }}</p>
                </div>
            </Card>

            <div class="grid gap-4 md:grid-cols-3 lg:self-start">
                <Card>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <ReceiptText :size="22" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-diamond-muted">Jumlah transaksi</p>
                    <p class="mt-2 text-2xl font-bold text-diamond-text">{{ customer.rentals_count }}</p>
                </Card>
                <Card>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-orange-50 text-diamond-accent">
                        <WalletCards :size="22" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-diamond-muted">Total nilai transaksi</p>
                    <p class="mt-2 text-2xl font-bold text-diamond-text">{{ formatMoney(customer.rentals_total_amount) }}</p>
                </Card>
                <Card>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <CalendarClock :size="22" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-diamond-muted">Transaksi terakhir</p>
                    <p class="mt-2 text-base font-bold leading-7 text-diamond-text">{{ formatDate(customer.last_transaction_at) }}</p>
                </Card>
            </div>
        </div>

        <section class="grid gap-3">
            <div>
                <h2 class="text-lg font-bold text-diamond-text">Riwayat transaksi rental</h2>
                <p class="mt-1 text-sm text-diamond-muted">Maksimal 20 transaksi terbaru untuk customer ini.</p>
            </div>

            <div class="grid gap-3 md:hidden">
                <Link
                    v-for="rental in rentalHistory"
                    :key="rental.id"
                    :href="route('rentals.show', rental.id)"
                    class="rounded-3xl border border-white/80 bg-white p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-diamond-text">{{ rental.invoice_number || `#${rental.id}` }}</p>
                            <p class="mt-1 text-sm text-diamond-muted">{{ formatDate(rental.created_at) }}</p>
                        </div>
                        <StatusBadge :value="rental.status" />
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-diamond-surface-soft p-3">
                            <p class="text-diamond-soft">Total</p>
                            <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(rental.total_amount) }}</p>
                        </div>
                        <div class="rounded-2xl bg-diamond-surface-soft p-3">
                            <p class="text-diamond-soft">Sisa</p>
                            <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(rental.remaining_amount) }}</p>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-2 rounded-2xl bg-diamond-surface-soft p-3 text-sm text-diamond-muted">
                        <p>Ambil: <span class="font-semibold text-diamond-text">{{ formatDate(rental.pickup_at) }}</span></p>
                        <p>Kembali: <span class="font-semibold text-diamond-text">{{ formatDate(rental.return_due_at) }}</span></p>
                    </div>
                </Link>

                <EmptyState
                    v-if="rentalHistory.length === 0"
                    :title="hasRentalHistory ? 'Belum ada riwayat transaksi rental.' : 'Riwayat transaksi rental belum tersedia.'"
                />
            </div>

            <div class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white md:block">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                        <tr>
                            <th class="px-6 py-4 font-bold">Invoice</th>
                            <th class="px-4 py-4 font-bold">Status</th>
                            <th class="px-4 py-4 font-bold">Pembayaran</th>
                            <th class="px-4 py-4 font-bold">Jadwal ambil</th>
                            <th class="px-4 py-4 font-bold">Jadwal kembali</th>
                            <th class="px-4 py-4 font-bold">Total</th>
                            <th class="px-6 py-4 font-bold">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="rental in rentalHistory" :key="rental.id" class="transition hover:bg-diamond-surface-soft">
                            <td class="px-6 py-4">
                                <Link :href="route('rentals.show', rental.id)" class="font-bold text-diamond-text hover:text-diamond-primary">
                                    {{ rental.invoice_number || `#${rental.id}` }}
                                </Link>
                                <p class="mt-1 text-xs text-diamond-muted">{{ formatDate(rental.created_at) }}</p>
                            </td>
                            <td class="px-4 py-4"><StatusBadge :value="rental.status" /></td>
                            <td class="px-4 py-4"><StatusBadge :value="rental.payment_status" type="payment" /></td>
                            <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.pickup_at) }}</td>
                            <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.return_due_at) }}</td>
                            <td class="px-4 py-4 font-semibold text-diamond-text">{{ formatMoney(rental.total_amount) }}</td>
                            <td class="px-6 py-4 text-diamond-muted">{{ formatMoney(rental.remaining_amount) }}</td>
                        </tr>
                        <tr v-if="rentalHistory.length === 0">
                            <td class="px-6 py-8" colspan="7">
                                <EmptyState :title="hasRentalHistory ? 'Belum ada riwayat transaksi rental.' : 'Riwayat transaksi rental belum tersedia.'" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</template>
