<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import { computed, ref } from 'vue'
import PublicIcon from '../../Components/Public/PublicIcon.vue'
import PublicSeoHead from '../../Components/Public/PublicSeoHead.vue'
import PublicStorefrontLayout from '../../Layouts/PublicStorefrontLayout.vue'

defineOptions({
    layout: [PublicStorefrontLayout, { activeNav: 'home', floatingWhatsapp: false }],
})

const props = defineProps({
    catalogStore: {
        type: Object,
        required: true,
    },
    rentalPackage: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    relatedPackages: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
const fallbackHeroImage = '/images/catalog-hero.png'

const defaultImage = computed(() => packageImage(props.rentalPackage))
const activeImage = ref(defaultImage.value)
const packageTitle = computed(() => props.rentalPackage.name)
const seoDescription = computed(() => {
    const description = props.rentalPackage.description?.trim()

    if (description) {
        return description
    }

    return `${props.rentalPackage.name} di ${props.catalogStore.name}. Lihat produk yang termasuk dalam paket lalu datang fitting ke toko sebelum transaksi rental difinalkan.`
})

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: props.rentalPackage.name,
    description: seoDescription.value,
    image: [absoluteUrl(defaultImage.value)],
    url: page.props.ziggy?.location,
    brand: {
        '@type': 'Brand',
        name: props.catalogStore.name,
    },
    offers: {
        '@type': 'Offer',
        priceCurrency: 'IDR',
        price: Number(props.rentalPackage.package_price || 0),
        availability: props.items.length > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        url: page.props.ziggy?.location,
    },
}))

const whatsappUrl = computed(() => {
    const phone = normalizePhone(props.catalogStore.whatsapp_number)
    const message = encodeURIComponent(`Halo ${props.catalogStore.name}, saya tertarik sewa ${props.rentalPackage.name}. Apakah paket ini tersedia?`)

    return phone ? `https://wa.me/${phone}?text=${message}` : '#'
})

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

function appRoute(name, params) {
    const ziggy = page.props.ziggy

    return ziggyRoute(name, params, true, {
        ...ziggy,
        location: new URL(ziggy.location),
    })
}

function productDetailUrl(productId) {
    return appRoute('public.catalog.show', productId)
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

function packageImage(rentalPackage) {
    return rentalPackage?.image_url || rentalPackage?.preview_items?.find((item) => item.image_url)?.image_url || fallbackHeroImage
}

function itemImage(item) {
    return item.product_variant?.image_url || item.product?.image_url || item.image_url || fallbackHeroImage
}

function itemVariantLabel(item) {
    const variant = item.product_variant

    if (!variant) {
        return 'Tanpa varian khusus'
    }

    return [variant.name, variant.size, variant.color].filter(Boolean).join(' / ')
}

function itemPrice(item) {
    return item.default_item_price || item.product_variant?.rental_price || item.product?.base_rental_price || 0
}

function changeImage(url) {
    if (url) {
        activeImage.value = url
    }
}
</script>

<template>
    <PublicSeoHead
        :title="packageTitle"
        :description="seoDescription"
        :structured-data="structuredData"
        :image="defaultImage"
        type="product"
    />

    <main class="min-h-screen w-full overflow-x-hidden bg-white text-[#202638]">
        <div class="mx-auto max-w-[1440px] px-4 py-6 md:px-8 xl:px-12">
            <Link
                :href="route('public.catalog')"
                class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-extrabold text-slate-600 transition hover:border-violet-200 hover:text-[var(--catalog-primary)]"
            >
                <PublicIcon name="arrow-left" :size="18" />
                Katalog
            </Link>

            <div class="grid gap-10 lg:grid-cols-[1.05fr_1fr] xl:gap-16">
                <div class="relative">
                    <div class="sticky top-[110px] space-y-4">
                        <div class="relative aspect-square w-full overflow-hidden rounded-[24px] bg-violet-50 md:rounded-[32px]">
                            <img loading="lazy"
                                :src="activeImage"
                                :alt="rentalPackage.name"
                                class="h-full w-full object-cover transition-opacity duration-300"
                            >
                            <span class="absolute left-5 top-5 rounded-full bg-diamond-primary/15 px-4 py-2 text-xs font-extrabold text-[var(--catalog-primary)] backdrop-blur-sm">
                                Paket rental
                            </span>
                        </div>

                        <div v-if="items.length" class="flex gap-3 overflow-x-auto px-1 py-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                            <button
                                v-for="item in items"
                                :key="item.id"
                                class="group w-[132px] shrink-0 overflow-hidden rounded-[16px] p-0 text-left transition-all duration-200 ring-1"
                                :class="activeImage === itemImage(item) ? 'bg-violet-50/60 ring-2 ring-[var(--catalog-primary)]' : 'bg-white ring-slate-100 hover:bg-slate-50 hover:ring-slate-200'"
                                type="button"
                                @click="changeImage(itemImage(item))"
                            >
                                <div class="aspect-square w-full overflow-hidden bg-slate-100">
                                    <img loading="lazy"
                                        v-if="itemImage(item)"
                                        :src="itemImage(item)"
                                        :alt="item.name"
                                        class="block h-full w-full object-cover transition-transform group-hover:scale-105"
                                    >
                                    <div v-else class="grid h-full place-items-center text-slate-300">
                                        <PublicIcon name="photo" :size="22" />
                                    </div>
                                </div>
                                <div class="space-y-1 p-2.5">
                                    <h3 class="line-clamp-2 text-[11px] font-bold leading-snug text-slate-800">{{ item.name }}</h3>
                                    <p class="text-[10px] font-semibold text-slate-500">Qty: {{ item.quantity }}</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col pt-2 lg:pb-12">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">Detail paket</p>
                        <h1 class="mt-3 text-3xl font-extrabold leading-tight text-slate-800 sm:text-4xl">{{ rentalPackage.name }}</h1>
                        <p v-if="rentalPackage.description" class="mt-4 text-sm font-semibold leading-7 text-slate-500">
                            {{ rentalPackage.description }}
                        </p>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Harga paket</p>
                            <p class="mt-1 text-xl font-extrabold text-[var(--catalog-primary)] sm:text-2xl">{{ formatMoney(rentalPackage.package_price) }}</p>
                        </div>
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Total item</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-800 sm:text-2xl">{{ rentalPackage.items_count }}</p>
                        </div>
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Wajib</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-800 sm:text-2xl">{{ rentalPackage.required_items_count }}</p>
                        </div>
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Opsional</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-800 sm:text-2xl">{{ rentalPackage.optional_items_count }}</p>
                        </div>
                    </div>

                    <div class="mt-10 rounded-[24px] border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                        <div class="grid gap-3 text-sm font-semibold text-slate-600">
                            <p class="flex items-start gap-3">
                                <PublicIcon name="check" :size="18" class="mt-0.5 shrink-0 text-emerald-500" />
                                <span>Isi paket tetap dikonfirmasi lagi saat fitting dan cek stok.</span>
                            </p>
                            <p class="flex items-start gap-3">
                                <PublicIcon name="check" :size="18" class="mt-0.5 shrink-0 text-emerald-500" />
                                <span>Item paket masih bisa disesuaikan oleh admin sesuai kebutuhan transaksi.</span>
                            </p>
                        </div>

                        <a
                            :href="whatsappUrl"
                            class="mt-8 flex h-14 w-full items-center justify-center gap-2 rounded-full bg-[var(--catalog-primary)] px-5 text-[15px] font-extrabold text-white transition hover:brightness-95 hover:shadow-lg hover:shadow-violet-900/20"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <PublicIcon name="brand-whatsapp" :size="20" />
                            Chat Admin & Cek Paket
                        </a>
                    </div>
                </div>
            </div>

            <section class="mt-16 border-t border-slate-100 pt-16">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">Isi paket</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-800 sm:text-3xl">Produk yang termasuk</h2>
                </div>

                <div v-if="items.length" class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="item in items"
                        :key="item.id"
                        :href="item.product?.id ? productDetailUrl(item.product.id) : '#'"
                        class="group flex gap-4 rounded-[20px] border border-slate-100 bg-white p-3 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="aspect-square w-[104px] shrink-0 overflow-hidden rounded-[16px] bg-slate-100">
                            <img loading="lazy"
                                :src="itemImage(item)"
                                :alt="item.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            >
                        </div>
                        <div class="min-w-0 flex-1 py-1">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="line-clamp-2 text-sm font-extrabold leading-snug text-slate-800 transition group-hover:text-[var(--catalog-primary)]">{{ item.name }}</h3>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ itemVariantLabel(item) }}</p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-extrabold"
                                    :class="item.is_optional ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600'"
                                >
                                    {{ item.is_optional ? 'Opsional' : 'Include' }}
                                </span>
                            </div>
                            <div class="mt-4 flex items-end justify-between gap-3">
                                <p class="text-xs font-semibold text-slate-500">Qty: {{ item.quantity }}</p>
                                <p class="text-sm font-extrabold text-[var(--catalog-primary)]">{{ formatMoney(itemPrice(item)) }}</p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-else class="mt-8 rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-12 text-center">
                    <p class="text-lg font-bold text-slate-800">Belum ada item aktif dalam paket</p>
                    <p class="mt-2 text-sm text-slate-500">Hubungi admin untuk memastikan detail pilihan.</p>
                </div>
            </section>

            <section v-if="relatedPackages.length" class="mt-16 border-t border-slate-100 pt-16">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">Rekomendasi</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-800 sm:text-3xl">Paket Lainnya</h2>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:gap-6">
                    <Link
                        v-for="relatedPackage in relatedPackages"
                        :key="relatedPackage.id"
                        :href="route('public.catalog.packages.show', relatedPackage.id)"
                        class="group rounded-[20px] border border-slate-100 bg-white shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="relative block aspect-[1/1] w-full overflow-hidden rounded-t-[20px] bg-slate-100">
                            <img loading="lazy"
                                :src="relatedPackage.image_url || fallbackHeroImage"
                                :alt="relatedPackage.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            >
                        </div>
                        <div class="p-3">
                            <h3 class="line-clamp-2 text-[15px] font-bold text-slate-800 transition group-hover:text-[var(--catalog-primary)]">{{ relatedPackage.name }}</h3>
                            <p class="mt-1 text-xs text-slate-400">{{ relatedPackage.items_count }} item</p>
                            <p class="mt-2 text-[15px] font-extrabold text-[var(--catalog-primary)]">{{ formatMoney(relatedPackage.package_price) }}</p>
                        </div>
                    </Link>
                </div>
            </section>
        </div>
    </main>
</template>
