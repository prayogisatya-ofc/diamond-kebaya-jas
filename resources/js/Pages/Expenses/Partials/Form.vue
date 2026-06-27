<script setup>
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import Input from '@/Components/Input.vue'

defineProps({
    form: {
        type: Object,
        required: true,
    },
    submitLabel: {
        type: String,
        default: 'Simpan pengeluaran',
    },
})

defineEmits(['submit'])

const categoryOptions = [
    { value: 'operasional', label: 'Operasional' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'laundry', label: 'Laundry' },
    { value: 'supplies', label: 'Supplies' },
    { value: 'other', label: 'Lainnya' },
]

function fieldClasses() {
    return 'min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10'
}
</script>

<template>
    <form class="grid gap-6" @submit.prevent="$emit('submit')">
        <Card>
            <div class="grid gap-5 lg:grid-cols-2">
                <Input
                    v-model="form.description"
                    :error="form.errors.description"
                    label="Deskripsi pengeluaran"
                    placeholder="contoh: Bayar listrik bulan Juni"
                />

                <CurrencyInput
                    v-model="form.amount"
                    :error="form.errors.amount"
                    label="Nominal"
                />

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-diamond-text">Kategori</span>
                    <select v-model="form.category" :class="fieldClasses()">
                        <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
                    <span v-if="form.errors.category" class="text-sm text-diamond-danger">{{ form.errors.category }}</span>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-diamond-text">Tanggal pengeluaran</span>
                    <input
                        v-model="form.expense_date"
                        :class="fieldClasses()"
                        type="date"
                    >
                    <span v-if="form.errors.expense_date" class="text-sm text-diamond-danger">{{ form.errors.expense_date }}</span>
                </label>

                <label class="grid gap-2 lg:col-span-2">
                    <span class="text-sm font-semibold text-diamond-text">Catatan</span>
                    <textarea
                        v-model="form.notes"
                        class="min-h-24 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm leading-6 text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        placeholder="Catatan opsional"
                    />
                    <span v-if="form.errors.notes" class="text-sm text-diamond-danger">{{ form.errors.notes }}</span>
                </label>
            </div>
        </Card>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
