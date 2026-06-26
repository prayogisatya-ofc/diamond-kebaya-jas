<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { route as ziggyRoute } from 'ziggy-js'
import { computed, ref } from 'vue'
import PublicIcon from '../Components/Public/PublicIcon.vue'

const props = defineProps({
    activeNav: {
        type: String,
        default: 'home',
    },
    floatingWhatsapp: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()
const mobileNavOpen = ref(false)
const publicLogoUrl = '/logo-diamond.png'

const catalogStore = computed(() => page.props.catalogStore ?? {
    name: 'Diamond Kebaya & Jas',
    whatsapp_number: '',
    primary_color: '#6533D6',
})

const primaryColor = computed(() => {
    const color = String(catalogStore.value.primary_color || '#6533D6')

    return /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(color) ? color : '#6533D6'
})

const cssVars = computed(() => ({
    '--catalog-primary': primaryColor.value,
}))

const navItems = [
    { key: 'home', label: 'Beranda', route: 'public.catalog' },
    { key: 'how', label: 'Cara Sewa', route: 'public.how-to-rent' },
    { key: 'faq', label: 'FAQ', route: 'public.faq' },
]

const whatsappUrl = computed(() => {
    const phone = normalizePhone(catalogStore.value.whatsapp_number)
    const message = encodeURIComponent(`Halo ${catalogStore.value.name}, saya mau tanya katalog rental.`)

    return phone ? `https://wa.me/${phone}?text=${message}` : '#'
})

const adminPanelUrl = computed(() => appRoute('dashboard'))

function normalizePhone(value) {
    const digits = String(value || '').replace(/\D/g, '')

    if (!digits) {
        return ''
    }

    if (digits.startsWith('0')) {
        return `62${digits.slice(1)}`
    }

    return digits
}

function appRoute(name, params) {
    const ziggy = page.props.ziggy

    return ziggyRoute(name, params, true, {
        ...ziggy,
        location: new URL(ziggy.location),
    })
}

function navUrl(item) {
    const baseUrl = appRoute(item.route)

    return item.hash ? `${baseUrl}${item.hash}` : baseUrl
}

function closeMobileMenu() {
    mobileNavOpen.value = false
}
</script>

<template>
    <main :style="cssVars" class="font-public min-h-screen w-full overflow-x-hidden bg-white text-[#202638]">
        <header class="sticky top-0 z-40 border-b border-slate-100 bg-white/95 backdrop-blur-md">
            <div class="hidden h-[88px] items-center justify-between px-6 lg:flex xl:px-12">
                <Link :href="appRoute('public.catalog')" class="flex items-center gap-3">
                    <img :src="publicLogoUrl" alt="Logo Diamond Kebaya & Jas" class="h-11">
                </Link>

                <nav class="flex items-center gap-10 text-sm font-extrabold text-slate-600">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="navUrl(item)"
                        class="border-b-2 pb-1 transition"
                        :class="activeNav === item.key ? 'border-[var(--catalog-primary,#6533D6)] text-[var(--catalog-primary,#6533D6)]' : 'border-transparent hover:text-[var(--catalog-primary,#6533D6)]'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-3">
                    <Link
                        :href="adminPanelUrl"
                        class="flex h-12 items-center gap-2 rounded-full border border-violet-100 bg-violet-50 px-5 text-sm font-extrabold text-[var(--catalog-primary,#6533D6)] transition hover:bg-violet-100"
                    >
                        <PublicIcon name="apps" :size="22" />
                        <span class="leading-tight">
                            <span class="block">Panel Admin</span>
                        </span>
                    </Link>

                    <a
                        :href="whatsappUrl"
                        class="flex h-12 items-center gap-2 rounded-full bg-emerald-50 px-5 text-sm font-extrabold text-emerald-700 transition hover:bg-emerald-100"
                        target="_blank"
                        rel="noreferrer"
                    >
                        <PublicIcon name="brand-whatsapp" :size="24" class="text-emerald-500" />
                        <span class="leading-tight">
                            <span class="block">WhatsApp</span>
                        </span>
                    </a>
                </div>
            </div>

            <div class="flex h-[72px] items-center justify-between px-4 lg:hidden">
                <button class="grid size-10 place-items-center text-slate-700" type="button" @click="mobileNavOpen = !mobileNavOpen">
                    <PublicIcon :name="mobileNavOpen ? 'x' : 'menu-2'" :size="26" />
                </button>

                <Link :href="appRoute('public.catalog')" class="flex items-center gap-2">
                    <img :src="publicLogoUrl" alt="Logo Diamond Kebaya & Jas" class="h-9">
                </Link>

                <a
                    :href="whatsappUrl"
                    class="grid size-10 place-items-center text-slate-700"
                    target="_blank"
                    rel="noreferrer"
                >
                    <PublicIcon name="brand-whatsapp" :size="26" />
                </a>
            </div>

            <div v-if="mobileNavOpen" class="border-t border-slate-100 bg-white px-4 py-4 lg:hidden">
                <nav class="grid gap-2">
                    <Link
                        :href="adminPanelUrl"
                        class="flex items-center gap-3 rounded-2xl bg-violet-50 px-4 py-3 text-sm font-extrabold text-[var(--catalog-primary,#6533D6)] transition hover:bg-violet-100"
                        @click="closeMobileMenu"
                    >
                        <PublicIcon name="apps" :size="21" />
                        Panel Admin
                    </Link>

                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="navUrl(item)"
                        class="rounded-2xl px-4 py-3 text-sm font-extrabold transition"
                        :class="activeNav === item.key ? 'bg-violet-50 text-[var(--catalog-primary,#6533D6)]' : 'text-slate-600 hover:bg-slate-50'"
                        @click="closeMobileMenu"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <slot />

        <footer class="mt-16 border-t border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-[1440px] px-4 py-8 md:px-8 xl:px-12">
                <div class="rounded-[24px] border border-slate-100 bg-white p-6 shadow-[0_2px_10px_rgba(0,0,0,0.02)] md:p-8">
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4 lg:divide-x lg:divide-slate-100">
                        <div class="flex items-center gap-4 lg:pr-6">
                            <span class="text-[var(--catalog-primary,#6533D6)]">
                                <PublicIcon name="map-pin" :size="32" :stroke-width="1.6" />
                            </span>
                            <div>
                                <span class="block text-[13px] font-bold text-slate-800">Alamat Toko</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">
                                    {{ catalogStore.address || 'Jl. Contoh Toko No. 12, Jakarta Selatan 12345' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 lg:px-6">
                            <span class="text-emerald-500">
                                <PublicIcon name="brand-whatsapp" :size="34" :stroke-width="1.7" />
                            </span>
                            <div>
                                <span class="block text-[13px] font-bold text-slate-800">WhatsApp</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">
                                    {{ catalogStore.whatsapp_number || '0812-3456-7890' }}<br>Chat kami langsung
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 lg:px-6">
                            <span class="text-[var(--catalog-primary,#6533D6)]">
                                <PublicIcon name="clock" :size="32" :stroke-width="1.6" />
                            </span>
                            <div>
                                <span class="block text-[13px] font-bold text-slate-800">Jam Operasional</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">Setiap Hari<br>10.00 - 20.00 WIB</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 lg:pl-6">
                            <span class="text-[var(--catalog-primary,#6533D6)]">
                                <PublicIcon name="shield-check" :size="32" :stroke-width="1.6" />
                            </span>
                            <div>
                                <span class="block text-[13px] font-bold text-slate-800">Terpercaya</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">Ratusan pelanggan puas<br>dan repeat order</span>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="py-8 text-center text-[11px] font-semibold text-slate-400 md:text-xs">
                    © 2026 Diamond Kebaya & Jas. All rights reserved.
                </p>
            </div>
        </footer>

        <a
            v-if="floatingWhatsapp"
            :href="whatsappUrl"
            class="fixed bottom-5 right-5 z-40 grid size-16 place-items-center rounded-full bg-emerald-500 text-white shadow-[0_18px_40px_rgba(16,185,129,0.35)] lg:hidden"
            target="_blank"
            rel="noreferrer"
        >
            <PublicIcon name="brand-whatsapp" :size="30" />
        </a>
    </main>
</template>
