<script setup>
import { computed, ref } from 'vue'
import PublicSeoHead from '../../Components/Public/PublicSeoHead.vue'
import PublicStorefrontLayout from '../../Layouts/PublicStorefrontLayout.vue'
import {
    CheckCircle2,
    ChevronDown,
    Clock,
    MessageCircle,
    ShieldCheck,
    Sparkles,
    HelpCircle
} from '@lucide/vue'
import PublicIcon from '../../Components/Public/PublicIcon.vue'
import { IconBrandWhatsapp } from '@tabler/icons-vue'

defineOptions({
    layout: [PublicStorefrontLayout, { activeNav: 'faq', floatingWhatsapp: true }],
})

const props = defineProps({
    catalogStore: {
        type: Object,
        required: true,
    },
    faqGroups: {
        type: Array,
        required: true,
    },
})

const cssVars = computed(() => ({
    '--catalog-primary': '#6533D6',
}))

const openedItems = ref(['0-0'])

// Menggunakan komponen ikon langsung agar konsisten
const groupIcons = [Sparkles, Clock, ShieldCheck, HelpCircle]
const keyRules = [
    'Fitting dulu sebelum deal',
    'Jadwal ambil setelah fix',
    'Jaminan saat pengambilan',
]

const whatsappUrl = computed(() => {
    const phone = normalizePhone(props.catalogStore.whatsapp_number)
    const message = encodeURIComponent(`Halo ${props.catalogStore.name}, saya sudah baca FAQ tapi ada yang ingin ditanyakan lagi tentang rental.`)
    return phone ? `https://wa.me/${phone}?text=${message}` : '#'
})

function normalizePhone(value) {
    const digits = String(value || '').replace(/\D/g, '')
    if (!digits) return ''
    return digits.startsWith('0') ? `62${digits.slice(1)}` : digits
}

const seoDescription = computed(() => `FAQ ${props.catalogStore.name} tentang fitting sebelum deal, booking, pengambilan, pelunasan, dan pengembalian rental.`)

const structuredData = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: props.faqGroups.flatMap((group) => group.items.map((item) => ({
        '@type': 'Question',
        name: item.question,
        acceptedAnswer: {
            '@type': 'Answer',
            text: item.answer,
        },
    }))),
}))

function isOpen(key) {
    return openedItems.value.includes(key)
}

function toggleItem(key) {
    if (isOpen(key)) {
        openedItems.value = openedItems.value.filter((item) => item !== key)
        return
    }
    openedItems.value = [...openedItems.value, key]
}
</script>

<template>
    <PublicSeoHead
        title="FAQ"
        :description="seoDescription"
        :structured-data="structuredData"
    />

    <main :style="cssVars" class="min-h-screen w-full overflow-x-hidden bg-white text-[#202638]">
        <div class="mx-auto max-w-[1440px] px-4 py-6 md:px-8 xl:px-12">
            
            <section class="relative overflow-hidden rounded-[32px] bg-[#F5F3FF]">
                <div class="absolute -left-20 -top-20 size-[350px] rounded-full bg-violet-100/60 blur-3xl"></div>
                <div class="absolute -bottom-32 right-10 size-[300px] rounded-full bg-indigo-100/40 blur-3xl"></div>

                <div class="relative grid gap-8 px-6 py-12 md:px-10 lg:grid-cols-[1.2fr_0.8fr] lg:items-center lg:px-14 lg:py-16">
                    <div class="space-y-6">
                        <div>
                            <span class="inline-block rounded-full bg-violet-100 px-4 py-1.5 text-[11px] font-bold uppercase tracking-[0.2em] text-[var(--catalog-primary)]">
                                Pertanyaan Umum
                            </span>
                        </div>
                        <h1 class="max-w-2xl text-[32px] font-bold leading-[1.15] text-slate-800 md:text-[46px]">
                            Hal penting yang sering ditanyakan sebelum <span class="text-[var(--catalog-primary)]">deal rental.</span>
                        </h1>
                        <p class="max-w-xl text-[15px] leading-relaxed text-slate-600 md:text-[17px]">
                            Ringkas, langsung ke inti, dan fokus membahas prosedur serta aturan yang paling sering membuat bingung.
                        </p>

                        <div class="flex flex-wrap gap-2.5 pt-2">
                            <span
                                v-for="rule in keyRules"
                                :key="rule"
                                class="flex items-center gap-1.5 rounded-full bg-white/80 px-4 py-2 text-[12px] font-bold text-[var(--catalog-primary)] shadow-sm backdrop-blur-sm"
                            >
                                <CheckCircle2 :size="14" stroke-width="2.5" />
                                {{ rule }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 lg:mt-0 lg:pl-8">
                        <a 
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="group block rounded-[24px] border border-white/60 bg-white/50 p-6 shadow-[0_8px_30px_rgb(0,0,0,0.03)] backdrop-blur-md transition-all hover:-translate-y-1 hover:bg-white/80"
                        >
                            <div class="flex items-center gap-4">
                                <span class="grid size-14 shrink-0 place-items-center rounded-full bg-emerald-500 text-white shadow-sm transition-transform group-hover:scale-110">
                                    <IconBrandWhatsapp :size="24" stroke-width="2" />
                                </span>
                                <div>
                                    <p class="text-[16px] font-bold text-slate-800">Masih ada pertanyaan?</p>
                                    <p class="mt-1 text-[13px] font-medium leading-relaxed text-slate-500">Admin siap bantu atur jadwal konsultasi dan fitting Anda ke toko.</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <section class="mt-10 grid gap-8 md:mt-14 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[300px_minmax(0,1fr)] lg:items-start">
                
                <aside class="sticky top-[100px] hidden rounded-[28px] border border-slate-100 bg-white p-6 shadow-sm lg:block">
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-slate-400">Daftar Topik</p>
                    <nav class="mt-5 flex flex-col gap-2">
                        <a
                            v-for="(group, groupIndex) in faqGroups"
                            :key="group.title"
                            :href="`#topik-${groupIndex}`"
                            class="group flex items-center gap-3 rounded-[16px] border border-transparent px-4 py-3.5 text-[14px] font-bold text-slate-600 transition-colors hover:border-violet-100 hover:bg-violet-50/50 hover:text-[var(--catalog-primary)]"
                        >
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 transition-colors group-hover:bg-white group-hover:text-[var(--catalog-primary)] group-hover:shadow-sm">
                                <component :is="groupIcons[groupIndex] || HelpCircle" :size="16" stroke-width="2.5" />
                            </span>
                            {{ group.title }}
                        </a>
                    </nav>
                </aside>

                <div class="grid gap-10">
                    <section
                        v-for="(group, groupIndex) in faqGroups"
                        :key="group.title"
                        :id="`topik-${groupIndex}`"
                        class="scroll-mt-28 rounded-[28px] border border-slate-100 bg-white p-5 shadow-sm md:p-8"
                    >
                        <div class="mb-6 flex items-center gap-4 border-b border-slate-100 pb-6">
                            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-violet-50 text-[var(--catalog-primary)]">
                                <component :is="groupIcons[groupIndex] || HelpCircle" :size="20" stroke-width="2.5" />
                            </span>
                            <div>
                                <h2 class="text-xl font-bold text-slate-800">{{ group.title }}</h2>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <article
                                v-for="(item, itemIndex) in group.items"
                                :key="item.question"
                                class="overflow-hidden rounded-[20px] border transition-all duration-300"
                                :class="isOpen(`${groupIndex}-${itemIndex}`) ? 'border-[var(--catalog-primary)] bg-white shadow-md shadow-violet-900/5' : 'border-slate-100 bg-slate-50 hover:border-slate-200'"
                            >
                                <button
                                    class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left outline-none md:px-6 md:py-5"
                                    type="button"
                                    @click="toggleItem(`${groupIndex}-${itemIndex}`)"
                                >
                                    <h3 
                                        class="text-[15px] font-bold leading-relaxed transition-colors"
                                        :class="isOpen(`${groupIndex}-${itemIndex}`) ? 'text-[var(--catalog-primary)]' : 'text-slate-800'"
                                    >
                                        {{ item.question }}
                                    </h3>
                                    <span 
                                        class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full transition-transform duration-300"
                                        :class="isOpen(`${groupIndex}-${itemIndex}`) ? 'rotate-180 bg-violet-100 text-[var(--catalog-primary)]' : 'bg-white text-slate-400 shadow-sm'"
                                    >
                                        <ChevronDown :size="16" stroke-width="2.5" />
                                    </span>
                                </button>

                                <div
                                    v-show="isOpen(`${groupIndex}-${itemIndex}`)"
                                    class="px-5 pb-5 md:px-6 md:pb-6"
                                >
                                    <div class="h-[1px] w-full bg-slate-100 mb-4"></div>
                                    <p class="text-[14.5px] font-medium leading-relaxed text-slate-600">
                                        {{ item.answer }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

            </section>
        </div>
    </main>
</template>

<style scoped>
html {
    scroll-behavior: smooth;
}
</style>