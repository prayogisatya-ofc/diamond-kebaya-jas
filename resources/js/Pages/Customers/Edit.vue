<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import CustomerForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    name: props.customer.name,
    whatsapp_number: props.customer.whatsapp_number,
    notes: props.customer.notes || '',
})

function submit() {
    form.put(route('customers.update', props.customer.id))
}
</script>

<template>
    <Head title="Edit Customer" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master customer"
            :title="`Edit ${customer.name}`"
        >
            <template #actions>
                <Button :href="route('customers.show', customer.id)" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <CustomerForm :form="form" submit-label="Simpan perubahan" @submit="submit" />
    </section>
</template>
