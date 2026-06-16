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
    variant: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    _method: 'put',
    sku: props.variant.sku || '',
    name: props.variant.name,
    size: props.variant.size || '',
    color: props.variant.color || '',
    image: null,
    remove_image: false,
    stock_quantity: props.variant.stock_quantity,
    rental_price: props.variant.rental_price || '',
    is_active: props.variant.is_active,
})

function submit() {
    form.post(route('product-variants.update', props.variant.id), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Edit Varian" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master produk"
            title="Edit varian produk"
            :subtitle="`${product.name} · ${variant.name}`"
        >
            <template #actions>
                <Button :href="route('products.show', product.id)" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-5xl">
            <VariantForm :form="form" :existing-image-url="variant.image_url" submit-label="Simpan perubahan" @submit="submit" />
        </div>
    </section>
</template>
