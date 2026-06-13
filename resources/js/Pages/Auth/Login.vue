<script setup>
import { computed, onMounted, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { CalendarDays, LogIn } from '@lucide/vue'
import GlobalToast from '@/Components/GlobalToast.vue'
import Switch from '@/Components/Switch.vue'

const page = usePage()
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

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
    })
}

onMounted(() => applyThemeVariables(themeStyle.value))

watch(themeStyle, (style) => applyThemeVariables(style), { immediate: true })
</script>

<template>
    <Head title="Masuk" />

    <main class="min-h-dvh bg-white text-diamond-text sm:flex sm:items-center sm:justify-center sm:bg-diamond-bg sm:px-6 sm:py-10" :style="themeStyle">
        <section class="flex min-h-dvh w-full flex-col justify-center px-6 py-8 sm:min-h-0 sm:max-w-md sm:rounded-[2rem] sm:border sm:border-white/80 sm:bg-white sm:p-8">
            <div class="mb-8">
                <div class="mb-7 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden text-diamond-primary">
                        <img
                            v-if="store.logo_url"
                            :alt="store.name || 'Logo toko'"
                            class="h-10 w-10 object-contain"
                            :src="store.logo_url"
                        >
                        <CalendarDays v-else :size="24" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-md font-bold text-diamond-text">{{ store.name || 'Diamond Kebaya & Jas' }}</p>
                        <p class="truncate text-xs text-diamond-muted">Rental Management POS</p>
                    </div>
                </div>

                <h1 class="text-3xl font-bold leading-tight text-diamond-text">Masuk ke sistem</h1>
                <p class="mt-2 text-sm leading-6 text-diamond-muted">Gunakan akun owner atau staff untuk membuka dashboard internal.</p>
            </div>

            <form @submit.prevent="submit">
                <div class="grid gap-5">
                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Email</span>
                        <input
                            v-model="form.email"
                            autocomplete="username"
                            autofocus
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            name="email"
                            placeholder="nama@email.com"
                            type="email"
                        >
                        <span v-if="form.errors.email" class="text-sm text-diamond-danger">{{ form.errors.email }}</span>
                    </label>

                    <label class="grid gap-2">
                        <span class="text-sm font-semibold text-diamond-text">Password</span>
                        <input
                            v-model="form.password"
                            autocomplete="current-password"
                            class="min-h-12 w-full rounded-xl border border-diamond-border bg-white px-4 py-3 text-sm text-diamond-text outline-none transition placeholder:text-diamond-soft focus:border-diamond-primary focus:ring-4 focus:ring-diamond-primary/10"
                            name="password"
                            placeholder="Masukkan password"
                            type="password"
                        >
                        <span v-if="form.errors.password" class="text-sm text-diamond-danger">{{ form.errors.password }}</span>
                    </label>

                    <Switch
                        v-model="form.remember"
                        label="Ingat sesi masuk"
                    />

                    <button
                        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-diamond-primary px-4 py-3 text-sm font-bold text-white transition hover:bg-diamond-primary-dark disabled:cursor-not-allowed disabled:opacity-60"
                        type="submit"
                        :disabled="form.processing"
                    >
                        <LogIn :size="18" />
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </button>
                </div>
            </form>
        </section>
        <GlobalToast />
    </main>
</template>
