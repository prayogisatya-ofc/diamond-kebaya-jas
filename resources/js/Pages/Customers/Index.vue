<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { MessageCircle, Pencil, RotateCcw, Search, Trash2, UserPlus } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    customers: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
})

const search = ref(props.filters.search || '')
const { confirmAction } = useConfirm()

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

function initials(name) {
    return String(name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
}

function whatsappUrl(number) {
    const digits = String(number || '').replace(/\D/g, '')
    const normalized = digits.startsWith('0') ? `62${digits.slice(1)}` : digits

    return normalized ? `https://wa.me/${normalized}` : '#'
}

function submitSearch() {
    router.get(
        route('customers.index'),
        { search: search.value },
        {
            preserveState: true,
            replace: true,
        },
    )
}

function resetSearch() {
    search.value = ''
    submitSearch()
}

async function destroyCustomer(customer) {
    const confirmed = await confirmAction({
        title: 'Hapus customer?',
        message: `Customer ${customer.name} akan dihapus jika tidak memiliki data yang mengunci.`,
        confirmLabel: 'Ya, hapus customer',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('customers.destroy', customer.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Customer" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master data"
            title="Customer"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('customers.create')">
                    <UserPlus :size="18" />
                    Tambah customer
                </Button>
            </template>
        </PageHeader>

        <form class="grid gap-3 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5 lg:grid-cols-[minmax(0,1fr)_auto]" @submit.prevent="submitSearch">
            <Input
                v-model="search"
                label="Cari customer"
                placeholder="Nama atau nomor WhatsApp"
                type="search"
            />
            <div class="flex gap-2 lg:self-end">
                <Button type="submit">
                    <Search :size="17" />
                    Cari
                </Button>
                <Button variant="secondary" type="button" @click="resetSearch">
                    <RotateCcw :size="17" />
                    Reset
                </Button>
            </div>
        </form>

        <div class="grid gap-3 md:hidden">
            <article
                v-for="customer in customers.data"
                :key="customer.id"
                class="rounded-3xl border border-white/80 bg-white p-4"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary text-sm font-bold text-white">
                        {{ initials(customer.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <Link :href="route('customers.show', customer.id)" class="line-clamp-1 font-bold text-diamond-text">
                            {{ customer.name }}
                        </Link>
                        <a :href="whatsappUrl(customer.whatsapp_number)" target="_blank" class="mt-1 inline-flex items-center gap-1 text-sm font-semibold text-diamond-primary">
                            <MessageCircle :size="15" />
                            {{ customer.whatsapp_number }}
                        </a>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-diamond-muted">{{ customer.notes || 'Tanpa catatan' }}</p>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2 text-sm">
                    <div class="rounded-2xl bg-diamond-surface-soft p-3">
                        <p class="text-diamond-soft">Transaksi</p>
                        <p class="mt-1 font-bold text-diamond-text">{{ customer.rentals_count }}</p>
                    </div>
                    <div class="rounded-2xl bg-diamond-surface-soft p-3">
                        <p class="text-diamond-soft">Total</p>
                        <p class="mt-1 truncate font-bold text-diamond-text">{{ formatMoney(customer.rentals_total_amount) }}</p>
                    </div>
                    <div class="rounded-2xl bg-diamond-surface-soft p-3">
                        <p class="text-diamond-soft">Terakhir</p>
                        <p class="mt-1 truncate font-bold text-diamond-text">{{ formatDate(customer.last_transaction_at) }}</p>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <Button :href="route('customers.edit', customer.id)" variant="secondary">
                        <Pencil :size="16" />
                        Edit
                    </Button>
                    <Button type="button" variant="danger" @click="destroyCustomer(customer)">
                        <Trash2 :size="16" />
                        Hapus
                    </Button>
                </div>
            </article>

            <EmptyState v-if="customers.data.length === 0" title="Belum ada customer." description="Tambahkan customer ketika pesanan rental sudah yakin." />
        </div>

        <div class="hidden overflow-hidden rounded-[2rem] border border-white/80 bg-white md:block">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase tracking-wide text-diamond-muted">
                    <tr>
                        <th class="px-6 py-4 font-bold">Customer</th>
                        <th class="px-4 py-4 font-bold">WhatsApp</th>
                        <th class="px-4 py-4 font-bold">Transaksi</th>
                        <th class="px-4 py-4 font-bold">Total nilai</th>
                        <th class="px-4 py-4 font-bold">Terakhir</th>
                        <th class="px-6 py-4 text-right font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-diamond-border">
                    <tr v-for="customer in customers.data" :key="customer.id" class="transition hover:bg-diamond-surface-soft">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary text-xs font-bold text-white">
                                    {{ initials(customer.name) }}
                                </div>
                                <div class="min-w-0">
                                    <Link :href="route('customers.show', customer.id)" class="font-bold text-diamond-text hover:text-diamond-primary">
                                        {{ customer.name }}
                                    </Link>
                                    <p class="mt-1 line-clamp-1 text-xs text-diamond-muted">{{ customer.notes || 'Tanpa catatan' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <a :href="whatsappUrl(customer.whatsapp_number)" target="_blank" class="font-semibold text-diamond-primary hover:text-diamond-primary-dark">
                                {{ customer.whatsapp_number }}
                            </a>
                        </td>
                        <td class="px-4 py-4 text-diamond-muted">{{ customer.rentals_count }}</td>
                        <td class="px-4 py-4 font-semibold text-diamond-text">{{ formatMoney(customer.rentals_total_amount) }}</td>
                        <td class="px-4 py-4 text-diamond-muted">{{ formatDate(customer.last_transaction_at) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <Link
                                    :href="route('customers.edit', customer.id)"
                                    class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-diamond-border px-3 py-2 text-sm font-semibold text-diamond-text transition hover:bg-diamond-surface-soft"
                                >
                                    <Pencil :size="15" />
                                    Edit
                                </Link>
                                <button
                                    class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                                    type="button"
                                    @click="destroyCustomer(customer)"
                                >
                                    <Trash2 :size="15" />
                                    Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="customers.data.length === 0">
                        <td class="px-6 py-8" colspan="6">
                            <EmptyState title="Belum ada customer." description="Tambahkan customer ketika pesanan rental sudah yakin." />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :paginator="customers" />
    </section>
</template>
