<script setup>
import { LockOutlined, MailOutlined } from '@ant-design/icons-vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout title="Reset password">
        <Head title="Reset Password" />

        <a-form layout="vertical" @submit.prevent="submit">
            <a-form-item
                label="Email"
                :validate-status="form.errors.email ? 'error' : ''"
                :help="form.errors.email"
            >
                <a-input
                    v-model:value="form.email"
                    placeholder="you@example.com"
                    type="email"
                    size="large"
                    autocomplete="username"
                >
                    <template #prefix><MailOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item
                label="New Password"
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    v-model:value="form.password"
                    size="large"
                    autocomplete="new-password"
                    autofocus
                >
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <a-form-item
                label="Confirm Password"
                :validate-status="form.errors.password_confirmation ? 'error' : ''"
                :help="form.errors.password_confirmation"
            >
                <a-input-password
                    v-model:value="form.password_confirmation"
                    size="large"
                    autocomplete="new-password"
                >
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <a-button
                type="primary"
                html-type="submit"
                size="large"
                block
                :loading="form.processing"
            >
                Reset Password
            </a-button>
        </a-form>
    </GuestLayout>
</template>
