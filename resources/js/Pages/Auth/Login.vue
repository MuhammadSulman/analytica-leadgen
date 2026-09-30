<script setup>
import { LockOutlined, LoginOutlined, MailOutlined } from '@ant-design/icons-vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout title="Sign in" subtitle="Log in to manage your leads.">
        <Head title="Log in" />

        <a-alert
            v-if="status"
            :message="status"
            type="success"
            show-icon
            style="margin-bottom: 16px"
        />

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
                    autofocus
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
                    autocomplete="current-password"
                >
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <a-flex justify="space-between" align="center" style="margin-bottom: 24px">
                <a-checkbox v-model:checked="form.remember">
                    Remember me
                </a-checkbox>

                <Link v-if="canResetPassword" :href="route('password.request')">
                    Forgot password?
                </Link>
            </a-flex>

            <a-button
                type="primary"
                html-type="submit"
                size="large"
                block
                :loading="form.processing"
            >
                <template #icon><LoginOutlined /></template>
                Log in
            </a-button>
        </a-form>
    </GuestLayout>
</template>
