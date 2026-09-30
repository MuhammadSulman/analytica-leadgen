<script setup>
import { LockOutlined, MailOutlined, UserOutlined } from '@ant-design/icons-vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout title="Create account">
        <Head title="Register" />

        <a-form layout="vertical" @submit.prevent="submit">
            <a-form-item
                label="Name"
                :validate-status="form.errors.name ? 'error' : ''"
                :help="form.errors.name"
            >
                <a-input
                    v-model:value="form.name"
                    size="large"
                    autocomplete="name"
                    autofocus
                >
                    <template #prefix><UserOutlined /></template>
                </a-input>
            </a-form-item>

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
                label="Password"
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    v-model:value="form.password"
                    size="large"
                    autocomplete="new-password"
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
                Register
            </a-button>

            <div style="margin-top: 16px; text-align: center">
                Already registered?
                <Link :href="route('login')">Log in</Link>
            </div>
        </a-form>
    </GuestLayout>
</template>
