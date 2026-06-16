<script setup>
import { computed } from 'vue'
import PublicSeoHead from '../../Components/Public/PublicSeoHead.vue'
import PublicStorefrontLayout from '../../Layouts/PublicStorefrontLayout.vue'
import {
    CheckCircle2,
    Clock,
    MessageCircle,
    Search,
    Shirt,
    Sparkles,
    Check
} from '@lucide/vue'

defineOptions({
    layout: [PublicStorefrontLayout, { activeNav: 'how', floatingWhatsapp: true }],
})

const props = defineProps({
    catalogStore: {
        type: Object,
        required: true,
    },
    steps: {
        type: Array,
        required: true,
    },
    tips: {
        type: Array,
        required: true,
    },
})

const cssVars = computed(() => ({
    '--catalog-primary': '#6533D6', // Konsisten dengan halaman katalog
}))

// Mapping ikon langsung ke komponen Lucide agar konsisten dengan halaman lain
const stepIcons = [Search, MessageCircle, Shirt, Sparkles, Clock, Check]

const highlightRules = [
    {
        title: 'Datang fitting dulu',
        text: 'Pesanan belum final sebelum dicoba langsung.',
        icon: Shirt,
    },
    {
        title: 'Fix setelah cocok',
        text: 'Item dan harga dikunci setelah pilihan pas.',
        icon: Sparkles,
    },
    {
        title: 'Jadwal dicatat belakangan',
        text: 'Jadwal ambil dibuat setelah transaksi benar-benar fix.',
        icon: Clock,
    },
]

const seoDescription = computed(() => `Panduan cara sewa di ${props.catalogStore.name}: pilih referensi, datang fitting ke toko, finalisasi pesanan, lalu atur jadwal pengambilan.`)

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: `Cara sewa di ${props.catalogStore.name}`,
    description: seoDescription.value,
    step: props.steps.map((step, index) => ({
        '@type': 'HowToStep',
        position: index + 1,
        name: step.title,
        text: step.description,
    })),
}))
</script>

<template>
    <PublicSeoHead
        title="Cara Sewa"
        :description="seoDescription"
        :structured-data="structuredData"
    />

    <main :style="cssVars" class="min-h-screen w-full overflow-x-hidden bg-white text-[#202638]">
        <div class="mx-auto max-w-[1440px] px-4 py-6 md:px-8 xl:px-12">
            
            <section class="relative overflow-hidden rounded-[32px] bg-[#F5F3FF]">
                <div class="absolute -right-20 -top-20 size-[400px] rounded-full bg-violet-100/50 blur-3xl"></div>
                <div class="absolute -bottom-32 left-10 size-[300px] rounded-full bg-indigo-100/50 blur-3xl"></div>

                <div class="relative grid gap-10 px-6 py-12 md:px-12 lg:grid-cols-[1.1fr_0.9fr] lg:px-16 lg:py-16 xl:gap-16">
                    <div class="flex flex-col justify-center space-y-6">
                        <div>
                            <span class="inline-block rounded-full bg-violet-100 px-4 py-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">
                                Panduan Rental
                            </span>
                        </div>
                        <h1 class="max-w-2xl text-[32px] font-bold leading-[1.15] text-slate-800 md:text-[48px]">
                            Sewa dimulai dari datang, fitting, lalu <span class="text-[var(--catalog-primary)]">fix setelah pas.</span>
                        </h1>
                        <p class="max-w-lg text-[15px] leading-relaxed text-slate-600 md:text-[17px]">
                            Katalog ini berfungsi sebagai referensi awal. Finalisasi pesanan tetap dilakukan di toko setelah kamu mencoba langsung dan merasa cocok dengan pakaiannya.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                            <div
                                v-for="(rule, index) in highlightRules"
                                :key="index"
                                class="group flex items-start gap-4 rounded-[24px] border border-white/60 bg-white/60 p-5 shadow-[0_8px_30px_rgb(0,0,0,0.02)] backdrop-blur-md transition-all hover:bg-white"
                            >
                                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-[var(--catalog-primary)] text-white shadow-sm transition-transform group-hover:scale-110">
                                    <component :is="rule.icon" :size="20" stroke-width="2" />
                                </span>
                                <div>
                                    <p class="text-[15px] font-bold text-slate-800">{{ rule.title }}</p>
                                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">{{ rule.text }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 rounded-[24px] border border-white/60 bg-white/60 p-6 backdrop-blur-md">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Ringkasnya</p>
                            <div class="mt-3 flex flex-wrap gap-2.5">
                                <span class="rounded-full bg-white px-4 py-2 text-[12px] font-bold text-slate-600 shadow-sm transition hover:text-[var(--catalog-primary)]">Lihat referensi</span>
                                <span class="rounded-full bg-white px-4 py-2 text-[12px] font-bold text-slate-600 shadow-sm transition hover:text-[var(--catalog-primary)]">Datang fitting</span>
                                <span class="rounded-full bg-white px-4 py-2 text-[12px] font-bold text-slate-600 shadow-sm transition hover:text-[var(--catalog-primary)]">Fix transaksi</span>
                                <span class="rounded-full bg-white px-4 py-2 text-[12px] font-bold text-slate-600 shadow-sm transition hover:text-[var(--catalog-primary)]">Atur jadwal ambil</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-12 md:mt-16">
                <div class="mb-8 text-center md:mb-12">
                    <h2 class="text-2xl font-bold text-slate-800 md:text-3xl">Alur Lengkap Sewa</h2>
                    <p class="mt-3 text-sm text-slate-500">Ikuti langkah mudah berikut untuk mendapatkan gaun atau jas impianmu.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="group relative overflow-hidden rounded-[28px] border border-slate-100 bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-violet-100 hover:shadow-xl hover:shadow-violet-900/5 md:p-8"
                    >
                        <div class="absolute -right-4 -top-6 select-none text-[120px] font-black text-slate-50 transition-transform duration-500 group-hover:scale-110">
                            0{{ index + 1 }}
                        </div>

                        <div class="relative">
                            <div class="flex items-center gap-4">
                                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-violet-50 text-[var(--catalog-primary)] transition-colors group-hover:bg-[var(--catalog-primary)] group-hover:text-white">
                                    <component :is="stepIcons[index] || Check" :size="20" stroke-width="2.5" />
                                </span>
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)] opacity-80">Langkah 0{{ index + 1 }}</p>
                                    <h3 class="mt-1 text-[17px] font-bold text-slate-800">{{ step.title }}</h3>
                                </div>
                            </div>
                            <p class="mt-5 text-[14.5px] font-medium leading-relaxed text-slate-500">
                                {{ step.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="mt-12 rounded-[32px] border border-slate-100 bg-white p-6 shadow-sm md:mt-16 md:p-10 lg:p-12">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">Tips Cepat</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-800 md:text-[32px]">Biar prosesnya enak & cepat</h2>
                    </div>
                    <p class="max-w-md text-sm font-medium leading-relaxed text-slate-500 md:text-right">
                        Tiga hal ini paling membantu proses konsultasi, fitting, dan finalisasi transaksi berjalan lebih mulus.
                    </p>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-3">
                    <div
                        v-for="tip in tips"
                        :key="tip"
                        class="flex items-start gap-4 rounded-[24px] bg-slate-50 p-6 transition hover:bg-slate-100"
                    >
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                            <CheckCircle2 :size="18" stroke-width="2.5" />
                        </span>
                        <p class="text-[14px] font-medium leading-relaxed text-slate-700">{{ tip }}</p>
                    </div>
                </div>
            </section>
            
        </div>
    </main>
</template>