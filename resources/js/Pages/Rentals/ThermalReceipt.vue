<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
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
        month: '2-digit',
        year: '2-digit',
    }).format(parsedDate)
    const timePart = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        hourCycle: 'h23',
    }).format(parsedDate)

    return `${datePart} ${timePart}`
}

function statusLabel(value) {
    return {
        booked: 'Booking',
        picked_up: 'Diambil',
        returned: 'Kembali',
        completed: 'Selesai',
        overdue: 'Terlambat',
        cancelled: 'Batal',
        unpaid: 'Belum bayar',
        dp: 'DP',
        paid: 'Lunas',
        overpaid: 'Lebih bayar',
    }[value] || value || '-'
}

function paymentTypeLabel(value) {
    return {
        dp: 'DP',
        pelunasan: 'Lunas',
        denda: 'Denda',
        refund: 'Refund',
        adjustment: 'Adjust',
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

function printReceipt() {
    if (typeof window !== 'undefined') {
        window.print()
    }
}
</script>

<template>
    <Head :title="`Struk ${rental.invoice_number}`" />

    <main class="min-h-screen bg-neutral-200 px-3 py-5 text-neutral-950 print:bg-white print:p-0">
        <div class="no-print mx-auto mb-4 grid max-w-sm gap-2 rounded-2xl bg-white p-3">
            <Link :href="route('rentals.show', rental.id)" class="text-xs font-semibold text-neutral-600">
                Kembali ke detail rental
            </Link>
            <button
                class="min-h-10 rounded-xl bg-neutral-950 px-4 text-xs font-bold text-white"
                type="button"
                @click="printReceipt"
            >
                Print Struk Thermal 58mm
            </button>
            <p class="text-[11px] leading-4 text-neutral-500">
                Pilih printer thermal Woya 58mm dari dialog print browser. Matikan header/footer browser jika tersedia.
            </p>
        </div>

        <article class="receipt mx-auto bg-white">
            <header class="center">
                <h1>{{ store.name }}</h1>
                <p>{{ store.address }}</p>
                <p>WA: {{ store.whatsapp_number }}</p>
            </header>

            <div class="line" />

            <section class="rows">
                <div>
                    <span>No</span>
                    <strong>{{ rental.invoice_number }}</strong>
                </div>
                <div>
                    <span>Tgl</span>
                    <strong>{{ formatDate(rental.created_at) }}</strong>
                </div>
                <div>
                    <span>Kasir</span>
                    <strong>{{ rental.creator?.name || '-' }}</strong>
                </div>
                <div>
                    <span>Customer</span>
                    <strong>{{ rental.customer?.name || '-' }}</strong>
                </div>
                <div>
                    <span>WA</span>
                    <strong>{{ rental.customer?.whatsapp_number || '-' }}</strong>
                </div>
                <div>
                    <span>Jaminan</span>
                    <strong class="uppercase">{{ rental.guarantee_type || 'Belum ada' }}</strong>
                </div>
                <div>
                    <span>Ambil</span>
                    <strong>{{ formatDate(rental.pickup_at) }}</strong>
                </div>
                <div>
                    <span>Kembali</span>
                    <strong>{{ formatDate(rental.return_due_at) }}</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong>{{ statusLabel(rental.status) }} / {{ statusLabel(rental.payment_status) }}</strong>
                </div>
            </section>

            <div class="line" />

            <section>
                <p class="section-title">Item Rental</p>
                <div v-for="item in items" :key="item.id" class="item">
                    <div class="item-name">
                        <strong>{{ item.item_name_snapshot }}</strong>
                        <span v-if="item.variant_name_snapshot">({{ item.variant_name_snapshot }})</span>
                    </div>
                    <p v-if="item.package_name" class="muted">Paket: {{ item.package_name }}</p>
                    <p v-if="item.notes" class="muted">Catatan: {{ item.notes }}</p>
                    <div class="item-total">
                        <span>{{ item.quantity }} x {{ formatMoney(item.unit_price) }}</span>
                        <strong>{{ formatMoney(item.final_price) }}</strong>
                    </div>
                </div>
                <p v-if="items.length === 0" class="muted center">Belum ada item.</p>
            </section>

            <div class="line" />

            <section class="totals">
                <div>
                    <span>Subtotal</span>
                    <strong>{{ formatMoney(rental.subtotal_amount) }}</strong>
                </div>
                <div v-if="Number(rental.custom_adjustment_amount || 0) !== 0">
                    <span>Adjustment</span>
                    <strong>{{ formatMoney(rental.custom_adjustment_amount) }}</strong>
                </div>
                <div v-if="Number(rental.penalty_amount || 0) > 0">
                    <span>Denda {{ Number(rental.penalty_days || 0) }} hari</span>
                    <strong>{{ formatMoney(rental.penalty_amount) }}</strong>
                </div>
                <div class="grand">
                    <span>Total</span>
                    <strong>{{ formatMoney(rental.total_amount) }}</strong>
                </div>
                <div>
                    <span>Dibayar</span>
                    <strong>{{ formatMoney(rental.paid_amount) }}</strong>
                </div>
                <div class="grand">
                    <span>Sisa</span>
                    <strong>{{ formatMoney(rental.remaining_amount) }}</strong>
                </div>
            </section>

            <template v-if="payments.length">
                <div class="line" />
                <section>
                    <p class="section-title">Pembayaran</p>
                    <div v-for="payment in payments" :key="payment.id" class="payment">
                        <span>{{ formatDate(payment.paid_at) }} {{ paymentTypeLabel(payment.payment_type) }} {{ paymentMethodLabel(payment.payment_method) }}</span>
                        <strong>{{ formatMoney(payment.amount) }}</strong>
                    </div>
                </section>
            </template>

            <template v-if="rental.notes">
                <div class="line" />
                <section>
                    <p class="section-title">Catatan</p>
                    <p>{{ rental.notes }}</p>
                </section>
            </template>

            <div class="line" />

            <footer class="center">
                <p>{{ store.footer_note }}</p>
                <p class="thanks">Terima kasih</p>
            </footer>
        </article>
    </main>
</template>

<style scoped>
.receipt {
    color: #000;
    font-family: "Courier New", Courier, monospace;
    font-size: 10.5px;
    line-height: 1.28;
    padding: 3mm;
    width: 58mm;
}

.center {
    text-align: center;
}

h1 {
    font-size: 13px;
    font-weight: 700;
    line-height: 1.15;
    margin: 0 0 2mm;
    text-transform: uppercase;
}

p {
    margin: 0;
    overflow-wrap: anywhere;
}

.line {
    border-top: 1px dashed #000;
    margin: 2mm 0;
}

.section-title {
    font-size: 10px;
    font-weight: 700;
    margin-bottom: 1mm;
    text-align: center;
    text-transform: uppercase;
}

.rows,
.totals {
    display: grid;
    gap: 0.8mm;
}

.rows div,
.totals div,
.item-total,
.payment {
    display: flex;
    gap: 2mm;
    justify-content: space-between;
}

.rows span {
    flex: 0 0 16mm;
}

.rows strong,
.totals strong,
.item-total strong,
.payment strong {
    text-align: right;
}

.item {
    display: grid;
    gap: 0.8mm;
    padding: 1mm 0;
}

.item + .item {
    border-top: 1px dotted #999;
}

.item-name {
    display: grid;
    gap: 0.5mm;
}

.muted {
    color: #333;
    font-size: 9.5px;
}

.grand {
    font-size: 11.5px;
    font-weight: 700;
}

.thanks {
    font-weight: 700;
    margin-top: 1.5mm;
    text-transform: uppercase;
}

@media print {
    @page {
        size: 58mm auto;
        margin: 0;
    }

    html,
    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 58mm;
    }

    .no-print {
        display: none !important;
    }

    .receipt {
        margin: 0 !important;
        padding: 2mm 3mm 4mm !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
        width: 58mm !important;
    }
}
</style>
