<script setup>
import { DeleteOutlined } from '@ant-design/icons-vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <a-card title="Delete Account">
        <a-typography-paragraph type="secondary">
            Once your account is deleted, all of its resources and data will
            be permanently deleted. Before deleting your account, please
            download any data or information that you wish to retain.
        </a-typography-paragraph>

        <a-button danger @click="confirmUserDeletion">
            <template #icon><DeleteOutlined /></template>
            Delete Account
        </a-button>

        <a-modal
            :open="confirmingUserDeletion"
            title="Are you sure you want to delete your account?"
            ok-text="Delete Account"
            :ok-button-props="{ danger: true, loading: form.processing }"
            @ok="deleteUser"
            @cancel="closeModal"
        >
            <a-typography-paragraph type="secondary">
                Once your account is deleted, all of its resources and data
                will be permanently deleted. Please enter your password to
                confirm you would like to permanently delete your account.
            </a-typography-paragraph>

            <a-form-item
                :validate-status="form.errors.password ? 'error' : ''"
                :help="form.errors.password"
            >
                <a-input-password
                    ref="passwordInput"
                    v-model:value="form.password"
                    placeholder="Password"
                    @press-enter="deleteUser"
                />
            </a-form-item>
        </a-modal>
    </a-card>
</template>
