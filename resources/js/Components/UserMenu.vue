<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import { ChevronDown, LogOut, UserRound } from '@lucide/vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useConfirm } from '@/Composables/useConfirm'

const props = defineProps({
    // 'full' menampilkan nama + role di samping avatar (desktop top bar),
    // 'compact' hanya avatar (mobile header).
    variant: {
        type: String,
        default: 'full',
    },
    align: {
        type: String,
        default: 'right',
    },
})

const page = usePage()
const { confirmAction } = useConfirm()

const open = ref(false)
const root = ref(null)

const user = computed(() => page.props.auth?.user || {})

const userInitials = computed(() => String(user.value?.name || '?')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase())

function toggle() {
    open.value = !open.value
}

function closeOnOutside(event) {
    if (!root.value || root.value.contains(event.target)) {
        return
    }

    open.value = false
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

    open.value = false
    router.post(route('logout'))
}

onMounted(() => document.addEventListener('pointerdown', closeOnOutside))
onBeforeUnmount(() => document.removeEventListener('pointerdown', closeOnOutside))
</script>

<template>
    <div ref="root" class="relative">
        <button
            class="flex min-h-10 items-center gap-2 rounded-2xl border border-diamond-border bg-white px-1.5 py-1.5 text-left transition hover:bg-diamond-surface-soft focus:outline-none focus:ring-4 focus:ring-diamond-primary/10"
            :class="variant === 'full' ? 'sm:pr-2.5' : ''"
            type="button"
            aria-haspopup="menu"
            :aria-expanded="open"
            @click.stop="toggle"
            @pointerdown.stop
        >
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-diamond-primary text-[11px] font-bold text-white">
                {{ userInitials }}
            </span>
            <span v-if="variant === 'full'" class="hidden min-w-0 sm:block">
                <span class="block max-w-32 truncate text-xs font-bold leading-tight text-diamond-text">{{ user.name }}</span>
                <span class="block truncate text-[11px] capitalize leading-tight text-diamond-muted">{{ user.role }}</span>
            </span>
            <ChevronDown
                v-if="variant === 'full'"
                class="hidden shrink-0 text-diamond-soft transition sm:block"
                :class="open ? 'rotate-180' : ''"
                :size="16"
            />
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
        >
            <div
                v-if="open"
                class="absolute top-[calc(100%+0.5rem)] z-50 w-60 overflow-hidden rounded-3xl border border-diamond-border bg-white shadow-xl shadow-slate-900/5"
                :class="align === 'right' ? 'right-0' : 'left-0'"
                role="menu"
                @pointerdown.stop
            >
                <div class="border-b border-diamond-border p-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary text-xs font-bold text-white">
                            {{ userInitials }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-bold text-diamond-text">{{ user.name }}</p>
                            <p class="truncate text-[11px] capitalize text-diamond-muted">{{ user.role }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid gap-1 p-1.5">
                    <Link
                        :href="route('profile.edit')"
                        class="flex min-h-10 items-center gap-2.5 rounded-2xl px-3 py-2 text-xs font-semibold text-diamond-text transition hover:bg-diamond-surface-soft"
                        role="menuitem"
                        @click="open = false"
                    >
                        <UserRound :size="16" />
                        Profil
                    </Link>
                    <button
                        class="flex min-h-10 items-center gap-2.5 rounded-2xl px-3 py-2 text-left text-xs font-semibold text-red-600 transition hover:bg-red-50"
                        type="button"
                        role="menuitem"
                        @click="confirmLogout"
                    >
                        <LogOut :size="16" />
                        Logout
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
