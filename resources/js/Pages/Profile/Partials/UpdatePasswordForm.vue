<script setup>
import { useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            message.success('Password updated.');
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <a-card title="Update Password">
        <a-typography-paragraph type="secondary">
            Ensure your account is using a long, random password to stay
            secure.
        </a-typography-paragraph>

        <a-form layout="vertical" @submit.prevent="updatePassword">
            <a-form-item
                label="Current Password"
                :validate-status="form.errors.current_password ? 'error' : ''"
                :help="form.errors.current_password"
            >
                <a-input-password
                    ref="currentPasswordInput"
                    v-model:value="form.current_password"
                    autocomplete="current-password"
                />
            </a-form-item>

            <a-form-item
                label="New Password"
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    ref="passwordInput"
                    v-model:value="form.password"
                    autocomplete="new-password"
                />
            </a-form-item>

            <a-form-item
                label="Confirm Password"
                :validate-status="form.errors.password_confirmation ? 'error' : ''"
                :help="form.errors.password_confirmation"
            >
                <a-input-password
                    v-model:value="form.password_confirmation"
                    autocomplete="new-password"
                />
            </a-form-item>

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
