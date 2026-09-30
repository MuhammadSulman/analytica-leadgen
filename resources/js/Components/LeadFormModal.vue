<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { statusColors, statusOptions } from '@/leadStatus';
import { platformOptions } from '@/leadPlatform';

// Add a lead by hand, or edit one when `lead` is given. Used by the leads list and the lead page.
const props = defineProps({
    open: Boolean,
    lead: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['update:open']);

// Freelance platforms don't allow sharing our public link, so admins add those leads by hand.
// Website is only offered when editing, since those leads arrive through the landing page.
const formPlatformOptions = computed(() =>
    props.lead ? platformOptions : platformOptions.filter((opt) => opt.value !== 'website'),
);

const leadForm = useForm({
    name: '',
    email: '',
    company: '',
    phone: '',
    message: '',
    platform: 'upwork',
    status: 'new',
});

// Fill the form each time the modal opens: blank for a new lead, or the lead's current details.
watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        leadForm.reset();
        leadForm.clearErrors();

        if (props.lead) {
            Object.assign(leadForm, {
                name: props.lead.name,
                email: props.lead.email ?? '',
                company: props.lead.company ?? '',
                phone: props.lead.phone ?? '',
                message: props.lead.message ?? '',
                platform: props.lead.platform,
                status: props.lead.status,
            });
        }
    },
);

function saveLead() {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('update:open', false),
    };

    if (props.lead) {
        leadForm.put(route('admin.leads.update', props.lead.id), options);
    } else {
        leadForm.post(route('admin.leads.store'), options);
    }
}

function fieldProps(field) {
    return {
        validateStatus: leadForm.errors[field] ? 'error' : '',
        help: leadForm.errors[field],
    };
}
</script>

<template>
    <a-modal
        :open="open"
        @update:open="(value) => emit('update:open', value)"
        :title="lead ? 'Edit lead' : 'Add lead'"
        :ok-text="lead ? 'Save changes' : 'Save lead'"
        :confirm-loading="leadForm.processing"
        destroy-on-close
        @ok="saveLead"
    >
        <a-form layout="vertical" @submit.prevent="saveLead">
            <a-row :gutter="16">
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Platform" required v-bind="fieldProps('platform')">
                        <a-select v-model:value="leadForm.platform">
                            <a-select-option
                                v-for="opt in formPlatformOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Status" required v-bind="fieldProps('status')">
                        <a-select v-model:value="leadForm.status">
                            <a-select-option
                                v-for="opt in statusOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                <a-badge :color="statusColors[opt.value]" :text="opt.label" />
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Name" required v-bind="fieldProps('name')">
                        <a-input v-model:value="leadForm.name" placeholder="Client name or username" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Email" v-bind="fieldProps('email')">
                        <a-input v-model:value="leadForm.email" type="email" placeholder="Optional" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Company" v-bind="fieldProps('company')">
                        <a-input v-model:value="leadForm.company" />
                    </a-form-item>
                </a-col>
                <a-col :xs="24" :sm="12">
                    <a-form-item label="Phone" v-bind="fieldProps('phone')">
                        <a-input v-model:value="leadForm.phone" />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-form-item label="Message" v-bind="fieldProps('message')">
                <a-textarea
                    v-model:value="leadForm.message"
                    :rows="3"
                    :maxlength="2000"
                    show-count
                    placeholder="What they asked for, job link, etc."
                />
            </a-form-item>
        </a-form>
    </a-modal>
</template>
