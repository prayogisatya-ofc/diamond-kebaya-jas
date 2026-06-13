<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { KeyRound, Save, ShieldCheck, UserRound } from '@lucide/vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
})

const profileForm = useForm({
    name: props.profile.name,
    username: props.profile.username ?? '',
    email: props.profile.email,
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const initials = computed(() => {
    return String(props.profile.name || '?')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase()
})

const roleLabel = computed(() => {
    return props.profile.role === 'owner' ? 'Owner' : 'Staff'
})

const statusLabel = computed(() => {
    return props.profile.is_active ? 'Aktif' : 'Nonaktif'
})

function submitProfile() {
    profileForm.patch(route('profile.update'), {
        preserveScroll: true,
    })
}

function submitPassword() {
    passwordForm.put(route('profile.password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}
</script>

<template>
    <Head title="Profil" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Akun"
            title="Profil"
        />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
            <Card>
                <div class="grid gap-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-3xl bg-diamond-primary text-xl font-bold text-white">
                            {{ initials }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold text-diamond-text">{{ profile.name }}</h2>
                            <p class="mt-1 truncate text-sm text-diamond-muted">{{ profile.email }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                            <span class="text-sm font-semibold text-diamond-muted">Role</span>
                            <span class="rounded-xl bg-diamond-primary-soft px-3 py-1 text-xs font-bold text-diamond-primary">{{ roleLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                            <span class="text-sm font-semibold text-diamond-muted">Status</span>
                            <span class="rounded-xl px-3 py-1 text-xs font-bold" :class="profile.is_active ? 'bg-diamond-success-soft text-diamond-success' : 'bg-diamond-danger-soft text-diamond-danger'">
                                {{ statusLabel }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-diamond-surface-soft px-4 py-3">
                            <span class="text-sm font-semibold text-diamond-muted">Username</span>
                            <span class="min-w-0 truncate text-sm font-bold text-diamond-text">{{ profile.username || '-' }}</span>
                        </div>
                    </div>
                </div>
            </Card>

            <div class="grid gap-6">
                <form @submit.prevent="submitProfile">
                    <Card>
                        <div class="grid gap-5">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                                    <UserRound :size="22" />
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-lg font-bold text-diamond-text">Data profil</h2>
                                    <p class="mt-1 text-sm leading-6 text-diamond-muted">Update nama, username, dan email akun kamu.</p>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <Input
                                    v-model="profileForm.name"
                                    :error="profileForm.errors.name"
                                    autocomplete="name"
                                    label="Nama lengkap"
                                    placeholder="Nama lengkap"
                                />

                                <Input
                                    v-model="profileForm.username"
                                    :error="profileForm.errors.username"
                                    autocomplete="username"
                                    label="Username"
                                    placeholder="username"
                                />

                                <Input
                                    v-model="profileForm.email"
                                    :error="profileForm.errors.email"
                                    autocomplete="email"
                                    label="Email"
                                    placeholder="nama@email.com"
                                    type="email"
                                />

                                <div class="grid gap-2">
                                    <span class="text-sm font-semibold text-diamond-text">Akses</span>
                                    <div class="flex min-h-12 items-center gap-3 rounded-xl border border-diamond-border bg-diamond-surface-soft px-4 py-3">
                                        <ShieldCheck :size="18" class="text-diamond-primary" />
                                        <span class="text-sm font-bold text-diamond-text">{{ roleLabel }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <Button :disabled="profileForm.processing" type="submit">
                                    <Save :size="18" />
                                    {{ profileForm.processing ? 'Menyimpan...' : 'Simpan profil' }}
                                </Button>
                            </div>
                        </div>
                    </Card>
                </form>

                <form @submit.prevent="submitPassword">
                    <Card>
                        <div class="grid gap-5">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-accent/15 text-diamond-accent">
                                    <KeyRound :size="22" />
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-lg font-bold text-diamond-text">Ganti password</h2>
                                    <p class="mt-1 text-sm leading-6 text-diamond-muted">Masukkan password lama sebelum membuat password baru.</p>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-3">
                                <Input
                                    v-model="passwordForm.current_password"
                                    :error="passwordForm.errors.current_password"
                                    autocomplete="current-password"
                                    label="Password saat ini"
                                    type="password"
                                />

                                <Input
                                    v-model="passwordForm.password"
                                    :error="passwordForm.errors.password"
                                    autocomplete="new-password"
                                    label="Password baru"
                                    type="password"
                                />

                                <Input
                                    v-model="passwordForm.password_confirmation"
                                    autocomplete="new-password"
                                    label="Konfirmasi password"
                                    type="password"
                                />
                            </div>

                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <Button :disabled="passwordForm.processing" type="submit" variant="accent">
                                    <KeyRound :size="18" />
                                    {{ passwordForm.processing ? 'Menyimpan...' : 'Ganti password' }}
                                </Button>
                            </div>
                        </div>
                    </Card>
                </form>
            </div>
        </div>
    </section>
</template>
