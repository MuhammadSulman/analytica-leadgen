<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const logout = () => {
    router.post(route('logout'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout
        title="Verify your email"
        subtitle="Thanks for signing up! Please verify your email address by clicking the link we just emailed to you. If you didn't receive it, we'll gladly send you another."
    >
        <Head title="Email Verification" />

        <a-alert
            v-if="verificationLinkSent"
            message="A new verification link has been sent to the email address you provided during registration."
            type="success"
            show-icon
            style="margin-bottom: 16px"
        />

        <a-flex justify="space-between" align="center">
            <a-button
                type="primary"
                :loading="form.processing"
                @click="submit"
            >
                Resend Verification Email
            </a-button>

            <a-button type="link" @click="logout">Log out</a-button>
        </a-flex>
    </GuestLayout>
</template>
