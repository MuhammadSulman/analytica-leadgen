<script setup>
import { ArrowRightOutlined, TeamOutlined } from '@ant-design/icons-vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { datePresets, describeRange } from '@/datePresets';
import { statusColors, statusLabels, statusOptions } from '@/leadStatus';
import { platformColors, platformLabels, platformOptions } from '@/leadPlatform';

const props = defineProps({
    // The date-received period the numbers cover: { from, to } as 'YYYY-MM-DD', or nulls for all time.
    filters: Object,
    totalLeads: Number,
    leadsByStatus: Object,
    leadsByPlatform: Object,
    recentLeads: Array,
});

const presets = datePresets();
const loading = ref(false);

const dateRange = computed(() =>
    props.filters.from || props.filters.to ? [props.filters.from, props.filters.to] : null,
);

const periodLabel = computed(() => describeRange(props.filters.from, props.filters.to));

// Only set params go in the URL, so "All time" is plain /dashboard.
const periodParams = computed(() =>
    Object.fromEntries(Object.entries(props.filters).filter(([, value]) => value)),
);

function onPeriodChange(range) {
    router.get(
        route('dashboard'),
        Object.fromEntries(
            Object.entries({ from: range?.[0], to: range?.[1] }).filter(([, value]) => value),
        ),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        },
    );
}

const countFor = (status) => props.leadsByStatus?.[status] ?? 0;
const platformCountFor = (platform) => props.leadsByPlatform?.[platform] ?? 0;
const platformPercent = (platform) =>
    props.totalLeads ? Math.round((platformCountFor(platform) / props.totalLeads) * 100) : 0;

const columns = [
    { title: 'Name', dataIndex: 'name', key: 'name' },
    { title: 'Company', dataIndex: 'company', key: 'company' },
    { title: 'Platform', dataIndex: 'platform', key: 'platform' },
    { title: 'Status', dataIndex: 'status', key: 'status' },
    { title: 'Received', dataIndex: 'created_at', key: 'created_at' },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout title="Dashboard">
        <a-flex
            justify="space-between"
            align="center"
            wrap="wrap"
            gap="middle"
            style="margin-bottom: 16px"
        >
            <a-typography-text type="secondary">
                Leads received: <a-typography-text strong>{{ periodLabel }}</a-typography-text>
            </a-typography-text>

            <a-range-picker
                :value="dateRange"
                value-format="YYYY-MM-DD"
                :presets="presets"
                :placeholder="['From', 'To']"
                allow-clear
                style="max-width: 100%"
                @change="onPeriodChange"
            />
        </a-flex>

        <a-spin :spinning="loading">
            <a-row :gutter="[16, 16]">
                <a-col :xs="24" :sm="12" :lg="8" :xl="4">
                    <a-card>
                        <a-statistic title="Total Leads" :value="totalLeads">
                            <template #prefix><TeamOutlined /></template>
                        </a-statistic>
                    </a-card>
                </a-col>
                <a-col
                    v-for="opt in statusOptions"
                    :key="opt.value"
                    :xs="12"
                    :sm="12"
                    :lg="8"
                    :xl="4"
                >
                    <a-card>
                        <a-statistic :value="countFor(opt.value)">
                            <template #title>
                                <a-badge :color="statusColors[opt.value]" :text="opt.label" />
                            </template>
                        </a-statistic>
                    </a-card>
                </a-col>
            </a-row>

            <a-card title="Leads by Platform" style="margin-top: 16px">
                <a-row :gutter="[32, 16]">
                    <a-col
                        v-for="opt in platformOptions"
                        :key="opt.value"
                        :xs="24"
                        :sm="12"
                        :lg="8"
                    >
                        <a-flex justify="space-between">
                            <a-tag :color="platformColors[opt.value]">{{ opt.label }}</a-tag>
                            <a-typography-text strong>{{ platformCountFor(opt.value) }}</a-typography-text>
                        </a-flex>
                        <a-progress :percent="platformPercent(opt.value)" size="small" />
                    </a-col>
                </a-row>
            </a-card>

            <a-card title="Recent Leads" style="margin-top: 16px">
                <template #extra>
                    <Link :href="route('admin.leads', periodParams)">
                        View all <ArrowRightOutlined />
                    </Link>
                </template>

                <a-table
                    :columns="columns"
                    :data-source="recentLeads"
                    :pagination="false"
                    row-key="id"
                    :scroll="{ x: 600 }"
                >
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'name'">
                            <Link :href="route('admin.leads.show', record.id)">
                            <div>{{ record.name }}</div>
                        </Link>
                            <a-typography-text type="secondary">
                                {{ record.email }}
                            </a-typography-text>
                        </template>
                        <template v-else-if="column.key === 'platform'">
                            <a-tag :color="platformColors[record.platform]">
                                {{ platformLabels[record.platform] ?? record.platform }}
                            </a-tag>
                        </template>
                        <template v-else-if="column.key === 'status'">
                            <a-tag :color="statusColors[record.status]">
                                {{ statusLabels[record.status] }}
                            </a-tag>
                        </template>
                        <template v-else-if="column.key === 'created_at'">
                            {{ new Date(record.created_at).toLocaleString() }}
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-spin>
    </AuthenticatedLayout>
</template>
