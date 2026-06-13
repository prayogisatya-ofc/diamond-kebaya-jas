<script setup>
import { computed } from 'vue'
import Badge from '@/Components/Badge.vue'

const props = defineProps({
    value: {
        type: String,
        default: null,
    },
    type: {
        type: String,
        default: 'rental',
    },
})

const statusMap = {
    rental: {
        booked: { label: 'Booking', tone: 'primary' },
        picked_up: { label: 'Diambil', tone: 'info' },
        returned: { label: 'Dikembalikan', tone: 'success' },
        completed: { label: 'Selesai', tone: 'success' },
        overdue: { label: 'Terlambat', tone: 'danger' },
        cancelled: { label: 'Dibatalkan', tone: 'neutral' },
    },
    payment: {
        unpaid: { label: 'Belum bayar', tone: 'danger' },
        dp: { label: 'DP', tone: 'warning' },
        paid: { label: 'Lunas', tone: 'success' },
        overpaid: { label: 'Lebih bayar', tone: 'info' },
    },
    user: {
        active: { label: 'Aktif', tone: 'success' },
        inactive: { label: 'Nonaktif', tone: 'neutral' },
    },
}

const fallbackLabel = computed(() => {
    if (!props.value) {
        return '-'
    }

    return props.value
        .split('_')
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
})

const status = computed(() => statusMap[props.type]?.[props.value] || {
    label: fallbackLabel.value,
    tone: 'neutral',
})
</script>

<template>
    <Badge :tone="status.tone">
        {{ status.label }}
    </Badge>
</template>
