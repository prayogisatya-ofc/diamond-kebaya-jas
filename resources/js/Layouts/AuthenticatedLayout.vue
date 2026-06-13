<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import {
    BarChart3,
    CalendarDays,
    Home,
    LogOut,
    Menu,
    Package,
    Settings,
    ShoppingBag,
    Tag,
    UserRound,
    Users,
    X,
} from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import GlobalToast from '@/Components/GlobalToast.vue'
import { useConfirm } from '@/Composables/useConfirm'

const page = usePage()
const drawerOpen = ref(false)
const profileMenuOpen = ref(false)
const profileMenuRoot = ref(null)
const { confirmAction } = useConfirm()

const user = computed(() => page.props.auth.user)
const isOwner = computed(() => user.value?.role === 'owner')
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

function applyThemeVariables(style) {
    if (typeof document === 'undefined') {
        return
    }

    Object.entries(style).forEach(([property, value]) => {
        document.documentElement.style.setProperty(property, value)
    })
}

const navigation = computed(() => {
    const items = [
        { label: 'Dashboard', route: 'dashboard', icon: Home },
        { label: 'Rental', route: 'rentals.index', icon: ShoppingBag },
        { label: 'Produk', route: 'products.index', icon: Package },
        { label: 'Paket', route: 'rental-packages.index', icon: Tag },
        { label: 'Customer', route: 'customers.index', icon: Users },
        { label: 'Laporan', route: 'reports.transactions', icon: BarChart3 },
    ]

    if (isOwner.value) {
        items.push(
            { label: 'User', route: 'users.index', icon: Users },
            { label: 'Setting', route: 'settings.edit', icon: Settings },
        )
    }

    return items
})

function isActive(item) {
    if (item.route === 'dashboard') {
        return route().current('dashboard')
    }

    const prefix = item.route.split('.')[0]

    return route().current(`${prefix}.*`)
}

function closeDrawer() {
    drawerOpen.value = false
}

const userInitials = computed(() => {
    return String(user.value?.name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
})

function closeProfileMenuWhenClickOutside(event) {
    if (!profileMenuRoot.value || profileMenuRoot.value.contains(event.target)) {
        return
    }

    profileMenuOpen.value = false
}

function toggleProfileMenu() {
    profileMenuOpen.value = !profileMenuOpen.value
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
    profileMenuOpen.value = false

    router.post(route('logout'))
}

onMounted(() => {
    applyThemeVariables(themeStyle.value)
    document.addEventListener('pointerdown', closeProfileMenuWhenClickOutside)
})

watch(themeStyle, (style) => applyThemeVariables(style), { immediate: true })

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', closeProfileMenuWhenClickOutside)
})
</script>

<template>
    <div class="min-h-screen min-w-0 overflow-x-hidden bg-diamond-bg text-diamond-text" :style="themeStyle">
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-24 lg:block">
            <div class="flex h-full flex-col items-center rounded-r-[2.25rem] bg-diamond-sidebar px-3 py-7">
                <Link :href="route('dashboard')" class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-white text-diamond-primary">
                    <img
                        v-if="store.logo_url"
                        :alt="store.name || 'Logo toko'"
                        class="h-9 w-9 object-contain"
                        :src="store.logo_url"
                    >
                    <CalendarDays v-else :size="24" />
                </Link>

                <nav class="mt-10 flex flex-1 flex-col items-center gap-3">
                    <Link
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        class="group relative flex h-12 w-12 items-center justify-center rounded-2xl transition"
                        :class="isActive(item) ? 'bg-white text-diamond-primary' : 'text-white/80 hover:bg-white/15 hover:text-white'"
                        :title="item.label"
                    >
                        <component :is="item.icon" :size="21" />
                        <span class="pointer-events-none absolute left-14 hidden rounded-xl bg-diamond-text px-3 py-2 text-xs font-semibold text-white group-hover:block">
                            {{ item.label }}
                        </span>
                    </Link>
                </nav>

                <button
                    class="flex h-12 w-12 items-center justify-center rounded-2xl text-white/80 transition hover:bg-white/15 hover:text-white"
                    type="button"
                    title="Keluar"
                    @click="confirmLogout"
                >
                    <LogOut :size="21" />
                </button>
            </div>
        </aside>

        <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
            <button class="absolute inset-0 bg-slate-950/35" type="button" aria-label="Tutup menu" @click="closeDrawer" />
            <aside class="relative flex h-full w-[min(320px,86vw)] flex-col rounded-r-[2rem] bg-diamond-primary p-5 text-white">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold">Diamond Kebaya & Jas</p>
                        <p class="text-xs text-white/70">Rental Management POS</p>
                    </div>
                    <button class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15" type="button" @click="closeDrawer">
                        <X :size="22" />
                    </button>
                </div>

                <nav class="mt-8 grid gap-2">
                    <Link
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        class="flex min-h-12 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition"
                        :class="isActive(item) ? 'bg-white text-diamond-primary' : 'text-white/85 hover:bg-white/15'"
                        @click="closeDrawer"
                    >
                        <component :is="item.icon" :size="20" />
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="mt-auto rounded-3xl bg-white/12 p-4">
                    <p class="text-sm font-semibold">{{ user.name }}</p>
                    <p class="text-xs capitalize text-white/70">{{ user.role }}</p>
                    <button class="mt-4 flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl bg-white text-sm font-semibold text-diamond-primary" type="button" @click="confirmLogout">
                        <LogOut :size="18" />
                        Keluar
                    </button>
                </div>
            </aside>
        </div>

        <div class="min-w-0 overflow-x-hidden lg:pl-24">
            <header class="sticky top-0 z-30 border-b border-diamond-border bg-white px-4 py-3 lg:hidden">
                <div class="grid grid-cols-[2.75rem_minmax(0,1fr)_2.75rem] items-center gap-3">
                    <button class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-diamond-primary" type="button" @click="drawerOpen = true">
                        <Menu :size="22" />
                    </button>
                    <Link :href="route('dashboard')" class="mx-auto flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl bg-white text-diamond-primary">
                        <img
                            v-if="store.logo_url"
                            :alt="store.name || 'Logo toko'"
                            class="h-8 w-8 object-contain"
                            :src="store.logo_url"
                        >
                        <CalendarDays v-else :size="23" />
                    </Link>
                    <div ref="profileMenuRoot" class="relative flex justify-end">
                        <button
                            v-if="user"
                            class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-diamond-text"
                            type="button"
                            :aria-expanded="profileMenuOpen"
                            aria-haspopup="menu"
                            @click.stop="toggleProfileMenu"
                            @pointerdown.stop
                        >
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-diamond-primary text-xs font-bold text-white">
                                {{ userInitials }}
                            </span>
                        </button>

                        <div
                            v-if="profileMenuOpen"
                            class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-64 overflow-hidden rounded-3xl border border-diamond-border bg-white"
                            role="menu"
                            @pointerdown.stop
                        >
                            <div class="border-b border-diamond-border p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary text-sm font-bold text-white">
                                        {{ userInitials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-diamond-text">{{ user.name }}</p>
                                        <p class="truncate text-xs capitalize text-diamond-muted">{{ user.role }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid gap-1 p-2">
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-diamond-text transition hover:bg-diamond-surface-soft"
                                    role="menuitem"
                                    @click="profileMenuOpen = false"
                                >
                                    <UserRound :size="18" />
                                    Profil
                                </Link>
                                <button
                                    class="flex min-h-11 items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50"
                                    type="button"
                                    role="menuitem"
                                    @click="confirmLogout"
                                >
                                    <LogOut :size="18" />
                                    Logout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="min-h-screen min-w-0 max-w-full overflow-x-hidden px-4 pb-7 pt-4 sm:px-6 lg:px-7 lg:pb-7 lg:pt-7 2xl:px-9 2xl:pb-9 2xl:pt-9">
                <slot />
            </main>
        </div>

        <ConfirmDialog />
        <GlobalToast />
    </div>
</template>
