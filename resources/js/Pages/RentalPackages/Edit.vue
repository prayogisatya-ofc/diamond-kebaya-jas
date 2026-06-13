<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PackageForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    rentalPackage: {
        type: Object,
        required: true,
    },
    items: {
        type: Array,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    name: props.rentalPackage.name,
    description: props.rentalPackage.description || '',
    package_price: props.rentalPackage.package_price,
    is_active: props.rentalPackage.is_active,
    items: props.items.map((item) => ({
        id: item.id,
        product_id: item.product_id,
        product_variant_id: item.product_variant_id || '',
        quantity: item.quantity,
        default_item_price: item.default_item_price || '',
        is_optional: item.is_optional,
        notes: item.notes || '',
        expanded: false,
    })),
})

function submit() {
    form.put(route('rental-packages.update', props.rentalPackage.id))
}
</script>

<template>
    <Head title="Edit Paket" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master paket"
            :title="`Edit ${rentalPackage.name}`"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('rental-packages.show', rentalPackage.id)" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <PackageForm :form="form" :products="products" submit-label="Simpan perubahan" @submit="submit" />
    </section>
</template>
