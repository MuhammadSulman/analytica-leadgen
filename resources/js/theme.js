import { theme as antTheme } from 'ant-design-vue';
import { computed, ref, watchEffect } from 'vue';

const STORAGE_KEY = 'analytica-theme';

/** Analytica brand palette, taken from analyticaconsultants.com and the logo. */
export const brand = {
    teal: '#1A6C7A',
    navy: '#153243',
    sky: '#60B1E7',
    aqua: '#B9EEF6',
    green: '#30B666',
    deepGreen: '#136B36',
};

function readStoredMode() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return ['light', 'dark', 'auto'].includes(stored) ? stored : 'auto';
    } catch {
        return 'auto';
    }
}

const systemQuery = window.matchMedia('(prefers-color-scheme: dark)');

/** The user's choice: 'light', 'dark' or 'auto' (follow the OS). */
export const themeMode = ref(readStoredMode());

const systemPrefersDark = ref(systemQuery.matches);
systemQuery.addEventListener('change', (event) => {
    systemPrefersDark.value = event.matches;
});

export const isDark = computed(() =>
    themeMode.value === 'auto'
        ? systemPrefersDark.value
        : themeMode.value === 'dark',
);

export function setThemeMode(mode) {
    themeMode.value = mode;

    try {
        localStorage.setItem(STORAGE_KEY, mode);
    } catch {
        // Storage can be unavailable (private mode); the choice still applies for this visit.
    }
}

watchEffect(() => {
    const root = document.documentElement;

    root.dataset.theme = isDark.value ? 'dark' : 'light';
    root.style.colorScheme = isDark.value ? 'dark' : 'light';
});

const sharedTokens = {
    colorInfo: brand.sky,
    colorSuccess: brand.green,
    borderRadius: 8,
    fontFamily:
        "Figtree, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
};

const siderComponents = {
    Layout: {
        siderBg: brand.navy,
        triggerBg: '#0f2733',
    },
    Menu: {
        darkItemBg: brand.navy,
        darkSubMenuItemBg: '#0f2733',
        darkItemSelectedBg: brand.teal,
    },
};

/** Ant Design theme config for the current mode. */
export const antdTheme = computed(() =>
    isDark.value
        ? {
              algorithm: antTheme.darkAlgorithm,
              token: {
                  ...sharedTokens,
                  colorPrimary: '#2A97AB',
                  colorLink: brand.sky,
                  colorBgLayout: '#0B1A22',
                  colorBgContainer: '#11232D',
                  colorBgElevated: '#17303C',
              },
              components: {
                  ...siderComponents,
                  Layout: { ...siderComponents.Layout, siderBg: '#0F2430' },
                  Menu: { ...siderComponents.Menu, darkItemBg: '#0F2430' },
              },
          }
        : {
              algorithm: antTheme.defaultAlgorithm,
              token: {
                  ...sharedTokens,
                  colorPrimary: brand.teal,
                  colorLink: brand.teal,
                  colorTextBase: '#1F2A30',
                  colorBgLayout: '#F3F8F9',
              },
              components: siderComponents,
          },
);
