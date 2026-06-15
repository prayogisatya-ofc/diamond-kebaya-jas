<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { ArrowLeft, CalendarClock, CheckCircle2, CreditCard, PackagePlus, Pencil, Plus, Printer, ReceiptText, RotateCcw, Save, Search, ShoppingBag, Trash2, X, XCircle } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import DateTimePicker from '@/Components/DateTimePicker.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { useConfirm } from '@/Composables/useConfirm'
import { printRentalThermalReceipt } from '@/Utils/thermalReceiptPrinter'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
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
    products: {
        type: Array,
        required: true,
    },
    store: {
        type: Object,
        required: true,
    },
})
const { confirmAction } = useConfirm()
const itemModalOpen = ref(false)
const paymentModalOpen = ref(false)
const pickUpModalOpen = ref(false)
const returnModalOpen = ref(false)
const productSearch = ref('')
const editingItem = ref(null)
const thermalPrintProcessing = ref(false)
const thermalPrintStatus = ref('')
const thermalPrintError = ref('')

const form = useForm({
    rental_package_id: '',
    product_id: '',
    product_variant_id: '',
    quantity: 1,
    unit_price: '',
    discount_amount: 0,
    notes: '',
})

const paymentForm = useForm({
    payment_type: 'dp',
    payment_method: 'cash',
    amount: '',
    paid_at: '',
    notes: '',
})

const pickUpForm = useForm({
    guarantee_type: props.rental.guarantee_type ?? '',
    payment_amount: '',
    payment_method: 'cash',
    paid_at: '',
    payment_notes: '',
})
const completeForm = useForm({})
const cancelForm = useForm({})
const deleteItemForm = useForm({})
const deletePaymentForm = useForm({})
const returnForm = useForm({
    returned_at: '',
    penalty_amount: 0,
    penalty_payment_method: 'cash',
    penalty_paid_at: '',
    penalty_notes: '',
})

const remainingAmount = computed(() => Number(props.rental.remaining_amount || 0))
const pickUpPaymentRequired = computed(() => remainingAmount.value > 0)

const productById = computed(() => {
    return props.products.reduce((carry, product) => {
        carry[product.id] = product

        return carry
    }, {})
})

const estimatedPenaltyDays = computed(() => {
    if (!returnForm.returned_at || !props.rental.return_due_at) {
        return 0
    }

    const returnedAt = new Date(returnForm.returned_at)
    const returnDueAt = new Date(props.rental.return_due_at)

    if (Number.isNaN(returnedAt.getTime()) || Number.isNaN(returnDueAt.getTime())) {
        return 0
    }

    const returnedDate = new Date(returnedAt.getFullYear(), returnedAt.getMonth(), returnedAt.getDate())
    const returnDueDate = new Date(returnDueAt.getFullYear(), returnDueAt.getMonth(), returnDueAt.getDate())

    if (returnedDate <= returnDueDate) {
        return 0
    }

    return Math.round((returnedDate.getTime() - returnDueDate.getTime()) / 86400000)
})

const filteredProducts = computed(() => {
    const keyword = productSearch.value.trim().toLowerCase()

    if (!keyword) {
        return props.products
    }

    return props.products.filter((product) => {
        return [product.name, product.code]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(keyword))
    })
})

const selectedProduct = computed(() => productById.value[form.product_id] || null)

function currentDateTimeInput() {
    const date = new Date()
    const pad = (value) => String(value).padStart(2, '0')

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

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
        dateStyle: 'medium',
    }).format(parsedDate)

    const timePart = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        hourCycle: 'h23',
    }).format(parsedDate)

    return `${datePart}, ${timePart}`
}

function fieldClasses() {
    return 'min-h-12 w-full min-w-0 max-w-full truncate rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}

function textareaClasses() {
    return 'min-h-24 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm leading-6 text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}

function paymentTypeLabel(value) {
    return {
        dp: 'DP',
        pelunasan: 'Pelunasan',
        denda: 'Denda',
        refund: 'Refund',
        adjustment: 'Adjustment',
    }[value] || value
}

function paymentMethodLabel(value) {
    return {
        cash: 'Cash',
        transfer: 'Transfer',
        qris: 'QRIS',
        debit: 'Debit',
        other: 'Lainnya',
    }[value] || value
}

function variantsFor(productId) {
    return productById.value[productId]?.variants ?? []
}

function itemImage(item) {
    return productById.value[item.product_id]?.image_url || null
}

function lineTotal() {
    return Math.max(0, Number(form.quantity || 0) * Number(form.unit_price || 0))
}

function defaultPrice() {
    const product = productById.value[form.product_id]
    const variant = variantsFor(form.product_id).find((candidate) => candidate.id === form.product_variant_id)

    return variant?.rental_price || product?.base_rental_price || ''
}

function onProductChanged() {
    const variants = variantsFor(form.product_id)

    if (!variants.some((variant) => variant.id === form.product_variant_id)) {
        form.product_variant_id = ''
    }

    form.unit_price = defaultPrice()
}

function onVariantChanged() {
    form.unit_price = defaultPrice()
}

function resetItemForm() {
    form.reset()
    form.quantity = 1
    form.discount_amount = 0
    editingItem.value = null
}

function openItemModal() {
    resetItemForm()
    productSearch.value = ''
    itemModalOpen.value = true
}

function openEditItem(item) {
    editingItem.value = item
    productSearch.value = ''
    form.rental_package_id = item.rental_package?.id || ''
    form.product_id = item.product_id
    form.product_variant_id = item.product_variant_id || ''
    form.quantity = item.quantity
    form.unit_price = item.unit_price
    form.discount_amount = 0
    form.notes = item.notes || ''
    itemModalOpen.value = true
}

function closeItemModal() {
    itemModalOpen.value = false
}

function chooseProduct(product) {
    form.product_id = product.id
    form.product_variant_id = ''
    form.unit_price = product.base_rental_price || ''
}

function openPaymentModal() {
    paymentForm.reset()
    paymentForm.payment_type = 'dp'
    paymentForm.payment_method = 'cash'
    paymentModalOpen.value = true
}

function closePaymentModal() {
    paymentModalOpen.value = false
}

function openPickUpModal() {
    pickUpForm.guarantee_type = props.rental.guarantee_type ?? ''
    pickUpForm.payment_amount = pickUpPaymentRequired.value ? String(remainingAmount.value) : ''
    pickUpForm.payment_method = 'cash'
    pickUpForm.paid_at = pickUpPaymentRequired.value ? currentDateTimeInput() : ''
    pickUpForm.payment_notes = pickUpPaymentRequired.value ? 'Pelunasan saat barang diambil.' : ''
    pickUpModalOpen.value = true
}

function closePickUpModal() {
    pickUpModalOpen.value = false
    pickUpForm.clearErrors()
}

function openReturnModal() {
    returnForm.returned_at = currentDateTimeInput()
    returnForm.penalty_amount = 0
    returnForm.penalty_payment_method = 'cash'
    returnForm.penalty_paid_at = currentDateTimeInput()
    returnForm.penalty_notes = ''
    returnModalOpen.value = true
}

function closeReturnModal() {
    returnModalOpen.value = false
    returnForm.clearErrors()
}

function submitItem() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetItemForm()
            closeItemModal()
        },
    }

    if (editingItem.value) {
        form.put(route('rentals.items.update', [props.rental.id, editingItem.value.id]), options)

        return
    }

    form.post(route('rentals.items.store', props.rental.id), options)
}

function submitPayment() {
    paymentForm.post(route('rentals.payments.store', props.rental.id), {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset()
            paymentForm.payment_type = 'dp'
            paymentForm.payment_method = 'cash'
            closePaymentModal()
        },
    })
}

function markPickedUp() {
    pickUpForm.post(route('rentals.pick-up', props.rental.id), {
        preserveScroll: true,
        onSuccess: () => closePickUpModal(),
    })
}

function markReturned() {
    returnForm.post(route('rentals.return', props.rental.id), {
        preserveScroll: true,
        onSuccess: () => {
            returnForm.reset()
            returnForm.penalty_amount = 0
            returnForm.penalty_payment_method = 'cash'
            closeReturnModal()
        },
    })
}

function completeRental() {
    completeForm.post(route('rentals.complete', props.rental.id), {
        preserveScroll: true,
    })
}

async function cancelRental() {
    const confirmed = await confirmAction({
        title: 'Batalkan rental?',
        message: `Rental ${props.rental.invoice_number} akan dibatalkan. Pembayaran lama tetap tersimpan.`,
        confirmLabel: 'Ya, batalkan',
        tone: 'warning',
    })

    if (!confirmed) {
        return
    }

    cancelForm.post(route('rentals.cancel', props.rental.id), {
        preserveScroll: true,
    })
}

async function destroyRental() {
    const confirmed = await confirmAction({
        title: 'Hapus rental?',
        message: `Rental ${props.rental.invoice_number}, item, pembayaran, dan notifikasi terkait akan dihapus permanen. Aksi ini dipakai hanya untuk pembersihan data.`,
        confirmLabel: 'Ya, hapus rental',
        tone: 'danger',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('rentals.destroy', props.rental.id), {
        preserveScroll: true,
    })
}

async function deleteItem(item) {
    const confirmed = await confirmAction({
        title: 'Hapus item rental?',
        message: `${item.item_name_snapshot} akan dihapus dari transaksi ini dan total rental akan dihitung ulang.`,
        confirmLabel: 'Ya, hapus item',
        tone: 'danger',
    })

    if (!confirmed) {
        return
    }

    deleteItemForm.delete(route('rentals.items.destroy', [props.rental.id, item.id]), {
        preserveScroll: true,
        onSuccess: () => {
            closeItemModal()
        },
    })
}

async function deletePayment(payment) {
    const confirmed = await confirmAction({
        title: 'Hapus pembayaran?',
        message: `Pembayaran ${paymentTypeLabel(payment.payment_type)} senilai ${formatMoney(payment.amount)} akan dihapus dan ringkasan pembayaran dihitung ulang.`,
        confirmLabel: 'Ya, hapus pembayaran',
        tone: 'danger',
    })

    if (!confirmed) {
        return
    }

    deletePaymentForm.delete(route('rentals.payments.destroy', [props.rental.id, payment.id]), {
        preserveScroll: true,
    })
}

async function printThermalReceipt() {
    thermalPrintStatus.value = ''
    thermalPrintError.value = ''

    try {
        thermalPrintProcessing.value = true

        await printRentalThermalReceipt({
            store: props.store,
            rental: props.rental,
            items: props.items,
            payments: props.payments,
        }, (status) => {
            thermalPrintStatus.value = status
        })
    } catch (error) {
        thermalPrintError.value = error?.message || 'Gagal print struk.'
    } finally {
        thermalPrintProcessing.value = false
    }
}
</script>

<template>
    <Head :title="rental.invoice_number" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Detail rental"
            :title="rental.invoice_number"
            :subtitle="`${rental.customer?.name || '-'} - ${rental.customer?.whatsapp_number || '-'}`"
        >
            <template #actions>
                <Button :href="route('rentals.index')" variant="secondary">
                    <ArrowLeft :size="18" />
                    Kembali
                </Button>
                <Button :href="route('rentals.invoice', rental.id)" variant="secondary">
                    <ReceiptText :size="18" />
                    Invoice
                </Button>
                <Button variant="accent" :disabled="thermalPrintProcessing" @click="printThermalReceipt">
                    <Printer :size="18" />
                    {{ thermalPrintProcessing ? 'Mengirim...' : 'Print Struk' }}
                </Button>
                <Button v-if="rental.actions.can_delete" variant="danger" @click="destroyRental">
                    <Trash2 :size="18" />
                    Hapus rental
                </Button>
            </template>
        </PageHeader>

        <div v-if="thermalPrintStatus || thermalPrintError" class="grid gap-2">
            <p v-if="thermalPrintStatus" class="rounded-2xl bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">
                {{ thermalPrintStatus }}
            </p>
            <p v-if="thermalPrintError" class="rounded-2xl bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">
                {{ thermalPrintError }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card class="min-w-0">
                <p class="text-sm font-semibold text-diamond-muted">Total tagihan</p>
                <p class="mt-2 break-words text-2xl font-bold text-diamond-text">{{ formatMoney(rental.total_amount) }}</p>
            </Card>
            <Card class="min-w-0">
                <p class="text-sm font-semibold text-diamond-muted">Sudah dibayar</p>
                <p class="mt-2 break-words text-2xl font-bold text-emerald-700">{{ formatMoney(rental.paid_amount) }}</p>
            </Card>
            <Card class="min-w-0">
                <p class="text-sm font-semibold text-diamond-muted">Sisa bayar</p>
                <p class="mt-2 break-words text-2xl font-bold" :class="Number(rental.remaining_amount || 0) > 0 ? 'text-diamond-danger' : 'text-emerald-700'">
                    {{ formatMoney(rental.remaining_amount) }}
                </p>
            </Card>
            <Card class="min-w-0">
                <p class="text-sm font-semibold text-diamond-muted">Jaminan</p>
                <p class="mt-2 break-words text-2xl font-bold uppercase text-diamond-text">{{ rental.guarantee_type || 'Belum ada' }}</p>
            </Card>
        </div>

        <Card>
            <div class="grid gap-5">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusBadge :value="rental.status" />
                            <StatusBadge :value="rental.payment_status" type="payment" />
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-diamond-text">Status operasional</h2>
                        <p class="mt-1 text-sm leading-6 text-diamond-muted">Aksi status mengikuti alur transaksi rental saat ini.</p>
                    </div>
                    <div class="grid gap-2 sm:flex sm:flex-wrap sm:justify-end">
                        <button
                            v-if="rental.actions.can_pick_up"
                            class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-diamond-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="pickUpForm.processing"
                            @click="openPickUpModal"
                        >
                            <ShoppingBag :size="18" />
                            {{ pickUpForm.processing ? 'Memproses...' : 'Tandai diambil' }}
                        </button>
                        <button
                            v-if="rental.actions.can_return"
                            class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-diamond-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="returnForm.processing"
                            @click="openReturnModal"
                        >
                            <RotateCcw :size="18" />
                            {{ returnForm.processing ? 'Memproses...' : 'Tandai dikembalikan' }}
                        </button>
                        <button
                            v-if="rental.actions.can_complete"
                            class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-diamond-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="completeForm.processing"
                            @click="completeRental"
                        >
                            <CheckCircle2 :size="18" />
                            {{ completeForm.processing ? 'Memproses...' : 'Selesaikan rental' }}
                        </button>
                        <button
                            v-if="rental.actions.can_cancel"
                            class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="cancelForm.processing"
                            @click="cancelRental"
                        >
                            <XCircle :size="18" />
                            {{ cancelForm.processing ? 'Memproses...' : 'Batalkan rental' }}
                        </button>
                    </div>
                </div>

            <span v-if="$page.props.errors.status" class="text-sm text-diamond-danger">{{ $page.props.errors.status }}</span>
            </div>
        </Card>

        <div class="grid gap-4 lg:grid-cols-[0.85fr_1.15fr]">
            <Card>
                <h2 class="text-lg font-bold text-diamond-text">Keterlambatan dan denda</h2>
                <dl class="mt-5 grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Hari terlambat</dt>
                        <dd class="mt-1 text-lg font-bold text-diamond-text">{{ rental.penalty_days || 0 }} hari</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Denda manual</dt>
                        <dd class="mt-1 text-lg font-bold text-diamond-text">{{ formatMoney(rental.penalty_amount) }}</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Status bayar</dt>
                        <dd class="mt-2"><StatusBadge :value="rental.payment_status" type="payment" /></dd>
                    </div>
                </dl>
            </Card>

            <Card>
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <CalendarClock :size="21" />
                    </div>
                    <h2 class="text-lg font-bold text-diamond-text">Detail jadwal</h2>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Jadwal ambil</dt>
                        <dd class="mt-1 text-sm font-bold text-diamond-text">{{ formatDate(rental.pickup_at) }}</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Jadwal kembali</dt>
                        <dd class="mt-1 text-sm font-bold text-diamond-text">{{ formatDate(rental.return_due_at) }}</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Diambil</dt>
                        <dd class="mt-1 text-sm font-bold text-diamond-text">{{ formatDate(rental.picked_up_at) }}</dd>
                        <dd class="mt-1 text-xs text-diamond-muted">{{ rental.picked_up_by?.name || '-' }}</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Dikembalikan</dt>
                        <dd class="mt-1 text-sm font-bold text-diamond-text">{{ formatDate(rental.returned_at) }}</dd>
                        <dd class="mt-1 text-xs text-diamond-muted">{{ rental.returned_by?.name || '-' }}</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Dibuat oleh</dt>
                        <dd class="mt-1 text-sm font-bold text-diamond-text">{{ rental.creator?.name || '-' }}</dd>
                    </div>
                    <div class="rounded-3xl bg-diamond-surface-soft p-4">
                        <dt class="text-sm font-semibold text-diamond-muted">Catatan</dt>
                        <dd class="mt-1 text-sm font-bold text-diamond-text">{{ rental.notes || '-' }}</dd>
                    </div>
                </dl>
            </Card>
        </div>

        <section class="grid gap-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-diamond-accent/15 text-diamond-accent">
                        <PackagePlus :size="21" />
                    </div>
                    <h2 class="text-lg font-bold text-diamond-text">Item rental</h2>
                </div>
                <Button type="button" @click="openItemModal">
                    <Plus :size="18" />
                    Tambah item
                </Button>
            </div>

            <div class="grid gap-3 md:hidden">
                <span v-if="$page.props.errors.items" class="text-sm text-diamond-danger">{{ $page.props.errors.items }}</span>
                <article
                    v-for="item in items"
                    :key="item.id"
                    class="rounded-3xl border border-white/70 bg-white p-4"
                    :class="rental.status === 'booked' ? 'cursor-pointer transition hover:border-diamond-primary/40 hover:bg-diamond-surface-soft' : ''"
                    @click="rental.status === 'booked' ? openEditItem(item) : null"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 gap-3">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-[11px] font-bold text-diamond-soft">
                                <img v-if="itemImage(item)" :src="itemImage(item)" :alt="item.item_name_snapshot" class="h-full w-full object-cover">
                                <PackagePlus v-else :size="19" class="text-diamond-accent" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-diamond-text">{{ item.item_name_snapshot }}</p>
                                <p class="mt-1 truncate text-xs text-diamond-muted">{{ item.variant_name_snapshot || 'Tanpa varian' }}</p>
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full bg-diamond-primary-soft px-3 py-1 text-xs font-bold text-diamond-primary">Qty {{ item.quantity }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-2xl bg-diamond-surface-soft p-3">
                            <p class="text-xs font-semibold text-diamond-muted">Harga</p>
                            <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(item.unit_price) }}</p>
                        </div>
                        <div class="rounded-2xl bg-diamond-surface-soft p-3">
                            <p class="text-xs font-semibold text-diamond-muted">Total</p>
                            <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(item.final_price) }}</p>
                        </div>
                    </div>
                    <p v-if="item.rental_package?.name || item.notes" class="mt-3 text-xs leading-5 text-diamond-muted">
                        {{ item.rental_package?.name || item.notes }}
                    </p>
                    <div v-if="rental.status === 'booked'" class="mt-4 grid grid-cols-2 gap-2">
                        <button
                            class="inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-diamond-border bg-white px-4 py-2 text-sm font-bold text-diamond-text transition hover:bg-diamond-surface-soft"
                            type="button"
                            @click.stop="openEditItem(item)"
                        >
                            <Pencil :size="16" />
                            Edit
                        </button>
                        <button
                            class="inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-2xl border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="deleteItemForm.processing"
                            @click.stop="deleteItem(item)"
                        >
                            <Trash2 :size="16" />
                            Hapus
                        </button>
                    </div>
                </article>
                <EmptyState v-if="items.length === 0" title="Belum ada item" description="Item rental akan muncul setelah transaksi memiliki produk." />
            </div>

            <div class="hidden overflow-x-auto rounded-3xl border border-white/70 bg-white md:block">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Item</th>
                            <th class="px-4 py-3 font-semibold">Paket</th>
                            <th class="px-4 py-3 font-semibold">Qty</th>
                            <th class="px-4 py-3 font-semibold">Harga</th>
                            <th class="px-4 py-3 font-semibold">Total item</th>
                            <th class="px-4 py-3 font-semibold">Catatan</th>
                            <th v-if="rental.status === 'booked'" class="px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="item in items" :key="item.id">
                            <td class="px-4 py-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-[10px] font-bold text-diamond-soft">
                                        <img v-if="itemImage(item)" :src="itemImage(item)" :alt="item.item_name_snapshot" class="h-full w-full object-cover">
                                        <PackagePlus v-else :size="17" class="text-diamond-accent" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-diamond-text">{{ item.item_name_snapshot }}</p>
                                        <p class="mt-1 truncate text-xs text-diamond-muted">{{ item.variant_name_snapshot || 'Tanpa varian' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-diamond-muted">{{ item.rental_package?.name || '-' }}</td>
                            <td class="px-4 py-3 text-diamond-muted">{{ item.quantity }}</td>
                            <td class="px-4 py-3 text-diamond-muted">{{ formatMoney(item.unit_price) }}</td>
                            <td class="px-4 py-3 font-bold text-diamond-text">{{ formatMoney(item.final_price) }}</td>
                            <td class="px-4 py-3 text-diamond-muted">{{ item.notes || '-' }}</td>
                            <td v-if="rental.status === 'booked'" class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button
                                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-diamond-border text-diamond-text transition hover:bg-diamond-surface-soft"
                                        type="button"
                                        aria-label="Edit item"
                                        @click="openEditItem(item)"
                                    >
                                        <Pencil :size="16" />
                                    </button>
                                    <button
                                        class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-red-200 text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                        type="button"
                                        :disabled="deleteItemForm.processing"
                                        aria-label="Hapus item"
                                        @click="deleteItem(item)"
                                    >
                                        <Trash2 :size="16" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-4">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-diamond-primary/15 text-diamond-primary">
                        <CreditCard :size="21" />
                    </div>
                    <h2 class="text-lg font-bold text-diamond-text">Pembayaran</h2>
                </div>
                <Button type="button" @click="openPaymentModal">
                    <Plus :size="18" />
                    Tambah pembayaran
                </Button>
            </div>

            <div class="grid gap-3 md:hidden">
                <span v-if="$page.props.errors.payments" class="text-sm text-diamond-danger">{{ $page.props.errors.payments }}</span>
                <article
                    v-for="payment in payments"
                    :key="payment.id"
                    class="rounded-3xl border border-white/70 bg-white p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-diamond-text">{{ paymentTypeLabel(payment.payment_type) }}</p>
                            <p class="mt-1 text-xs text-diamond-muted">{{ formatDate(payment.paid_at) }}</p>
                        </div>
                        <p class="shrink-0 text-sm font-bold text-diamond-primary">{{ formatMoney(payment.amount) }}</p>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold text-diamond-muted">
                        <span class="rounded-full bg-diamond-surface-soft px-3 py-1">{{ paymentMethodLabel(payment.payment_method) }}</span>
                        <span v-if="payment.notes" class="rounded-full bg-diamond-surface-soft px-3 py-1">{{ payment.notes }}</span>
                    </div>
                    <button
                        v-if="rental.status !== 'completed'"
                        class="mt-4 inline-flex min-h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-red-200 bg-white px-4 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="deletePaymentForm.processing"
                        @click="deletePayment(payment)"
                    >
                        <Trash2 :size="16" />
                        Hapus pembayaran
                    </button>
                </article>
                <EmptyState v-if="payments.length === 0" title="Belum ada pembayaran" description="Riwayat pembayaran akan muncul setelah staff menambahkan pembayaran." />
            </div>

            <div class="hidden overflow-x-auto rounded-3xl border border-white/70 bg-white md:block">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Tanggal</th>
                            <th class="px-4 py-3 font-semibold">Jenis</th>
                            <th class="px-4 py-3 font-semibold">Metode</th>
                            <th class="px-4 py-3 font-semibold">Nominal</th>
                            <th class="px-4 py-3 font-semibold">Catatan</th>
                            <th v-if="rental.status !== 'completed'" class="px-4 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="payment in payments" :key="payment.id">
                            <td class="px-4 py-3 text-diamond-muted">{{ formatDate(payment.paid_at) }}</td>
                            <td class="px-4 py-3 text-diamond-muted">{{ paymentTypeLabel(payment.payment_type) }}</td>
                            <td class="px-4 py-3 text-diamond-muted">{{ paymentMethodLabel(payment.payment_method) }}</td>
                            <td class="px-4 py-3 font-bold text-diamond-text">{{ formatMoney(payment.amount) }}</td>
                            <td class="px-4 py-3 text-diamond-muted">{{ payment.notes || '-' }}</td>
                            <td v-if="rental.status !== 'completed'" class="px-4 py-3">
                                <button
                                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-2xl border border-red-200 text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                    type="button"
                                    :disabled="deletePaymentForm.processing"
                                    aria-label="Hapus pembayaran"
                                    @click="deletePayment(payment)"
                                >
                                    <Trash2 :size="16" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="payments.length === 0">
                            <td class="px-4 py-8 text-center text-diamond-muted" :colspan="rental.status !== 'completed' ? 6 : 5">Belum ada pembayaran.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <Teleport to="body">
            <div
                v-if="pickUpModalOpen"
                class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closePickUpModal"
            >
                <section class="flex max-h-[92vh] min-h-0 w-full max-w-2xl flex-col overflow-hidden rounded-[2rem] border border-white/80 bg-white">
                    <div class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-diamond-accent">Pengambilan barang</p>
                            <h2 class="mt-1 text-xl font-bold text-diamond-text">Tandai barang diambil</h2>
                            <p class="mt-2 text-sm leading-6 text-diamond-muted">
                                Pastikan jaminan sudah diterima saat barang keluar dari toko.
                            </p>
                        </div>
                        <button
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            @click="closePickUpModal"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <form class="grid min-h-0 flex-1 gap-5 overflow-y-auto p-5 sm:p-6" @submit.prevent="markPickedUp">
                        <label class="grid min-w-0 gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Jaminan</span>
                            <select v-model="pickUpForm.guarantee_type" :class="fieldClasses()" class="uppercase">
                                <option value="">Pilih jaminan</option>
                                <option value="ktp">KTP</option>
                                <option value="sim">SIM</option>
                            </select>
                            <span v-if="pickUpForm.errors.guarantee_type" class="text-sm text-diamond-danger">{{ pickUpForm.errors.guarantee_type }}</span>
                        </label>

                        <div class="rounded-3xl bg-diamond-surface-soft p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-diamond-muted">Invoice</p>
                                    <p class="mt-1 text-base font-bold text-diamond-text">{{ rental.invoice_number }}</p>
                                    <p class="mt-1 text-sm text-diamond-muted">{{ rental.customer?.name || '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-diamond-muted">Sisa bayar</p>
                                    <p class="mt-1 text-base font-bold" :class="pickUpPaymentRequired ? 'text-diamond-danger' : 'text-emerald-700'">
                                        {{ formatMoney(rental.remaining_amount) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="pickUpPaymentRequired" class="grid gap-4 rounded-3xl border border-diamond-border bg-white p-4">
                            <div>
                                <h3 class="text-base font-bold text-diamond-text">Pelunasan saat ambil</h3>
                                <p class="mt-1 text-sm leading-6 text-diamond-muted">
                                    Barang hanya bisa ditandai diambil setelah sisa pembayaran dilunasi.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <CurrencyInput v-model="pickUpForm.payment_amount" :error="pickUpForm.errors.payment_amount" label="Nominal pelunasan" />

                                <label class="grid min-w-0 gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Metode pelunasan</span>
                                    <select v-model="pickUpForm.payment_method" :class="fieldClasses()">
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="qris">QRIS</option>
                                        <option value="debit">Debit</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <span v-if="pickUpForm.errors.payment_method" class="text-sm text-diamond-danger">{{ pickUpForm.errors.payment_method }}</span>
                                </label>

                                <DateTimePicker
                                    v-model="pickUpForm.paid_at"
                                    :error="pickUpForm.errors.paid_at"
                                    label="Tanggal pelunasan"
                                    placeholder="Pilih tanggal bayar"
                                />

                                <label class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Catatan pelunasan</span>
                                    <input v-model="pickUpForm.payment_notes" :class="fieldClasses()" type="text">
                                    <span v-if="pickUpForm.errors.payment_notes" class="text-sm text-diamond-danger">{{ pickUpForm.errors.payment_notes }}</span>
                                </label>
                            </div>
                        </div>

                        <div v-else class="rounded-3xl bg-emerald-50 p-4 text-sm font-semibold leading-6 text-emerald-700">
                            Transaksi sudah lunas. Barang bisa langsung ditandai diambil setelah jaminan diterima.
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-diamond-border pt-5 sm:flex-row sm:justify-end">
                            <Button type="button" variant="secondary" @click="closePickUpModal">Batal</Button>
                            <Button type="submit" :disabled="pickUpForm.processing">
                                <ShoppingBag :size="18" />
                                {{ pickUpForm.processing ? 'Memproses...' : 'Tandai diambil' }}
                            </Button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="returnModalOpen"
                class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closeReturnModal"
            >
                <section class="flex max-h-[92vh] min-h-0 w-full max-w-2xl flex-col overflow-hidden rounded-[2rem] border border-white/80 bg-white">
                    <div class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-diamond-accent">Pengembalian barang</p>
                            <h2 class="mt-1 text-xl font-bold text-diamond-text">Tandai barang dikembalikan</h2>
                        </div>
                        <button
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            @click="closeReturnModal"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <form class="grid min-h-0 flex-1 gap-5 overflow-y-auto p-5 sm:p-6" @submit.prevent="markReturned">
                        <div class="grid gap-4 md:grid-cols-2">
                            <DateTimePicker
                                v-model="returnForm.returned_at"
                                :error="returnForm.errors.returned_at"
                                label="Tanggal barang dikembalikan"
                                placeholder="Pilih tanggal kembali"
                            />
                            <div class="grid gap-2">
                                <span class="text-sm font-semibold text-diamond-text">Estimasi terlambat</span>
                                <p class="min-h-12 rounded-xl border border-diamond-border bg-diamond-surface-soft px-4 py-3 text-sm font-bold text-diamond-text">
                                    {{ estimatedPenaltyDays }} hari
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4 rounded-3xl border border-diamond-border bg-white p-4">
                            <div>
                                <h3 class="text-base font-bold text-diamond-text">Denda keterlambatan</h3>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <CurrencyInput
                                    v-model="returnForm.penalty_amount"
                                    :error="returnForm.errors.penalty_amount"
                                    label="Nominal denda"
                                />

                                <label class="grid min-w-0 gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Metode bayar denda</span>
                                    <select v-model="returnForm.penalty_payment_method" :class="fieldClasses()">
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer</option>
                                        <option value="qris">QRIS</option>
                                        <option value="debit">Debit</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <span v-if="returnForm.errors.penalty_payment_method" class="text-sm text-diamond-danger">{{ returnForm.errors.penalty_payment_method }}</span>
                                </label>

                                <DateTimePicker
                                    v-model="returnForm.penalty_paid_at"
                                    :error="returnForm.errors.penalty_paid_at"
                                    label="Tanggal bayar denda"
                                    placeholder="Pilih tanggal bayar"
                                />

                                <label class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Catatan denda</span>
                                    <input v-model="returnForm.penalty_notes" :class="fieldClasses()" type="text">
                                    <span v-if="returnForm.errors.penalty_notes" class="text-sm text-diamond-danger">{{ returnForm.errors.penalty_notes }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-diamond-border pt-5 sm:flex-row sm:justify-end">
                            <Button type="button" variant="secondary" @click="closeReturnModal">Batal</Button>
                            <Button type="submit" :disabled="returnForm.processing">
                                <RotateCcw :size="18" />
                                {{ returnForm.processing ? 'Memproses...' : 'Tandai dikembalikan' }}
                            </Button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="itemModalOpen"
                class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closeItemModal"
            >
                <section class="flex max-h-[92vh] min-h-0 w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] border border-white/80 bg-white">
                    <div class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-diamond-accent">
                                {{ editingItem ? 'Edit item' : 'Tambah item' }}
                            </p>
                            <h2 class="mt-1 truncate text-xl font-bold text-diamond-text">
                                {{ selectedProduct ? selectedProduct.name : 'Pilih produk rental' }}
                            </h2>
                        </div>
                        <button
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            @click="closeItemModal"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden p-5 sm:p-6">
                        <section v-if="!selectedProduct" class="grid gap-4">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-muted" :size="18" />
                                <input
                                    v-model="productSearch"
                                    class="min-h-12 w-full rounded-xl border border-diamond-border bg-white py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                    placeholder="Cari produk atau kode"
                                    type="search"
                                >
                            </div>

                            <div class="grid max-h-[58vh] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                                <button
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-3xl border border-diamond-border bg-white p-3 text-left transition hover:border-diamond-primary/40 hover:bg-diamond-surface-soft"
                                    type="button"
                                    @click="chooseProduct(product)"
                                >
                                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-[11px] font-bold text-diamond-soft">
                                        <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover">
                                        <span v-else>Foto</span>
                                    </div>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-bold text-diamond-text">{{ product.name }}</span>
                                        <span class="mt-1 flex min-w-0 items-center gap-2">
                                            <span class="truncate text-xs text-diamond-muted">{{ product.code || 'Tanpa kode' }}</span>
                                            <span class="shrink-0 rounded-full bg-diamond-surface-soft px-2.5 py-1 text-[11px] font-semibold text-diamond-muted">
                                                {{ product.variants.length }} varian
                                            </span>
                                        </span>
                                    </span>
                                    <Plus :size="18" class="shrink-0 text-diamond-primary" />
                                </button>
                                <EmptyState v-if="filteredProducts.length === 0" title="Produk tidak ditemukan" description="Coba kata kunci lain." />
                            </div>
                        </section>

                        <form v-else class="grid gap-4" @submit.prevent="submitItem">
                            <div class="grid gap-4 lg:grid-cols-[180px_minmax(0,1fr)]">
                                <div class="grid content-start gap-3">
                                    <div class="aspect-square overflow-hidden rounded-[1.5rem] bg-diamond-surface-soft">
                                        <img v-if="selectedProduct.image_url" :src="selectedProduct.image_url" :alt="selectedProduct.name" class="h-full w-full object-cover">
                                        <div v-else class="grid h-full place-items-center text-sm font-bold text-diamond-soft">Foto produk</div>
                                    </div>
                                    <Button type="button" variant="secondary" full @click="form.product_id = ''; form.product_variant_id = ''; form.unit_price = ''">
                                        Ganti produk
                                    </Button>
                                    <button
                                        v-if="editingItem"
                                        class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                                        type="button"
                                        :disabled="deleteItemForm.processing"
                                        @click="deleteItem(editingItem)"
                                    >
                                        <Trash2 :size="16" />
                                        Hapus item
                                    </button>
                                </div>

                                <div class="grid min-w-0 content-start gap-4">
                                    <label class="grid min-w-0 gap-2">
                                        <span class="text-sm font-semibold text-diamond-text">Varian</span>
                                        <select v-model="form.product_variant_id" :class="fieldClasses()" @change="onVariantChanged">
                                            <option value="">Tanpa varian khusus</option>
                                            <option v-for="variant in variantsFor(form.product_id)" :key="variant.id" :value="variant.id">
                                                {{ variant.name }}{{ variant.sku ? ` (${variant.sku})` : '' }} - tersedia {{ variant.available_quantity ?? variant.stock_quantity }}
                                            </option>
                                        </select>
                                        <span v-if="form.errors.product_variant_id" class="text-sm text-diamond-danger">{{ form.errors.product_variant_id }}</span>
                                    </label>

                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="grid min-w-0 gap-2">
                                            <span class="text-sm font-semibold text-diamond-text">Quantity</span>
                                            <input v-model="form.quantity" :class="fieldClasses()" min="1" step="1" type="number">
                                            <span v-if="form.errors.quantity" class="text-sm text-diamond-danger">{{ form.errors.quantity }}</span>
                                        </label>
                                        <CurrencyInput v-model="form.unit_price" :error="form.errors.unit_price" label="Harga" />
                                        <div class="grid gap-2">
                                            <span class="text-sm font-semibold text-diamond-text">Total item</span>
                                            <p class="min-h-12 rounded-xl border border-diamond-border bg-diamond-surface-soft px-4 py-3 text-sm font-bold text-diamond-text">
                                                {{ formatMoney(lineTotal()) }}
                                            </p>
                                        </div>
                                    </div>

                                    <label class="grid gap-2">
                                        <span class="text-sm font-semibold text-diamond-text">Catatan item</span>
                                        <textarea v-model="form.notes" :class="textareaClasses()" />
                                    </label>
                                </div>
                            </div>

                            <div class="flex flex-col-reverse gap-3 border-t border-diamond-border pt-5 sm:flex-row sm:justify-end">
                                <Button type="button" variant="secondary" @click="closeItemModal">Batal</Button>
                                <Button type="submit" :disabled="form.processing">
                                    <Save :size="18" />
                                    {{ form.processing ? 'Menyimpan...' : editingItem ? 'Simpan perubahan' : 'Tambah item' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="paymentModalOpen"
                class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closePaymentModal"
            >
                <section class="flex max-h-[92vh] min-h-0 w-full max-w-3xl flex-col overflow-hidden rounded-[2rem] border border-white/80 bg-white">
                    <div class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-diamond-accent">Pembayaran</p>
                            <h2 class="mt-1 truncate text-xl font-bold text-diamond-text">Tambah pembayaran</h2>
                        </div>
                        <button
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            @click="closePaymentModal"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <form class="grid min-h-0 flex-1 gap-4 overflow-y-auto p-5 sm:p-6" @submit.prevent="submitPayment">
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="grid min-w-0 gap-2">
                                <span class="text-sm font-semibold text-diamond-text">Tipe pembayaran</span>
                                <select v-model="paymentForm.payment_type" :class="fieldClasses()">
                                    <option value="dp">DP</option>
                                    <option value="pelunasan">Pelunasan</option>
                                    <option value="denda">Denda</option>
                                    <option value="refund">Refund</option>
                                    <option value="adjustment">Adjustment</option>
                                </select>
                                <span v-if="paymentForm.errors.payment_type" class="text-sm text-diamond-danger">{{ paymentForm.errors.payment_type }}</span>
                            </label>

                            <label class="grid min-w-0 gap-2">
                                <span class="text-sm font-semibold text-diamond-text">Metode</span>
                                <select v-model="paymentForm.payment_method" :class="fieldClasses()">
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="qris">QRIS</option>
                                    <option value="debit">Debit</option>
                                    <option value="other">Other</option>
                                </select>
                                <span v-if="paymentForm.errors.payment_method" class="text-sm text-diamond-danger">{{ paymentForm.errors.payment_method }}</span>
                            </label>

                            <CurrencyInput v-model="paymentForm.amount" :error="paymentForm.errors.amount" label="Nominal" />
                            <DateTimePicker v-model="paymentForm.paid_at" :error="paymentForm.errors.paid_at" label="Tanggal bayar" placeholder="Pilih tanggal bayar" />
                        </div>

                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Catatan pembayaran</span>
                            <textarea v-model="paymentForm.notes" :class="textareaClasses()" />
                            <span v-if="paymentForm.errors.notes" class="text-sm text-diamond-danger">{{ paymentForm.errors.notes }}</span>
                        </label>

                        <div class="flex flex-col-reverse gap-3 border-t border-diamond-border pt-5 sm:flex-row sm:justify-end">
                            <Button type="button" variant="secondary" @click="closePaymentModal">Batal</Button>
                            <Button type="submit" :disabled="paymentForm.processing">
                                <Save :size="18" />
                                {{ paymentForm.processing ? 'Menyimpan...' : 'Tambah pembayaran' }}
                            </Button>
                        </div>
                    </form>
                </section>
            </div>
        </Teleport>
    </section>
</template>
