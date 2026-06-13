<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import CategoryForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const form = useForm({
    name: '',
    is_active: true,
})

function submit() {
    form.post(route('product-categories.store'))
}
</script>

<template>
    <Head title="Tambah Kategori" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master produk"
            title="Tambah kategori"
        >
            <template #actions>
                <Button :href="route('product-categories.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-2xl">
            <CategoryForm :form="form" submit-label="Simpan kategori" @submit="submit" />
        </div>
    </section>
</template>
