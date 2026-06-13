<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import CustomerForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const form = useForm({
    name: '',
    whatsapp_number: '',
    notes: '',
})

function submit() {
    form.post(route('customers.store'))
}
</script>

<template>
    <Head title="Tambah Customer" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Master customer"
            title="Tambah customer"
            subtitle=""
        >
            <template #actions>
                <Button :href="route('customers.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <CustomerForm :form="form" submit-label="Simpan customer" @submit="submit" />
    </section>
</template>
