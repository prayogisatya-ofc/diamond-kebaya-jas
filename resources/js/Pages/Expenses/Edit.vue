<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import ExpenseForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    expense: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    _method: 'put',
    description: props.expense.description,
    amount: props.expense.amount,
    category: props.expense.category,
    expense_date: props.expense.expense_date,
    notes: props.expense.notes || '',
})

function submit() {
    form.post(route('expenses.update', props.expense.id))
}
</script>

<template>
    <Head title="Edit Pengeluaran" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Keuangan"
            :title="`Edit ${expense.description}`"
        >
            <template #actions>
                <Button :href="route('expenses.index')" variant="secondary">
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <div class="max-w-4xl">
            <ExpenseForm :form="form" submit-label="Simpan perubahan" @submit="submit" />
        </div>
    </section>
</template>
