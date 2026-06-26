<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import { Trash2 } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { useConfirm } from '@/Composables/useConfirm'
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
const { confirmAction } = useConfirm()

function submit() {
    form.post(route('product-variants.update', props.variant.id), {
        forceFormData: true,
    })
}

async function destroyVariant() {
    const confirmed = await confirmAction({
        title: 'Hapus varian?',
        message: `Varian ${props.variant.name} akan dihapus dari produk ini.`,
        confirmLabel: 'Ya, hapus varian',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('product-variants.destroy', props.variant.id), {
        preserveScroll: true,
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
                <Button variant="danger" type="button" @click="destroyVariant">
                    <Trash2 :size="18" />
                    Hapus varian
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-5xl">
            <VariantForm :form="form" :existing-image-url="variant.image_url" submit-label="Simpan perubahan" @submit="submit" />
        </div>
    </section>
</template>
