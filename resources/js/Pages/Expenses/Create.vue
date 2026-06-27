<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ExpenseForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const form = useForm({
    description: '',
    amount: '',
    category: 'operasional',
    expense_date: new Date().toISOString().split('T')[0],
    notes: '',
})

function submit() {
    form.post(route('expenses.store'))
}
</script>

<template>
    <Head title="Catat Pengeluaran" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Keuangan"
            title="Catat pengeluaran"
        >
            <template #actions>
                <Button :href="route('expenses.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-4xl">
            <ExpenseForm :form="form" submit-label="Simpan pengeluaran" @submit="submit" />
        </div>
    </section>
</template>
