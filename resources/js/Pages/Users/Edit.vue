<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowLeft, KeyRound } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import Card from '@/Components/Card.vue'
import Input from '@/Components/Input.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { useConfirm } from '@/Composables/useConfirm'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

const props = defineProps({
    managedUser: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
})

const { confirmAction } = useConfirm()

const form = useForm({
    name: props.managedUser.name,
    username: props.managedUser.username ?? '',
    email: props.managedUser.email,
    role: props.managedUser.role,
    is_active: props.managedUser.is_active,
})

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
})

function submit() {
    form.put(route('users.update', props.managedUser.id))
}

async function resetPassword() {
    const confirmed = await confirmAction({
        title: 'Reset password user?',
        message: `Password ${props.managedUser.name} akan diganti dengan password baru yang kamu input.`,
        confirmLabel: 'Ya, reset password',
        tone: 'warning',
    })

    if (!confirmed) {
        return
    }

    passwordForm.post(route('users.password.update', props.managedUser.id), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}
</script>

<template>
    <Head :title="`Edit ${managedUser.name}`" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Owner"
            :title="`Edit ${managedUser.name}`"
        >
            <template #actions>
                <Button :href="route('users.index')" variant="secondary">
                    <ArrowLeft :size="18" />
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <UserForm :form="form" :roles="roles" submit-label="Simpan perubahan" @submit="submit" />

        <form class="max-w-5xl" @submit.prevent="resetPassword">
            <Card>
                <div class="grid gap-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-diamond-accent/15 text-diamond-accent">
                            <KeyRound :size="22" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold text-diamond-text">Reset password</h2>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
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
                            label="Konfirmasi password baru"
                            type="password"
                        />
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Button :disabled="passwordForm.processing" type="submit" variant="accent">
                            <KeyRound :size="18" />
                            Reset password
                        </Button>
                    </div>
                </div>
            </Card>
        </form>
    </section>
</template>
