<script setup>
import { Link } from '@inertiajs/vue3'
import { CreditCard, PackageSearch, ReceiptText } from '@lucide/vue'

defineProps({
    active: {
        type: String,
        required: true,
    },
})

const tabs = [
    { key: 'transactions', label: 'Transaksi', route: 'reports.transactions', icon: ReceiptText },
    { key: 'payments', label: 'Pembayaran', route: 'reports.payments', icon: CreditCard },
    { key: 'rented-products', label: 'Produk disewa', route: 'reports.rented-products', icon: PackageSearch },
]
</script>

<template>
    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
        <Link
            v-for="tab in tabs"
            :key="tab.key"
            :href="route(tab.route)"
            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition sm:w-auto"
            :class="active === tab.key ? 'bg-diamond-primary text-white hover:bg-diamond-primary-dark' : 'border border-diamond-border bg-white text-diamond-text hover:bg-diamond-surface-soft'"
        >
            <component :is="tab.icon" :size="18" />
            {{ tab.label }}
        </Link>
    </div>
</template>
