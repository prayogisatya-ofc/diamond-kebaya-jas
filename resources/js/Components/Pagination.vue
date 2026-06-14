<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronsLeft, ChevronsRight, ChevronLeft, ChevronRight } from '@lucide/vue'
import { computed } from 'vue'

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
})

const currentPage = computed(() => Number(props.paginator.current_page || 1))
const lastPage = computed(() => Number(props.paginator.last_page || 1))
const shouldShow = computed(() => lastPage.value > 1)
const fromItem = computed(() => props.paginator.from || 0)
const toItem = computed(() => props.paginator.to || 0)
const totalItems = computed(() => Number(props.paginator.total || 0))

const pageItems = computed(() => {
    const last = lastPage.value
    const current = currentPage.value

    if (last <= 7) {
        return Array.from({ length: last }, (_, index) => index + 1)
    }

    const pages = new Set([1, last, current, current - 1, current + 1])

    if (current <= 3) {
        pages.add(2)
        pages.add(3)
        pages.add(4)
    }

    if (current >= last - 2) {
        pages.add(last - 3)
        pages.add(last - 2)
        pages.add(last - 1)
    }

    const normalizedPages = [...pages]
        .filter((page) => page >= 1 && page <= last)
        .sort((first, second) => first - second)

    return normalizedPages.reduce((items, page, index) => {
        const previousPage = normalizedPages[index - 1]

        if (previousPage && page - previousPage > 1) {
            items.push(`ellipsis-${previousPage}-${page}`)
        }

        items.push(page)

        return items
    }, [])
})

function pageUrl(page) {
    const firstPageUrl = props.paginator.first_page_url || props.paginator.path

    if (!firstPageUrl) {
        return null
    }

    const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost'
    const url = new URL(firstPageUrl, origin)
    url.searchParams.set('page', page)

    return `${url.pathname}${url.search}${url.hash}`
}
</script>

<template>
    <nav
        v-if="shouldShow"
        class="grid gap-3 rounded-[1.35rem] border border-white/80 bg-white p-3 sm:gap-4 sm:rounded-[2rem] sm:p-5 xl:grid-cols-[1fr_auto] xl:items-center"
        aria-label="Pagination"
    >
        <div class="min-w-0 text-xs leading-5 text-diamond-muted sm:text-sm sm:leading-6">
            <p>
                Menampilkan
                <span class="font-bold text-diamond-text">{{ fromItem }}</span>
                -
                <span class="font-bold text-diamond-text">{{ toItem }}</span>
                dari
                <span class="font-bold text-diamond-text">{{ totalItems }}</span>
                data
            </p>
            <p class="text-[11px] font-semibold text-diamond-soft sm:text-xs">Halaman {{ currentPage }} dari {{ lastPage }}</p>
        </div>

        <div class="grid gap-2 sm:gap-3">
            <div class="grid grid-cols-2 gap-1.5 sm:hidden">
                <Link
                    :href="currentPage > 1 ? pageUrl(currentPage - 1) : '#'"
                    class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-2xl border px-3 text-xs font-bold transition"
                    :class="currentPage > 1 ? 'cursor-pointer border-diamond-border bg-white text-diamond-text hover:bg-diamond-surface-soft' : 'pointer-events-none border-diamond-border bg-diamond-surface-soft text-diamond-soft'"
                    preserve-scroll
                    preserve-state
                >
                    <ChevronLeft :size="15" />
                    Sebelumnya
                </Link>
                <Link
                    :href="currentPage < lastPage ? pageUrl(currentPage + 1) : '#'"
                    class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-2xl border px-3 text-xs font-bold transition"
                    :class="currentPage < lastPage ? 'cursor-pointer border-diamond-border bg-white text-diamond-text hover:bg-diamond-surface-soft' : 'pointer-events-none border-diamond-border bg-diamond-surface-soft text-diamond-soft'"
                    preserve-scroll
                    preserve-state
                >
                    Berikutnya
                    <ChevronRight :size="15" />
                </Link>
            </div>

            <div class="hidden items-center justify-end gap-2 sm:flex">
                <Link
                    :href="currentPage > 1 ? pageUrl(1) : '#'"
                    class="pagination-icon"
                    :class="currentPage > 1 ? 'pagination-link' : 'pagination-disabled'"
                    aria-label="Halaman pertama"
                    preserve-scroll
                    preserve-state
                >
                    <ChevronsLeft :size="17" />
                </Link>
                <Link
                    :href="currentPage > 1 ? pageUrl(currentPage - 1) : '#'"
                    class="pagination-icon"
                    :class="currentPage > 1 ? 'pagination-link' : 'pagination-disabled'"
                    aria-label="Halaman sebelumnya"
                    preserve-scroll
                    preserve-state
                >
                    <ChevronLeft :size="17" />
                </Link>

                <template v-for="item in pageItems" :key="item">
                    <span
                        v-if="typeof item === 'string'"
                        class="inline-flex h-11 min-w-11 items-center justify-center rounded-2xl px-3 text-sm font-bold text-diamond-soft"
                    >
                        ...
                    </span>
                    <Link
                        v-else
                        :href="pageUrl(item)"
                        class="inline-flex h-11 min-w-11 cursor-pointer items-center justify-center rounded-2xl border px-3 text-sm font-bold transition"
                        :class="item === currentPage ? 'border-diamond-primary bg-diamond-primary text-white' : 'border-diamond-border bg-white text-diamond-text hover:bg-diamond-surface-soft'"
                        :aria-current="item === currentPage ? 'page' : undefined"
                        preserve-scroll
                        preserve-state
                    >
                        {{ item }}
                    </Link>
                </template>

                <Link
                    :href="currentPage < lastPage ? pageUrl(currentPage + 1) : '#'"
                    class="pagination-icon"
                    :class="currentPage < lastPage ? 'pagination-link' : 'pagination-disabled'"
                    aria-label="Halaman berikutnya"
                    preserve-scroll
                    preserve-state
                >
                    <ChevronRight :size="17" />
                </Link>
                <Link
                    :href="currentPage < lastPage ? pageUrl(lastPage) : '#'"
                    class="pagination-icon"
                    :class="currentPage < lastPage ? 'pagination-link' : 'pagination-disabled'"
                    aria-label="Halaman terakhir"
                    preserve-scroll
                    preserve-state
                >
                    <ChevronsRight :size="17" />
                </Link>
            </div>
        </div>
    </nav>
</template>

<style scoped>
.pagination-icon {
    align-items: center;
    border-radius: 1rem;
    border-width: 1px;
    display: inline-flex;
    height: 2.75rem;
    justify-content: center;
    transition-duration: 150ms;
    width: 2.75rem;
}

.pagination-link {
    background: #fff;
    border-color: var(--color-diamond-border);
    color: var(--color-diamond-text);
    cursor: pointer;
}

.pagination-link:hover {
    background: var(--color-diamond-surface-soft);
}

.pagination-disabled {
    background: var(--color-diamond-surface-soft);
    border-color: var(--color-diamond-border);
    color: var(--color-diamond-soft);
    pointer-events: none;
}
</style>
