<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    DeleteOutlined,
    DownOutlined,
    DownloadOutlined,
    EditOutlined,
    PlusOutlined,
    UndoOutlined,
} from '@ant-design/icons-vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LeadFormModal from '@/Components/LeadFormModal.vue';
import { datePresets } from '@/datePresets';
import { statusColors, statusLabels, statusOptions } from '@/leadStatus';
import { platformColors, platformLabels, platformOptions } from '@/leadPlatform';

const props = defineProps({
    // A Laravel paginator: { data, current_page, per_page, total, ... }.
    leads: Object,
    // The search, filters and sort the server applied, with defaults filled in.
    filters: Object,
    counts: Object,
});

const pageSizeOptions = ['10', '20', '50', '100'];
const LIST_URL_KEY = 'analytica-leads-list-url';

// 'active' shows current leads; 'deleted' shows soft-deleted leads that can be restored.
const showingDeleted = computed(() => props.filters.view === 'deleted');

const search = ref(props.filters.search);
const loading = ref(false);

// Reload the list from the server with the current options plus `changes`.
// Anything except moving between pages starts again from page 1.
function load(changes = {}) {
    const query = {
        view: props.filters.view,
        search: search.value.trim(),
        platforms: props.filters.platforms,
        statuses: props.filters.statuses,
        from: props.filters.from,
        to: props.filters.to,
        sort: props.filters.sort,
        direction: props.filters.direction,
        ...changes,
    };

    // Keep the URL tidy: drop empty values so a plain list is just /admin/leads.
    const params = Object.fromEntries(
        Object.entries(query).filter(([, value]) =>
            Array.isArray(value) ? value.length : value !== undefined && value !== null && value !== '',
        ),
    );

    router.get(route('admin.leads'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onSuccess: rememberListUrl,
        onFinish: () => (loading.value = false),
    });
}

// Remember this exact list (tab, search, filters, page) so the lead page's Back link returns to it.
function rememberListUrl() {
    try {
        window.sessionStorage.setItem(LIST_URL_KEY, window.location.pathname + window.location.search);
    } catch {
        // Storage blocked; Back then goes to the plain list.
    }
}

onMounted(rememberListUrl);

// Search as the admin types, without a request per keystroke.
let searchTimer;

watch(search, (value) => {
    clearTimeout(searchTimer);

    if (value.trim() === props.filters.search) {
        return;
    }

    searchTimer = setTimeout(() => load(), 350);
});

onBeforeUnmount(() => clearTimeout(searchTimer));

function changeView(view) {
    selectedIds.value = [];
    // Column filters and sorting belong to one tab; the search carries over.
    load({ view, platforms: [], statuses: [], sort: undefined, direction: undefined });
}

function onTableChange(page, tableFilters, sorter, { action }) {
    const changes = {
        platforms: tableFilters.platform ?? [],
        statuses: tableFilters.status ?? [],
        sort: sorter.order ? sorter.columnKey : undefined,
        direction: sorter.order ? (sorter.order === 'ascend' ? 'asc' : 'desc') : undefined,
    };

    if (page.pageSize !== props.leads.per_page) {
        // The server remembers the new size for this admin's later visits.
        changes.per_page = page.pageSize;
    } else if (action === 'paginate') {
        changes.page = page.current;
    }

    load(changes);
}

const pagination = computed(() => ({
    current: props.leads.current_page,
    pageSize: props.leads.per_page,
    total: props.leads.total,
    pageSizeOptions,
    showSizeChanger: true,
    showTotal: (total, [from, to]) => `${from}–${to} of ${total}`,
}));

const sortOrderFor = (key) =>
    props.filters.sort === key ? (props.filters.direction === 'asc' ? 'ascend' : 'descend') : null;

const isFiltered = computed(
    () =>
        props.filters.search !== '' ||
        props.filters.platforms.length > 0 ||
        props.filters.statuses.length > 0 ||
        !!props.filters.from ||
        !!props.filters.to,
);

// Date received filter. Values are 'YYYY-MM-DD' strings, matching what the server expects.
const dateRange = computed(() =>
    props.filters.from || props.filters.to ? [props.filters.from, props.filters.to] : null,
);

const presets = datePresets();

function onDateRangeChange(range) {
    load({ from: range?.[0], to: range?.[1] });
}

// The export uses the same search, filters and sort as the list, so the file matches what's on screen.
const exportUrl = computed(() =>
    route('admin.leads.export', {
        search: props.filters.search || undefined,
        platforms: props.filters.platforms,
        statuses: props.filters.statuses,
        from: props.filters.from || undefined,
        to: props.filters.to || undefined,
        sort: props.filters.sort,
        direction: props.filters.direction,
    }),
);

// Selections are kept while moving between pages, so bulk actions can span several pages.
const selectedIds = ref([]);

const rowSelection = computed(() => ({
    selectedRowKeys: selectedIds.value,
    preserveSelectedRowKeys: true,
    onChange: (keys) => {
        selectedIds.value = keys;
    },
}));

const bulkProcessing = ref(false);

function runBulkAction(method, routeName, extra = {}) {
    router.visit(route(routeName), {
        method,
        data: { ids: selectedIds.value, ...extra },
        preserveScroll: true,
        onStart: () => (bulkProcessing.value = true),
        onFinish: () => (bulkProcessing.value = false),
        onSuccess: () => (selectedIds.value = []),
        onError: () => message.error('Could not update the selected leads.'),
    });
}

const bulkDelete = () => runBulkAction('delete', 'admin.leads.bulk-destroy');
const bulkSetStatus = ({ key }) => runBulkAction('patch', 'admin.leads.bulk-status', { status: key });
const bulkRestore = () => runBulkAction('patch', 'admin.leads.bulk-restore');
const bulkForceDelete = () => runBulkAction('delete', 'admin.leads.bulk-force-destroy');

const viewOptions = computed(() => [
    { value: 'active', label: `Leads (${props.counts.active})` },
    { value: 'deleted', label: `Deleted (${props.counts.deleted})` },
]);

const showLeadModal = ref(false);
const editingLead = ref(null);

function openAddLead() {
    editingLead.value = null;
    showLeadModal.value = true;
}

function openEditLead(lead) {
    editingLead.value = lead;
    showLeadModal.value = true;
}

function deleteLead(lead) {
    router.delete(route('admin.leads.destroy', lead.id), {
        preserveScroll: true,
        onError: () => message.error('Could not delete the lead.'),
    });
}

function restoreLead(lead) {
    router.patch(route('admin.leads.restore', lead.id), {}, {
        preserveScroll: true,
        onError: () => message.error('Could not restore the lead.'),
    });
}

function forceDeleteLead(lead) {
    router.delete(route('admin.leads.force-destroy', lead.id), {
        preserveScroll: true,
        onError: () => message.error('Could not delete the lead.'),
    });
}

function updateStatus(lead, newStatus) {
    router.patch(
        route('admin.leads.status', lead.id),
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => message.success(`${lead.name} marked as ${statusLabels[newStatus]}.`),
            onError: () => message.error('Could not update the status.'),
        },
    );
}

const columns = computed(() => [
    {
        title: 'Name',
        dataIndex: 'name',
        key: 'name',
        sorter: true,
        sortOrder: sortOrderFor('name'),
        sortDirections: ['ascend', 'descend'],
    },
    { title: 'Company', dataIndex: 'company', key: 'company' },
    { title: 'Phone', dataIndex: 'phone', key: 'phone' },
    {
        title: 'Platform',
        key: 'platform',
        width: 120,
        filters: platformOptions.map((opt) => ({ text: opt.label, value: opt.value })),
        filteredValue: props.filters.platforms.length ? props.filters.platforms : null,
    },
    { title: 'Message', dataIndex: 'message', key: 'message', ellipsis: true },
    {
        title: 'Status',
        key: 'status',
        width: 170,
        filters: statusOptions.map((opt) => ({ text: opt.label, value: opt.value })),
        filteredValue: props.filters.statuses.length ? props.filters.statuses : null,
    },
    {
        title: 'Received',
        dataIndex: 'created_at',
        key: 'created_at',
        width: 130,
        sorter: true,
        sortOrder: sortOrderFor('created_at'),
        // Dates start newest-first; a third click goes back to the default order.
        sortDirections: ['descend', 'ascend'],
    },
    ...(showingDeleted.value
        ? [
              {
                  title: 'Deleted',
                  dataIndex: 'deleted_at',
                  key: 'deleted_at',
                  width: 130,
                  sorter: true,
                  sortOrder: sortOrderFor('deleted_at'),
                  sortDirections: ['descend', 'ascend'],
              },
          ]
        : []),
    { title: '', key: 'actions', width: 96, fixed: 'right', align: 'center' },
]);
</script>

<template>
    <Head title="Leads" />

    <AuthenticatedLayout title="Leads">
        <a-card>
            <a-flex
                justify="space-between"
                align="center"
                wrap="wrap"
                gap="middle"
                style="margin-bottom: 16px"
            >
                <a-segmented :value="filters.view" :options="viewOptions" @change="changeView" />

                <a-space wrap>
                    <a-input-search
                        v-model:value="search"
                        placeholder="Search name, email, company or phone"
                        allow-clear
                        style="width: 320px; max-width: 100%"
                    />
                    <a-range-picker
                        :value="dateRange"
                        value-format="YYYY-MM-DD"
                        :presets="presets"
                        :placeholder="['Received from', 'Received to']"
                        allow-clear
                        style="max-width: 100%"
                        @change="onDateRangeChange"
                    />
                    <a-tooltip
                        v-if="!showingDeleted"
                        :title="isFiltered
                            ? `Exports the ${leads.total} leads matching your search, dates and filters`
                            : 'Exports all leads'"
                    >
                        <a-button :href="exportUrl" :disabled="!leads.total">
                            <template #icon><DownloadOutlined /></template>
                            {{ isFiltered ? `Export CSV (${leads.total})` : 'Export CSV' }}
                        </a-button>
                    </a-tooltip>
                    <a-button type="primary" @click="openAddLead">
                        <template #icon><PlusOutlined /></template>
                        Add lead
                    </a-button>
                </a-space>
            </a-flex>

            <a-flex
                v-if="selectedIds.length"
                class="bulk-bar"
                justify="space-between"
                align="center"
                wrap="wrap"
                gap="small"
            >
                <a-space>
                    <a-typography-text strong>
                        {{ selectedIds.length }} selected
                    </a-typography-text>
                    <a-button type="link" size="small" @click="selectedIds = []">
                        Clear
                    </a-button>
                </a-space>

                <a-space wrap>
                    <template v-if="showingDeleted">
                        <a-button :loading="bulkProcessing" @click="bulkRestore">
                            <template #icon><UndoOutlined /></template>
                            Restore
                        </a-button>
                        <a-popconfirm
                            :title="`Permanently delete ${selectedIds.length} ${selectedIds.length === 1 ? 'lead' : 'leads'}?`"
                            description="This can't be undone."
                            ok-text="Delete forever"
                            :ok-button-props="{ danger: true }"
                            placement="bottomRight"
                            @confirm="bulkForceDelete"
                        >
                            <a-button danger :loading="bulkProcessing">
                                <template #icon><DeleteOutlined /></template>
                                Delete forever
                            </a-button>
                        </a-popconfirm>
                    </template>
                    <a-button
                        v-if="!showingDeleted"
                        :href="route('admin.leads.export', { ids: selectedIds })"
                    >
                        <template #icon><DownloadOutlined /></template>
                        Export
                    </a-button>
                    <a-dropdown v-if="!showingDeleted" :trigger="['click']">
                        <a-button :loading="bulkProcessing">
                            Set status <DownOutlined />
                        </a-button>
                        <template #overlay>
                            <a-menu @click="bulkSetStatus">
                                <a-menu-item v-for="opt in statusOptions" :key="opt.value">
                                    <a-badge :color="statusColors[opt.value]" :text="opt.label" />
                                </a-menu-item>
                            </a-menu>
                        </template>
                    </a-dropdown>
                    <a-popconfirm
                        v-if="!showingDeleted"
                        :title="`Delete ${selectedIds.length} ${selectedIds.length === 1 ? 'lead' : 'leads'}?`"
                        description="You can restore them from the Deleted tab."
                        ok-text="Delete"
                        :ok-button-props="{ danger: true }"
                        placement="bottomRight"
                        @confirm="bulkDelete"
                    >
                        <a-button danger :loading="bulkProcessing">
                            <template #icon><DeleteOutlined /></template>
                            Delete
                        </a-button>
                    </a-popconfirm>
                </a-space>
            </a-flex>

            <a-table
                :row-selection="rowSelection"
                :columns="columns"
                :data-source="leads.data"
                :loading="loading"
                row-key="id"
                :pagination="pagination"
                :scroll="{ x: 900 }"
                @change="onTableChange"
            >
                <template #emptyText>
                    <a-empty
                        :description="isFiltered
                            ? 'No leads match your search, dates and filters.'
                            : showingDeleted
                              ? 'No deleted leads.'
                              : 'No leads yet. Submissions from the landing page will appear here.'"
                    />
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'name'">
                        <Link :href="route('admin.leads.show', record.id)" class="lead-name">
                            {{ record.name }}
                        </Link>
                        <a-typography-link v-if="record.email" :href="`mailto:${record.email}`">
                            {{ record.email }}
                        </a-typography-link>
                    </template>
                    <template v-else-if="column.key === 'platform'">
                        <a-tag :color="platformColors[record.platform]">
                            {{ platformLabels[record.platform] ?? record.platform }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'message'">
                        <a-tooltip v-if="record.message" :title="record.message">
                            {{ record.message }}
                        </a-tooltip>
                    </template>
                    <template v-else-if="column.key === 'status' && showingDeleted">
                        <a-tag :color="statusColors[record.status]">
                            {{ statusLabels[record.status] }}
                        </a-tag>
                    </template>
                    <template v-else-if="column.key === 'status'">
                        <a-select
                            :value="record.status"
                            style="width: 140px"
                            @change="(val) => updateStatus(record, val)"
                        >
                            <a-select-option
                                v-for="opt in statusOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                <a-badge :color="statusColors[opt.value]" :text="opt.label" />
                            </a-select-option>
                        </a-select>
                    </template>
                    <template v-else-if="column.key === 'created_at'">
                        {{ new Date(record.created_at).toLocaleDateString() }}
                    </template>
                    <template v-else-if="column.key === 'deleted_at'">
                        {{ new Date(record.deleted_at).toLocaleDateString() }}
                    </template>
                    <template v-else-if="column.key === 'actions' && showingDeleted">
                        <a-space :size="0">
                            <a-tooltip title="Restore lead">
                                <a-button type="text" @click="restoreLead(record)">
                                    <template #icon><UndoOutlined /></template>
                                </a-button>
                            </a-tooltip>
                            <a-popconfirm
                                :title="`Permanently delete ${record.name}?`"
                                description="This can't be undone."
                                ok-text="Delete forever"
                                :ok-button-props="{ danger: true }"
                                placement="left"
                                @confirm="forceDeleteLead(record)"
                            >
                                <a-tooltip title="Delete forever">
                                    <a-button type="text" danger>
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-tooltip>
                            </a-popconfirm>
                        </a-space>
                    </template>
                    <template v-else-if="column.key === 'actions'">
                        <a-space :size="0">
                            <a-tooltip title="Edit lead">
                                <a-button type="text" @click="openEditLead(record)">
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                            </a-tooltip>
                            <a-popconfirm
                                :title="`Delete ${record.name}?`"
                                description="You can restore it from the Deleted tab."
                                ok-text="Delete"
                                :ok-button-props="{ danger: true }"
                                placement="left"
                                @confirm="deleteLead(record)"
                            >
                                <a-tooltip title="Delete lead">
                                    <a-button type="text" danger>
                                        <template #icon><DeleteOutlined /></template>
                                    </a-button>
                                </a-tooltip>
                            </a-popconfirm>
                        </a-space>
                    </template>
                </template>
            </a-table>
        </a-card>

        <LeadFormModal v-model:open="showLeadModal" :lead="editingLead" />
    </AuthenticatedLayout>
</template>

<style scoped>
.lead-name {
    display: block;
    font-weight: 500;
}

.bulk-bar {
    margin-bottom: 12px;
    padding: 8px 12px;
    border: 1px solid var(--app-border);
    border-radius: 8px;
    background: var(--app-bg);
}
</style>
