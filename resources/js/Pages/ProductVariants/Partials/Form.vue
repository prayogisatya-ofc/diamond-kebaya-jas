<script setup>
import { computed, ref } from 'vue'
import { ImagePlus, X } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import Input from '@/Components/Input.vue'
import Switch from '@/Components/Switch.vue'
import { useConfirm } from '@/Composables/useConfirm'

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
    existingImageUrl: {
        type: String,
        default: null,
    },
})

defineEmits(['submit'])

const imagePreviewUrl = ref(null)
const imageInput = ref(null)
const { confirmAction } = useConfirm()

const displayImageUrl = computed(() => imagePreviewUrl.value || (!props.form.remove_image ? props.existingImageUrl : null))

function updateImage(event) {
    const file = event.target.files?.[0] ?? null

    props.form.image = file
    props.form.remove_image = false

    if (imagePreviewUrl.value && typeof URL !== 'undefined') {
        URL.revokeObjectURL(imagePreviewUrl.value)
    }

    imagePreviewUrl.value = file && typeof URL !== 'undefined' ? URL.createObjectURL(file) : null
}

async function clearImage() {
    const confirmed = await confirmAction({
        title: 'Hapus foto varian?',
        message: 'Foto yang sedang dipilih akan dihapus dari form. Jika ini foto lama, perubahan berlaku setelah disimpan.',
        confirmLabel: 'Ya, hapus foto',
    })

    if (!confirmed) {
        return
    }

    props.form.image = null
    props.form.remove_image = Boolean(props.existingImageUrl)

    if (imageInput.value) {
        imageInput.value.value = ''
    }

    if (imagePreviewUrl.value && typeof URL !== 'undefined') {
        URL.revokeObjectURL(imagePreviewUrl.value)
    }

    imagePreviewUrl.value = null
}
</script>

<template>
    <form class="grid gap-6 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7" @submit.prevent="$emit('submit')">
        <section class="grid gap-5">
            <section class="grid gap-4 rounded-3xl bg-diamond-surface-soft p-4 sm:grid-cols-[160px_minmax(0,1fr)] sm:p-5">
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-3xl border border-white bg-white">
                    <img
                        v-if="displayImageUrl"
                        :src="displayImageUrl"
                        alt="Foto varian"
                        class="h-full w-full object-cover"
                    >
                    <div v-else class="grid place-items-center gap-2 text-center text-diamond-soft">
                        <ImagePlus :size="32" />
                        <span class="text-sm font-semibold">Belum ada foto</span>
                    </div>
                </div>

                <div class="grid content-center gap-3">
                    <label class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-diamond-primary-dark sm:w-fit">
                        <ImagePlus :size="18" />
                        Pilih foto
                        <input ref="imageInput" class="sr-only" accept="image/jpeg,image/png,image/webp" type="file" @change="updateImage">
                    </label>

                    <button
                        v-if="displayImageUrl || form.image"
                        class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50 sm:w-fit"
                        type="button"
                        @click="clearImage"
                    >
                        <X :size="17" />
                        Hapus foto
                    </button>

                    <span v-if="form.errors.image" class="text-sm text-diamond-danger">{{ form.errors.image }}</span>
                </div>
            </section>

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
