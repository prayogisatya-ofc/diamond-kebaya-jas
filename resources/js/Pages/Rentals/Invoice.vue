<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
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
        booked: 'Booking',
        picked_up: 'Diambil',
        returned: 'Dikembalikan',
        completed: 'Selesai',
        overdue: 'Terlambat',
        cancelled: 'Dibatalkan',
        unpaid: 'Belum bayar',
        dp: 'DP',
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

function printInvoice() {
    if (typeof window !== 'undefined') {
        window.print()
    }
}
</script>

<template>
    <Head :title="`Invoice ${rental.invoice_number}`" />

    <main class="invoice-shell min-h-screen bg-neutral-200 px-4 py-8 text-neutral-950 print:bg-white print:p-0">
        <div class="no-print mx-auto mb-5 flex max-w-[210mm] flex-col justify-between gap-3 rounded-xl bg-white px-4 py-3 sm:flex-row sm:items-center">
            <Link v-if="!publicView" :href="route('rentals.show', rental.id)" class="inline-flex min-h-10 cursor-pointer items-center text-sm font-semibold text-neutral-600 hover:text-neutral-950">
                Kembali ke detail rental
            </Link>
            <p v-else class="text-sm font-semibold text-neutral-600">
                Detail order rental
            </p>
            <button
                class="inline-flex min-h-10 cursor-pointer items-center justify-center rounded-lg bg-neutral-950 px-5 text-sm font-semibold text-white transition hover:bg-neutral-800"
                type="button"
                @click="printInvoice"
            >
                Print Invoice
            </button>
        </div>

        <article class="invoice-page mx-auto min-h-[297mm] max-w-[210mm] bg-white px-[16mm] py-[14mm]">
            <header class="grid gap-8 border-b-2 border-neutral-950 pb-6 sm:grid-cols-[1fr_auto] sm:items-start">
                <div class="flex min-w-0 gap-4">
                    <div v-if="store.logo_url" class="grid h-20 w-20 shrink-0 place-items-center border border-neutral-300 p-2">
                        <img :alt="store.name" class="max-h-full max-w-full object-contain" :src="store.logo_url">
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-[24px] font-bold uppercase leading-tight tracking-[0.08em] text-neutral-950">{{ store.name }}</h1>
                        <p class="mt-3 max-w-[118mm] text-[12px] leading-5 text-neutral-700">{{ store.address || '-' }}</p>
                        <p class="mt-1 text-[12px] leading-5 text-neutral-700">WhatsApp: {{ store.whatsapp_number || '-' }}</p>
                    </div>
                </div>

                <div class="text-left sm:text-right">
                    <p class="text-[28px] font-bold uppercase leading-none tracking-[0.16em] text-neutral-950">Invoice</p>
                    <p class="mt-3 text-[13px] font-bold text-neutral-950">{{ rental.invoice_number }}</p>
                    <p class="mt-1 text-[12px] text-neutral-600">{{ formatDate(rental.created_at) }}</p>
                </div>
            </header>

            <section class="grid gap-6 border-b border-neutral-300 py-6 sm:grid-cols-2">
                <div>
                    <p class="section-title">Ditagihkan kepada</p>
                    <dl class="mt-3 grid gap-2 text-[12px]">
                        <div class="info-row">
                            <dt>Nama customer</dt>
                            <dd>{{ rental.customer?.name || '-' }}</dd>
                        </div>
                        <div class="info-row">
                            <dt>WhatsApp</dt>
                            <dd>{{ rental.customer?.whatsapp_number || '-' }}</dd>
                        </div>
                        <div class="info-row">
                            <dt>Jaminan</dt>
                            <dd class="uppercase">{{ rental.guarantee_type || 'Belum diserahkan' }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <p class="section-title">Detail rental</p>
                    <dl class="mt-3 grid gap-2 text-[12px]">
                        <div class="info-row">
                            <dt>Jadwal ambil</dt>
                            <dd>{{ formatDate(rental.pickup_at) }}</dd>
                        </div>
                        <div class="info-row">
                            <dt>Jadwal kembali</dt>
                            <dd>{{ formatDate(rental.return_due_at) }}</dd>
                        </div>
                        <div class="info-row">
                            <dt>Status rental</dt>
                            <dd>{{ statusLabel(rental.status) }}</dd>
                        </div>
                        <div class="info-row">
                            <dt>Status pembayaran</dt>
                            <dd>{{ statusLabel(rental.payment_status) }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="py-6">
                <p class="section-title">Daftar item rental</p>
                <div class="mt-3 overflow-hidden border border-neutral-950">
                    <table class="invoice-table">
                        <thead>
                            <tr>
                                <th class="w-10 text-center">No</th>
                                <th>Item</th>
                                <th>Varian</th>
                                <th class="w-12 text-center">Qty</th>
                                <th class="text-right">Harga</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.id" class="avoid-break">
                                <td class="text-center">{{ index + 1 }}</td>
                                <td>
                                    <p class="font-semibold text-neutral-950">{{ item.item_name_snapshot }}</p>
                                    <p v-if="item.package_name" class="mt-1 text-[10px] text-neutral-600">Paket: {{ item.package_name }}</p>
                                    <p v-if="item.notes" class="mt-1 text-[10px] text-neutral-600">Catatan: {{ item.notes }}</p>
                                </td>
                                <td>{{ item.variant_name_snapshot || '-' }}</td>
                                <td class="text-center">{{ item.quantity }}</td>
                                <td class="text-right">{{ formatMoney(item.unit_price) }}</td>
                                <td class="text-right font-semibold text-neutral-950">{{ formatMoney(item.final_price) }}</td>
                            </tr>
                            <tr v-if="items.length === 0">
                                <td class="py-8 text-center text-neutral-500" colspan="6">Belum ada item rental.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-8 border-t border-neutral-300 pt-6 sm:grid-cols-[1fr_72mm]">
                <div class="grid content-start gap-6">
                    <div>
                        <p class="section-title">Riwayat pembayaran</p>
                        <div class="mt-3 overflow-hidden border border-neutral-300">
                            <table class="invoice-table payment-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Tipe</th>
                                        <th>Metode</th>
                                        <th class="text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="payment in payments" :key="payment.id" class="avoid-break">
                                        <td>{{ formatDate(payment.paid_at) }}</td>
                                        <td>{{ paymentTypeLabel(payment.payment_type) }}</td>
                                        <td>{{ paymentMethodLabel(payment.payment_method) }}</td>
                                        <td class="text-right font-semibold text-neutral-950">{{ formatMoney(payment.amount) }}</td>
                                    </tr>
                                    <tr v-if="payments.length === 0">
                                        <td class="py-6 text-center text-neutral-500" colspan="4">Belum ada pembayaran.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="rental.notes || store.footer_note" class="grid gap-4">
                        <div v-if="rental.notes">
                            <p class="section-title">Catatan transaksi</p>
                            <p class="mt-2 text-[11px] leading-5 text-neutral-700">{{ rental.notes }}</p>
                        </div>
                        <div v-if="store.footer_note">
                            <p class="section-title">Catatan toko</p>
                            <p class="mt-2 text-[11px] leading-5 text-neutral-700">{{ store.footer_note }}</p>
                        </div>
                    </div>
                </div>

                <aside>
                    <p class="section-title">Ringkasan tagihan</p>
                    <dl class="summary-box mt-3">
                        <div>
                            <dt>Subtotal</dt>
                            <dd>{{ formatMoney(rental.subtotal_amount) }}</dd>
                        </div>
                        <div>
                            <dt>Adjustment</dt>
                            <dd>{{ formatMoney(rental.custom_adjustment_amount) }}</dd>
                        </div>
                        <div>
                            <dt>Denda manual</dt>
                            <dd>{{ formatMoney(rental.penalty_amount) }}</dd>
                        </div>
                        <div class="total-row">
                            <dt>Total tagihan</dt>
                            <dd>{{ formatMoney(rental.total_amount) }}</dd>
                        </div>
                        <div>
                            <dt>Sudah dibayar</dt>
                            <dd>{{ formatMoney(rental.paid_amount) }}</dd>
                        </div>
                        <div class="remaining-row">
                            <dt>Sisa pembayaran</dt>
                            <dd>{{ formatMoney(rental.remaining_amount) }}</dd>
                        </div>
                    </dl>
                </aside>
            </section>

            <section class="mt-12 grid gap-10 text-[12px] sm:grid-cols-2">
                <div>
                    <p class="font-semibold text-neutral-950">Diterima oleh,</p>
                    <div class="mt-16 w-48 border-t border-neutral-950 pt-2 text-center">
                        {{ rental.customer?.name || 'Customer' }}
                    </div>
                </div>
                <div class="sm:text-right">
                    <p class="font-semibold text-neutral-950">Hormat kami,</p>
                    <div class="mt-16 inline-block w-48 border-t border-neutral-950 pt-2 text-center">
                        {{ rental.creator?.name || store.name }}
                    </div>
                </div>
            </section>
        </article>
    </main>
</template>

<style scoped>
.invoice-page {
    color: #171717;
    font-family: Arial, Helvetica, sans-serif;
}

.section-title {
    color: #171717;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.info-row {
    display: grid;
    grid-template-columns: 34mm minmax(0, 1fr);
    gap: 10px;
}

.info-row dt {
    color: #666;
}

.info-row dd {
    color: #171717;
    font-weight: 600;
    min-width: 0;
    overflow-wrap: anywhere;
}

.invoice-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 11px;
    line-height: 1.45;
}

.invoice-table th {
    background: #f5f5f5;
    border-bottom: 1px solid #171717;
    color: #171717;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 9px 8px;
    text-transform: uppercase;
}

.invoice-table td {
    border-bottom: 1px solid #d4d4d4;
    color: #404040;
    padding: 9px 8px;
    vertical-align: top;
}

.invoice-table tbody tr:last-child td {
    border-bottom: 0;
}

.summary-box {
    border: 1px solid #171717;
    display: grid;
    font-size: 12px;
}

.summary-box div {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 9px 10px;
}

.summary-box dt {
    color: #525252;
}

.summary-box dd {
    color: #171717;
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
}

.summary-box .total-row {
    border-top: 1px solid #171717;
    border-bottom: 1px solid #171717;
    font-size: 13px;
}

.summary-box .total-row dt,
.summary-box .remaining-row dt {
    color: #171717;
    font-weight: 700;
}

.summary-box .remaining-row {
    background: #f5f5f5;
}

@media print {
    @page {
        size: A4;
        margin: 12mm;
    }

    html,
    body {
        background: #fff !important;
    }

    .no-print {
        display: none !important;
    }

    .invoice-shell {
        background: #fff !important;
        padding: 0 !important;
    }

    .invoice-page {
        min-height: auto !important;
        max-width: none !important;
        padding: 0 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .avoid-break,
    table,
    tr,
    aside,
    section {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .invoice-table th {
        background: #f5f5f5 !important;
    }

    .summary-box .remaining-row {
        background: #f5f5f5 !important;
    }
}
</style>
