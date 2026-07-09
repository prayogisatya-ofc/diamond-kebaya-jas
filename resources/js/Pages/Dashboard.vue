<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
    AlertTriangle,
    ArrowRight,
    Banknote,
    CalendarCheck,
    CalendarClock,
    Clock3,
    CreditCard,
    FileText,
    HandCoins,
    PackageCheck,
    Plus,
    ReceiptText,
    TrendingUp,
} from '@lucide/vue'
import { computed } from 'vue'
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import AppLayout from '@/Layouts/AppLayout.vue'
import Badge from '@/Components/Badge.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import StatusBadge from '@/Components/StatusBadge.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Filler, Tooltip, Legend)

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    pickupToday: {
        type: Array,
        required: true,
    },
    returnToday: {
        type: Array,
        required: true,
    },
    overdueRentals: {
        type: Array,
        required: true,
    },
    recentRentals: {
        type: Array,
        required: true,
    },
    dailyRevenue: {
        type: Array,
        required: true,
    },
})

const page = usePage()
const user = computed(() => page.props.auth.user)
const store = computed(() => page.props.store || {})
const primaryColor = computed(() => {
    const color = String(store.value.primary_color || '')

    return /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(color) ? color : '#615cf9'
})
const primaryDarkColor = computed(() => mixColor(primaryColor.value, '#000000', 0.18))
const primarySoftColor = computed(() => mixColor(primaryColor.value, '#ffffff', 0.88))
const primaryMutedColor = computed(() => mixColor(primaryColor.value, '#ffffff', 0.56))

const todayLabel = computed(() => new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
}).format(new Date()))

const summaryCards = computed(() => [
    { label: 'Pendapatan hari ini', value: props.summary.revenue_today, type: 'money', icon: TrendingUp, tone: 'primary' },
    { label: 'Pendapatan bulan ini', value: props.summary.revenue_month, type: 'money', icon: Banknote, tone: 'success' },
    { label: 'Total DP masuk', value: props.summary.dp_total, type: 'money', icon: HandCoins, tone: 'accent' },
    { label: 'Total pelunasan', value: props.summary.pelunasan_total, type: 'money', icon: CreditCard, tone: 'info' },
    { label: 'Total denda', value: props.summary.penalty_total, type: 'money', icon: AlertTriangle, tone: 'warning' },
    { label: 'Sisa belum lunas', value: props.summary.outstanding_total, type: 'money', icon: ReceiptText, tone: 'danger' },
    { label: 'Transaksi aktif', value: props.summary.active_transactions, type: 'number', icon: PackageCheck, tone: 'primary' },
    { label: 'Terlambat kembali', value: props.summary.overdue_count, type: 'number', icon: Clock3, tone: 'danger' },
])

const greeting = computed(() => {
    const hour = new Date().getHours()

    if (hour < 11) {
        return 'Selamat pagi'
    }

    if (hour < 15) {
        return 'Selamat siang'
    }

    if (hour < 18) {
        return 'Selamat sore'
    }

    return 'Selamat malam'
})

const revenueChartData = computed(() => ({
    labels: props.dailyRevenue.map((day) => formatDay(day.date)),
    datasets: [
        {
            label: 'Pendapatan',
            data: props.dailyRevenue.map((day) => Number(day.total || 0)),
            backgroundColor: primaryColor.value,
            hoverBackgroundColor: primaryDarkColor.value,
            borderRadius: 14,
            borderSkipped: false,
            maxBarThickness: 42,
        },
    ],
}))

const revenueChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        intersect: false,
        mode: 'index',
    },
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            padding: 12,
            cornerRadius: 14,
            displayColors: false,
            callbacks: {
                label(context) {
                    return `Pendapatan ${formatMoney(context.parsed.y)}`
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            border: {
                display: false,
            },
            ticks: {
                color: '#6b7280',
                font: {
                    size: 11,
                    weight: 700,
                },
            },
        },
        y: {
            beginAtZero: true,
            grid: {
                color: primarySoftColor.value,
                drawTicks: false,
            },
            border: {
                display: false,
            },
            ticks: {
                color: primaryMutedColor.value,
                padding: 10,
                callback(value) {
                    return formatMoney(value).replace('Rp', 'Rp ')
                },
            },
        },
    },
}))

const scheduleGroups = computed(() => [
    {
        title: 'Ambil hari ini',
        count: props.summary.pickup_today_count,
        items: props.pickupToday,
        field: 'pickup_at',
        empty: 'Tidak ada jadwal ambil hari ini.',
        icon: CalendarCheck,
        tone: 'accent',
    },
    {
        title: 'Kembali hari ini',
        count: props.summary.return_today_count,
        items: props.returnToday,
        field: 'return_due_at',
        empty: 'Tidak ada jadwal kembali hari ini.',
        icon: CalendarClock,
        tone: 'primary',
    },
    {
        title: 'Terlambat',
        count: props.summary.overdue_count,
        items: props.overdueRentals,
        field: 'return_due_at',
        empty: 'Tidak ada keterlambatan aktif.',
        icon: AlertTriangle,
        tone: 'danger',
    },
])

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

function formatDay(value) {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'short',
        day: '2-digit',
    }).format(new Date(value))
}

function cardValue(card) {
    return card.type === 'money' ? formatMoney(card.value) : Number(card.value ?? 0)
}

function normalizedHex(color) {
    const value = String(color || '').replace('#', '')

    if (value.length === 3) {
        return value
            .split('')
            .map((character) => character + character)
            .join('')
    }

    return value.padEnd(6, '0').slice(0, 6)
}

function hexToRgb(color) {
    const hex = normalizedHex(color)

    return {
        r: Number.parseInt(hex.slice(0, 2), 16),
        g: Number.parseInt(hex.slice(2, 4), 16),
        b: Number.parseInt(hex.slice(4, 6), 16),
    }
}

function toHex(value) {
    return Math.max(0, Math.min(255, Math.round(value)))
        .toString(16)
        .padStart(2, '0')
}

function mixColor(color, targetColor, targetWeight) {
    const base = hexToRgb(color)
    const target = hexToRgb(targetColor)
    const baseWeight = 1 - targetWeight

    return `#${toHex(base.r * baseWeight + target.r * targetWeight)}${toHex(base.g * baseWeight + target.g * targetWeight)}${toHex(base.b * baseWeight + target.b * targetWeight)}`
}

function iconBoxClass(tone) {
    return {
        primary: 'bg-diamond-primary-soft text-diamond-primary',
        success: 'bg-diamond-success-soft text-emerald-600',
        accent: 'bg-diamond-accent-soft text-diamond-accent',
        info: 'bg-diamond-info-soft text-blue-600',
        warning: 'bg-diamond-warning-soft text-amber-600',
        danger: 'bg-diamond-danger-soft text-red-600',
    }[tone] || 'bg-diamond-primary-soft text-diamond-primary'
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="grid gap-7">
        <section class="overflow-hidden rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7">
                <div class="grid items-center gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase text-diamond-accent">
                            Dashboard
                        </div>
                        <h1 class="mt-4 text-2xl font-bold leading-tight text-diamond-text sm:text-3xl">
                            {{ greeting }}, {{ user.name }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-diamond-muted">
                            Pantau pembayaran, jadwal ambil/kembali, keterlambatan, dan transaksi terbaru dalam satu layar.
                        </p>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <Button :href="route('rentals.create')" variant="primary">
                                <Plus :size="18" />
                                Buat rental
                            </Button>
                            <Button :href="route('reports.transactions')" variant="secondary">
                                <FileText :size="18" />
                                Laporan
                            </Button>
                        </div>
                    </div>

                    <div class="relative hidden min-h-40 overflow-hidden rounded-[1.75rem] bg-diamond-primary-soft lg:block">
                        <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-diamond-primary/15" />
                        <div class="absolute -bottom-10 left-8 h-32 w-32 rounded-full bg-diamond-accent/15" />
                        <div class="relative grid h-full gap-3 p-5">
                            <div class="self-start justify-self-end rounded-2xl bg-white px-4 py-3 text-right">
                                <p class="text-xs font-semibold text-diamond-muted">Hari ini</p>
                                <p class="mt-1 text-sm font-bold text-diamond-text">{{ todayLabel }}</p>
                            </div>
                            <div class="self-end rounded-3xl bg-diamond-primary p-4 text-white">
                                <p class="text-xs font-semibold text-white/75">Transaksi aktif</p>
                                <p class="mt-1 text-3xl font-bold">{{ summary.active_transactions }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <Card>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-diamond-accent">Jadwal hari ini</p>
                        <h2 class="mt-2 text-lg font-bold text-diamond-text">{{ todayLabel }}</h2>
                    </div>
                    <div class="rounded-2xl bg-diamond-accent-soft p-3 text-diamond-accent">
                        <CalendarClock :size="22" />
                    </div>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div v-for="group in scheduleGroups" :key="group.title" class="rounded-2xl bg-diamond-surface-soft p-4">
                        <p class="text-sm font-semibold text-diamond-muted">{{ group.title }}</p>
                        <p class="mt-2 text-2xl font-bold text-diamond-text">{{ group.count }}</p>
                    </div>
                </div>
            </Card>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <section
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[1.75rem] border border-white/80 bg-white p-4 transition hover:border-diamond-primary/20 hover:bg-white/95 2xl:p-5"
                >
                    <div class="flex min-h-24 flex-col justify-between gap-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl" :class="iconBoxClass(card.tone)">
                                <component :is="card.icon" :size="19" />
                            </div>
                            <p class="text-right text-[11px] font-bold uppercase tracking-[0.12em] text-diamond-soft">
                                {{ card.type === 'money' ? 'Nominal' : 'Jumlah' }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold text-diamond-muted">{{ card.label }}</p>
                            <p class="mt-2 whitespace-nowrap text-lg font-bold tracking-tight text-diamond-text 2xl:text-xl">{{ cardValue(card) }}</p>
                        </div>
                    </div>
                </section>
            </div>

            <section class="rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-lg font-bold text-diamond-text">Pendapatan 7 hari</h2>
                        <p class="mt-1 text-sm text-diamond-muted">Chart interaktif berdasarkan riwayat pembayaran masuk.</p>
                    </div>
                    <Badge tone="success">Hari ini {{ formatMoney(summary.revenue_today) }}</Badge>
                </div>
                <div class="mt-7 h-72">
                    <Bar :data="revenueChartData" :options="revenueChartOptions" />
                </div>
            </section>

            <div class="grid gap-5 2xl:grid-cols-3">
                <Card v-for="group in scheduleGroups" :key="group.title">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-diamond-text">{{ group.title }}</h2>
                            <p class="mt-1 text-sm text-diamond-muted">{{ group.count }} transaksi</p>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl" :class="iconBoxClass(group.tone)">
                            <component :is="group.icon" :size="20" />
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <Link
                            v-for="rental in group.items"
                            :key="rental.id"
                            :href="route('rentals.show', rental.id)"
                            class="rounded-2xl border border-diamond-border bg-diamond-surface-soft p-4 transition hover:border-diamond-primary/30 hover:bg-white"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold text-diamond-text">{{ rental.invoice_number }}</p>
                                <ArrowRight class="text-diamond-soft" :size="17" />
                            </div>
                            <p class="mt-2 text-sm text-diamond-muted">{{ rental.customer_name || '-' }}</p>
                            <p class="mt-1 text-xs text-diamond-soft">{{ formatDate(rental[group.field]) }}</p>
                        </Link>
                        <EmptyState v-if="group.items.length === 0" :title="group.empty" />
                    </div>
                </Card>
            </div>

            <Card :padded="false" class="overflow-hidden">
                <div class="flex items-center justify-between gap-3 px-5 py-5 sm:px-6">
                    <div>
                        <h2 class="text-lg font-bold text-diamond-text">Transaksi terbaru</h2>
                        <p class="mt-1 text-sm text-diamond-muted">Aktivitas rental terakhir.</p>
                    </div>
                    <Link :href="route('rentals.index')" class="hidden text-sm font-bold text-diamond-primary hover:text-diamond-primary-dark sm:inline-flex">
                        Lihat semua
                    </Link>
                </div>

                <div class="grid gap-3 px-5 pb-5 sm:hidden">
                    <Link
                        v-for="rental in recentRentals"
                        :key="rental.id"
                        :href="route('rentals.show', rental.id)"
                        class="rounded-2xl border border-diamond-border bg-diamond-surface-soft p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-diamond-text">{{ rental.invoice_number }}</p>
                                <p class="mt-1 text-sm text-diamond-muted">{{ rental.customer_name || '-' }}</p>
                            </div>
                            <StatusBadge :value="rental.status" />
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-diamond-soft">Total</p>
                                <p class="font-semibold text-diamond-text">{{ formatMoney(rental.total_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-diamond-soft">Sisa</p>
                                <p class="font-semibold text-diamond-text">{{ formatMoney(rental.remaining_amount) }}</p>
                            </div>
                        </div>
                    </Link>
                    <EmptyState v-if="recentRentals.length === 0" title="Belum ada transaksi." />
                </div>

                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead class="border-y border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                            <tr>
                                <th class="px-6 py-4 font-bold">Invoice</th>
                                <th class="px-4 py-4 font-bold">Customer</th>
                                <th class="px-4 py-4 font-bold">Status</th>
                                <th class="px-4 py-4 font-bold">Pembayaran</th>
                                <th class="px-4 py-4 text-right font-bold">Total</th>
                                <th class="px-6 py-4 text-right font-bold">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-diamond-border">
                            <tr v-for="rental in recentRentals" :key="rental.id" class="transition hover:bg-diamond-surface-soft">
                                <td class="px-6 py-4">
                                    <Link :href="route('rentals.show', rental.id)" class="font-bold text-diamond-text hover:text-diamond-primary">
                                        {{ rental.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-4 text-diamond-muted">{{ rental.customer_name || '-' }}</td>
                                <td class="px-4 py-4"><StatusBadge :value="rental.status" /></td>
                                <td class="px-4 py-4"><StatusBadge :value="rental.payment_status" type="payment" /></td>
                                <td class="px-4 py-4 text-right font-semibold text-diamond-text">{{ formatMoney(rental.total_amount) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-diamond-text">{{ formatMoney(rental.remaining_amount) }}</td>
                            </tr>
                            <tr v-if="recentRentals.length === 0">
                                <td class="px-6 py-8 text-center text-diamond-muted" colspan="6">Belum ada transaksi.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
    </div>
</template>
