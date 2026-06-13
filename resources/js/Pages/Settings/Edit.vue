<script setup>
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ImagePlus, Palette, Save, Store, X } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    store_name: props.settings.store_name,
    store_address: props.settings.store_address,
    store_whatsapp_number: props.settings.store_whatsapp_number,
    invoice_footer_note: props.settings.invoice_footer_note,
    primary_color: props.settings.primary_color || '#615cf9',
    logo: null,
})

const logoInput = ref(null)
const logoPreviewUrl = ref(null)
const selectedLogoName = ref('')

const displayLogoUrl = computed(() => logoPreviewUrl.value || props.settings.store_logo_url)

function updateLogo(event) {
    const file = event.target.files?.[0] ?? null

    form.logo = file
    selectedLogoName.value = file?.name ?? ''

    if (logoPreviewUrl.value && typeof URL !== 'undefined') {
        URL.revokeObjectURL(logoPreviewUrl.value)
    }

    logoPreviewUrl.value = file && typeof URL !== 'undefined' ? URL.createObjectURL(file) : null
}

function clearSelectedLogo() {
    form.logo = null
    selectedLogoName.value = ''

    if (logoInput.value) {
        logoInput.value.value = ''
    }

    if (logoPreviewUrl.value && typeof URL !== 'undefined') {
        URL.revokeObjectURL(logoPreviewUrl.value)
    }

    logoPreviewUrl.value = null
}

function submit() {
    form.post(route('settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearSelectedLogo()
        },
    })
}
</script>

<template>
    <Head title="Setting" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Owner"
            title="Setting"
        />

        <form class="grid max-w-5xl gap-6 rounded-[2rem] border border-white/80 bg-white p-6 sm:p-7" @submit.prevent="submit">
            <section class="grid gap-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <Store :size="22" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-diamond-text">Profil toko</h2>
                        <p class="mt-1 text-sm leading-6 text-diamond-muted">
                            Pastikan nama, alamat, dan WhatsApp toko sesuai dengan informasi yang ingin tampil di invoice.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <Input
                        v-model="form.store_name"
                        :error="form.errors.store_name"
                        autofocus
                        label="Nama toko"
                        placeholder="Diamond Kebaya & Jas"
                    />

                    <Input
                        v-model="form.store_whatsapp_number"
                        :error="form.errors.store_whatsapp_number"
                        label="Nomor WhatsApp toko"
                        placeholder="0812..."
                    />
                </div>

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-diamond-text">Alamat toko</span>
                    <textarea
                        v-model="form.store_address"
                        class="min-h-32 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm leading-6 text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        placeholder="Alamat lengkap toko"
                    />
                    <span v-if="form.errors.store_address" class="text-sm text-diamond-danger">{{ form.errors.store_address }}</span>
                </label>

                <label class="grid gap-2">
                    <span class="text-sm font-semibold text-diamond-text">Catatan footer nota/invoice</span>
                    <textarea
                        v-model="form.invoice_footer_note"
                        class="min-h-32 rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm leading-6 text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                        placeholder="Contoh: Terima kasih sudah menyewa di Diamond Kebaya & Jas."
                    />
                    <span v-if="form.errors.invoice_footer_note" class="text-sm text-diamond-danger">{{ form.errors.invoice_footer_note }}</span>
                </label>

                <div class="grid gap-4 rounded-3xl bg-diamond-surface-soft p-4 sm:grid-cols-[auto_minmax(0,1fr)] sm:p-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-diamond-primary">
                        <Palette :size="22" />
                    </div>
                    <div class="grid gap-4">
                        <div>
                            <p class="text-sm font-bold text-diamond-text">Warna utama aplikasi</p>
                            <p class="mt-1 text-sm leading-6 text-diamond-muted">
                                Warna ini dipakai global untuk sidebar, tombol utama, badge, link, dan aksen form.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-[5rem_minmax(0,14rem)_minmax(0,1fr)] sm:items-start">
                            <label class="grid gap-2">
                                <span class="text-sm font-semibold text-diamond-text">Pilih</span>
                                <input
                                    v-model="form.primary_color"
                                    class="h-12 w-20 cursor-pointer rounded-xl border border-diamond-border bg-white p-1"
                                    type="color"
                                >
                            </label>

                            <Input
                                v-model="form.primary_color"
                                :error="form.errors.primary_color"
                                label="Kode warna"
                                maxlength="7"
                                placeholder="#615cf9"
                            />

                            <div class="grid gap-2">
                                <span class="text-sm font-semibold text-diamond-text">Preview</span>
                                <div class="flex min-h-12 items-center gap-3 rounded-xl border border-white bg-white px-4 py-3">
                                    <span class="h-6 w-6 rounded-lg" :style="{ backgroundColor: form.primary_color }" />
                                    <span class="text-sm font-semibold text-diamond-text">{{ form.primary_color }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 rounded-3xl bg-diamond-surface-soft p-4 sm:grid-cols-[180px_minmax(0,1fr)] sm:p-5">
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-3xl border border-white bg-white">
                    <img
                        v-if="displayLogoUrl"
                        :src="displayLogoUrl"
                        :alt="settings.store_name"
                        class="h-full w-full object-contain p-3"
                    >
                    <div v-else class="grid place-items-center gap-2 text-center text-diamond-soft">
                        <ImagePlus :size="34" />
                        <span class="text-sm font-semibold">Belum ada logo</span>
                    </div>
                </div>

                <div class="grid content-center gap-3">
                    <div>
                        <p class="text-sm font-bold text-diamond-text">Logo toko</p>
                        <p class="mt-1 text-sm leading-6 text-diamond-muted">
                            Logo akan muncul di nota print jika sudah diupload. Gunakan JPG, PNG, atau WebP maksimal 2 MB.
                        </p>
                    </div>

                    <div v-if="selectedLogoName" class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-diamond-text">
                        {{ selectedLogoName }}
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <label class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-semibold text-white transition hover:bg-diamond-primary-dark sm:w-fit">
                            <ImagePlus :size="18" />
                            Pilih logo
                            <input ref="logoInput" class="sr-only" accept="image/jpeg,image/png,image/webp" type="file" @change="updateLogo">
                        </label>

                        <button
                            v-if="form.logo"
                            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50 sm:w-fit"
                            type="button"
                            @click="clearSelectedLogo"
                        >
                            <X :size="17" />
                            Batal pilih
                        </button>
                    </div>

                    <progress v-if="form.progress" class="h-2 w-full overflow-hidden rounded-full" max="100" :value="form.progress.percentage" />
                    <span v-if="form.errors.logo" class="text-sm text-diamond-danger">{{ form.errors.logo }}</span>
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <Button type="submit" :disabled="form.processing">
                    <Save :size="18" />
                    {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                </Button>
            </div>
        </form>
    </section>
</template>
