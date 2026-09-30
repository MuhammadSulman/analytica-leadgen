<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
        onSuccess: () => message.success('Profile saved.'),
    });
};
</script>

<template>
    <a-card title="Profile Information">
        <a-typography-paragraph type="secondary">
            Update your account's profile information and email address.
        </a-typography-paragraph>

        <a-form layout="vertical" @submit.prevent="submit">
            <a-form-item
                label="Name"
                :validate-status="form.errors.name ? 'error' : ''"
                :help="form.errors.name"
            >
                <a-input v-model:value="form.name" autocomplete="name" />
            </a-form-item>

            <a-form-item
                label="Email"
                :validate-status="form.errors.email ? 'error' : ''"
                :help="form.errors.email"
            >
                <a-input
                    v-model:value="form.email"
                    type="email"
                    autocomplete="username"
                />
            </a-form-item>

            <a-alert
                v-if="mustVerifyEmail && user.email_verified_at === null"
                type="warning"
                show-icon
                style="margin-bottom: 24px"
            >
                <template #message>
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="ant-btn ant-btn-link"
                        style="padding: 0; height: auto"
                    >
                        Re-send the verification email.
                    </Link>
                </template>
                <template
                    v-if="status === 'verification-link-sent'"
                    #description
                >
                    A new verification link has been sent to your email
                    address.
                </template>
            </a-alert>

            <a-button
                type="primary"
                html-type="submit"
                :loading="form.processing"
            >
                Save
            </a-button>
        </a-form>
    </a-card>
</template>
