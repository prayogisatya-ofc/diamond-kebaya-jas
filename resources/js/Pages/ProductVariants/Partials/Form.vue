<script setup>
import Button from '@/Components/Button.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import Input from '@/Components/Input.vue'
import Switch from '@/Components/Switch.vue'

defineProps({
    form: {
        type: Object,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
})

defineEmits(['submit'])
</script>

<template>
    <form class="grid gap-6 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7" @submit.prevent="$emit('submit')">
        <section class="grid gap-5">
            <div class="grid gap-5 md:grid-cols-2">
                <Input v-model="form.name" :error="form.errors.name" autofocus label="Nama varian" />
                <Input v-model="form.sku" :error="form.errors.sku" label="SKU" />
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <Input v-model="form.size" :error="form.errors.size" label="Size" placeholder="Contoh: M, L, XL" />
                <Input v-model="form.color" :error="form.errors.color" label="Warna" placeholder="Contoh: Merah marun" />
            </div>
        </section>

        <section class="grid gap-5 rounded-3xl bg-diamond-surface-soft p-4 sm:p-5">
            <div class="grid gap-5 md:grid-cols-2">
                <Input
                    v-model="form.stock_quantity"
                    :error="form.errors.stock_quantity"
                    label="Stok"
                    min="0"
                    step="1"
                    type="number"
                />

                <CurrencyInput
                    v-model="form.rental_price"
                    :error="form.errors.rental_price"
                    label="Harga sewa varian"
                    placeholder="Kosongkan jika ikut harga produk"
                />
            </div>

            <Switch
                v-model="form.is_active"
                label="Status Varian"
                description="Varian aktif bisa dipilih dalam paket dan transaksi."
            />
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
