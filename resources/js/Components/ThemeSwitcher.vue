<script setup>
import { DesktopOutlined } from '@ant-design/icons-vue';
import { setThemeMode, themeMode } from '@/theme';

const options = [
    { value: 'light', payload: { title: 'Light' } },
    { value: 'dark', payload: { title: 'Dark' } },
    { value: 'auto', payload: { title: 'Auto (system)' } },
];
</script>

<template>
    <a-segmented
        :value="themeMode"
        :options="options"
        aria-label="Color theme"
        @change="setThemeMode"
    >
        <template #label="{ value, payload }">
            <a-tooltip :title="payload.title">
                <span class="theme-option" role="img" :aria-label="payload.title">
                    <svg
                        v-if="value === 'light'"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    >
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                    </svg>
                    <svg
                        v-else-if="value === 'dark'"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linejoin="round"
                    >
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                    </svg>
                    <DesktopOutlined v-else />
                </span>
            </a-tooltip>
        </template>
    </a-segmented>
</template>

<style scoped>
.theme-option {
    display: inline-flex;
    align-items: center;
    padding: 0 2px;
}

.theme-option svg {
    width: 1em;
    height: 1em;
}
</style>
