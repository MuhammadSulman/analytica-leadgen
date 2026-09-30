<script setup>
import { MailOutlined } from '@ant-design/icons-vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout
        title="Forgot password"
        subtitle="Enter your email and we'll send you a link to choose a new password."
    >
        <Head title="Forgot Password" />

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

            <a-button
                type="primary"
                html-type="submit"
                size="large"
                block
                :loading="form.processing"
            >
                Email Password Reset Link
            </a-button>

            <div style="margin-top: 16px; text-align: center">
                <Link :href="route('login')">Back to log in</Link>
            </div>
        </a-form>
    </GuestLayout>
</template>
