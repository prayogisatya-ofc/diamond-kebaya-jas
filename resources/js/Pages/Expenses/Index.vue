<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import { Download, Pencil, Plus, RotateCcw, Search, Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    expenses: {
        type: Object,
        required: true,
    },
    totalAmount: {
        type: Number,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
})

const filterForm = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})
const { confirmAction } = useConfirm()

const categoryOptions = [
    { value: '', label: 'Semua kategori' },
    { value: 'operasional', label: 'Operasional' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'laundry', label: 'Laundry' },
    { value: 'supplies', label: 'Supplies' },
    { value: 'other', label: 'Lainnya' },
]

const hasActiveFilters = computed(() => {
    return Object.values(filterForm).some((v) => String(v || '').trim() !== '')
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

function categoryLabel(value) {
    return categoryOptions.find((o) => o.value === value)?.label || value
}

const pdfUrl = computed(() => {
    const params = cleanFilters()
    const url = new URL(route('expenses.pdf'), window.location.origin)
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v))

    return url.toString()
})

function applyFilters() {
    router.get(route('expenses.index'), cleanFilters(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function resetFilters() {
    filterForm.search = ''
    filterForm.category = ''
    filterForm.date_from = ''
    filterForm.date_to = ''
    applyFilters()
}

function cleanFilters() {
    return Object.fromEntries(
        Object.entries(filterForm).filter(([, value]) => String(value || '').trim() !== '')
    )
}

async function destroyExpense(expense) {
    const confirmed = await confirmAction({
        title: 'Hapus pengeluaran?',
        message: `Pengeluaran "${expense.description}" akan dihapus permanen.`,
        confirmLabel: 'Ya, hapus',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('expenses.destroy', expense.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Pengeluaran" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Keuangan"
            title="Pengeluaran"
        >
            <template #actions>
                <a :href="pdfUrl" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm font-semibold text-diamond-text transition hover:bg-diamond-surface-soft">
                    <Download :size="18" />
                    Export PDF
                </a>
                <Button :href="route('expenses.create')">
                    <Plus :size="18" />
                    Catat pengeluaran
                </Button>
            </template>
        </PageHeader>

        <Card>
            <form class="grid gap-4" @submit.prevent="applyFilters">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_200px_200px_200px_auto]">
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Pencarian</span>
                        <span class="relative">
                            <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-muted" :size="18" />
                            <input
                                v-model="filterForm.search"
                                class="min-h-12 w-full rounded-xl border border-diamond-border bg-white py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                placeholder="Cari deskripsi..."
                                type="search"
                            >
                        </span>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Kategori</span>
                        <select
                            v-model="filterForm.category"
                            class="min-h-12 w-full cursor-pointer rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        >
                            <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Dari tanggal</span>
                        <input
                            v-model="filterForm.date_from"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            type="date"
                        >
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Sampai tanggal</span>
                        <input
                            v-model="filterForm.date_to"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            type="date"
                        >
                    </label>

                    <div class="flex gap-2 lg:self-end">
                        <Button type="submit">
                            <Search :size="17" />
                            Filter
                        </Button>
                        <Button variant="secondary" type="button" :disabled="!hasActiveFilters" @click="resetFilters">
                            <RotateCcw :size="17" />
                            Reset
                        </Button>
                    </div>
                </div>
            </form>
        </Card>

        <Card class="hidden items-center justify-between gap-4 lg:flex">
            <p class="text-sm font-semibold text-diamond-muted">Total pengeluaran</p>
            <p class="text-xl font-bold text-diamond-danger">{{ formatMoney(totalAmount) }}</p>
        </Card>

        <div class="grid gap-3 lg:hidden">
            <div class="flex items-center justify-between rounded-2xl bg-white px-4 py-3">
                <p class="text-xs font-semibold text-diamond-muted">Total</p>
                <p class="text-base font-bold text-diamond-danger">{{ formatMoney(totalAmount) }}</p>
            </div>

            <article
                v-for="expense in expenses.data"
                :key="expense.id"
                class="rounded-3xl border border-white/80 bg-white p-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-base font-bold text-diamond-text">{{ expense.description }}</p>
                        <p class="mt-1 text-sm text-diamond-muted">{{ expense.creator?.name || '-' }} · {{ formatDate(expense.expense_date) }}</p>
                    </div>
                    <p class="shrink-0 text-lg font-bold text-diamond-danger">{{ formatMoney(expense.amount) }}</p>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-diamond-surface-soft px-3 py-1 text-xs font-semibold text-diamond-muted">
                        {{ categoryLabel(expense.category) }}
                    </span>
                    <span v-if="expense.notes" class="truncate rounded-full bg-diamond-surface-soft px-3 py-1 text-xs font-semibold text-diamond-muted">
                        {{ expense.notes }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <Button :href="route('expenses.edit', expense.id)" variant="secondary">
                        <Pencil :size="16" />
                        Edit
                    </Button>
                    <Button variant="danger" type="button" @click="destroyExpense(expense)">
                        <Trash2 :size="16" />
                        Hapus
                    </Button>
                </div>
            </article>

            <EmptyState v-if="expenses.data.length === 0" :title="hasActiveFilters ? 'Pengeluaran tidak ditemukan.' : 'Belum ada catatan pengeluaran.'" :description="hasActiveFilters ? 'Coba ubah filter yang dipilih.' : 'Catat pengeluaran pertama untuk mulai memantau keuangan toko.'" />
        </div>

        <div v-if="expenses.data.length > 0" class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Deskripsi</th>
                        <th class="px-4 py-4 font-bold">Kategori</th>
                        <th class="px-4 py-4 font-bold">Tanggal</th>
                        <th class="px-4 py-4 text-right font-bold">Nominal</th>
                        <th class="px-4 py-4 font-bold">Dicatat oleh</th>
                        <th class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="expense in expenses.data" :key="expense.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <p class="font-bold text-diamond-text">{{ expense.description }}</p>
                            <p v-if="expense.notes" class="mt-1 text-xs text-diamond-muted">{{ expense.notes }}</p>
                        </td>
                        <td class="px-4 py-4 text-diamond-muted">{{ categoryLabel(expense.category) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(expense.expense_date) }}</td>
                        <td class="px-4 py-4 text-right font-bold text-diamond-danger">{{ formatMoney(expense.amount) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ expense.creator?.name || '-' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Button :href="route('expenses.edit', expense.id)" variant="secondary">
                                    <Pencil :size="15" />
                                    Edit
                                </Button>
                                <Button variant="danger" type="button" @click="destroyExpense(expense)">
                                    <Trash2 :size="15" />
                                    Hapus
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState
            v-if="expenses.data.length === 0"
            class="hidden lg:block"
            :title="hasActiveFilters ? 'Pengeluaran tidak ditemukan.' : 'Belum ada catatan pengeluaran.'"
            :description="hasActiveFilters ? 'Coba ubah filter yang dipilih.' : 'Catat pengeluaran pertama untuk mulai memantau keuangan toko.'"
        />

        <Pagination :paginator="expenses" />
    </section>
</template>
