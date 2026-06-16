<script setup>
import { computed, ref } from 'vue'
import { FolderPlus, ImagePlus, ListChecks, X } from '@lucide/vue'
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
    categories: {
        type: Array,
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

const categoryMode = ref(props.form.new_product_category_name ? 'new' : 'existing')
const imagePreviewUrl = ref(null)
const imageInput = ref(null)
const { confirmAction } = useConfirm()

const activeCategories = computed(() => props.categories.filter((category) => category.is_active))
const inactiveCategories = computed(() => props.categories.filter((category) => !category.is_active))
const displayImageUrl = computed(() => imagePreviewUrl.value || (!props.form.remove_image ? props.existingImageUrl : null))

function useExistingCategory() {
    categoryMode.value = 'existing'
    props.form.new_product_category_name = ''
}

function useNewCategory() {
    categoryMode.value = 'new'
    props.form.product_category_id = ''
}

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
        title: 'Hapus foto produk?',
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
        <section class="grid gap-4 rounded-3xl bg-diamond-surface-soft p-4 sm:p-5">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-bold text-diamond-text">Kategori produk</p>
                    <p class="mt-1 text-sm leading-6 text-diamond-muted">Pilih kategori yang ada atau buat kategori baru langsung dari form ini.</p>
                </div>
                <div class="grid grid-cols-2 gap-2 rounded-2xl bg-white p-1">
                    <button
                        class="flex min-h-11 items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold transition"
                        :class="categoryMode === 'existing' ? 'bg-diamond-primary text-white' : 'text-diamond-muted hover:bg-diamond-surface-soft'"
                        type="button"
                        @click="useExistingCategory"
                    >
                        <ListChecks :size="17" />
                        Pilih
                    </button>
                    <button
                        class="flex min-h-11 items-center justify-center gap-2 rounded-xl px-3 text-sm font-semibold transition"
                        :class="categoryMode === 'new' ? 'bg-diamond-primary text-white' : 'text-diamond-muted hover:bg-diamond-surface-soft'"
                        type="button"
                        @click="useNewCategory"
                    >
                        <FolderPlus :size="17" />
                        Baru
                    </button>
                </div>
            </div>

            <label v-if="categoryMode === 'existing'" class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Kategori</span>
                <select
                    v-model="form.product_category_id"
                    class="min-h-12 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                >
                    <option value="">Pilih kategori</option>
                    <option v-for="category in activeCategories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                    <option v-for="category in inactiveCategories" :key="category.id" :value="category.id">
                        {{ category.name }} (nonaktif)
                    </option>
                </select>
                <span v-if="form.errors.product_category_id" class="text-sm text-diamond-danger">{{ form.errors.product_category_id }}</span>
            </label>

            <Input
                v-else
                v-model="form.new_product_category_name"
                :error="form.errors.new_product_category_name"
                label="Nama kategori baru"
            />
        </section>

        <section class="grid gap-5">
            <section class="grid gap-4 rounded-3xl bg-diamond-surface-soft p-4 sm:grid-cols-[180px_minmax(0,1fr)] sm:p-5">
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-3xl border border-white bg-white">
                    <img
                        v-if="displayImageUrl"
                        :src="displayImageUrl"
                        alt="Foto produk"
                        class="h-full w-full object-cover"
                    >
                    <div v-else class="grid place-items-center gap-2 text-center text-diamond-soft">
                        <ImagePlus :size="34" />
                        <span class="text-sm font-semibold">Belum ada foto</span>
                    </div>
                </div>

                <div class="grid content-center gap-3">
                    <div>
                        <p class="text-sm font-bold text-diamond-text">Foto produk</p>
                        <p class="mt-1 text-sm leading-6 text-diamond-muted">Gunakan foto JPG, PNG, atau WebP. File kamera sampai 10 MB akan dikompres saat disimpan.</p>
                    </div>

                    <label class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-diamond-primary-dark sm:w-fit">
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
                <Input v-model="form.name" :error="form.errors.name" autofocus label="Nama produk" />
                <Input v-model="form.code" :error="form.errors.code" label="Kode produk" />
            </div>

            <label class="grid gap-2">
                <span class="text-sm font-semibold text-diamond-text">Deskripsi</span>
                <textarea
                    v-model="form.description"
                    class="min-h-32 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                />
                <span v-if="form.errors.description" class="text-sm text-diamond-danger">{{ form.errors.description }}</span>
            </label>

            <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_220px]">
                <CurrencyInput
                    v-model="form.base_rental_price"
                    :error="form.errors.base_rental_price"
                    label="Harga sewa default"
                />

                <Switch
                    v-model="form.is_active"
                    label="Status Produk"
                />
            </div>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Menyimpan...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>
