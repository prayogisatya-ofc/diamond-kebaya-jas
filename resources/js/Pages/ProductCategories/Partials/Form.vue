<script setup>
import Button from '@/Components/Button.vue'
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
    <form class="grid gap-5 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7" @submit.prevent="$emit('submit')">
        <Input
            v-model="form.name"
            :error="form.errors.name"
            autofocus
            label="Nama kategori"
            placeholder="Contoh: Kebaya, Jas, Aksesoris"
        />

        <Switch
            v-model="form.is_active"
            label="Kategori aktif"
            description="Kategori aktif bisa dipilih saat membuat produk."
        />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
