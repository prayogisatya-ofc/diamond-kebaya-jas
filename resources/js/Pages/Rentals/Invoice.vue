<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { CalendarClock, CheckCircle2, Clock3, CreditCard, MapPin, MessageCircle, PackageCheck, ReceiptText, ShieldCheck, ShoppingBag, UserRound } from '@lucide/vue'
import { computed } from 'vue'

const logoUrl = '/logo-ig.jpg'

const props = defineProps({
    publicView: {
        type: Boolean,
        default: false,
    },
    store: {
        type: Object,
        required: true,
    },
    rental: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    payments: {
        type: Array,
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
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(new Date(value))
}

function formatDateTime(value) {
    if (!value) {
        return '-'
    }

    const parsedDate = new Date(value)
    const datePart = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(parsedDate)
    const timePart = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        hourCycle: 'h23',
    }).format(parsedDate)

    return `${datePart}, ${timePart}`
}

function statusLabel(value) {
    return {
        booked: 'Booking tercatat',
        picked_up: 'Barang sudah diambil',
        returned: 'Barang sudah kembali',
        completed: 'Pesanan selesai',
        overdue: 'Terlambat kembali',
        cancelled: 'Pesanan dibatalkan',
        unpaid: 'Belum ada pembayaran',
        dp: 'Sudah DP',
        paid: 'Lunas',
        overpaid: 'Lebih bayar',
    }[value] || value || '-'
}

function paymentTypeLabel(value) {
    return {
        dp: 'DP',
        pelunasan: 'Pelunasan',
        denda: 'Denda',
        refund: 'Refund',
        adjustment: 'Adjustment',
    }[value] || value || '-'
}

function paymentMethodLabel(value) {
    return {
        cash: 'Cash',
        transfer: 'Transfer',
        qris: 'QRIS',
        debit: 'Debit',
        other: 'Lainnya',
    }[value] || value || '-'
}

function statusClasses(value) {
    return {
        booked: 'bg-diamond-info-soft text-diamond-info',
        picked_up: 'bg-amber-100 text-amber-700',
        returned: 'bg-diamond-primary-soft text-diamond-primary',
        completed: 'bg-emerald-100 text-emerald-700',
        overdue: 'bg-red-100 text-red-700',
        cancelled: 'bg-slate-200 text-slate-600',
        unpaid: 'bg-slate-200 text-slate-600',
        dp: 'bg-amber-100 text-amber-700',
        paid: 'bg-emerald-100 text-emerald-700',
        overpaid: 'bg-diamond-primary-soft text-diamond-primary',
    }[value] || 'bg-slate-200 text-slate-600'
}

function whatsappUrl(number) {
    const normalized = String(number || '').replace(/\D/g, '')

    if (!normalized) {
        return null
    }

    const target = normalized.startsWith('0') ? `62${normalized.slice(1)}` : normalized

    return `https://wa.me/${target}`
}

const primaryColor = computed(() => {
    const color = String(props.store.primary_color || '')

    return /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(color) ? color : '#615cf9'
})

const themeStyle = computed(() => ({
    '--color-diamond-primary': primaryColor.value,
    '--color-diamond-primary-dark': `color-mix(in srgb, ${primaryColor.value} 82%, black)`,
    '--color-diamond-primary-soft': `color-mix(in srgb, ${primaryColor.value} 12%, white)`,
    '--color-diamond-primary-muted': `color-mix(in srgb, ${primaryColor.value} 44%, white)`,
    '--color-diamond-sidebar': primaryColor.value,
}))

const storeWhatsappUrl = whatsappUrl(props.store.whatsapp_number)

const timeline = [
    {
        label: 'Order dibuat',
        value: formatDateTime(props.rental.created_at),
        active: true,
        icon: ReceiptText,
    },
    {
        label: 'Jadwal ambil',
        value: formatDate(props.rental.pickup_at),
        active: ['booked', 'picked_up', 'returned', 'completed', 'overdue'].includes(props.rental.status),
        icon: ShoppingBag,
    },
    {
        label: props.rental.picked_up_at ? 'Sudah diambil' : 'Belum diambil',
        value: props.rental.picked_up_at ? formatDateTime(props.rental.picked_up_at) : 'Menunggu pengambilan',
        active: Boolean(props.rental.picked_up_at),
        icon: PackageCheck,
    },
    {
        label: props.rental.returned_at ? 'Sudah dikembalikan' : 'Jadwal kembali',
        value: props.rental.returned_at ? formatDateTime(props.rental.returned_at) : formatDate(props.rental.return_due_at),
        active: Boolean(props.rental.returned_at),
        icon: CheckCircle2,
    },
]
</script>

<template>
    <Head :title="`Detail Order ${rental.invoice_number}`" />

    <main class="min-h-screen bg-diamond-bg px-4 py-5 text-diamond-text sm:px-6 lg:px-8" :style="themeStyle">
        <div class="mx-auto grid max-w-6xl gap-5">
            <header class="overflow-hidden rounded-[2rem] bg-white">
                <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[1fr_360px] lg:p-8">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl text-diamond-primary ring-2 ring-diamond-primary/15">
                                <img :src="logoUrl" alt="Diamond Kebaya & Jas" class="h-full w-full object-contain">
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-500">{{ store.name }}</p>
                                <h1 class="truncate text-2xl font-bold text-diamond-text sm:text-3xl">Detail order rental</h1>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="statusClasses(rental.status)">
                                {{ statusLabel(rental.status) }}
                            </span>
                            <span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="statusClasses(rental.payment_status)">
                                {{ statusLabel(rental.payment_status) }}
                            </span>
                            <span class="rounded-full bg-diamond-accent-soft px-3 py-1.5 text-xs font-bold text-diamond-accent">
                                {{ rental.invoice_number }}
                            </span>
                        </div>

                        <p class="mt-5 max-w-2xl text-sm leading-6 text-slate-600">
                            Halo {{ rental.customer?.name || 'Customer' }}, berikut detail pesanan rental Anda. Simpan halaman ini untuk melihat jadwal, item yang disewa, pembayaran, dan status pengembalian.
                        </p>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <a
                                v-if="storeWhatsappUrl"
                                :href="storeWhatsappUrl"
                                target="_blank"
                                class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl bg-diamond-primary px-5 text-sm font-bold text-white transition hover:bg-diamond-primary-dark"
                            >
                                <MessageCircle :size="18" />
                                Hubungi toko
                            </a>
                            <Link
                                v-if="!publicView"
                                :href="route('rentals.show', rental.id)"
                                class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50"
                            >
                                Kembali ke detail rental
                            </Link>
                        </div>
                    </div>

                    <aside class="grid content-start gap-3 rounded-[1.5rem] border border-diamond-primary/15 bg-diamond-primary-soft p-5 text-diamond-text">
                        <p class="text-sm font-semibold text-diamond-muted">Total tagihan</p>
                        <p class="break-words text-3xl font-bold text-diamond-primary">{{ formatMoney(rental.total_amount) }}</p>
                        <div class="mt-2 grid gap-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-diamond-muted">Sudah dibayar</span>
                                <span class="font-bold text-diamond-text">{{ formatMoney(rental.paid_amount) }}</span>
                            </div>
                            <div class="flex justify-between gap-3 border-t border-diamond-primary/15 pt-2">
                                <span class="text-diamond-muted">Sisa bayar</span>
                                <span class="font-bold" :class="Number(rental.remaining_amount || 0) > 0 ? 'text-diamond-danger' : 'text-emerald-700'">
                                    {{ formatMoney(rental.remaining_amount) }}
                                </span>
                            </div>
                        </div>
                    </aside>
                </div>
            </header>

            <section class="grid gap-5 lg:grid-cols-[1fr_380px]">
                <div class="grid gap-5">
                    <section class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <CalendarClock class="text-diamond-primary" :size="22" />
                            <h2 class="text-lg font-bold text-diamond-text">Jadwal dan status</h2>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div v-for="step in timeline" :key="step.label" class="rounded-3xl bg-slate-50 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl" :class="step.active ? 'bg-diamond-primary-soft text-diamond-primary' : 'bg-slate-200 text-slate-500'">
                                        <component :is="step.icon" :size="18" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ step.label }}</p>
                                        <p class="mt-1 text-sm font-bold leading-5 text-diamond-text">{{ step.value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <PackageCheck class="text-diamond-primary" :size="22" />
                                <h2 class="text-lg font-bold text-diamond-text">Item rental</h2>
                            </div>
                            <span class="rounded-full bg-diamond-primary-soft px-3 py-1 text-xs font-bold text-diamond-primary">{{ items.length }} item</span>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <article v-for="item in items" :key="item.id" class="rounded-3xl border border-slate-100 bg-white p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-base font-bold text-diamond-text">{{ item.item_name_snapshot }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ item.variant_name_snapshot || 'Tanpa varian khusus' }}<span v-if="item.variant_sku" class="ml-2 rounded-full bg-diamond-surface-soft px-2 py-0.5 text-[11px] font-semibold text-diamond-muted">{{ item.variant_sku }}</span></p>
                                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-slate-500">
                                            <span v-if="item.package_name" class="rounded-full bg-slate-100 px-3 py-1">Paket: {{ item.package_name }}</span>
                                            <span v-if="item.notes" class="rounded-full bg-slate-100 px-3 py-1">{{ item.notes }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span class="inline-block rounded-full bg-diamond-accent-soft px-3 py-1 text-xs font-bold text-diamond-accent">Qty {{ item.quantity }}</span>
                                        <p class="mt-3 text-sm font-bold text-diamond-text">{{ formatMoney(item.final_price) }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                    <span class="text-slate-500">Harga satuan</span>
                                    <span class="font-bold text-diamond-text">{{ formatMoney(item.unit_price) }}</span>
                                </div>
                            </article>
                            <div v-if="items.length === 0" class="rounded-3xl bg-slate-50 p-6 text-center text-sm font-semibold text-slate-500">
                                Belum ada item rental.
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <CreditCard class="text-diamond-primary" :size="22" />
                            <h2 class="text-lg font-bold text-diamond-text">Riwayat pembayaran</h2>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <article v-for="payment in payments" :key="payment.id" class="flex items-start justify-between gap-4 rounded-3xl bg-slate-50 p-4">
                                <div class="min-w-0">
                                    <p class="font-bold text-diamond-text">{{ paymentTypeLabel(payment.payment_type) }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ formatDateTime(payment.paid_at) }}</p>
                                    <p v-if="payment.notes" class="mt-2 text-sm leading-5 text-slate-500">{{ payment.notes }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="font-bold text-diamond-primary">{{ formatMoney(payment.amount) }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ paymentMethodLabel(payment.payment_method) }}</p>
                                </div>
                            </article>
                            <div v-if="payments.length === 0" class="rounded-3xl bg-slate-50 p-6 text-center text-sm font-semibold text-slate-500">
                                Belum ada pembayaran tercatat.
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="grid content-start gap-5">
                    <section class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <UserRound class="text-diamond-primary" :size="22" />
                            <h2 class="text-lg font-bold text-diamond-text">Data customer</h2>
                        </div>
                        <dl class="mt-5 grid gap-3 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-semibold text-slate-500">Nama</dt>
                                <dd class="mt-1 font-bold text-diamond-text">{{ rental.customer?.name || '-' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-semibold text-slate-500">WhatsApp</dt>
                                <dd class="mt-1 font-bold text-diamond-text">{{ rental.customer?.whatsapp_number || '-' }}</dd>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <dt class="font-semibold text-slate-500">Jaminan</dt>
                                <dd class="mt-1 font-bold uppercase text-diamond-text">{{ rental.guarantee_type || 'Belum diserahkan' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <ShieldCheck class="text-diamond-primary" :size="22" />
                            <h2 class="text-lg font-bold text-diamond-text">Ringkasan tagihan</h2>
                        </div>
                        <dl class="mt-5 grid gap-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Subtotal</dt>
                                <dd class="font-bold text-diamond-text">{{ formatMoney(rental.subtotal_amount) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Adjustment</dt>
                                <dd class="font-bold text-diamond-text">{{ formatMoney(rental.custom_adjustment_amount) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Denda</dt>
                                <dd class="font-bold text-diamond-text">{{ formatMoney(rental.penalty_amount) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3">
                                <dt class="font-bold text-diamond-text">Total</dt>
                                <dd class="font-bold text-diamond-text">{{ formatMoney(rental.total_amount) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-500">Dibayar</dt>
                                <dd class="font-bold text-emerald-700">{{ formatMoney(rental.paid_amount) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 rounded-2xl bg-diamond-primary-soft px-4 py-3 text-diamond-text">
                                <dt class="font-bold text-diamond-primary">Sisa</dt>
                                <dd class="font-bold text-diamond-primary">{{ formatMoney(rental.remaining_amount) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <MapPin class="text-diamond-primary" :size="22" />
                            <h2 class="text-lg font-bold text-diamond-text">Info toko</h2>
                        </div>
                        <div class="mt-5 grid gap-3 text-sm leading-6 text-slate-600">
                            <p class="font-bold text-diamond-text">{{ store.name }}</p>
                            <p>{{ store.address || '-' }}</p>
                            <p>WhatsApp: {{ store.whatsapp_number || '-' }}</p>
                            <p v-if="store.footer_note" class="rounded-2xl bg-diamond-primary-soft p-4 font-semibold text-diamond-primary">{{ store.footer_note }}</p>
                        </div>
                    </section>

                    <section v-if="rental.notes" class="rounded-[2rem] bg-white p-5 sm:p-6">
                        <div class="flex items-center gap-3">
                            <Clock3 class="text-diamond-primary" :size="22" />
                            <h2 class="text-lg font-bold text-diamond-text">Catatan order</h2>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ rental.notes }}</p>
                    </section>
                </aside>
            </section>
        </div>
    </main>
</template>
