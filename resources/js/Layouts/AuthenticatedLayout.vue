<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    CalendarDays,
    ChevronLeft,
    LogOut,
    Menu,
    PanelLeftClose,
    PanelLeftOpen,
    X,
} from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import GlobalSpinner from '@/Components/GlobalSpinner.vue'
import GlobalToast from '@/Components/GlobalToast.vue'
import UserMenu from '@/Components/UserMenu.vue'
import { useConfirm } from '@/Composables/useConfirm'
import { useNavigation } from '@/Composables/useNavigation'
import { useSidebar } from '@/Composables/useSidebar'

const page = usePage()
const drawerOpen = ref(false)
const { confirmAction } = useConfirm()
const { navigation, breadcrumbs, isActive } = useNavigation()
const { collapsed, toggleCollapsed } = useSidebar()

const store = computed(() => page.props.store || {})
const primaryColor = computed(() => {
    const color = String(store.value.primary_color || '')

    return /^#(?:[0-9a-fA-F]{3}){1,2}$/.test(color) ? color : '#615cf9'
})
const themeStyle = computed(() => ({
    '--color-diamond-primary': primaryColor.value,
    '--color-diamond-primary-dark': `color-mix(in srgb, ${primaryColor.value} 82%, black)`,
    '--color-diamond-primary-soft': `color-mix(in srgb, ${primaryColor.value} 12%, white)`,
    '--color-diamond-primary-muted': `color-mix(in srgb, ${primaryColor.value} 44%, white)`,
    '--color-diamond-sidebar': primaryColor.value,
}))

const currentTitle = computed(() => {
    const trail = breadcrumbs.value

    return trail[trail.length - 1]?.label || 'Dashboard'
})

function applyThemeVariables(style) {
    if (typeof document === 'undefined') {
        return
    }

    Object.entries(style).forEach(([property, value]) => {
        document.documentElement.style.setProperty(property, value)
    })
}

function closeDrawer() {
    drawerOpen.value = false
}

async function confirmLogout() {
    const confirmed = await confirmAction({
        title: 'Keluar dari aplikasi?',
        message: 'Sesi kamu akan ditutup dan perlu login lagi untuk masuk.',
        confirmLabel: 'Ya, keluar',
        tone: 'logout',
    })

    if (!confirmed) {
        return
    }

    drawerOpen.value = false
    router.post(route('logout'))
}

onMounted(() => applyThemeVariables(themeStyle.value))
watch(themeStyle, (style) => applyThemeVariables(style), { immediate: true })
</script>

<template>
    <div class="min-h-screen min-w-0 overflow-x-hidden bg-diamond-bg text-diamond-text" :style="themeStyle">
        <!-- Sidebar desktop: collapsible berlabel -->
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden p-3 transition-[width] duration-200 ease-out lg:block"
            :class="collapsed ? 'w-[5.5rem]' : 'w-64'"
        >
            <div class="flex h-full flex-col rounded-[2rem] bg-diamond-sidebar text-white">
                <div class="flex items-center gap-3 px-4 py-5" :class="collapsed ? 'justify-center px-0' : ''">
                    <Link
                        :href="route('dashboard')"
                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-diamond-primary"
                    >
                        <img
                            v-if="store.logo_url"
                            :alt="store.name || 'Logo toko'"
                            class="h-11 w-11 object-contain"
                            :src="store.logo_url"
                        >
                        <CalendarDays v-else :size="22" />
                    </Link>
                    <div v-if="!collapsed" class="min-w-0">
                        <p class="truncate text-sm font-bold leading-tight">{{ store.name || 'Diamond Kebaya & Jas' }}</p>
                        <p class="truncate text-[11px] leading-tight text-white/70">Rental Management</p>
                    </div>
                </div>

                <nav class="flex flex-1 flex-col gap-1.5 overflow-hidden px-3 py-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        class="group relative flex min-h-11 items-center rounded-2xl text-sm font-semibold transition"
                        :class="[
                            collapsed ? 'justify-center px-0' : 'gap-3 px-3.5',
                            isActive(item) ? 'bg-white text-diamond-primary' : 'text-white/80 hover:bg-white/15 hover:text-white',
                        ]"
                        :title="collapsed ? item.label : undefined"
                    >
                        <component :is="item.icon" class="shrink-0" :size="20" />
                        <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
                        <span
                            v-if="collapsed"
                            class="pointer-events-none absolute left-[calc(100%+0.75rem)] z-50 hidden whitespace-nowrap rounded-xl bg-diamond-text px-3 py-2 text-xs font-semibold text-white group-hover:block"
                        >
                            {{ item.label }}
                        </span>
                    </Link>
                </nav>

                <div class="grid gap-1.5 px-3 pb-4 pt-2">
                    <button
                        class="flex min-h-11 items-center rounded-2xl text-sm font-semibold text-white/80 transition hover:bg-white/15 hover:text-white"
                        :class="collapsed ? 'justify-center px-0' : 'gap-3 px-3.5'"
                        type="button"
                        :title="collapsed ? 'Keluar' : undefined"
                        @click="confirmLogout"
                    >
                        <LogOut class="shrink-0" :size="20" />
                        <span v-if="!collapsed">Keluar</span>
                    </button>
                    <button
                        class="flex min-h-11 items-center rounded-2xl text-sm font-semibold text-white/70 transition hover:bg-white/15 hover:text-white"
                        :class="collapsed ? 'justify-center px-0' : 'gap-3 px-3.5'"
                        type="button"
                        :title="collapsed ? 'Lebarkan menu' : 'Ciutkan menu'"
                        @click="toggleCollapsed"
                    >
                        <component :is="collapsed ? PanelLeftOpen : PanelLeftClose" class="shrink-0" :size="20" />
                        <span v-if="!collapsed">Ciutkan menu</span>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Drawer mobile -->
        <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
            <button class="absolute inset-0 bg-slate-950/40" type="button" aria-label="Tutup menu" @click="closeDrawer" />
            <aside class="relative flex h-full w-[min(300px,84vw)] flex-col bg-diamond-sidebar p-4 text-white">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white text-diamond-primary">
                            <img
                                v-if="store.logo_url"
                                :alt="store.name || 'Logo toko'"
                                class="h-10 w-10 object-contain"
                                :src="store.logo_url"
                            >
                            <CalendarDays v-else :size="20" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold">{{ store.name || 'Diamond Kebaya & Jas' }}</p>
                            <p class="truncate text-[11px] text-white/70">Rental Management</p>
                        </div>
                    </div>
                    <button class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/15" type="button" @click="closeDrawer">
                        <X :size="20" />
                    </button>
                </div>

                <nav class="mt-6 grid gap-1.5">
                    <Link
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex min-h-11 items-center gap-3 rounded-2xl px-3.5 py-2 text-sm font-semibold transition"
                        :class="isActive(item) ? 'bg-white text-diamond-primary' : 'text-white/85 hover:bg-white/15'"
                        @click="closeDrawer"
                    >
                        <component :is="item.icon" :size="19" />
                        {{ item.label }}
                    </Link>
                </nav>

                <button
                    class="mt-auto flex min-h-11 items-center gap-3 rounded-2xl px-3.5 py-2 text-sm font-semibold text-white/85 transition hover:bg-white/15"
                    type="button"
                    @click="confirmLogout"
                >
                    <LogOut :size="19" />
                    Keluar
                </button>
            </aside>
        </div>

        <div class="min-w-0 overflow-x-hidden transition-[padding] duration-200 ease-out" :class="collapsed ? 'lg:pl-[5.5rem]' : 'lg:pl-64'">
            <!-- Top bar wrapper: floating pill on desktop, full-width on mobile -->
            <div class="sticky top-0 z-30 lg:px-3 lg:pt-3">
                <header class="border-b border-diamond-border bg-white/85 backdrop-blur lg:static lg:rounded-[2rem] lg:border lg:border-white/80">
                    <div class="flex items-center gap-3 px-3 py-2.5 sm:px-5 lg:px-5">
                        <button
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-diamond-border bg-white text-diamond-primary lg:hidden"
                            type="button"
                            aria-label="Buka menu"
                            @click="drawerOpen = true"
                        >
                            <Menu :size="20" />
                        </button>

                        <div class="min-w-0 flex-1">
                            <Breadcrumb :items="breadcrumbs" class="hidden sm:flex" />
                            <p class="truncate text-sm font-bold text-diamond-text sm:hidden">{{ currentTitle }}</p>
                        </div>

                        <UserMenu class="shrink-0" />
                    </div>
                </header>
            </div>

            <main class="min-h-[calc(100vh-3.75rem)] min-w-0 max-w-full overflow-x-hidden px-3 pb-5 pt-4 sm:px-6 lg:px-7 lg:pb-7 lg:pt-6 2xl:px-9">
                <slot />
            </main>
        </div>

        <ConfirmDialog />
        <GlobalToast />
        <GlobalSpinner />
    </div>
</template>
