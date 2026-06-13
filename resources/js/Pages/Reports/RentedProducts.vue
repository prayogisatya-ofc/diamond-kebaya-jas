<script setup>
import { Head, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import { PackageSearch, RotateCcw, Search } from '@lucide/vue'
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
    items: Object,
})

const form = reactive({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

function submit() {
    router.get(route('reports.rented-products'), clean(form), {
        preserveState: true,
        preserveScroll: true,
    })
}

function resetFilters() {
    router.get(route('reports.rented-products'))
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

function inputClasses() {
    return 'min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}
</script>

<template>
    <Head title="Laporan Produk Disewa" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Laporan"
            title="Laporan produk disewa"
        >
            <template #actions>
                <ReportTabs active="rented-products" />
            </template>
        </PageHeader>

        <form class="grid gap-4 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]" @submit.prevent="submit">
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Dari</span>
                <input v-model="form.date_from" :class="inputClasses()" type="date">
            </label>
            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Sampai</span>
                <input v-model="form.date_to" :class="inputClasses()" type="date">
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

        <div v-if="items.data.length > 0" class="grid gap-3 lg:hidden">
            <article
                v-for="item in items.data"
                :key="item.id"
                class="rounded-3xl border border-white/80 bg-white p-4"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-diamond-surface-soft text-diamond-primary">
                        <PackageSearch :size="24" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 text-base font-bold text-diamond-text">{{ item.product_name }}</p>
                        <p class="mt-1 truncate text-sm text-diamond-muted">{{ item.variant_name || 'Tanpa varian' }}</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-diamond-muted">Total qty</p>
                        <p class="mt-1 text-xl font-bold text-diamond-text">{{ item.total_quantity }}</p>
                    </div>
                    <div>
                        <p class="text-diamond-muted">Pendapatan</p>
                        <p class="mt-1 font-bold text-diamond-primary">{{ formatMoney(item.total_revenue) }}</p>
                    </div>
                </div>
            </article>
        </div>

        <div v-if="items.data.length > 0" class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white lg:block">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Produk</th>
                        <th class="px-4 py-4 font-bold">Varian</th>
                        <th class="px-4 py-4 font-bold">Total qty</th>
                        <th class="px-6 py-4 font-bold">Pendapatan item</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="item in items.data" :key="item.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <p class="font-bold text-diamond-text">{{ item.product_name }}</p>
                            <p class="mt-1 text-xs text-diamond-muted">{{ item.product_code || '-' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-semibold text-diamond-text">{{ item.variant_name || 'Tanpa varian' }}</p>
                            <p class="mt-1 text-xs text-diamond-muted">{{ item.variant_sku || '-' }}</p>
                        </td>
                        <td class="px-4 py-4 text-lg font-bold text-diamond-text">{{ item.total_quantity }}</td>
                        <td class="px-6 py-4 font-bold text-diamond-primary">{{ formatMoney(item.total_revenue) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState v-else title="Belum ada produk disewa sesuai filter." />

        <Pagination :paginator="items" />
    </section>
</template>
