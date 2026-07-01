<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { Filter, Plus, ReceiptText, RotateCcw, Search, X } from '@lucide/vue'
import { computed, reactive, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    rentals: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
})

const filterForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    payment_status: props.filters.payment_status || '',
    date_field: props.filters.date_field || 'pickup_at',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})
const mobileFiltersOpen = ref(false)

const dateFieldOptions = [
    { value: 'created_at', label: 'Tanggal dibuat' },
    { value: 'pickup_at', label: 'Jadwal ambil' },
    { value: 'return_due_at', label: 'Jadwal kembali' },
    { value: 'returned_at', label: 'Tanggal dikembalikan' },
]

const hasActiveFilters = computed(() => {
    return activeFilterCount.value > 0
})

const activeFilterCount = computed(() => {
    return Object.entries(filterForm).filter(([key, value]) => {
        if (key === 'date_field') {
            return value !== 'pickup_at'
        }

        return String(value || '').trim() !== ''
    }).length
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
    }).format(new Date(value))
}

function dateFieldLabel(value) {
    return dateFieldOptions.find((option) => option.value === value)?.label || 'Jadwal ambil'
}

function submitFilters(closeMobileFilters = false) {
    router.get(route('rentals.index'), cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })

    if (closeMobileFilters) {
        mobileFiltersOpen.value = false
    }
}

function resetFilters(closeMobileFilters = false) {
    filterForm.search = ''
    filterForm.status = ''
    filterForm.payment_status = ''
    filterForm.date_field = 'pickup_at'
    filterForm.date_from = ''
    filterForm.date_to = ''

    router.get(route('rentals.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })

    if (closeMobileFilters) {
        mobileFiltersOpen.value = false
    }
}

function cleanFilters() {
    return Object.fromEntries(
        Object.entries(filterForm).filter(([key, value]) => {
            if (key === 'date_field') {
                return value !== 'pickup_at' || filterForm.date_from || filterForm.date_to
            }

            return String(value || '').trim() !== ''
        })
    )
}
</script>

<template>
    <Head title="Transaksi Rental" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Operasional"
            title="Transaksi Rental"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('rentals.create')">
                    <Plus :size="18" />
                    Buat rental
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3 lg:hidden">
            <button
                class="flex min-h-14 items-center justify-between gap-3 rounded-3xl border border-white/80 bg-white px-4 py-3 text-left shadow-sm"
                type="button"
                @click="mobileFiltersOpen = true"
            >
                <span class="flex min-w-0 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <Filter :size="20" />
                    </span>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-diamond-text">Filter transaksi</span>
                        <span class="mt-0.5 block truncate text-xs text-diamond-muted">
                            {{ activeFilterCount > 0 ? `${activeFilterCount} filter aktif` : `${dateFieldLabel(filterForm.date_field)} terbaru` }}
                        </span>
                    </span>
                </span>
                <span class="rounded-full bg-diamond-primary px-3 py-1 text-xs font-bold text-white">
                    Buka
                </span>
            </button>

            <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 text-xs font-semibold text-diamond-muted">
                <span v-if="filterForm.search" class="rounded-full bg-white px-3 py-1">Cari: {{ filterForm.search }}</span>
                <span v-if="filterForm.status" class="rounded-full bg-white px-3 py-1">Status: {{ filterForm.status }}</span>
                <span v-if="filterForm.payment_status" class="rounded-full bg-white px-3 py-1">Bayar: {{ filterForm.payment_status }}</span>
                <span v-if="filterForm.date_from || filterForm.date_to || filterForm.date_field !== 'pickup_at'" class="rounded-full bg-white px-3 py-1">
                    {{ dateFieldLabel(filterForm.date_field) }}: {{ filterForm.date_from || '...' }} - {{ filterForm.date_to || '...' }}
                </span>
            </div>
        </div>

        <Card class="hidden lg:block">
            <form class="grid gap-4" @submit.prevent="submitFilters">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <Filter :size="20" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base font-bold text-diamond-text">Filter transaksi</h2>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-[minmax(220px,1.2fr)_repeat(5,minmax(140px,1fr))]">
                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Pencarian</span>
                        <span class="relative">
                            <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-muted" :size="18" />
                            <input
                                v-model="filterForm.search"
                                class="min-h-12 w-full rounded-xl border border-diamond-border bg-white py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                placeholder="Invoice, nama, WhatsApp"
                                type="search"
                            >
                        </span>
                    </label>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Status rental</span>
                        <select
                            v-model="filterForm.status"
                            class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        >
                            <option value="">Semua status</option>
                            <option value="booked">Booking</option>
                            <option value="picked_up">Diambil</option>
                            <option value="returned">Dikembalikan</option>
                            <option value="completed">Selesai</option>
                            <option value="overdue">Terlambat</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </label>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Pembayaran</span>
                        <select
                            v-model="filterForm.payment_status"
                            class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        >
                            <option value="">Semua pembayaran</option>
                            <option value="unpaid">Belum bayar</option>
                            <option value="dp">DP</option>
                            <option value="paid">Lunas</option>
                            <option value="overpaid">Lebih bayar</option>
                        </select>
                    </label>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Filter tanggal</span>
                        <select
                            v-model="filterForm.date_field"
                            class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        >
                            <option v-for="option in dateFieldOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Dari tanggal</span>
                        <input
                            v-model="filterForm.date_from"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            type="date"
                        >
                    </label>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Sampai tanggal</span>
                        <input
                            v-model="filterForm.date_to"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            type="date"
                        >
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button type="button" variant="secondary" :disabled="!hasActiveFilters" @click="resetFilters()">
                        <RotateCcw :size="18" />
                        Reset
                    </Button>
                    <Button type="submit">
                        <Search :size="18" />
                        Terapkan filter
                    </Button>
                </div>
            </form>
        </Card>

        <Teleport to="body">
            <div v-if="mobileFiltersOpen" class="fixed inset-0 z-50 lg:hidden">
                <button class="absolute inset-0 bg-diamond-text/40" type="button" aria-label="Tutup filter" @click="mobileFiltersOpen = false" />

                <form class="absolute inset-x-0 bottom-0 grid max-h-[88vh] gap-4 overflow-y-auto rounded-t-[2rem] bg-white p-5 shadow-2xl" @submit.prevent="submitFilters(true)">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-bold text-diamond-text">Filter transaksi</h2>
                            <p class="mt-1 text-xs text-diamond-muted">Pilih status dan range tanggal.</p>
                        </div>
                        <button class="grid size-10 place-items-center rounded-2xl bg-diamond-surface-soft text-diamond-muted" type="button" @click="mobileFiltersOpen = false">
                            <X :size="20" />
                        </button>
                    </div>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Pencarian</span>
                        <span class="relative">
                            <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-muted" :size="18" />
                            <input
                                v-model="filterForm.search"
                                class="min-h-12 w-full rounded-xl border border-diamond-border bg-white py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                placeholder="Invoice, nama, WhatsApp"
                                type="search"
                            >
                        </span>
                    </label>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="grid min-w-0 gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Status rental</span>
                            <select
                                v-model="filterForm.status"
                                class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            >
                                <option value="">Semua status</option>
                                <option value="booked">Booking</option>
                                <option value="picked_up">Diambil</option>
                                <option value="returned">Dikembalikan</option>
                                <option value="completed">Selesai</option>
                                <option value="overdue">Terlambat</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </label>

                        <label class="grid min-w-0 gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Pembayaran</span>
                            <select
                                v-model="filterForm.payment_status"
                                class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            >
                                <option value="">Semua pembayaran</option>
                                <option value="unpaid">Belum bayar</option>
                                <option value="dp">DP</option>
                                <option value="paid">Lunas</option>
                                <option value="overpaid">Lebih bayar</option>
                            </select>
                        </label>
                    </div>

                    <label class="grid min-w-0 gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Range berdasarkan</span>
                        <select
                            v-model="filterForm.date_field"
                            class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        >
                            <option v-for="option in dateFieldOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid min-w-0 gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Dari</span>
                            <input
                                v-model="filterForm.date_from"
                                class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                type="date"
                            >
                        </label>

                        <label class="grid min-w-0 gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Sampai</span>
                            <input
                                v-model="filterForm.date_to"
                                class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                type="date"
                            >
                        </label>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-1">
                        <Button type="button" variant="secondary" :disabled="!hasActiveFilters" @click="resetFilters(true)">
                            <RotateCcw :size="18" />
                            Reset
                        </Button>
                        <Button type="submit">
                            <Search :size="18" />
                            Terapkan
                        </Button>
                    </div>
                </form>
            </div>
        </Teleport>

        <div v-if="rentals.data.length > 0" class="grid gap-3 lg:hidden">
            <Link
                v-for="rental in rentals.data"
                :key="rental.id"
                :href="route('rentals.show', rental.id)"
                class="rounded-3xl border border-white/80 bg-white p-4 transition hover:bg-white/80"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <ReceiptText :size="22" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-diamond-text">{{ rental.invoice_number }}</p>
                                <p class="mt-1 truncate text-sm text-diamond-muted">{{ rental.customer?.name || '-' }}</p>
                            </div>
                            <StatusBadge :value="rental.status" />
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-diamond-muted">Ambil</p>
                                <p class="mt-1 font-semibold text-diamond-text">{{ formatDate(rental.pickup_at) }}</p>
                            </div>
                            <div>
                                <p class="text-diamond-muted">Total</p>
                                <p class="mt-1 font-bold text-diamond-primary">{{ formatMoney(rental.total_amount) }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <StatusBadge :value="rental.payment_status" type="payment" />
                            <span class="rounded-full bg-diamond-surface-soft px-3 py-1 text-xs font-semibold uppercase text-diamond-muted">
                                {{ rental.guarantee_type || 'Belum ada jaminan' }}
                            </span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>

        <div v-if="rentals.data.length > 0" class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <table class="w-full min-w-[1040px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Invoice</th>
                        <th class="px-4 py-4 font-bold">Customer</th>
                        <th class="px-4 py-4 font-bold">Jadwal ambil</th>
                        <th class="px-4 py-4 font-bold">Jadwal kembali</th>
                        <th class="px-4 py-4 font-bold">Status</th>
                        <th class="px-4 py-4 font-bold">Pembayaran</th>
                        <th class="px-4 py-4 font-bold">Total</th>
                        <th class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="rental in rentals.data" :key="rental.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <Link :href="route('rentals.show', rental.id)" class="font-bold text-diamond-text hover:text-diamond-primary">
                                {{ rental.invoice_number }}
                            </Link>
                            <p class="mt-1 text-xs font-semibold uppercase text-diamond-muted">{{ rental.guarantee_type || 'Belum ada jaminan' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-bold text-diamond-text">{{ rental.customer?.name || '-' }}</p>
                            <p class="mt-1 text-xs text-diamond-muted">{{ rental.customer?.whatsapp_number || '-' }}</p>
                        </td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.pickup_at) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.return_due_at) }}</td>
                        <td class="px-4 py-4"><StatusBadge :value="rental.status" /></td>
                        <td class="px-4 py-4"><StatusBadge :value="rental.payment_status" type="payment" /></td>
                        <td class="px-4 py-4 font-bold text-diamond-primary">{{ formatMoney(rental.total_amount) }}</td>
                        <td class="px-6 py-4 text-right">
                            <Button :href="route('rentals.show', rental.id)" variant="secondary">
                                Detail
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState
            v-else
            :title="hasActiveFilters ? 'Transaksi tidak ditemukan.' : 'Belum ada transaksi rental.'"
            :description="hasActiveFilters ? 'Coba ubah kata kunci atau filter yang dipilih.' : 'Buat transaksi rental pertama untuk mulai mencatat booking customer.'"
        />

        <Pagination :paginator="rentals" />
    </section>
</template>
