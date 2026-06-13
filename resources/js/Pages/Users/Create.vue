<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from '@lucide/vue'
import Button from '@/Components/Button.vue'
import PageHeader from '@/Components/PageHeader.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import UserForm from './Partials/Form.vue'

defineOptions({
    layout: AppLayout,
})

defineProps({
    roles: {
        type: Array,
        required: true,
    },
})

const form = useForm({
    name: '',
    username: '',
    email: '',
    role: 'staff',
    is_active: true,
    password: '',
    password_confirmation: '',
})

function submit() {
    form.post(route('users.store'))
}
</script>

<template>
    <Head title="Tambah User" />

    <section class="grid gap-6">
        <PageHeader
            eyebrow="Owner"
            title="Tambah user"
        >
            <template #actions>
                <Button :href="route('users.index')" variant="secondary">
                    <ArrowLeft :size="18" />
                    Kembali
                </Button>
            </template>
        </PageHeader>

        <UserForm :form="form" :roles="roles" submit-label="Simpan user" show-password @submit="submit" />
    </section>
</template>
