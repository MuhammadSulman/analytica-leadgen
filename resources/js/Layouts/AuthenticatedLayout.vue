<script setup>
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { message } from 'ant-design-vue';
import {
    DashboardOutlined,
    LogoutOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import { computed, h, watch } from 'vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const menuItems = [
    { key: 'dashboard', label: 'Dashboard', icon: () => h(DashboardOutlined) },
    { key: 'admin.leads', label: 'Leads', icon: () => h(TeamOutlined) },
    { key: 'profile.edit', label: 'Profile', icon: () => h(UserOutlined) },
];

const selectedKeys = computed(() => {
    // Also match child pages, e.g. a lead's page (admin.leads.show) highlights "Leads".
    const current = menuItems.find(
        (item) => route().current(item.key) || route().current(`${item.key}.*`),
    );

    return current ? [current.key] : [];
});

const initials = computed(() =>
    (user.value?.name ?? '?')
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);

function navigate({ key }) {
    router.visit(route(key));
}

function onUserMenuClick({ key }) {
    if (key === 'logout') {
        router.post(route('logout'));
    } else {
        router.visit(route(key));
    }
}

watch(
    () => page.props.flash?.success,
    (success) => {
        if (success) {
            message.success(success);
        }
    },
    { immediate: true },
);
</script>

<template>
    <a-layout class="app-shell">
        <a-layout-sider
            breakpoint="lg"
            collapsed-width="0"
            theme="dark"
            :width="220"
        >
            <Link :href="route('dashboard')" class="brand">
                <img
                    src="/images/analytica-logo.png"
                    alt="Analytica Business Consultants"
                    class="brand-logo"
                />
            </Link>

            <a-menu
                theme="dark"
                mode="inline"
                :selected-keys="selectedKeys"
                :items="menuItems"
                @click="navigate"
            />
        </a-layout-sider>

        <a-layout>
            <a-layout-header class="app-header">
                <a-typography-title :level="4" class="page-title">
                    {{ title }}
                </a-typography-title>

                <a-space :size="16">
                    <slot name="extra" />

                    <ThemeSwitcher />

                    <a-dropdown placement="bottomRight">
                        <a-space class="user-trigger">
                            <a-avatar class="user-avatar">
                                {{ initials }}
                            </a-avatar>
                            <span class="user-name">{{ user.name }}</span>
                        </a-space>

                        <template #overlay>
                            <a-menu @click="onUserMenuClick">
                                <a-menu-item key="profile.edit">
                                    <template #icon><UserOutlined /></template>
                                    Profile
                                </a-menu-item>
                                <a-menu-divider />
                                <a-menu-item key="logout" danger>
                                    <template #icon><LogoutOutlined /></template>
                                    Log out
                                </a-menu-item>
                            </a-menu>
                        </template>
                    </a-dropdown>
                </a-space>
            </a-layout-header>

            <a-layout-content class="app-content">
                <slot />
            </a-layout-content>
        </a-layout>
    </a-layout>
</template>

<style scoped>
.app-shell {
    min-height: 100vh;
}

.brand {
    display: flex;
    justify-content: center;
    padding: 16px;
}

/* The logo is dark green, so it sits on a light tile to stay readable on the dark sidebar. */
.brand-logo {
    display: block;
    height: 44px;
    width: auto;
    padding: 3px 8px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.95);
}

.app-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: var(--app-surface);
    border-bottom: 1px solid var(--app-border);
}

.user-avatar {
    background: var(--brand-mark-bg);
}

.page-title {
    margin: 0 !important;
}

.user-trigger {
    cursor: pointer;
}

.app-content {
    padding: 24px;
}

@media (max-width: 575px) {
    .user-name {
        display: none;
    }

    .app-header {
        padding-left: 56px;
    }

    .app-content {
        padding: 16px;
    }
}
</style>
