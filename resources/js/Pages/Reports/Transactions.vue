<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { RotateCcw, Search } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import ReportTabs from './Partials/ReportTabs.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    filters: Object,
    rentals: Object,
    customers: Array,
    statusOptions: Array,
    paymentStatusOptions: Array,
})

const form = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    status: props.filters.status || '',
    payment_status: props.filters.payment_status || '',
    customer_id: props.filters.customer_id || '',
})

function submit() {
    router.get(route('reports.transactions'), clean(form), {
        preserveState: true,
        preserveScroll: true,
    })
}

function resetFilters() {
    router.get(route('reports.transactions'))
}

function clean(values) {
    return Object.fromEntries(Object.entries(values).filter(([, value]) => value !== '' && value !== null))
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

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value))
}

function selectClasses() {
    return 'min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}
</script>

<template>
    <Head title="Laporan Transaksi" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Laporan"
            title="Laporan transaksi"
        >
            <template #actions>
                <ReportTabs active="transactions" />
            </template>
        </PageHeader>

        <form class="grid gap-4 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5 xl:grid-cols-[repeat(5,minmax(0,1fr))_auto]" @submit.prevent="submit">
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Dari</span>
                <input v-model="form.date_from" :class="selectClasses()" type="date">
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Sampai</span>
                <input v-model="form.date_to" :class="selectClasses()" type="date">
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Status rental</span>
                <select v-model="form.status" :class="selectClasses()">
                    <option value="">Semua status</option>
                    <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                </select>
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Status bayar</span>
                <select v-model="form.payment_status" :class="selectClasses()">
                    <option value="">Semua pembayaran</option>
                    <option v-for="status in paymentStatusOptions" :key="status" :value="status">{{ status }}</option>
                </select>
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Customer</span>
                <select v-model="form.customer_id" :class="selectClasses()">
                    <option value="">Semua customer</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option>
                </select>
            </label>
            <div class="flex items-end gap-2">
                <Button type="submit">
                    <Search :size="17" />
                    Filter
                </Button>
                <Button type="button" variant="secondary" @click="resetFilters">
                    <RotateCcw :size="17" />
                    Reset
                </Button>
            </div>
        </form>

        <div v-if="rentals.data.length > 0" class="grid gap-3 lg:hidden">
            <Link
                v-for="rental in rentals.data"
                :key="rental.id"
                :href="route('rentals.show', rental.id)"
                class="rounded-3xl border border-white/80 bg-white p-4 transition hover:bg-white/80"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-diamond-text">{{ rental.invoice_number }}</p>
                        <p class="mt-1 truncate text-sm text-diamond-muted">{{ rental.customer_name || '-' }}</p>
                    </div>
                    <StatusBadge :value="rental.status" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-diamond-muted">Total</p>
                        <p class="mt-1 font-bold text-diamond-text">{{ formatMoney(rental.total_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Sisa</p>
                        <p class="mt-1 font-bold text-diamond-primary">{{ formatMoney(rental.remaining_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Ambil</p>
                        <p class="mt-1 font-semibold text-diamond-text">{{ formatDate(rental.pickup_at) }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Pembayaran</p>
                        <div class="mt-1"><StatusBadge :value="rental.payment_status" type="payment" /></div>
                    </div>
                </div>
            </Link>
        </div>

        <div v-if="rentals.data.length > 0" class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <table class="w-full min-w-[1100px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Invoice</th>
                        <th class="px-4 py-4 font-bold">Customer</th>
                        <th class="px-4 py-4 font-bold">Dibuat</th>
                        <th class="px-4 py-4 font-bold">Ambil</th>
                        <th class="px-4 py-4 font-bold">Kembali</th>
                        <th class="px-4 py-4 font-bold">Status</th>
                        <th class="px-4 py-4 font-bold">Pembayaran</th>
                        <th class="px-4 py-4 font-bold">Total</th>
                        <th class="px-4 py-4 font-bold">Sisa</th>
                        <th class="px-6 py-4 font-bold">Denda</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="rental in rentals.data" :key="rental.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <Link :href="route('rentals.show', rental.id)" class="font-bold text-diamond-text hover:text-diamond-primary">{{ rental.invoice_number }}</Link>
                        </td>
                        <td class="px-4 py-4 text-diamond-muted">{{ rental.customer_name || '-' }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.created_at) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.pickup_at) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(rental.return_due_at) }}</td>
                        <td class="px-4 py-4"><StatusBadge :value="rental.status" /></td>
                        <td class="px-4 py-4"><StatusBadge :value="rental.payment_status" type="payment" /></td>
                        <td class="px-4 py-4 font-semibold text-diamond-text">{{ formatMoney(rental.total_amount) }}</td>
                        <td class="px-4 py-4 font-semibold text-diamond-primary">{{ formatMoney(rental.remaining_amount) }}</td>
                        <td class="px-6 py-4 text-diamond-muted">{{ formatMoney(rental.penalty_amount) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState v-else title="Tidak ada transaksi sesuai filter." />

        <Pagination :paginator="rentals" />
    </section>
</template>
