<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { ArrowLeft, Check, ChevronDown, PackagePlus, Plus, Save, Search, ShoppingCart, Trash2, UserPlus, UserRound, X } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    customers: {
        type: Array,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
    packages: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    customer_mode: 'existing',
    customer_id: '',
    new_customer: {
        name: '',
        whatsapp_number: '',
        notes: '',
    },
    guarantee_type: '',
    pickup_at: '',
    return_due_at: '',
    notes: '',
    selected_package_id: '',
    custom_total_amount: '',
    initial_payment_amount: '',
    initial_payment_method: 'cash',
    initial_payment_notes: '',
    items: [
        {
            rental_package_id: '',
            product_id: '',
            product_variant_id: '',
            quantity: 1,
            unit_price: '',
            discount_amount: 0,
            notes: '',
        },
    ],
})
const { confirmAction } = useConfirm()
const productSearch = ref('')
const customerPickerOpen = ref(false)
const customerSearch = ref('')
const newCustomerExpanded = ref(false)
const itemModalOpen = ref(false)
const itemModalMode = ref('product')
const editingItemIndex = ref(null)
const modalProduct = ref(null)
const modalPackage = ref(null)
const itemDraft = ref(emptyItem())
const packageDrafts = ref([])
const expandedPackageDraftIndex = ref(0)

const productById = computed(() => {
    return props.products.reduce((carry, product) => {
        carry[product.id] = product

        return carry
    }, {})
})

const packageById = computed(() => {
    return props.packages.reduce((carry, rentalPackage) => {
        carry[rentalPackage.id] = rentalPackage

        return carry
    }, {})
})

const subtotal = computed(() => {
    return form.items.reduce((total, item) => total + lineTotal(item), 0)
})

const totalAmount = computed(() => {
    if (form.custom_total_amount !== '' && form.custom_total_amount !== null) {
        return Number(form.custom_total_amount || 0)
    }

    return subtotal.value
})

const remainingAmount = computed(() => {
    return totalAmount.value - Number(form.initial_payment_amount || 0)
})

const cartItemCount = computed(() => {
    return form.items.filter((item) => item.product_id).length
})

const filteredProducts = computed(() => {
    const keyword = productSearch.value.trim().toLowerCase()

    if (!keyword) {
        return props.products
    }

    return props.products.filter((product) => {
        return [product.name, product.code]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(keyword))
    })
})

const filteredCustomers = computed(() => {
    const keyword = customerSearch.value.trim().toLowerCase()

    if (!keyword) {
        return props.customers
    }

    return props.customers.filter((customer) => {
        return [customer.name, customer.whatsapp_number]
            .filter(Boolean)
            .some((value) => value.toLowerCase().includes(keyword))
    })
})

const selectedCustomer = computed(() => {
    if (form.customer_mode !== 'existing' || !form.customer_id) {
        return null
    }

    return props.customers.find((customer) => customer.id === form.customer_id)
})

const customerSummary = computed(() => {
    if (form.customer_mode === 'new' && form.new_customer.name) {
        return `${form.new_customer.name}${form.new_customer.whatsapp_number ? ` - ${form.new_customer.whatsapp_number}` : ''}`
    }

    if (selectedCustomer.value) {
        return `${selectedCustomer.value.name} - ${selectedCustomer.value.whatsapp_number}`
    }

    return 'Belum ada customer dipilih'
})

const hasNewCustomerErrors = computed(() => {
    return Boolean(
        form.errors['new_customer.name']
        || form.errors['new_customer.whatsapp_number']
        || form.errors['new_customer.notes'],
    )
})

const paymentMethodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'transfer', label: 'Transfer' },
    { value: 'qris', label: 'QRIS' },
    { value: 'debit', label: 'Debit' },
    { value: 'other', label: 'Lainnya' },
]

function openCustomerPicker() {
    newCustomerExpanded.value = form.customer_mode === 'new' || hasNewCustomerErrors.value
    customerPickerOpen.value = true
}

function closeCustomerPicker() {
    customerPickerOpen.value = false
}

function chooseExistingCustomer(customer) {
    form.customer_mode = 'existing'
    form.customer_id = customer.id
    closeCustomerPicker()
}

function useNewCustomerFromModal() {
    form.customer_mode = 'new'
    form.customer_id = ''
    closeCustomerPicker()
}

function formatMoney(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0))
}

function variantsFor(productId) {
    return productById.value[productId]?.variants ?? []
}

function lineTotal(item) {
    return Math.max(0, Number(item.quantity || 0) * Number(item.unit_price || 0))
}

function defaultPrice(item) {
    const product = productById.value[item.product_id]
    const variant = variantsFor(item.product_id).find((candidate) => candidate.id === item.product_variant_id)

    return variant?.rental_price || product?.base_rental_price || ''
}

function selectedProduct(item) {
    return productById.value[item.product_id]
}

function selectedVariant(item) {
    return variantsFor(item.product_id).find((candidate) => candidate.id === item.product_variant_id)
}

function itemTitle(item, index) {
    return selectedProduct(item)?.name || `Item ${index + 1}`
}

function itemSubtitle(item) {
    const variant = selectedVariant(item)

    if (variant) {
        return [variant.name, variant.size, variant.color].filter(Boolean).join(' / ')
    }

    return selectedProduct(item)?.code || 'Pilih produk dan varian'
}

function itemImage(item) {
    return selectedProduct(item)?.image_url || null
}

function variantLabel(variant) {
    if (!variant) {
        return 'Tanpa varian khusus'
    }

    return [variant.name, variant.size, variant.color].filter(Boolean).join(' / ')
}

function fieldClasses() {
    return 'min-h-12 w-full min-w-0 max-w-full truncate rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}

function emptyItem(overrides = {}) {
    return {
        rental_package_id: '',
        product_id: '',
        product_variant_id: '',
        quantity: 1,
        unit_price: '',
        discount_amount: 0,
        notes: '',
        ...overrides,
    }
}

function cloneItem(item) {
    return emptyItem({ ...item })
}

function hasOnlyEmptyItem() {
    return form.items.length === 1 && !form.items[0].product_id
}

function replaceEmptyItemOrPush(items) {
    if (hasOnlyEmptyItem()) {
        form.items.splice(0, 1, ...items)

        return
    }

    form.items.push(...items)
}

function defaultProductItem(product, variant = null) {
    return emptyItem({
        product_id: product.id,
        product_variant_id: variant?.id || '',
        unit_price: variant?.rental_price || product.base_rental_price || '',
    })
}

function openProductConfigurator(product) {
    itemModalMode.value = 'product'
    editingItemIndex.value = null
    modalProduct.value = product
    modalPackage.value = null
    itemDraft.value = defaultProductItem(product)
    packageDrafts.value = []
    itemModalOpen.value = true
}

function openPackageConfigurator(rentalPackage) {
    itemModalMode.value = 'package'
    editingItemIndex.value = null
    modalPackage.value = rentalPackage
    modalProduct.value = null
    packageDrafts.value = rentalPackage.items.map((item) => cloneItem({
        rental_package_id: item.rental_package_id,
        product_id: item.product_id,
        product_variant_id: item.product_variant_id || '',
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount_amount: 0,
        notes: item.notes || '',
    }))
    expandedPackageDraftIndex.value = 0
    itemModalOpen.value = true
}

function openEditItem(item, index) {
    itemModalMode.value = 'edit'
    editingItemIndex.value = index
    modalProduct.value = selectedProduct(item)
    modalPackage.value = null
    itemDraft.value = cloneItem(item)
    packageDrafts.value = []
    itemModalOpen.value = true
}

function closeItemModal() {
    itemModalOpen.value = false
    editingItemIndex.value = null
    modalProduct.value = null
    modalPackage.value = null
    packageDrafts.value = []
    itemDraft.value = emptyItem()
}

function saveConfiguredItem() {
    if (itemModalMode.value === 'package') {
        const configuredItems = packageDrafts.value
            .filter((item) => item.product_id)
            .map((item) => cloneItem(item))

        replaceEmptyItemOrPush(configuredItems)

        if (modalPackage.value) {
            form.custom_total_amount = modalPackage.value.package_price
        }

        closeItemModal()

        return
    }

    const configuredItem = cloneItem(itemDraft.value)

    if (editingItemIndex.value !== null) {
        form.items.splice(editingItemIndex.value, 1, configuredItem)
    } else {
        replaceEmptyItemOrPush([configuredItem])
    }

    closeItemModal()
}

async function removeItem(index) {
    if (!form.items[index]?.product_id) {
        return false
    }

    const confirmed = await confirmAction({
        title: 'Hapus item transaksi?',
        message: `Item ${index + 1} akan dihapus dari form transaksi ini.`,
        confirmLabel: 'Ya, hapus item',
    })

    if (!confirmed) {
        return false
    }

    if (cartItemCount.value === 1) {
        form.items.splice(0, form.items.length, emptyItem())

        return true
    }

    form.items.splice(index, 1)

    return true
}

async function deleteEditingItem() {
    if (editingItemIndex.value === null) {
        return
    }

    const index = editingItemIndex.value

    const removed = await removeItem(index)

    if (removed) {
        closeItemModal()
    }
}

function onProductChanged(item) {
    const variants = variantsFor(item.product_id)

    if (!variants.some((variant) => variant.id === item.product_variant_id)) {
        item.product_variant_id = ''
    }

    item.unit_price = defaultPrice(item)
}

function onVariantChanged(item) {
    item.unit_price = defaultPrice(item)
}

function fieldError(index, field) {
    return form.errors[`items.${index}.${field}`]
}

function submit() {
    form.post(route('rentals.store'))
}
</script>

<template>
    <Head title="Buat Rental" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Transaksi"
            title="Buat Transaksi"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('rentals.index')" variant="secondary">
                    <ArrowLeft :size="18" />
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <form class="grid gap-6" @submit.prevent="submit">
            <section class="grid gap-5 rounded-[2rem] border border-white/80 bg-white p-5 sm:p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <UserRound :size="22" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-diamond-text">Info transaksi</h2>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_0.8fr_1fr_1fr]">
                    <div class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Customer</span>
                        <button
                            class="flex min-h-12 w-full items-center justify-between gap-4 rounded-xl border border-diamond-border bg-white px-4 py-3 text-left transition hover:bg-diamond-surface-soft focus:outline-none focus:ring-4 focus:ring-diamond-primary/10"
                            type="button"
                            @click="openCustomerPicker"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold text-diamond-text">{{ customerSummary }}</span>
                                <span class="mt-1 block text-xs text-diamond-muted">
                                    {{ form.customer_mode === 'new' ? 'Customer baru' : 'Customer lama' }}
                                </span>
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-diamond-primary px-3 py-2 text-xs font-bold text-white">
                                <UserPlus :size="15" />
                                Pilih
                            </span>
                        </button>
                        <span v-if="form.errors.customer_mode" class="text-sm text-diamond-danger">{{ form.errors.customer_mode }}</span>
                        <span v-if="form.errors.customer_id" class="text-sm text-diamond-danger">{{ form.errors.customer_id }}</span>
                        <span v-if="form.errors['new_customer.name']" class="text-sm text-diamond-danger">{{ form.errors['new_customer.name'] }}</span>
                        <span v-if="form.errors['new_customer.whatsapp_number']" class="text-sm text-diamond-danger">
                            {{ form.errors['new_customer.whatsapp_number'] }}
                        </span>
                    </div>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Jaminan</span>
                        <select v-model="form.guarantee_type" class="uppercase" :class="fieldClasses()">
                            <option value="">Belum diserahkan</option>
                            <option value="ktp">KTP</option>
                            <option value="sim">SIM</option>
                        </select>
                        <span v-if="form.errors.guarantee_type" class="text-sm text-diamond-danger">{{ form.errors.guarantee_type }}</span>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Jadwal ambil</span>
                        <input
                            v-model="form.pickup_at"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            type="date"
                        >
                        <span v-if="form.errors.pickup_at" class="text-sm text-diamond-danger">{{ form.errors.pickup_at }}</span>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Jadwal kembali</span>
                        <input
                            v-model="form.return_due_at"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            type="date"
                        >
                        <span v-if="form.errors.return_due_at" class="text-sm text-diamond-danger">{{ form.errors.return_due_at }}</span>
                    </label>
                </div>

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-diamond-text">Catatan</span>
                    <textarea
                        v-model="form.notes"
                        class="min-h-20 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm leading-6 text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        placeholder="Catatan opsional untuk transaksi ini"
                    />
                </label>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_430px] xl:items-start">
                <section class="grid gap-4 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5">
                    <div>
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                            <div>
                                <h2 class="text-lg font-bold text-diamond-text">Katalog POS</h2>
                            </div>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-muted" :size="18" />
                                <input
                                    v-model="productSearch"
                                    class="min-h-12 w-full rounded-xl border border-diamond-border bg-white py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10 lg:w-80"
                                    placeholder="Cari produk atau kode"
                                    type="search"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="max-h-[64vh] overflow-y-auto rounded-[1.5rem] bg-diamond-surface-soft p-3 pr-2 lg:max-h-[calc(100vh-320px)]">
                        <div v-if="packages.length" class="grid gap-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-bold uppercase text-diamond-muted">Paket</p>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                                <button
                                    v-for="rentalPackage in packages"
                                    :key="rentalPackage.id"
                                    class="grid min-h-32 cursor-pointer gap-3 rounded-3xl border border-white/80 bg-white p-4 text-left transition hover:border-diamond-primary/40 hover:bg-white/80"
                                    type="button"
                                    @click="openPackageConfigurator(rentalPackage)"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="line-clamp-2 text-sm font-bold text-diamond-text">{{ rentalPackage.name }}</p>
                                            <p class="mt-1 text-xs text-diamond-muted">{{ rentalPackage.items.length }} item paket</p>
                                        </div>
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-diamond-accent/15 text-diamond-accent">
                                            <PackagePlus :size="19" />
                                        </div>
                                    </div>
                                    <div class="mt-auto flex items-center justify-between gap-3">
                                        <p class="text-base font-bold text-diamond-primary">{{ formatMoney(rentalPackage.package_price) }}</p>
                                        <span class="rounded-full bg-diamond-primary-soft px-3 py-1 text-xs font-bold text-diamond-primary">Tambah</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <p class="text-sm font-bold uppercase text-diamond-muted">Produk</p>
                            <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                                <button
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    class="grid cursor-pointer gap-3 rounded-3xl border border-white/80 bg-white p-3 text-left transition hover:border-diamond-primary/40 hover:bg-white/80"
                                    type="button"
                                    @click="openProductConfigurator(product)"
                                >
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-xs font-bold text-diamond-soft">
                                            <img
                                                v-if="product.image_url"
                                                :src="product.image_url"
                                                :alt="product.name"
                                                class="h-full w-full object-cover"
                                            >
                                            <span v-else>Foto</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="line-clamp-2 text-sm font-bold leading-6 text-diamond-text">{{ product.name }}</p>
                                            <div class="mt-2 flex min-w-0 items-center gap-2">
                                                <span class="truncate text-xs text-diamond-muted">{{ product.code || 'Tanpa kode' }}</span>
                                                <span class="shrink-0 rounded-full bg-diamond-surface-soft px-2.5 py-1 text-[11px] font-semibold text-diamond-muted">
                                                    {{ product.variants.length }} varian
                                                </span>
                                            </div>
                                            <div class="mt-3 flex items-center justify-between gap-3">
                                                <p class="truncate text-base font-bold text-diamond-primary">{{ formatMoney(product.base_rental_price) }}</p>
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                                                    <Plus :size="18" />
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                            <div v-if="filteredProducts.length === 0" class="rounded-3xl border border-dashed border-diamond-border bg-white p-6 text-center text-sm font-semibold text-diamond-muted">
                                Produk tidak ditemukan.
                            </div>
                        </div>
                    </div>
                </section>

                <aside class="grid gap-4 xl:sticky xl:top-20">
                    <section class="grid gap-5 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                                    <ShoppingCart :size="21" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-diamond-text">Keranjang rental</h2>
                                    <p class="text-sm text-diamond-muted">{{ cartItemCount }} item</p>
                                </div>
                            </div>
                        </div>

                        <span v-if="form.errors.items" class="text-sm text-diamond-danger">{{ form.errors.items }}</span>

                        <div class="grid max-h-[48vh] gap-3 overflow-y-auto pr-1 xl:max-h-[42vh]">
                            <div
                                v-if="cartItemCount === 0"
                                class="rounded-3xl border border-dashed border-diamond-border bg-diamond-surface-soft p-6 text-center"
                            >
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-diamond-primary">
                                    <ShoppingCart :size="22" />
                                </div>
                                <p class="mt-3 text-sm font-bold text-diamond-text">Keranjang masih kosong</p>
                                <p class="mt-1 text-sm leading-6 text-diamond-muted">Pilih produk atau paket dari katalog untuk mulai membuat transaksi.</p>
                            </div>

                            <button
                                v-for="(item, index) in form.items"
                                :key="index"
                                v-show="item.product_id"
                                class="grid w-full cursor-pointer gap-3 overflow-hidden rounded-3xl border border-diamond-border bg-white p-4 text-left transition hover:border-diamond-primary/40 hover:bg-diamond-surface-soft"
                                type="button"
                                @click="openEditItem(item, index)"
                            >
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex min-w-0 flex-1 gap-3">
                                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-[11px] font-bold text-diamond-soft">
                                            <img
                                                v-if="itemImage(item)"
                                                :src="itemImage(item)"
                                                :alt="itemTitle(item, index)"
                                                class="h-full w-full object-cover"
                                            >
                                            <PackagePlus v-else-if="item.rental_package_id" :size="20" class="text-diamond-accent" />
                                            <span v-else>Foto</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-bold text-diamond-text">{{ itemTitle(item, index) }}</p>
                                            <p class="mt-1 truncate text-xs text-diamond-muted">{{ itemSubtitle(item) }}</p>
                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-semibold text-diamond-muted">
                                                <span class="rounded-full bg-diamond-surface-soft px-2.5 py-1">Qty {{ item.quantity }}</span>
                                                <span class="rounded-full bg-diamond-surface-soft px-2.5 py-1">{{ formatMoney(item.unit_price) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex min-w-0 items-center justify-between gap-3 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                                    <span class="text-sm font-semibold text-diamond-muted">Total item</span>
                                    <span class="truncate text-sm font-bold text-diamond-text">{{ formatMoney(lineTotal(item)) }}</span>
                                </div>
                            </button>
                        </div>
                    </section>

                    <section class="grid gap-4 rounded-[2rem] border border-white/80 bg-white p-4 sm:p-5">
                        <div class="grid gap-3 rounded-3xl bg-diamond-surface-soft p-4">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-semibold text-diamond-muted">Subtotal</span>
                                <span class="text-base font-bold text-diamond-text">{{ formatMoney(subtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-semibold text-diamond-muted">Total final</span>
                                <span class="text-xl font-bold text-diamond-primary">{{ formatMoney(totalAmount) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm font-semibold text-diamond-muted">Sisa bayar</span>
                                <span class="text-xl font-bold" :class="remainingAmount > 0 ? 'text-diamond-danger' : 'text-emerald-700'">
                                    {{ formatMoney(remainingAmount) }}
                                </span>
                            </div>
                        </div>

                        <CurrencyInput v-model="form.custom_total_amount" :error="form.errors.custom_total_amount" label="Total final manual" :placeholder="String(subtotal)" />
                        <CurrencyInput v-model="form.initial_payment_amount" :error="form.errors.initial_payment_amount" label="Pembayaran awal" />

                        <label class="grid gap-2">
                            <span class="text-sm font-semibold text-diamond-text">Metode pembayaran awal</span>
                            <select v-model="form.initial_payment_method" :class="fieldClasses()">
                                <option v-for="method in paymentMethodOptions" :key="method.value" :value="method.value">{{ method.label }}</option>
                            </select>
                            <span v-if="form.errors.initial_payment_method" class="text-sm text-diamond-danger">{{ form.errors.initial_payment_method }}</span>
                        </label>

                        <Input v-model="form.initial_payment_notes" label="Catatan pembayaran awal" placeholder="Opsional" />

                        <Button :disabled="form.processing" full type="submit">
                            <Save :size="18" />
                            {{ form.processing ? 'Menyimpan...' : 'Simpan rental' }}
                        </Button>
                    </section>
                </aside>
            </div>
        </form>

        <Teleport to="body">
            <div
                v-if="customerPickerOpen"
                class="fixed inset-0 z-[90] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closeCustomerPicker"
            >
                <section
                    class="flex max-h-[90vh] min-h-0 w-full max-w-4xl flex-col overflow-hidden rounded-[2rem] border border-white/80 bg-white"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Pilih customer"
                >
                    <div class="flex items-center justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div class="min-w-0">
                            <h2 class="text-xl font-bold text-diamond-text">Pilih customer</h2>
                        </div>
                        <button
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            aria-label="Tutup"
                            @click="closeCustomerPicker"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <div class="grid min-h-0 flex-1 gap-5 overflow-y-auto p-5 sm:p-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                        <section class="grid min-h-0 content-start gap-4">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-diamond-muted" :size="18" />
                                <input
                                    v-model="customerSearch"
                                    class="min-h-12 w-full rounded-xl border border-diamond-border bg-white py-3 pl-11 pr-4 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                                    placeholder="Cari nama atau WhatsApp"
                                    type="search"
                                >
                            </div>

                            <div class="grid max-h-[42vh] min-h-0 gap-3 overflow-y-auto pr-1 lg:max-h-[52vh]">
                                <button
                                    v-for="customer in filteredCustomers"
                                    :key="customer.id"
                                    class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded-3xl border border-diamond-border bg-white p-4 text-left transition hover:border-diamond-primary/40 hover:bg-diamond-surface-soft"
                                    type="button"
                                    @click="chooseExistingCustomer(customer)"
                                >
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-bold text-diamond-text">{{ customer.name }}</span>
                                        <span class="mt-1 block truncate text-sm text-diamond-muted">{{ customer.whatsapp_number }}</span>
                                    </span>
                                    <span class="rounded-xl bg-diamond-primary px-3 py-2 text-xs font-bold text-white">Tambah</span>
                                </button>

                                <div v-if="filteredCustomers.length === 0" class="rounded-3xl border border-dashed border-diamond-border bg-diamond-surface-soft p-6 text-center">
                                    <p class="text-sm font-semibold text-diamond-text">Customer tidak ditemukan</p>
                                    <p class="mt-1 text-sm text-diamond-muted">Gunakan form customer baru.</p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-3xl bg-diamond-surface-soft p-4">
                            <button
                                class="flex w-full cursor-pointer items-center justify-between gap-4 text-left"
                                type="button"
                                @click="newCustomerExpanded = !newCustomerExpanded"
                            >
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-diamond-text">Customer baru</span>
                                </span>
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-diamond-muted transition">
                                    <ChevronDown
                                        :size="18"
                                        class="transition"
                                        :class="newCustomerExpanded || hasNewCustomerErrors ? 'rotate-180' : ''"
                                    />
                                </span>
                            </button>

                            <div
                                class="mt-4 content-start gap-4"
                                :class="newCustomerExpanded || hasNewCustomerErrors ? 'grid' : 'hidden lg:grid'"
                            >
                                <Input
                                    v-model="form.new_customer.name"
                                    :error="form.errors['new_customer.name']"
                                    label="Nama customer"
                                    placeholder="Nama penyewa"
                                />

                                <Input
                                    v-model="form.new_customer.whatsapp_number"
                                    :error="form.errors['new_customer.whatsapp_number']"
                                    inputmode="tel"
                                    label="WhatsApp customer"
                                    placeholder="0812..."
                                />

                                <Input
                                    v-model="form.new_customer.notes"
                                    label="Catatan customer"
                                    placeholder="Catatan opsional"
                                />

                                <Button type="button" full @click="useNewCustomerFromModal">
                                    <UserPlus :size="18" />
                                    Gunakan customer baru
                                </Button>
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="itemModalOpen"
                class="fixed inset-0 z-[95] flex items-start justify-center overflow-y-auto bg-slate-950/45 px-4 py-6 backdrop-blur-sm"
                role="presentation"
                @click.self="closeItemModal"
            >
                <section
                    class="flex max-h-[92vh] min-h-0 w-full max-w-4xl flex-col overflow-hidden overflow-x-hidden rounded-[2rem] border border-white/80 bg-white"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Atur item rental"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-diamond-border p-5 sm:p-6">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase text-diamond-accent">
                                {{ itemModalMode === 'package' ? 'Paket' : editingItemIndex !== null ? 'Edit item' : 'Produk' }}
                            </p>
                            <h2 class="mt-1 truncate text-xl font-bold text-diamond-text">
                                {{ itemModalMode === 'package' ? modalPackage?.name : modalProduct?.name }}
                            </h2>
                        </div>
                        <button
                            class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-2xl text-diamond-muted transition hover:bg-diamond-surface-soft hover:text-diamond-text"
                            type="button"
                            aria-label="Tutup"
                            @click="closeItemModal"
                        >
                            <X :size="20" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden p-5 sm:p-6">
                        <section v-if="itemModalMode !== 'package'" class="grid min-w-0 gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                            <div class="grid min-w-0 content-start gap-3">
                                <div class="aspect-square overflow-hidden rounded-[1.5rem] bg-diamond-surface-soft">
                                    <img
                                        v-if="modalProduct?.image_url"
                                        :src="modalProduct.image_url"
                                        :alt="modalProduct.name"
                                        class="h-full w-full object-cover"
                                    >
                                    <div v-else class="grid h-full place-items-center text-sm font-bold text-diamond-soft">
                                        Foto produk
                                    </div>
                                </div>
                                <div class="rounded-3xl bg-diamond-surface-soft p-4">
                                    <p class="truncate text-sm font-bold text-diamond-text">{{ modalProduct?.name }}</p>
                                    <div class="mt-2 flex min-w-0 items-center gap-2">
                                        <span class="truncate text-xs text-diamond-muted">{{ modalProduct?.code || 'Tanpa kode' }}</span>
                                        <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-diamond-muted">
                                            {{ modalProduct?.variants?.length || 0 }} varian
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="grid min-w-0 content-start gap-4">
                                <label class="grid min-w-0 gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Varian produk</span>
                                    <select v-model="itemDraft.product_variant_id" :class="fieldClasses()" @change="onVariantChanged(itemDraft)">
                                        <option value="">Tanpa varian khusus</option>
                                        <option v-for="variant in variantsFor(itemDraft.product_id)" :key="variant.id" :value="variant.id">
                                            {{ variantLabel(variant) }}{{ variant.sku ? ` - ${variant.sku}` : '' }} - stok {{ variant.available_quantity ?? variant.stock_quantity }}
                                        </option>
                                    </select>
                                </label>

                                <div class="grid min-w-0 gap-4 md:grid-cols-2">
                                    <label class="grid min-w-0 gap-2">
                                        <span class="text-sm font-semibold text-diamond-text">Qty</span>
                                        <input v-model="itemDraft.quantity" :class="fieldClasses()" min="1" step="1" type="number">
                                    </label>

                                    <CurrencyInput v-model="itemDraft.unit_price" label="Harga" />
                                </div>

                                <Input v-model="itemDraft.notes" label="Catatan item" placeholder="Opsional" />

                                <div class="grid min-w-0 gap-1 rounded-3xl bg-diamond-surface-soft px-4 py-4 sm:flex sm:items-center sm:justify-between sm:gap-3">
                                    <span class="text-sm font-semibold text-diamond-muted">Total item</span>
                                    <span class="min-w-0 break-words text-lg font-bold text-diamond-primary sm:text-right">{{ formatMoney(lineTotal(itemDraft)) }}</span>
                                </div>
                            </div>
                        </section>

                        <section v-else class="grid gap-4">
                            <article
                                v-for="(draft, index) in packageDrafts"
                                :key="index"
                                class="overflow-hidden rounded-3xl border border-diamond-border bg-white"
                            >
                                <button
                                    class="grid w-full cursor-pointer gap-3 p-4 text-left transition hover:bg-diamond-surface-soft sm:flex sm:items-center sm:justify-between sm:gap-4"
                                    type="button"
                                    @click="expandedPackageDraftIndex = expandedPackageDraftIndex === index ? -1 : index"
                                >
                                    <div class="flex min-w-0 gap-3">
                                        <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-diamond-surface-soft text-[11px] font-bold text-diamond-soft">
                                            <img
                                                v-if="itemImage(draft)"
                                                :src="itemImage(draft)"
                                                :alt="itemTitle(draft, index)"
                                                class="h-full w-full object-cover"
                                            >
                                            <span v-else>Foto</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-bold text-diamond-text">{{ itemTitle(draft, index) }}</p>
                                            <p class="mt-1 truncate text-xs text-diamond-muted">{{ itemSubtitle(draft) }}</p>
                                        </div>
                                    </div>
                                    <span class="w-fit rounded-full bg-diamond-primary-soft px-3 py-1 text-xs font-bold text-diamond-primary sm:shrink-0">
                                        {{ formatMoney(lineTotal(draft)) }}
                                    </span>
                                </button>

                                <div v-if="expandedPackageDraftIndex === index" class="grid min-w-0 gap-4 border-t border-diamond-border p-4">
                                    <label class="grid min-w-0 gap-2">
                                        <span class="text-sm font-semibold text-diamond-text">Varian produk</span>
                                        <select v-model="draft.product_variant_id" :class="fieldClasses()" @change="onVariantChanged(draft)">
                                            <option value="">Tanpa varian khusus</option>
                                            <option v-for="variant in variantsFor(draft.product_id)" :key="variant.id" :value="variant.id">
                                                {{ variantLabel(variant) }}{{ variant.sku ? ` - ${variant.sku}` : '' }} - stok {{ variant.available_quantity ?? variant.stock_quantity }}
                                            </option>
                                        </select>
                                    </label>

                                    <div class="grid min-w-0 gap-4 md:grid-cols-2">
                                        <label class="grid min-w-0 gap-2">
                                            <span class="text-sm font-semibold text-diamond-text">Qty</span>
                                            <input v-model="draft.quantity" :class="fieldClasses()" min="1" step="1" type="number">
                                        </label>

                                        <CurrencyInput v-model="draft.unit_price" label="Harga" />
                                    </div>

                                    <Input v-model="draft.notes" label="Catatan item" placeholder="Opsional" />
                                </div>
                            </article>
                        </section>
                    </div>

                    <div class="grid gap-3 border-t border-diamond-border p-5 sm:flex sm:items-center sm:justify-between sm:p-6">
                        <button
                            v-if="editingItemIndex !== null"
                            class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-red-200 bg-white px-5 py-3 text-sm font-bold text-red-700 transition hover:bg-red-50 sm:w-auto"
                            type="button"
                            @click="deleteEditingItem"
                        >
                            <Trash2 :size="18" />
                            Hapus item
                        </button>
                        <span v-else class="hidden sm:block" />

                        <div class="grid gap-3 sm:flex sm:justify-end">
                            <Button type="button" variant="secondary" @click="closeItemModal">
                                Batal
                            </Button>
                            <Button type="button" @click="saveConfiguredItem">
                                <Check :size="18" />
                                {{ itemModalMode === 'package' ? 'Tambahkan paket' : editingItemIndex !== null ? 'Simpan perubahan' : 'Tambahkan item' }}
                            </Button>
                        </div>
                    </div>
                </section>
            </div>
        </Teleport>
    </section>
</template>
