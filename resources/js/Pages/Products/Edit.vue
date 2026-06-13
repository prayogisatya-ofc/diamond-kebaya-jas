<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ProductForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    _method: 'put',
    product_category_id: props.product.product_category_id,
    name: props.product.name,
    code: props.product.code || '',
    description: props.product.description || '',
    image: null,
    remove_image: false,
    base_rental_price: props.product.base_rental_price,
    is_active: props.product.is_active,
    new_product_category_name: '',
})

function submit() {
    form.post(route('products.update', props.product.id), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Edit Produk" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master produk"
            :title="`Edit ${product.name}`"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('products.show', product.id)" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-5xl">
            <ProductForm :form="form" :categories="categories" :existing-image-url="product.image_url" submit-label="Simpan perubahan" @submit="submit" />
        </div>
    </section>
</template>
