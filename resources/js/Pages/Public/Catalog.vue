<script setup>
import { InfiniteScroll, Link, router, usePage } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import PublicIcon from '../../Components/Public/PublicIcon.vue'
import PublicSeoHead from '../../Components/Public/PublicSeoHead.vue'
import PublicStorefrontLayout from '../../Layouts/PublicStorefrontLayout.vue'

defineOptions({
    layout: [PublicStorefrontLayout, { activeNav: 'home', floatingWhatsapp: true }],
})

const props = defineProps({
    catalogStore: {
        type: Object,
        required: true,
    },
    products: {
        type: Object,
        required: true,
    },
    packages: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
})

const FAVORITES_KEY = 'diamond-public-catalog-favorites'

const page = usePage()
const fallbackHeroImage = '/images/catalog-hero.png'
const favoriteIds = ref([])
const favoriteOnly = ref(false)
const showMoreCategories = ref(false)

const sortOptions = [
    { value: 'name_asc', label: 'Nama A-Z' },
    { value: 'name_desc', label: 'Nama Z-A' },
    { value: 'price_asc', label: 'Harga terendah' },
    { value: 'price_desc', label: 'Harga tertinggi' },
    { value: 'latest', label: 'Terbaru' },
]

const form = reactive({
    search: props.filters.search || '',
    category: props.filters.category || '',
    sort: props.filters.sort || 'name_asc',
})

const allProducts = computed(() => props.products.data || [])
const allPackages = computed(() => props.packages || [])
const allCatalogItems = computed(() => sortCatalogItems([...allProducts.value, ...allPackages.value]))
const primaryCategories = computed(() => props.categories.slice(0, 7))
const overflowCategories = computed(() => props.categories.slice(7))
const hasOverflowSelection = computed(() => overflowCategories.value.some((category) => category.id === form.category))
const categoryChips = computed(() => [
    { id: '', name: 'Semua', icon: 'apps' },
    ...primaryCategories.value.map((category) => ({
        ...category,
        icon: categoryIcon(category.name),
    })),
    ...(overflowCategories.value.length ? [{ id: '__more', name: 'Lainnya', icon: 'dots' }] : []),
])

const filteredCatalogItems = computed(() => {
    if (!favoriteOnly.value) {
        return allCatalogItems.value
    }

    return allProducts.value.filter((product) => favoriteIds.value.includes(product.id))
})

const whatsappUrl = computed(() => {
    const phone = normalizePhone(props.catalogStore.whatsapp_number)
    const message = encodeURIComponent(`Halo ${props.catalogStore.name}, saya mau tanya katalog rental kebaya dan jas.`)

    return phone ? `https://wa.me/${phone}?text=${message}` : '#'
})

const seoDescription = computed(() => {
    if (props.filters.search) {
        return `Temukan koleksi kebaya dan jas untuk ${props.filters.search} di ${props.catalogStore.name}. Lihat referensi model, pilih favorit, lalu jadwalkan fitting di toko.`
    }

    return `Katalog public ${props.catalogStore.name} untuk referensi sewa kebaya, jas, dan item pendukung. Pilih model favorit lalu datang fitting ke toko sebelum transaksi difinalkan.`
})

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: props.catalogStore.name,
    description: seoDescription.value,
    url: page.props.ziggy?.location,
    image: absoluteUrl(catalogItemImage(allCatalogItems.value[0])),
    mainEntity: {
        '@type': 'ItemList',
        itemListElement: allCatalogItems.value.slice(0, 10).map((item, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            url: item.type === 'package' ? packageDetailUrl(item) : productDetailUrl(item),
            name: item.name,
        })),
    },
}))

watch(() => props.filters, (filters) => {
    form.search = filters.search || ''
    form.category = filters.category || ''
    form.sort = filters.sort || 'name_asc'
}, { deep: true })

watch(() => form.category, (category) => {
    showMoreCategories.value = overflowCategories.value.some((item) => item.id === category)
}, { immediate: true })

onMounted(() => {
    favoriteIds.value = readFavorites()
})

function appRoute(name, params) {
    const ziggy = page.props.ziggy

    return ziggyRoute(name, params, true, {
        ...ziggy,
        location: new URL(ziggy.location),
    })
}

function normalizePhone(value) {
    const digits = String(value || '').replace(/\D/g, '')

    if (!digits) {
        return ''
    }

    if (digits.startsWith('0')) {
        return `62${digits.slice(1)}`
    }

    return digits
}

function absoluteUrl(value) {
    if (!value) {
        return fallbackHeroImage
    }

    try {
        return new URL(value, page.props.ziggy?.location).toString()
    } catch {
        return value
    }
}

function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value || 0))
}

function productImage(product) {
    const variants = Array.isArray(product?.variants) ? product.variants : []

    return product?.image_url || variants.find((variant) => variant.image_url)?.image_url || fallbackHeroImage
}

function packageImage(rentalPackage) {
    return rentalPackage?.image_url || rentalPackage?.preview_items?.find((item) => item.image_url)?.image_url || fallbackHeroImage
}

function catalogItemImage(item) {
    return item?.type === 'package' ? packageImage(item) : productImage(item)
}

function productDetailUrl(product) {
    return appRoute('public.catalog.show', product.id)
}

function packageDetailUrl(rentalPackage) {
    return appRoute('public.catalog.packages.show', rentalPackage.id)
}

function catalogItemUrl(item) {
    return item?.type === 'package' ? packageDetailUrl(item) : productDetailUrl(item)
}

function variantPreviewImages(product) {
    const variantImages = product.variants.filter((variant) => variant.image_url).slice(0, 3)

    if (variantImages.length) {
        return variantImages
    }

    return product.variants.slice(0, 3)
}

function catalogItemPreviewImages(item) {
    if (item?.type === 'package') {
        return item.preview_items || []
    }

    return variantPreviewImages(item)
}

function catalogItemBadge(item) {
    if (item?.type === 'package') {
        return {
            label: 'Paket',
            class: 'bg-violet-100/90 text-[var(--catalog-primary)] backdrop-blur-sm',
        }
    }

    if (Number(item.total_stock || 0) <= 1) {
        return {
            label: 'Stok Terbatas',
            class: 'bg-orange-100/90 text-orange-600 backdrop-blur-sm',
        }
    }

    return {
        label: 'Tersedia',
        class: 'bg-emerald-100/90 text-emerald-600 backdrop-blur-sm',
    }
}

function catalogItemMeta(item) {
    if (item?.type === 'package') {
        return `${item.items_count || 0} item`
    }

    return item.category?.name || 'Koleksi'
}

function catalogItemDescription(item) {
    if (item?.type === 'package') {
        return item.description || 'Paket lengkap'
    }

    return `${item.colors.length || 1} Warna · ${item.sizes.length || item.variants_count || 1} Ukuran`
}

function catalogItemPrice(item) {
    return item?.type === 'package' ? item.package_price : item.lowest_price
}

function catalogItemPriceSuffix(item) {
    return item?.type === 'package' ? '/ paket' : '/ 3 hari'
}

function sortCatalogItems(items) {
    return items.sort((first, second) => {
        if (form.sort === 'price_asc') {
            return Number(catalogItemPrice(first) || 0) - Number(catalogItemPrice(second) || 0)
        }

        if (form.sort === 'price_desc') {
            return Number(catalogItemPrice(second) || 0) - Number(catalogItemPrice(first) || 0)
        }

        if (form.sort === 'name_asc') {
            return String(first.name || '').localeCompare(String(second.name || ''), 'id')
        }

        if (form.sort === 'name_desc') {
            return String(second.name || '').localeCompare(String(first.name || ''), 'id')
        }

        return new Date(second.created_at || 0).getTime() - new Date(first.created_at || 0).getTime()
    })
}

function categoryIcon(name) {
    const normalizedName = String(name || '').toLowerCase()

    if (normalizedName.includes('sepatu')) {
        return 'shoe'
    }

    if (normalizedName.includes('dasi')) {
        return 'tie'
    }

    if (normalizedName.includes('aksesoris')) {
        return 'sparkles'
    }

    if (normalizedName.includes('lain')) {
        return 'dots'
    }

    return 'shirt'
}

function submitFilters() {
    router.get(appRoute('public.catalog'), {
        search: form.search || undefined,
        category: form.category || undefined,
        sort: form.sort !== 'name_asc' ? form.sort : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['products', 'packages', 'filters'],
        reset: ['products'],
    })
}

function chooseCategory(categoryId) {
    if (categoryId === '__more') {
        showMoreCategories.value = !showMoreCategories.value
        return
    }

    form.category = categoryId
    submitFilters()
}

function applyExtraCategory(categoryId) {
    form.category = categoryId
    showMoreCategories.value = true
    submitFilters()
}

function isFavorite(productId) {
    return favoriteIds.value.includes(productId)
}

function toggleFavorite(productId) {
    const nextFavorites = new Set(favoriteIds.value)

    if (nextFavorites.has(productId)) {
        nextFavorites.delete(productId)
    } else {
        nextFavorites.add(productId)
    }

    favoriteIds.value = Array.from(nextFavorites)
    persistFavorites()
}

function toggleFavoriteOnly() {
    favoriteOnly.value = !favoriteOnly.value
}

function readFavorites() {
    if (typeof window === 'undefined') {
        return []
    }

    try {
        const raw = window.localStorage.getItem(FAVORITES_KEY)
        const parsed = raw ? JSON.parse(raw) : []

        return Array.isArray(parsed) ? parsed : []
    } catch {
        return []
    }
}

function persistFavorites() {
    if (typeof window === 'undefined') {
        return
    }

    window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(favoriteIds.value))
}
</script>

<template>
    <PublicSeoHead
        title="Katalog"
        :description="seoDescription"
        :structured-data="structuredData"
        :image="catalogItemImage(allCatalogItems[0])"
    />

    <main class="min-h-screen w-full overflow-x-hidden bg-white text-[#202638]">
        <div class="mx-auto max-w-[1440px] px-4 py-6 md:px-8 xl:px-12">
            <section
                id="hero"
                class="relative w-full overflow-hidden rounded-[24px] bg-[#F5F3FF] md:h-[420px]"
            >
                <div class="relative grid h-full md:grid-cols-[minmax(0,1fr)_minmax(320px,46%)]">
                    <div class="relative mb-4 overflow-hidden rounded-t-[24px] md:hidden">
                        <img
                            :src="fallbackHeroImage"
                            alt="Sewa kebaya dan jas"
                            class="h-[300px] w-full object-cover object-[64%_top] md:object-[64%_center]"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-[#F5F3FF] via-transparent to-transparent" />
                    </div>

                    <div class="relative z-10 flex h-full flex-col justify-center gap-4 px-6 py-10 lg:px-14">
                        <div>
                            <h1 class="text-3xl font-extrabold leading-tight text-slate-800 md:text-[48px]">
                                Sewa Kebaya & Jas
                                <span class="block text-[var(--catalog-primary)]">Untuk Momen Terbaikmu</span>
                            </h1>
                            <p class="mt-4 text-sm leading-relaxed text-slate-600 md:max-w-md md:text-base">
                                Tampil elegan dan percaya diri di setiap acara spesial bersama koleksi pilihan Diamond Kebaya & Jas.
                            </p>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2 md:mt-6 md:flex">
                            <a
                                href="#katalog"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-full bg-[var(--catalog-primary)] px-6 text-sm font-extrabold text-white transition hover:brightness-95 md:w-auto"
                            >
                                <PublicIcon name="search" :size="18" />
                                Jelajahi Koleksi
                            </a>
                            <a
                                :href="whatsappUrl"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-6 text-sm font-extrabold text-slate-700 transition hover:border-emerald-200 hover:text-emerald-600 md:w-auto"
                                target="_blank"
                                rel="noreferrer"
                            >
                                <PublicIcon name="brand-whatsapp" :size="20" class="text-emerald-500" />
                                Hubungi Kami
                            </a>
                        </div>
                    </div>

                    <div class="relative hidden h-full md:block">
                        <img
                            :src="fallbackHeroImage"
                            alt="Sewa kebaya dan jas"
                            class="h-full w-full object-cover object-[72%_center]"
                        >
                        <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-[#F5F3FF] via-[#F5F3FF]/80 to-transparent lg:w-44" />
                        <div class="absolute inset-y-0 left-0 w-16 bg-[#F5F3FF]/65 blur-2xl" />
                    </div>
                </div>
            </section>

            <section id="katalog" class="mt-8 grid gap-8 md:mt-12">
                <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px_160px]" @submit.prevent="submitFilters">
                    <label class="relative flex h-[52px] items-center rounded-full border border-slate-200 bg-white pl-6 pr-2">
                        <input
                            v-model="form.search"
                            class="h-full w-full bg-transparent pr-3 text-sm font-semibold text-slate-700 outline-none placeholder:text-slate-400"
                            placeholder="Cari kebaya, jas, warna, ukuran..."
                            type="search"
                        >
                        <button
                            class="grid size-10 shrink-0 place-items-center rounded-full bg-slate-50 text-slate-500 transition hover:bg-[var(--catalog-primary)] hover:text-white"
                            type="submit"
                        >
                            <PublicIcon name="search" :size="18" />
                        </button>
                    </label>

                    <label class="relative">
                        <select
                            v-model="form.category"
                            class="h-[52px] w-full appearance-none rounded-full border border-slate-200 bg-white px-5 pr-12 text-sm font-extrabold text-slate-700 outline-none transition focus:border-[var(--catalog-primary)]"
                            @change="submitFilters"
                        >
                            <option value="">Semua Kategori</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <PublicIcon name="chevron-down" :size="18" class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-slate-500" />
                    </label>

                    <label class="relative">
                        <select
                            v-model="form.sort"
                            class="h-[52px] w-full appearance-none rounded-full border border-slate-200 bg-white px-5 pr-12 text-sm font-extrabold text-slate-700 outline-none transition focus:border-[var(--catalog-primary)]"
                            @change="submitFilters"
                        >
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <PublicIcon name="chevron-down" :size="18" class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-slate-500" />
                    </label>

                    <button
                        class="inline-flex h-[52px] items-center justify-center gap-2 rounded-full border px-5 text-sm font-extrabold transition"
                        :class="favoriteOnly ? 'border-rose-200 bg-rose-50 text-rose-600' : 'border-slate-200 bg-white text-slate-700 hover:border-rose-200 hover:text-rose-500'"
                        type="button"
                        @click="toggleFavoriteOnly"
                    >
                        <PublicIcon name="heart" :size="18" />
                        {{ favoriteIds.length ? `Disukai (${favoriteIds.length})` : 'Disukai' }}
                    </button>
                </form>

                <div class="flex gap-4 overflow-x-auto pb-4 pt-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    <button
                        v-for="chip in categoryChips"
                        :key="chip.id"
                        class="flex shrink-0 flex-col items-center gap-3 transition"
                        type="button"
                        @click="chooseCategory(chip.id)"
                    >
                        <span
                            class="grid size-[58px] place-items-center rounded-full border transition-all duration-300 md:size-[70px]"
                            :class="form.category === chip.id || (chip.id === '__more' && (showMoreCategories || hasOverflowSelection)) ? 'border-[var(--catalog-primary)] bg-[var(--catalog-primary)] text-white shadow-md shadow-violet-200' : 'border-slate-100 bg-slate-50 text-slate-600 hover:border-slate-300 hover:bg-slate-100'"
                        >
                            <PublicIcon :name="chip.icon" :size="26" />
                        </span>
                        <span
                            class="text-[13px] font-extrabold tracking-wide"
                            :class="form.category === chip.id || (chip.id === '__more' && (showMoreCategories || hasOverflowSelection)) ? 'text-[var(--catalog-primary)]' : 'text-slate-600'"
                        >
                            {{ chip.name }}
                        </span>
                    </button>
                </div>

                <div
                    v-if="showMoreCategories && overflowCategories.length"
                    class="grid gap-2 rounded-[22px] border border-slate-100 bg-slate-50 p-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <button
                        v-for="category in overflowCategories"
                        :key="category.id"
                        class="inline-flex items-center justify-center rounded-full border px-4 py-3 text-sm font-extrabold transition"
                        :class="form.category === category.id ? 'border-[var(--catalog-primary)] bg-white text-[var(--catalog-primary)]' : 'border-transparent bg-white text-slate-600 hover:border-slate-200'"
                        type="button"
                        @click="applyExtraCategory(category.id)"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <InfiniteScroll data="products" manual only-next>
                    <template #default="{ loadingNext }">
                        <div v-if="filteredCatalogItems.length" class="space-y-4">
                            <div class="hidden grid-cols-2 gap-5 md:grid lg:grid-cols-4 xl:grid-cols-5">
                                <article
                                    v-for="item in filteredCatalogItems"
                                    :key="`${item.type}-${item.id}`"
                                    class="group rounded-[20px] border border-slate-100 bg-white shadow-sm transition-shadow hover:shadow-sm"
                                >
                                    <Link :href="catalogItemUrl(item)" class="relative block aspect-[1/1] w-full overflow-hidden rounded-t-[20px] bg-slate-100">
                                        <img
                                            :src="catalogItemImage(item)"
                                            :alt="item.name"
                                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                        >
                                        <button
                                            v-if="item.type === 'product'"
                                            class="absolute right-3 top-3 grid size-[34px] place-items-center rounded-full bg-white shadow-sm transition"
                                            :class="isFavorite(item.id) ? 'text-rose-500' : 'text-slate-400 hover:text-rose-500'"
                                            type="button"
                                            @click.prevent.stop="toggleFavorite(item.id)"
                                        >
                                            <PublicIcon name="heart" :size="18" />
                                        </button>
                                        <span
                                            class="absolute bottom-3 left-3 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                            :class="catalogItemBadge(item).class"
                                        >
                                            {{ catalogItemBadge(item).label }}
                                        </span>
                                    </Link>

                                    <div class="px-3 pb-3 pt-2">
                                        <div class="flex h-9 gap-1.5 overflow-hidden">
                                            <span
                                                v-for="variant in catalogItemPreviewImages(item)"
                                                :key="variant.id"
                                                class="size-9 shrink-0 overflow-hidden rounded-[6px] border border-slate-200 bg-white"
                                            >
                                                <img
                                                    v-if="variant.image_url"
                                                    :src="variant.image_url"
                                                    :alt="variant.name"
                                                    class="h-full w-full object-cover"
                                                >
                                            </span>
                                        </div>

                                        <Link :href="catalogItemUrl(item)" class="mt-3 block truncate text-[15px] font-bold text-slate-800 transition hover:text-[var(--catalog-primary)]">
                                            {{ item.name }}
                                        </Link>
                                        <p class="mt-1 text-[13px] text-slate-500">{{ catalogItemMeta(item) }}</p>
                                        <p class="mt-1.5 text-xs text-slate-400">{{ catalogItemDescription(item) }}</p>
                                        <p class="mt-3 text-[17px] font-bold text-[var(--catalog-primary)]">
                                            {{ formatMoney(catalogItemPrice(item)) }}
                                            <span class="text-[11px] font-medium text-slate-400">{{ catalogItemPriceSuffix(item) }}</span>
                                        </p>
                                    </div>
                                </article>
                            </div>

                            <div class="grid gap-3 md:hidden">
                                <article
                                    v-for="item in filteredCatalogItems"
                                    :key="`${item.type}-${item.id}-mobile`"
                                    class="relative flex gap-4 rounded-[18px] border border-slate-100 bg-white p-3 shadow-sm active:scale-[0.99] transition-transform"
                                >
                                    <Link :href="catalogItemUrl(item)" class="relative block aspect-[1/1] w-[112px] shrink-0 overflow-hidden rounded-[14px] bg-slate-100">
                                        <img
                                            :src="catalogItemImage(item)"
                                            :alt="item.name"
                                            class="h-full w-full object-cover"
                                        >
                                        <span
                                            class="absolute bottom-2 left-2 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                                            :class="catalogItemBadge(item).class"
                                        >
                                            {{ catalogItemBadge(item).label }}
                                        </span>
                                    </Link>

                                    <div class="flex flex-1 flex-col justify-center gap-1 py-0.5">
                                        <div class="pr-10">
                                            <Link :href="catalogItemUrl(item)" class="line-clamp-2 text-[15px] font-bold leading-snug text-slate-800">
                                                {{ item.name }}
                                            </Link>
                                        </div>
                                        <p class="text-[11px] font-semibold text-slate-400">{{ catalogItemMeta(item) }}</p>

                                        <div class="mt-1 flex gap-1.5">
                                            <span
                                                v-for="preview in catalogItemPreviewImages(item).slice(0, 3)"
                                                :key="preview.id"
                                                class="size-8 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white"
                                            >
                                                <img
                                                    v-if="preview.image_url"
                                                    :src="preview.image_url"
                                                    :alt="preview.name"
                                                    class="h-full w-full object-cover"
                                                >
                                            </span>
                                        </div>

                                        <p class="mt-1.5 text-lg font-bold text-[var(--catalog-primary)]">
                                            {{ formatMoney(catalogItemPrice(item)) }}
                                            <span class="text-[11px] font-medium text-slate-400">{{ catalogItemPriceSuffix(item) }}</span>
                                        </p>
                                    </div>

                                    <button
                                        v-if="item.type === 'product'"
                                        class="absolute right-3 top-3 grid size-9 place-items-center rounded-full bg-white/90 shadow-sm backdrop-blur transition active:scale-110"
                                        :class="isFavorite(item.id) ? 'text-rose-500' : 'text-slate-300 hover:text-rose-500'"
                                        type="button"
                                        @click.prevent.stop="toggleFavorite(item.id)"
                                    >
                                        <PublicIcon name="heart" :size="20" />
                                    </button>
                                </article>
                            </div>

                            <div v-if="loadingNext" class="rounded-[22px] border border-slate-100 bg-slate-50 px-5 py-4 text-center text-sm font-bold text-slate-500">
                                Memuat koleksi berikutnya...
                            </div>
                        </div>

                        <div v-else class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-12 text-center">
                            <p class="text-lg font-bold text-slate-800">
                                {{ favoriteOnly ? 'Belum ada produk yang disukai' : 'Koleksi tidak ditemukan' }}
                            </p>
                            <p class="mt-2 text-sm text-slate-500">
                                {{ favoriteOnly ? 'Tekan ikon hati pada produk untuk menyimpannya di perangkat ini.' : 'Coba gunakan kata kunci atau pilih kategori lain.' }}
                            </p>
                        </div>
                    </template>

                    <template #next="{ loading, fetch, hasMore }">
                        <div class="mt-4 flex justify-center">
                            <button
                                v-if="hasMore"
                                class="inline-flex h-[52px] w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-8 text-sm font-extrabold text-slate-700 transition hover:border-[var(--catalog-primary)] hover:text-[var(--catalog-primary)] md:w-auto md:min-w-[400px]"
                                type="button"
                                :disabled="loading"
                                @click="fetch"
                            >
                                {{ loading ? 'Memuat koleksi...' : 'Muat Lebih Banyak' }}
                                <PublicIcon name="chevron-down" :size="18" />
                            </button>
                            <button
                                v-else
                                class="inline-flex h-[52px] w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-8 text-sm font-extrabold text-slate-500 md:w-auto md:min-w-[400px]"
                                type="button"
                                disabled
                            >
                                Semua koleksi sudah ditampilkan
                            </button>
                        </div>
                    </template>
                </InfiniteScroll>
            </section>

        </div>
    </main>
</template>
