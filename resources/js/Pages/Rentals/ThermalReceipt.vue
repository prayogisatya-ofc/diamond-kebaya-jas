<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const props = defineProps({
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

const printStatus = ref('')
const printError = ref('')
const serialPrinting = ref(false)

const serialAvailable = computed(() => {
    return typeof navigator !== 'undefined' && 'serial' in navigator
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

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: '2-digit',
    }).format(parsedDate)
}

function formatDateTime(value) {
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

function normalizePrinterText(value) {
    return String(value ?? '')
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\x20-\x7E\n\r]/g, '')
}

function padRight(value, width) {
    const text = normalizePrinterText(value).slice(0, width)

    return text.padEnd(width, ' ')
}

function line(left, right = '', width = 32) {
    const leftText = normalizePrinterText(left)
    const rightText = normalizePrinterText(right)
    const availableLeftWidth = Math.max(1, width - rightText.length)

    return `${padRight(leftText, availableLeftWidth)}${rightText}\n`
}

function wrapText(value, width = 32) {
    const words = normalizePrinterText(value).split(/\s+/).filter(Boolean)
    const lines = []
    let currentLine = ''

    words.forEach((word) => {
        if ((currentLine.length + word.length + 1) > width) {
            if (currentLine) {
                lines.push(currentLine)
            }

            currentLine = word
            return
        }

        currentLine = currentLine ? `${currentLine} ${word}` : word
    })

    if (currentLine) {
        lines.push(currentLine)
    }

    return lines.length ? lines.join('\n') : ''
}

function centerText(value, width = 32) {
    const text = normalizePrinterText(value).slice(0, width)
    const padding = Math.max(0, Math.floor((width - text.length) / 2))

    return `${' '.repeat(padding)}${text}\n`
}

function printerCenteredWrappedText(value, width = 32) {
    return wrapText(value, width)
        .split('\n')
        .filter(Boolean)
        .map((text) => `${text}\n`)
        .join('')
}

function buildEscPosReceipt() {
    const width = 32
    const separator = `${'-'.repeat(width)}\n`
    const feedAndCut = '\n\n\n\x1D\x56\x00'
    let text = '\x1B\x40'

    text += '\x1B\x61\x01'
    text += printerCenteredWrappedText(props.store.name, width)
    text += printerCenteredWrappedText(props.store.address, width)
    text += printerCenteredWrappedText(`WA: ${props.store.whatsapp_number}`, width)
    text += '\x1B\x61\x00'
    text += separator
    text += line('No', props.rental.invoice_number, width)
    text += line('Tgl', formatDateTime(props.rental.created_at), width)
    text += line('Kasir', props.rental.creator?.name || '-', width)
    text += line('Customer', props.rental.customer?.name || '-', width)
    text += line('WA', props.rental.customer?.whatsapp_number || '-', width)
    text += line('Jaminan', props.rental.guarantee_type || 'Belum ada', width)
    text += line('Ambil', formatDate(props.rental.pickup_at), width)
    text += line('Kembali', formatDate(props.rental.return_due_at), width)
    text += line('Status', `${statusLabel(props.rental.status)} / ${statusLabel(props.rental.payment_status)}`, width)
    text += separator
    text += centerText('ITEM RENTAL', width)

    props.items.forEach((item) => {
        const itemName = item.variant_name_snapshot
            ? `${item.item_name_snapshot} (${item.variant_name_snapshot})${item.variant_sku ? ` [SKU: ${item.variant_sku}]` : ""}`
            : item.item_name_snapshot

        text += `${wrapText(itemName, width)}\n`

        text += line(`${item.quantity} x ${formatMoney(item.unit_price)}`, formatMoney(item.final_price), width)
    })

    if (!props.items.length) {
        text += centerText('Belum ada item', width)
    }

    text += separator
    text += line('Subtotal', formatMoney(props.rental.subtotal_amount), width)

    if (Number(props.rental.custom_adjustment_amount || 0) !== 0) {
        text += line('Adjustment', formatMoney(props.rental.custom_adjustment_amount), width)
    }

    if (Number(props.rental.penalty_amount || 0) > 0) {
        text += line(`Denda ${Number(props.rental.penalty_days || 0)} hari`, formatMoney(props.rental.penalty_amount), width)
    }

    text += line('TOTAL', formatMoney(props.rental.total_amount), width)
    text += line('Dibayar', formatMoney(props.rental.paid_amount), width)
    text += line('Sisa', formatMoney(props.rental.remaining_amount), width)

    if (props.payments.length) {
        text += separator
        text += centerText('PEMBAYARAN', width)

        props.payments.forEach((payment) => {
            text += line(
                `${paymentTypeLabel(payment.payment_type)} ${paymentMethodLabel(payment.payment_method)}`,
                formatMoney(payment.amount),
                width,
            )
        })
    }

    text += separator
    text += '\x1B\x61\x01'
    text += printerCenteredWrappedText(props.store.footer_note, width)
    text += printerCenteredWrappedText('Terima kasih', width)
    text += feedAndCut

    return new TextEncoder().encode(text)
}

async function printSerialReceipt() {
    printError.value = ''
    printStatus.value = ''

    if (!serialAvailable.value) {
        printError.value = 'Browser ini belum mendukung Web Serial. Gunakan Chrome/Edge desktop, atau Chrome Android versi terbaru.'
        return
    }

    let port = null
    let writer = null

    try {
        serialPrinting.value = true
        printStatus.value = 'Pilih printer Woya yang sudah di-pair...'

        port = await navigator.serial.requestPort()

        printStatus.value = 'Membuka koneksi serial...'
        await port.open({ baudRate: 9600 })

        writer = port.writable.getWriter()
        printStatus.value = 'Mengirim data struk...'
        await writer.write(buildEscPosReceipt())
        printStatus.value = 'Struk berhasil dikirim ke printer.'
    } catch (error) {
        printError.value = error?.message || 'Gagal print via serial Bluetooth.'
    } finally {
        if (writer) {
            writer.releaseLock()
        }

        if (port?.readable || port?.writable) {
            await port.close().catch(() => {})
        }

        serialPrinting.value = false
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
                class="min-h-10 rounded-xl bg-neutral-800 px-4 text-xs font-bold text-white disabled:opacity-60"
                :disabled="serialPrinting"
                type="button"
                @click="printSerialReceipt"
            >
                {{ serialPrinting ? 'Mengirim...' : 'Print Bluetooth Woya' }}
            </button>
            <p class="text-[11px] leading-4 text-neutral-500">
                Pastikan printer Woya sudah di-pair ke perangkat, lalu pilih printer dari dialog browser.
            </p>
            <p class="text-[11px] leading-4 text-neutral-500">
                Support API: Web Serial {{ serialAvailable ? 'ada' : 'tidak ada' }}.
            </p>
            <p v-if="printStatus" class="rounded-xl bg-emerald-50 p-2 text-[11px] font-semibold leading-4 text-emerald-700">
                {{ printStatus }}
            </p>
            <p v-if="printError" class="rounded-xl bg-red-50 p-2 text-[11px] font-semibold leading-4 text-red-700">
                {{ printError }}
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
                    <strong>{{ formatDateTime(rental.created_at) }}</strong>
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
                        <span v-if="item.variant_name_snapshot">({{ item.variant_name_snapshot }})</span><span v-if="item.variant_sku" class="sku-badge">SKU: {{ item.variant_sku }}</span>
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
                        <span>{{ formatDateTime(payment.paid_at) }} {{ paymentTypeLabel(payment.payment_type) }} {{ paymentMethodLabel(payment.payment_method) }}</span>
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
