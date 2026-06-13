<script setup>
import { computed } from 'vue'
import { ShieldCheck, UserRound } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import Input from '@/Components/Input.vue'
import Switch from '@/Components/Switch.vue'

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    submitLabel: {
        type: String,
        required: true,
    },
    showPassword: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['submit'])

const roleDescriptions = {
    owner: 'Akses penuh termasuk laporan, setting toko, dan manajemen user.',
    staff: 'Akses operasional untuk customer, produk, paket, transaksi, dan pembayaran.',
}

const selectedRole = computed(() => {
    return props.roles.find((role) => role.value === props.form.role)
})
</script>

<template>
    <form class="grid max-w-5xl gap-6" @submit.prevent="$emit('submit')">
        <Card>
            <div class="grid gap-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                            <UserRound :size="22" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-diamond-text">Profil user</h2>
                        </div>
                    </div>
                    <div class="rounded-2xl bg-diamond-surface-soft px-4 py-3 text-sm font-semibold text-diamond-primary">
                        {{ selectedRole?.label ?? 'Role belum dipilih' }}
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <Input
                        v-model="form.name"
                        :error="form.errors.name"
                        autocomplete="name"
                        label="Nama lengkap"
                        placeholder="Contoh: Karenech Smith"
                    />

                    <Input
                        v-model="form.username"
                        :error="form.errors.username"
                        autocomplete="username"
                        label="Username"
                        placeholder="contoh: staff01"
                    />

                    <Input
                        v-model="form.email"
                        :error="form.errors.email"
                        autocomplete="email"
                        label="Email"
                        placeholder="nama@email.com"
                        type="email"
                    />

                    <Switch
                        v-model="form.is_active"
                        :error="form.errors.is_active"
                        description="User nonaktif tidak bisa login ke aplikasi."
                        label="Status aktif"
                    />
                </div>

                <div class="grid gap-3">
                    <div>
                        <p class="text-sm font-semibold text-diamond-text">Role akses</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <button
                            v-for="role in roles"
                            :key="role.value"
                            class="flex min-h-28 cursor-pointer items-start gap-4 rounded-2xl border bg-white p-4 text-left transition focus:outline-none focus:ring-4 focus:ring-diamond-primary/10"
                            :class="form.role === role.value ? 'border-diamond-primary bg-diamond-primary-soft/70' : 'border-diamond-border hover:border-diamond-primary/40 hover:bg-diamond-surface-soft'"
                            type="button"
                            @click="form.role = role.value"
                        >
                            <span
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                                :class="form.role === role.value ? 'bg-diamond-primary text-white' : 'bg-diamond-surface-soft text-diamond-muted'"
                            >
                                <ShieldCheck :size="20" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-diamond-text">{{ role.label }}</span>
                                <span class="mt-1 block text-sm leading-6 text-diamond-muted">
                                    {{ roleDescriptions[role.value] ?? 'Akses operasional aplikasi.' }}
                                </span>
                            </span>
                        </button>
                    </div>

                    <span v-if="form.errors.role" class="text-sm text-diamond-danger">{{ form.errors.role }}</span>
                </div>
            </div>
        </Card>

        <Card v-if="showPassword">
            <div class="grid gap-5">
                <div>
                    <h2 class="text-lg font-bold text-diamond-text">Password awal</h2>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <Input
                        v-model="form.password"
                        :error="form.errors.password"
                        autocomplete="new-password"
                        label="Password"
                        type="password"
                    />

                    <Input
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        label="Konfirmasi password"
                        type="password"
                    />
                </div>
            </div>
        </Card>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Button :disabled="form.processing" type="submit">
                {{ submitLabel }}
            </Button>
        </div>
    </form>
</template>
