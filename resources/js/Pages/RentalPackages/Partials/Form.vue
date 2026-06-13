<script setup>
import { computed, ref } from 'vue'
import { ChevronDown, ChevronUp, CopyPlus, ImagePlus, PackageCheck, Search, Trash2, X } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import EmptyState from '@/Components/EmptyState.vue'
import Input from '@/Components/Input.vue'
import Switch from '@/Components/Switch.vue'
import { useConfirm } from '@/Composables/useConfirm'

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
})

defineEmits(['submit'])

const { confirmAction } = useConfirm()
const pickerOpen = ref(false)
const productSearch = ref('')

const productById = computed(() => {
    return props.products.reduce((carry, product) => {
        carry[product.id] = product

        return carry
    }, {})
})

const filteredProducts = computed(() => {
    const search = productSearch.value.trim().toLowerCase()

    if (!search) {
        return props.products
    }

    return props.products.filter((product) => {
        return [product.name, product.code]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(search))
    })
})

const requiredItemsCount = computed(() => props.form.items.filter((item) => !item.is_optional).length)
const optionalItemsCount = computed(() => props.form.items.filter((item) => item.is_optional).length)
const estimatedSubtotal = computed(() => props.form.items.reduce((total, item) => total + itemTotal(item), 0))
const packageSaving = computed(() => Math.max(0, estimatedSubtotal.value - Number(props.form.package_price || 0)))

function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

function openPicker() {
    productSearch.value = ''
    pickerOpen.value = true
}

function closePicker() {
    pickerOpen.value = false
}

function addProduct(product) {
    props.form.items.push({
        id: null,
        product_id: product.id,
        product_variant_id: '',
        quantity: 1,
        default_item_price: product.base_rental_price || '',
        is_optional: false,
        notes: '',
        expanded: true,
    })

    closePicker()
}

function duplicateItem(item) {
    props.form.items.push({
        id: null,
        product_id: item.product_id,
        product_variant_id: item.product_variant_id,
        quantity: item.quantity || 1,
        default_item_price: item.default_item_price,
        is_optional: item.is_optional,
        notes: item.notes,
        expanded: true,
    })
}

async function removeItem(index) {
    const confirmed = await confirmAction({
        title: 'Hapus item paket?',
        message: `Item ${index + 1} akan dihapus dari paket ini.`,
        confirmLabel: 'Ya, hapus item',
    })

    if (!confirmed) {
        return
    }

    props.form.items.splice(index, 1)
}

function selectedProduct(item) {
    return productById.value[item.product_id] ?? null
}

function variantsFor(productId) {
    return productById.value[productId]?.variants ?? []
}

function selectedVariant(item) {
    return variantsFor(item.product_id).find((variant) => variant.id === item.product_variant_id) ?? null
}

function suggestedPrice(item) {
    return selectedVariant(item)?.rental_price || selectedProduct(item)?.base_rental_price || ''
}

function applySuggestedPrice(item) {
    item.default_item_price = suggestedPrice(item)
}

function onProductChanged(item) {
    const variants = variantsFor(item.product_id)

    if (!variants.some((variant) => variant.id === item.product_variant_id)) {
        item.product_variant_id = ''
    }

    applySuggestedPrice(item)
}

function onVariantChanged(item) {
    if (suggestedPrice(item)) {
        applySuggestedPrice(item)
    }
}

function itemTotal(item) {
    return Number(item.quantity || 0) * Number(item.default_item_price || suggestedPrice(item) || 0)
}

function toggleItem(item) {
    item.expanded = !item.expanded
}

function fieldError(index, field) {
    return props.form.errors[`items.${index}.${field}`]
}
</script>

<template>
    <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]" @submit.prevent="$emit('submit')">
        <div class="grid gap-6">
            <section class="grid gap-5 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <PackageCheck :size="24" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-diamond-text">Data paket</h2>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <Input v-model="form.name" :error="form.errors.name" autofocus label="Nama paket" placeholder="Contoh: Jas Full Set" />
                    <CurrencyInput v-model="form.package_price" :error="form.errors.package_price" label="Harga paket" />
                </div>

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-diamond-text">Deskripsi</span>
                    <textarea
                        v-model="form.description"
                        class="min-h-28 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        placeholder="Catatan singkat isi paket atau kondisi penggunaan"
                    />
                    <span v-if="form.errors.description" class="text-sm text-diamond-danger">{{ form.errors.description }}</span>
                </label>

                <Switch
                    v-model="form.is_active"
                    label="Status Paket"
                    description="Paket aktif bisa dipilih saat membuat transaksi rental."
                />
            </section>

            <section class="grid gap-4 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-lg font-bold text-diamond-text">Susun item paket</h2>
                    </div>
                    <Button type="button" variant="secondary" @click="openPicker">
                        <Search :size="18" />
                        Pilih produk
                    </Button>
                </div>

                <span v-if="form.errors.items" class="text-sm text-diamond-danger">{{ form.errors.items }}</span>

                <div class="grid gap-3">
                    <EmptyState
                        v-if="form.items.length === 0"
                        title="Belum ada item paket."
                        description="Klik Pilih produk untuk mulai menyusun isi paket rental."
                    />

                    <article
                        v-for="(item, index) in form.items"
                        :key="item.id ?? index"
                        class="overflow-hidden rounded-3xl bg-diamond-surface-soft"
                    >
                        <button
                            class="flex w-full items-center gap-4 p-3 text-left sm:p-4"
                            type="button"
                            @click="toggleItem(item)"
                        >
                            <div class="flex h-18 w-18 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-diamond-soft sm:h-20 sm:w-20">
                                <img
                                    v-if="selectedProduct(item)?.image_url"
                                    :src="selectedProduct(item).image_url"
                                    :alt="selectedProduct(item).name"
                                    class="h-full w-full object-cover"
                                >
                                <ImagePlus v-else :size="26" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-xl bg-white px-3 py-1 text-xs font-bold text-diamond-primary">Item {{ index + 1 }}</span>
                                    <span class="rounded-xl px-3 py-1 text-xs font-bold" :class="item.is_optional ? 'bg-orange-50 text-diamond-accent' : 'bg-emerald-50 text-emerald-700'">
                                        {{ item.is_optional ? 'Opsional' : 'Wajib' }}
                                    </span>
                                </div>
                                <p class="mt-2 line-clamp-1 font-bold text-diamond-text">{{ selectedProduct(item)?.name || 'Produk belum dipilih' }}</p>
                                <p class="mt-1 line-clamp-1 text-sm text-diamond-muted">
                                    {{ selectedVariant(item)?.name || selectedProduct(item)?.code || 'Klik untuk atur detail' }}
                                </p>
                                <p class="mt-2 text-sm font-bold text-diamond-primary">{{ formatMoney(itemTotal(item)) }}</p>
                            </div>

                            <component :is="item.expanded ? ChevronUp : ChevronDown" class="shrink-0 text-diamond-muted" :size="22" />
                        </button>

                        <div v-if="item.expanded" class="grid gap-4 border-t border-white/80 p-4 sm:p-5">
                            <div class="grid gap-4 lg:grid-cols-2">
                                <label class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Produk</span>
                                    <select
                                        v-model="item.product_id"
                                        class="min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                        @change="onProductChanged(item)"
                                    >
                                        <option value="">Pilih produk</option>
                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                            {{ product.name }}{{ product.code ? ` (${product.code})` : '' }}
                                        </option>
                                    </select>
                                    <span v-if="fieldError(index, 'product_id')" class="text-sm text-diamond-danger">{{ fieldError(index, 'product_id') }}</span>
                                </label>

                                <label class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Varian</span>
                                    <select
                                        v-model="item.product_variant_id"
                                        class="min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                        @change="onVariantChanged(item)"
                                    >
                                        <option value="">Tanpa varian khusus</option>
                                        <option v-for="variant in variantsFor(item.product_id)" :key="variant.id" :value="variant.id">
                                            {{ variant.name }}{{ variant.sku ? ` (${variant.sku})` : '' }}
                                        </option>
                                    </select>
                                    <span v-if="fieldError(index, 'product_variant_id')" class="text-sm text-diamond-danger">
                                        {{ fieldError(index, 'product_variant_id') }}
                                    </span>
                                </label>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-[140px_minmax(0,1fr)_220px]">
                                <Input
                                    v-model="item.quantity"
                                    :error="fieldError(index, 'quantity')"
                                    label="Qty"
                                    min="1"
                                    step="1"
                                    type="number"
                                />

                                <CurrencyInput
                                    v-model="item.default_item_price"
                                    :error="fieldError(index, 'default_item_price')"
                                    label="Harga default item"
                                    placeholder="Opsional"
                                />

                                <div class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Harga produk</span>
                                    <button
                                        class="inline-flex min-h-12 items-center justify-center rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm font-semibold text-diamond-text transition hover:bg-white/70 disabled:cursor-not-allowed disabled:opacity-50"
                                        type="button"
                                        :disabled="!suggestedPrice(item)"
                                        @click="applySuggestedPrice(item)"
                                    >
                                        {{ suggestedPrice(item) ? formatMoney(suggestedPrice(item)) : 'Belum ada' }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
                                <Switch
                                    v-model="item.is_optional"
                                    label="Item opsional"
                                    description="Staff boleh menghapus item ini dari transaksi."
                                />

                                <label class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Catatan item</span>
                                    <textarea
                                        v-model="item.notes"
                                        class="min-h-24 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                        placeholder="Contoh: bisa diganti warna senada"
                                    />
                                    <span v-if="fieldError(index, 'notes')" class="text-sm text-diamond-danger">{{ fieldError(index, 'notes') }}</span>
                                </label>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <button
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-diamond-border bg-white px-3 py-2 text-sm font-semibold text-diamond-text transition hover:bg-white/70"
                                    type="button"
                                    @click="duplicateItem(item)"
                                >
                                    <CopyPlus :size="16" />
                                    Duplikat
                                </button>
                                <button
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-3 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                    type="button"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 :size="16" />
                                    Hapus item
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        <aside class="grid gap-4 self-start xl:sticky xl:top-8">
            <section class="grid gap-4 rounded-[2rem] border border-white/80 bg-white p-5 sm:p-6">
                <div>
                    <p class="text-sm font-bold text-diamond-muted">Ringkasan paket</p>
                    <p class="mt-2 text-2xl font-bold text-diamond-text">{{ formatMoney(form.package_price) }}</p>
                </div>

                <div class="grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                        <span class="text-diamond-muted">Total item</span>
                        <span class="font-bold text-diamond-text">{{ form.items.length }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                        <span class="text-diamond-muted">Wajib</span>
                        <span class="font-bold text-diamond-text">{{ requiredItemsCount }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                        <span class="text-diamond-muted">Opsional</span>
                        <span class="font-bold text-diamond-text">{{ optionalItemsCount }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                        <span class="text-diamond-muted">Estimasi item</span>
                        <span class="font-bold text-diamond-text">{{ formatMoney(estimatedSubtotal) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-orange-50 px-4 py-3">
                        <span class="text-diamond-accent">Selisih paket</span>
                        <span class="font-bold text-diamond-accent">{{ formatMoney(packageSaving) }}</span>
                    </div>
                </div>

                <Button type="submit" :disabled="form.processing" full>
                    {{ form.processing ? 'Menyimpan...' : submitLabel }}
                </Button>
            </section>
        </aside>

        <Teleport to="body">
            <div
                v-if="pickerOpen"
                class="fixed inset-0 z-[90] grid place-items-center bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closePicker"
            >
                <section class="grid max-h-[88vh] w-full max-w-4xl overflow-hidden rounded-[2rem] border border-white/80 bg-white" role="dialog" aria-modal="true" aria-label="Pilih produk paket">
                    <div class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div>
                            <h2 class="text-lg font-bold text-diamond-text">Pilih produk paket</h2>
                            <p class="mt-1 text-sm leading-6 text-diamond-muted">Cari produk, lalu klik tambah untuk memasukkan ke paket.</p>
                        </div>
                        <button
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            aria-label="Tutup"
                            @click="closePicker"
                        >
                            <X :size="19" />
                        </button>
                    </div>

                    <div class="grid gap-4 overflow-y-auto p-5 sm:p-6">
                        <label class="relative block">
                            <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-soft" :size="18" />
                            <input
                                v-model="productSearch"
                                class="min-h-12 w-full rounded-2xl border border-diamond-border bg-diamond-surface-soft py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                placeholder="Cari nama atau kode produk"
                                type="search"
                            >
                        </label>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <article
                                v-for="product in filteredProducts"
                                :key="product.id"
                                class="flex gap-3 rounded-3xl border border-diamond-border bg-white p-3 transition hover:bg-diamond-surface-soft"
                            >
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-diamond-soft">
                                    <img
                                        v-if="product.image_url"
                                        :src="product.image_url"
                                        :alt="product.name"
                                        class="h-full w-full object-cover"
                                    >
                                    <ImagePlus v-else :size="26" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="line-clamp-2 font-bold leading-6 text-diamond-text">{{ product.name }}</p>
                                    <p class="mt-1 truncate text-sm text-diamond-muted">{{ product.code || 'Tanpa kode' }}</p>
                                    <p class="mt-2 text-sm font-bold text-diamond-primary">{{ formatMoney(product.base_rental_price) }}</p>
                                </div>
                                <button
                                    class="self-center rounded-xl bg-diamond-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-diamond-primary-dark"
                                    type="button"
                                    @click="addProduct(product)"
                                >
                                    Tambah
                                </button>
                            </article>
                        </div>

                        <EmptyState v-if="filteredProducts.length === 0" title="Produk tidak ditemukan." description="Coba gunakan kata kunci lain." />
                    </div>
                </section>
            </div>
        </Teleport>
    </form>
</template>
