<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    ArrowLeftOutlined,
    DeleteOutlined,
    EditOutlined,
    SendOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LeadFormModal from '@/Components/LeadFormModal.vue';
import { statusColors, statusLabels, statusOptions } from '@/leadStatus';
import { platformColors, platformLabels } from '@/leadPlatform';

dayjs.extend(relativeTime);

const props = defineProps({
    lead: Object,
    // Newest first: { id, body, author, created_at, can_delete }.
    notes: Array,
});

const isDeleted = computed(() => !!props.lead.deleted_at);

const sourceLabels = {
    landing_page: 'Landing page',
    manual: 'Added manually',
    outbound: 'Outbound',
};

const formatDateTime = (value) => new Date(value).toLocaleString();

// Back to the exact list the admin came from (search, filters, page), if the Leads page recorded one.
const backUrl = (() => {
    try {
        const saved = window.sessionStorage.getItem('analytica-leads-list-url');

        return saved?.startsWith('/admin/leads') ? saved : route('admin.leads');
    } catch {
        return route('admin.leads');
    }
})();

const showEditModal = ref(false);

function updateStatus(newStatus) {
    router.patch(
        route('admin.leads.status', props.lead.id),
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => message.success(`Marked as ${statusLabels[newStatus]}.`),
            onError: () => message.error('Could not update the status.'),
        },
    );
}

function deleteLead() {
    router.delete(route('admin.leads.destroy', props.lead.id), { preserveScroll: true });
}

function restoreLead() {
    router.patch(route('admin.leads.restore', props.lead.id), {}, { preserveScroll: true });
}

const noteForm = useForm({ body: '' });

function addNote() {
    noteForm.post(route('admin.leads.notes.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => noteForm.reset(),
    });
}

function deleteNote(note) {
    router.delete(route('admin.leads.notes.destroy', [props.lead.id, note.id]), {
        preserveScroll: true,
        onError: () => message.error('Could not delete the note.'),
    });
}
</script>

<template>
    <Head :title="lead.name" />

    <AuthenticatedLayout :title="lead.name">
        <a-flex justify="space-between" align="center" wrap="wrap" gap="middle" style="margin-bottom: 16px">
            <Link :href="backUrl">
                <ArrowLeftOutlined /> Back to leads
            </Link>

            <a-space v-if="!isDeleted" wrap>
                <a-button @click="showEditModal = true">
                    <template #icon><EditOutlined /></template>
                    Edit
                </a-button>
                <a-popconfirm
                    :title="`Delete ${lead.name}?`"
                    description="You can restore it from the Deleted tab."
                    ok-text="Delete"
                    :ok-button-props="{ danger: true }"
                    placement="bottomRight"
                    @confirm="deleteLead"
                >
                    <a-button danger>
                        <template #icon><DeleteOutlined /></template>
                        Delete
                    </a-button>
                </a-popconfirm>
            </a-space>
        </a-flex>

        <a-alert
            v-if="isDeleted"
            type="warning"
            show-icon
            message="This lead is in Deleted"
            :description="`Deleted on ${formatDateTime(lead.deleted_at)}. Restore it to edit it or add notes.`"
            style="margin-bottom: 16px"
        >
            <template #action>
                <a-button size="small" @click="restoreLead">
                    <template #icon><UndoOutlined /></template>
                    Restore
                </a-button>
            </template>
        </a-alert>

        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :lg="10">
                <a-card title="Details">
                    <a-descriptions :column="1" size="small">
                        <a-descriptions-item label="Status">
                            <a-tag v-if="isDeleted" :color="statusColors[lead.status]">
                                {{ statusLabels[lead.status] }}
                            </a-tag>
                            <a-select
                                v-else
                                :value="lead.status"
                                size="small"
                                style="width: 150px"
                                @change="updateStatus"
                            >
                                <a-select-option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                    <a-badge :color="statusColors[opt.value]" :text="opt.label" />
                                </a-select-option>
                            </a-select>
                        </a-descriptions-item>
                        <a-descriptions-item label="Email">
                            <a-typography-link v-if="lead.email" :href="`mailto:${lead.email}`" copyable>
                                {{ lead.email }}
                            </a-typography-link>
                            <a-typography-text v-else type="secondary">Not provided</a-typography-text>
                        </a-descriptions-item>
                        <a-descriptions-item label="Phone">
                            <a-typography-link v-if="lead.phone" :href="`tel:${lead.phone}`">
                                {{ lead.phone }}
                            </a-typography-link>
                            <a-typography-text v-else type="secondary">Not provided</a-typography-text>
                        </a-descriptions-item>
                        <a-descriptions-item label="Company">
                            <span v-if="lead.company">{{ lead.company }}</span>
                            <a-typography-text v-else type="secondary">Not provided</a-typography-text>
                        </a-descriptions-item>
                        <a-descriptions-item label="Platform">
                            <a-tag :color="platformColors[lead.platform]">
                                {{ platformLabels[lead.platform] ?? lead.platform }}
                            </a-tag>
                        </a-descriptions-item>
                        <a-descriptions-item label="Source">
                            {{ sourceLabels[lead.source] ?? lead.source }}
                        </a-descriptions-item>
                        <a-descriptions-item label="Received">
                            {{ formatDateTime(lead.created_at) }}
                        </a-descriptions-item>
                        <a-descriptions-item label="Last updated">
                            {{ formatDateTime(lead.updated_at) }}
                        </a-descriptions-item>
                    </a-descriptions>
                </a-card>

                <a-card title="Message" style="margin-top: 16px">
                    <div v-if="lead.message" class="pre-wrap">{{ lead.message }}</div>
                    <a-typography-text v-else type="secondary">No message.</a-typography-text>
                </a-card>
            </a-col>

            <a-col :xs="24" :lg="14">
                <a-card :title="`Notes (${notes.length})`">
                    <a-form v-if="!isDeleted" layout="vertical" @submit.prevent="addNote">
                        <a-form-item
                            :validate-status="noteForm.errors.body ? 'error' : ''"
                            :help="noteForm.errors.body"
                        >
                            <a-textarea
                                v-model:value="noteForm.body"
                                :rows="3"
                                :maxlength="5000"
                                placeholder="Add a note: a call, an email, what was agreed... (Ctrl+Enter to save)"
                                @keydown.ctrl.enter.prevent="addNote"
                                @keydown.meta.enter.prevent="addNote"
                            />
                        </a-form-item>
                        <a-flex justify="flex-end" style="margin-top: -8px; margin-bottom: 16px">
                            <a-button
                                type="primary"
                                html-type="submit"
                                :loading="noteForm.processing"
                                :disabled="!noteForm.body.trim()"
                            >
                                <template #icon><SendOutlined /></template>
                                Add note
                            </a-button>
                        </a-flex>
                    </a-form>

                    <a-empty v-if="!notes.length" description="No notes yet." />

                    <a-timeline v-else class="notes">
                        <a-timeline-item v-for="note in notes" :key="note.id">
                            <a-flex justify="space-between" align="center" gap="small">
                                <a-space :size="6" wrap>
                                    <a-typography-text strong>
                                        {{ note.author ?? 'Former admin' }}
                                    </a-typography-text>
                                    <a-tooltip :title="formatDateTime(note.created_at)">
                                        <a-typography-text type="secondary">
                                            {{ dayjs(note.created_at).fromNow() }}
                                        </a-typography-text>
                                    </a-tooltip>
                                </a-space>

                                <a-popconfirm
                                    v-if="note.can_delete && !isDeleted"
                                    title="Delete this note?"
                                    ok-text="Delete"
                                    :ok-button-props="{ danger: true }"
                                    placement="left"
                                    @confirm="deleteNote(note)"
                                >
                                    <a-button type="text" size="small" danger>
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-popconfirm>
                            </a-flex>
                            <div class="pre-wrap note-body">{{ note.body }}</div>
                        </a-timeline-item>
                    </a-timeline>
                </a-card>
            </a-col>
        </a-row>

        <LeadFormModal v-model:open="showEditModal" :lead="lead" />
    </AuthenticatedLayout>
</template>

<style scoped>
.pre-wrap {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.note-body {
    margin-top: 4px;
}

.notes {
    margin-top: 8px;
}
</style>
