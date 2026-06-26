<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronRight } from '@lucide/vue'

defineProps({
    // [{ label, route|null }]
    items: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <nav class="flex min-w-0 items-center gap-1 text-xs font-semibold" aria-label="Breadcrumb">
        <template v-for="(item, index) in items" :key="`${item.label}-${index}`">
            <ChevronRight v-if="index > 0" class="shrink-0 text-diamond-soft" :size="14" />
            <Link
                v-if="item.route && index < items.length - 1"
                :href="route(item.route)"
                class="shrink-0 text-diamond-muted transition hover:text-diamond-primary"
            >
                {{ item.label }}
            </Link>
            <span
                v-else
                class="truncate"
                :class="index === items.length - 1 ? 'text-diamond-text' : 'text-diamond-muted'"
            >
                {{ item.label }}
            </span>
        </template>
    </nav>
</template>
