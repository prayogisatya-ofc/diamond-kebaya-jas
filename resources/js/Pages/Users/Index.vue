<script setup>
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { AtSign, Mail, Pencil, ShieldCheck, Trash2, UserPlus, Users } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import EmptyState from '@/Components/EmptyState.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
})

const summary = computed(() => {
    const activeUsers = props.users.filter((user) => user.is_active)

    return [
        {
            label: 'Total user',
            value: props.users.length,
            helper: 'Akun terdaftar',
        },
        {
            label: 'User aktif',
            value: activeUsers.length,
            helper: 'Bisa login',
        },
        {
            label: 'Owner',
            value: props.users.filter((user) => user.role === 'owner').length,
            helper: 'Akses penuh',
        },
        {
            label: 'Staff',
            value: props.users.filter((user) => user.role === 'staff').length,
            helper: 'Akses operasional',
        },
    ]
})

function initials(name) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((word) => word.charAt(0).toUpperCase())
        .join('')
}

function roleLabel(role) {
    return {
        owner: 'Owner',
        staff: 'Staff',
    }[role] ?? role
}

function formattedDate(value) {
    if (!value) {
        return '-'
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value))
}

const { confirmAction } = useConfirm()

async function destroyUser(user) {
    const confirmed = await confirmAction({
        title: 'Hapus user?',
        message: `User ${user.name} akan dihapus permanen dari sistem.`,
        confirmLabel: 'Ya, hapus user',
    })

    if (!confirmed) {
        return
    }

    router.delete(route('users.destroy', user.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Manajemen User" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Owner"
            title="Manajemen user"
        >
            <template #actions>
                <Button :href="route('users.create')">
                    <UserPlus :size="18" />
                    Tambah user
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <Card v-for="item in summary" :key="item.label" class="min-h-32">
                <div class="flex h-full items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-diamond-muted">{{ item.label }}</p>
                        <p class="mt-3 text-3xl font-bold text-diamond-text">{{ item.value }}</p>
                        <p class="mt-1 text-sm text-diamond-muted">{{ item.helper }}</p>
                    </div>
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-diamond-primary">
                        <Users :size="20" />
                    </div>
                </div>
            </Card>
        </div>

        <div v-if="users.length > 0" class="grid gap-3 lg:hidden">
            <article
                v-for="managedUser in users"
                :key="managedUser.id"
                class="rounded-3xl border border-white/70 bg-white p-4"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary text-base font-bold text-white">
                        {{ initials(managedUser.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-base font-bold text-diamond-text">{{ managedUser.name }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase text-diamond-muted">{{ roleLabel(managedUser.role) }}</p>
                            </div>
                            <StatusBadge :value="managedUser.is_active ? 'active' : 'inactive'" type="user" />
                        </div>

                        <div class="mt-4 grid gap-2 text-sm text-diamond-muted">
                            <p class="flex min-w-0 items-center gap-2">
                                <Mail :size="16" class="shrink-0" />
                                <span class="truncate">{{ managedUser.email }}</span>
                            </p>
                            <p class="flex min-w-0 items-center gap-2">
                                <AtSign :size="16" class="shrink-0" />
                                <span class="truncate">{{ managedUser.username || '-' }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <Button :href="route('users.edit', managedUser.id)" variant="secondary">
                        <Pencil :size="16" />
                        Edit
                    </Button>
                    <Button variant="danger" type="button" @click="destroyUser(managedUser)">
                        <Trash2 :size="16" />
                        Hapus
                    </Button>
                </div>
            </article>
        </div>

        <Card v-if="users.length > 0" class="hidden overflow-hidden lg:block" :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="border-b border-diamond-border bg-diamond-surface-soft text-xs uppercase text-diamond-muted">
                        <tr>
                            <th class="px-6 py-4 font-bold">User</th>
                            <th class="px-6 py-4 font-bold">Username</th>
                            <th class="px-6 py-4 font-bold">Email</th>
                            <th class="px-6 py-4 font-bold">Role</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold">Dibuat</th>
                            <th class="px-6 py-4 text-right font-bold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-diamond-border">
                        <tr v-for="managedUser in users" :key="managedUser.id" class="bg-white">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-diamond-primary-soft text-sm font-bold text-diamond-primary">
                                        {{ initials(managedUser.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-diamond-text">{{ managedUser.name }}</p>
                                        <p class="mt-1 text-xs text-diamond-muted">{{ roleLabel(managedUser.role) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-diamond-muted">{{ managedUser.username || '-' }}</td>
                            <td class="px-6 py-4 text-diamond-muted">{{ managedUser.email }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2 rounded-xl bg-diamond-surface-soft px-3 py-2 text-xs font-bold text-diamond-text">
                                    <ShieldCheck :size="14" />
                                    {{ roleLabel(managedUser.role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <StatusBadge :value="managedUser.is_active ? 'active' : 'inactive'" type="user" />
                            </td>
                            <td class="px-6 py-4 text-diamond-muted">{{ formattedDate(managedUser.created_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <Button :href="route('users.edit', managedUser.id)" variant="secondary">
                                        <Pencil :size="16" />
                                        Edit
                                    </Button>
                                    <Button variant="danger" type="button" @click="destroyUser(managedUser)">
                                        <Trash2 :size="16" />
                                        Hapus
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <EmptyState
            v-else
            description="Tambahkan owner atau staff agar akses aplikasi bisa dibagi sesuai tanggung jawab."
            title="Belum ada user"
        />
    </section>
</template>
