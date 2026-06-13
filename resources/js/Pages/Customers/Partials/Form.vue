<script setup>
import { MessageCircle, UserRound } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import Input from '@/Components/Input.vue'

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
    <form class="grid max-w-5xl gap-6" @submit.prevent="$emit('submit')">
        <section class="grid gap-5 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                    <UserRound :size="24" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-diamond-text">Profil customer</h2>
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <Input v-model="form.name" :error="form.errors.name" autofocus label="Nama customer" placeholder="Nama lengkap customer" />
                <Input
                    v-model="form.whatsapp_number"
                    :error="form.errors.whatsapp_number"
                    inputmode="tel"
                    label="Nomor WhatsApp"
                    placeholder="Contoh: 081234567890"
                    type="text"
                />
            </div>

            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Catatan</span>
                <textarea
                    v-model="form.notes"
                    class="min-h-32 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                    placeholder="Contoh: langganan kebaya warna soft, ukuran M"
                />
                <span v-if="form.errors.notes" class="text-sm text-diamond-danger">{{ form.errors.notes }}</span>
            </label>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : submitLabel }}
                </Button>
            </div>
        </section>
    </form>
</template>
