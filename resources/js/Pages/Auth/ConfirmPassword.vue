<script setup>
import { LockOutlined } from '@ant-design/icons-vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout
        title="Confirm password"
        subtitle="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm Password" />

        <a-form layout="vertical" @submit.prevent="submit">
            <a-form-item
                label="Password"
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    v-model:value="form.password"
                    size="large"
                    autocomplete="current-password"
                    autofocus
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
                Confirm
            </a-button>
        </a-form>
    </GuestLayout>
</template>
