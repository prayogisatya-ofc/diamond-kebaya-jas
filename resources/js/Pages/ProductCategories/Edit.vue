<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import CategoryForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    category: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    name: props.category.name,
    is_active: props.category.is_active,
})

function submit() {
    form.put(route('product-categories.update', props.category.id))
}
</script>

<template>
    <Head title="Edit Kategori" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master produk"
            title="Edit kategori"
            :subtitle="`${category.products_count} produk menggunakan kategori ini.`"
        >
            <template #actions>
                <Button :href="route('product-categories.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-2xl">
            <CategoryForm :form="form" submit-label="Simpan perubahan" @submit="submit" />
        </div>
    </section>
</template>
