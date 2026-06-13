<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PackageForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

defineProps({
    products: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    name: '',
    description: '',
    package_price: 0,
    is_active: true,
    items: [],
})

function submit() {
    form.post(route('rental-packages.store'))
}
</script>

<template>
    <Head title="Tambah Paket" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master paket"
            title="Tambah paket"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('rental-packages.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <PackageForm :form="form" :products="products" submit-label="Simpan paket" @submit="submit" />
    </section>
</template>
