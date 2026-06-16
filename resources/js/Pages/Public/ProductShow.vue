<script setup>
import { Link, usePage } from '@inertiajs/vue3'
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
    product: {
        type: Object,
        required: true,
    },
    relatedProducts: {
        type: Array,
        required: true,
    },
})

const page = usePage()
const fallbackHeroImage = '/images/catalog-hero.png'

const cssVars = computed(() => ({
    '--catalog-primary': '#6533D6',
}))

const defaultImage = computed(() => productImage(props.product))
const activeImage = ref(defaultImage.value)
const productTitle = computed(() => `${props.product.name}`)
const seoDescription = computed(() => {
    const category = props.product.category?.name || 'koleksi rental'
    const description = props.product.description?.trim()

    if (description) {
        return description
    }

    return `${props.product.name} dari kategori ${category} di ${props.catalogStore.name}. Lihat detail varian, referensi harga, lalu datang fitting ke toko sebelum transaksi rental difinalkan.`
})

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: props.product.name,
    description: seoDescription.value,
    image: [absoluteUrl(defaultImage.value)],
    category: props.product.category?.name,
    sku: props.product.code || undefined,
    url: page.props.ziggy?.location,
    brand: {
        '@type': 'Brand',
        name: props.catalogStore.name,
    },
    offers: {
        '@type': 'Offer',
        priceCurrency: 'IDR',
        price: Number(props.product.lowest_price || 0),
        availability: props.product.total_stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        url: page.props.ziggy?.location,
    },
}))

const whatsappUrl = computed(() => {
    const phone = normalizePhone(props.catalogStore.whatsapp_number)
    const message = encodeURIComponent(`Halo ${props.catalogStore.name}, saya tertarik sewa ${props.product.name}. Apakah tersedia?`)

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

function variantLabel(variant) {
    return [variant.name, variant.size, variant.color].filter(Boolean).join(' / ') || 'Varian produk'
}

function variantPrice(variant) {
    return variant.rental_price || props.product.base_rental_price
}

function changeImage(url) {
    if (url) {
        activeImage.value = url
    }
}
</script>

<template>
    <PublicSeoHead
        :title="productTitle"
        :description="seoDescription"
        :structured-data="structuredData"
        :image="defaultImage"
        type="product"
    />

    <main :style="cssVars" class="min-h-screen w-full overflow-x-hidden bg-white text-[#202638]">
        <div class="mx-auto max-w-[1440px] px-4 py-6 md:px-8 xl:px-12">
            <Link
                :href="route('public.catalog')"
                class="mb-6 inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-extrabold text-slate-600 transition hover:border-violet-200 hover:text-[var(--catalog-primary)]"
            >
                <PublicIcon name="arrow-left" :size="18" />
                Kembali
            </Link>

            <div class="grid gap-10 lg:grid-cols-[1.1fr_1fr] xl:gap-16">
                <div class="relative">
                    <div class="sticky top-[110px] space-y-4">
                        <div class="relative aspect-square w-full overflow-hidden rounded-[24px] bg-slate-50 md:rounded-[32px]">
                            <img
                                :src="activeImage"
                                :alt="product.name"
                                class="h-full w-full object-cover transition-opacity duration-300"
                            >
                        </div>

                        <div v-if="product.variants.length">
                            <div class="flex gap-3 overflow-x-auto py-1 px-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                <button
                                    v-for="variant in product.variants"
                                    :key="variant.id"
                                    class="group w-[132px] shrink-0 overflow-hidden rounded-[16px] border text-left transition-all duration-200"
                                    :class="activeImage === (variant.image_url || defaultImage) ? 'border-[var(--catalog-primary)] bg-violet-50/60 ring-1 ring-[var(--catalog-primary)]' : 'border-slate-100 bg-white hover:border-slate-200 hover:bg-slate-50'"
                                    type="button"
                                    @click="changeImage(variant.image_url || defaultImage)"
                                >
                                    <div class="aspect-square w-full overflow-hidden bg-slate-100">
                                        <img
                                            v-if="variant.image_url"
                                            :src="variant.image_url"
                                            :alt="variant.name"
                                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        >
                                        <div v-else class="grid h-full place-items-center text-slate-300">
                                            <PublicIcon name="photo" :size="22" />
                                        </div>
                                    </div>
                                    <div class="space-y-1 p-2.5">
                                        <h3 class="line-clamp-2 text-[11px] font-bold leading-snug text-slate-800">{{ variantLabel(variant) }}</h3>
                                        <p class="text-[11px] font-extrabold text-[var(--catalog-primary)]">{{ formatMoney(variantPrice(variant)) }}</p>
                                        <p class="text-[10px] font-semibold text-slate-500">Stok: {{ variant.stock_quantity }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col pt-2 lg:pb-12">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">Detail produk</p>
                        <h1 class="mt-3 text-3xl font-extrabold leading-tight text-slate-800 sm:text-4xl">{{ product.name }}</h1>
                        <p class="mt-2 text-sm font-semibold text-slate-500">Kode: {{ product.code || 'Tanpa kode' }}</p>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Harga mulai</p>
                            <p class="mt-1 text-xl font-extrabold text-[var(--catalog-primary)] sm:text-2xl">{{ formatMoney(product.lowest_price) }}</p>
                        </div>
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Total stok</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-800 sm:text-2xl">{{ product.total_stock }}</p>
                        </div>
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Total Varian</p>
                            <p class="mt-1 text-xl font-extrabold text-slate-800 sm:text-2xl">{{ product.variants_count }}</p>
                        </div>
                        <div class="rounded-[20px] bg-slate-50 p-4 sm:p-5">
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 sm:text-[11px]">Kategori</p>
                            <p class="mt-1 truncate text-xl font-extrabold text-slate-800 sm:text-2xl">{{ product.category?.name || '-' }}</p>
                        </div>
                    </div>

                    <div v-if="!product.variants.length" class="mt-10 grid place-items-center rounded-[20px] border border-dashed border-slate-200 bg-slate-50 px-5 py-10 text-center">
                        <span class="grid size-12 place-items-center rounded-full bg-slate-200 text-slate-500">
                            <PublicIcon name="package" :size="24" />
                        </span>
                        <h2 class="mt-3 text-sm font-extrabold text-slate-800">Belum ada varian aktif</h2>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Hubungi admin untuk memastikan detail pilihan.</p>
                    </div>

                    <div class="mt-10 rounded-[24px] border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                        <div class="grid gap-3 text-sm font-semibold text-slate-600">
                            <p class="flex items-start gap-3">
                                <PublicIcon name="check" :size="18" class="mt-0.5 shrink-0 text-emerald-500" />
                                <span>Stok dikonfirmasi lagi sesuai tanggal rental yang diinginkan.</span>
                            </p>
                            <p class="flex items-start gap-3">
                                <PublicIcon name="check" :size="18" class="mt-0.5 shrink-0 text-emerald-500" />
                                <span>Harga final mengikuti kesepakatan transaksi dengan admin.</span>
                            </p>
                        </div>

                        <a
                            :href="whatsappUrl"
                            class="mt-8 flex h-14 w-full items-center justify-center gap-2 rounded-full bg-[var(--catalog-primary)] px-5 text-[15px] font-extrabold text-white transition hover:brightness-95 hover:shadow-lg hover:shadow-violet-900/20"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <PublicIcon name="brand-whatsapp" :size="20" />
                            Chat Admin & Cek Ketersediaan
                        </a>
                    </div>
                </div>
            </div>

            <div v-if="relatedProducts.length" class="mt-16 border-t border-slate-100 pt-16">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">Rekomendasi</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-800 sm:text-3xl">Koleksi Sejenis</h2>
                </div>

                <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 xl:gap-6">
                    <Link
                        v-for="relatedProduct in relatedProducts"
                        :key="relatedProduct.id"
                        :href="route('public.catalog.show', relatedProduct.id)"
                        class="group rounded-[20px] border border-slate-100 bg-white p-3 shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="relative block aspect-[3/4] w-full overflow-hidden rounded-[14px] bg-slate-100">
                            <img
                                :src="productImage(relatedProduct)"
                                :alt="relatedProduct.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            >
                        </div>
                        <div class="mt-4 px-1 pb-1">
                            <h3 class="truncate text-[15px] font-bold text-slate-800 transition group-hover:text-[var(--catalog-primary)]">{{ relatedProduct.name }}</h3>
                            <p class="mt-2 text-[15px] font-extrabold text-[var(--catalog-primary)]">{{ formatMoney(relatedProduct.lowest_price) }}</p>
                        </div>
                    </Link>
                </div>
            </div>

        </div>
    </main>
</template>
