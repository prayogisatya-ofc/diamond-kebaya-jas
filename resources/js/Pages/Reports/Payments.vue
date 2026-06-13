<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { RotateCcw, Search } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import ReportTabs from './Partials/ReportTabs.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    filters: Object,
    payments: Object,
    staff: Array,
    paymentTypeOptions: Array,
    paymentMethodOptions: Array,
})

const form = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    payment_type: props.filters.payment_type || '',
    payment_method: props.filters.payment_method || '',
    staff_id: props.filters.staff_id || '',
})

function submit() {
    router.get(route('reports.payments'), clean(form), {
        preserveState: true,
        preserveScroll: true,
    })
}

function resetFilters() {
    router.get(route('reports.payments'))
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

function humanize(value) {
    if (!value) {
        return '-'
    }

    return value
        .split('_')
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

function selectClasses() {
    return 'min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}
</script>

<template>
    <Head title="Laporan Pembayaran" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Laporan"
            title="Laporan pembayaran"
        >
            <template #actions>
                <ReportTabs active="payments" />
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
                <span class="text-sm font-semibold text-diamond-text">Tipe</span>
                <select v-model="form.payment_type" :class="selectClasses()">
                    <option value="">Semua tipe</option>
                    <option v-for="type in paymentTypeOptions" :key="type" :value="type">{{ humanize(type) }}</option>
                </select>
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Metode</span>
                <select v-model="form.payment_method" :class="selectClasses()">
                    <option value="">Semua metode</option>
                    <option v-for="method in paymentMethodOptions" :key="method" :value="method">{{ humanize(method) }}</option>
                </select>
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Staff</span>
                <select v-model="form.staff_id" :class="selectClasses()">
                    <option value="">Semua staff</option>
                    <option v-for="user in staff" :key="user.id" :value="user.id">{{ user.name }}</option>
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

        <div v-if="payments.data.length > 0" class="grid gap-3 lg:hidden">
            <Link
                v-for="payment in payments.data"
                :key="payment.id"
                :href="route('rentals.show', payment.rental_id)"
                class="rounded-3xl border border-white/80 bg-white p-4 transition hover:bg-white/80"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-diamond-text">{{ payment.invoice_number }}</p>
                        <p class="mt-1 truncate text-sm text-diamond-muted">{{ payment.customer_name || '-' }}</p>
                    </div>
                    <p class="shrink-0 text-base font-bold text-diamond-primary">{{ formatMoney(payment.amount) }}</p>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-diamond-muted">Tanggal</p>
                        <p class="mt-1 font-semibold text-diamond-text">{{ formatDate(payment.paid_at) }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Staff</p>
                        <p class="mt-1 font-semibold text-diamond-text">{{ payment.staff_name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Tipe</p>
                        <p class="mt-1 font-semibold text-diamond-text">{{ humanize(payment.payment_type) }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Metode</p>
                        <p class="mt-1 font-semibold text-diamond-text">{{ humanize(payment.payment_method) }}</p>
                    </div>
                </div>
            </Link>
        </div>

        <div v-if="payments.data.length > 0" class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Tanggal</th>
                        <th class="px-4 py-4 font-bold">Invoice</th>
                        <th class="px-4 py-4 font-bold">Customer</th>
                        <th class="px-4 py-4 font-bold">Tipe</th>
                        <th class="px-4 py-4 font-bold">Metode</th>
                        <th class="px-4 py-4 font-bold">Nominal</th>
                        <th class="px-4 py-4 font-bold">Staff</th>
                        <th class="px-6 py-4 font-bold">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="payment in payments.data" :key="payment.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4 text-diamond-muted">{{ formatDate(payment.paid_at) }}</td>
                        <td class="px-4 py-4">
                            <Link :href="route('rentals.show', payment.rental_id)" class="font-bold text-diamond-text hover:text-diamond-primary">{{ payment.invoice_number }}</Link>
                        </td>
                        <td class="px-4 py-4 text-diamond-muted">{{ payment.customer_name || '-' }}</td>
                        <td class="px-4 py-4 font-semibold text-diamond-text">{{ humanize(payment.payment_type) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ humanize(payment.payment_method) }}</td>
                        <td class="px-4 py-4 font-bold text-diamond-primary">{{ formatMoney(payment.amount) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ payment.staff_name || '-' }}</td>
                        <td class="px-6 py-4 text-diamond-muted">{{ payment.notes || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState v-else title="Tidak ada pembayaran sesuai filter." />

        <Pagination :paginator="payments" />
    </section>
</template>
