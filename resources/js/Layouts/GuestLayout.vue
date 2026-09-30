<script setup>
import ThemeSwitcher from '@/Components/ThemeSwitcher.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="guest-page">
        <div class="theme-corner">
            <ThemeSwitcher />
        </div>

        <Link href="/" class="brand">
            <img src="/images/analytica-logo.png" alt="Analytica Business Consultants" class="brand-logo" />
        </Link>

        <a-card class="guest-card" :bordered="false">
            <a-typography-title v-if="title" :level="3" class="guest-title">
                {{ title }}
            </a-typography-title>
            <a-typography-paragraph v-if="subtitle" type="secondary">
                {{ subtitle }}
            </a-typography-paragraph>

            <slot />
        </a-card>
    </div>
</template>

<style scoped>
.guest-page {
    position: relative;
    display: flex;
    min-height: 100vh;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 64px 16px 24px;
    background: var(--app-hero-bg);
}

.theme-corner {
    position: absolute;
    top: 16px;
    right: 16px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 24px;
}

.brand-logo {
    height: 96px;
    width: auto;
}

:root[data-theme='dark'] .brand-logo {
    padding: 8px 12px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.92);
}

.guest-card {
    width: 100%;
    max-width: 420px;
    box-shadow: var(--app-shadow);
}

.guest-title {
    margin-bottom: 4px !important;
}
</style>
