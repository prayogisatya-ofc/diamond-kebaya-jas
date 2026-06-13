<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import VariantForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    sku: '',
    name: '',
    size: '',
    color: '',
    stock_quantity: 0,
    rental_price: '',
    is_active: true,
})

function submit() {
    form.post(route('products.variants.store', props.product.id))
}
</script>

<template>
    <Head title="Tambah Varian" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master produk"
            title="Tambah varian produk"
            :subtitle="`${product.name} · ${product.code || 'Tanpa kode'}`"
        >
            <template #actions>
                <Button :href="route('products.show', product.id)" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-5xl">
            <VariantForm :form="form" submit-label="Simpan varian" @submit="submit" />
        </div>
    </section>
</template>
