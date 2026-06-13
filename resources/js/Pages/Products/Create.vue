<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ProductForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

defineProps({
    categories: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    product_category_id: '',
    name: '',
    code: '',
    description: '',
    image: null,
    base_rental_price: 0,
    is_active: true,
    new_product_category_name: '',
})

function submit() {
    form.post(route('products.store'), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Tambah Produk" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master produk"
            title="Tambah produk"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('products.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-5xl">
            <ProductForm :form="form" :categories="categories" submit-label="Simpan produk" @submit="submit" />
        </div>
    </section>
</template>
