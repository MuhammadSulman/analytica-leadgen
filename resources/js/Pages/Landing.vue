<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircleFilled, SendOutlined } from '@ant-design/icons-vue';
import { computed } from 'vue';

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const benefits = [
    'A clear snapshot of your cash flow and runway',
    'The top financial risks holding your business back',
    'Practical next steps a Virtual CFO would take',
];

const form = useForm({
    name: '',
    email: '',
    company: '',
    phone: '',
    message: '',
});

function submit() {
    form.post(route('leads.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Free CFO Readiness Review" />

    <a-layout class="landing">
        <a-layout-header class="landing-header">
            <div class="brand">
                <img src="/images/analytica-logo.png" alt="Analytica Business Consultants" class="brand-logo" />
            </div>
        </a-layout-header>

        <a-layout-content class="landing-content">
            <a-row :gutter="[48, 32]" align="middle" class="landing-inner">
                <a-col :xs="24" :lg="12">
                    <a-tag color="blue" class="eyebrow">Free · No obligation</a-tag>

                    <a-typography-title class="hero-title">
                        Free CFO Readiness Review
                    </a-typography-title>

                    <a-typography-paragraph class="hero-text" type="secondary">
                        Find out where your business stands financially, and
                        what a Virtual CFO can fix.
                    </a-typography-paragraph>

                    <a-list :data-source="benefits" :split="false">
                        <template #renderItem="{ item }">
                            <a-list-item class="benefit">
                                <a-space>
                                    <CheckCircleFilled class="benefit-icon" />
                                    <span>{{ item }}</span>
                                </a-space>
                            </a-list-item>
                        </template>
                    </a-list>
                </a-col>

                <a-col :xs="24" :lg="12">
                    <a-card class="form-card" :bordered="false">
                        <a-result
                            v-if="successMessage"
                            status="success"
                            title="Request received"
                            :sub-title="successMessage"
                        />

                        <template v-else>
                            <a-typography-title :level="4">
                                Request your review
                            </a-typography-title>

                            <a-form layout="vertical" @submit.prevent="submit">
                                <a-row :gutter="16">
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Full Name"
                                            required
                                            :validate-status="form.errors.name ? 'error' : ''"
                                            :help="form.errors.name"
                                        >
                                            <a-input
                                                v-model:value="form.name"
                                                placeholder="John Doe"
                                                autocomplete="name"
                                            />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Work Email"
                                            required
                                            :validate-status="form.errors.email ? 'error' : ''"
                                            :help="form.errors.email"
                                        >
                                            <a-input
                                                v-model:value="form.email"
                                                type="email"
                                                placeholder="john@company.com"
                                                autocomplete="email"
                                            />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Company Name"
                                            :validate-status="form.errors.company ? 'error' : ''"
                                            :help="form.errors.company"
                                        >
                                            <a-input
                                                v-model:value="form.company"
                                                placeholder="Your company"
                                                autocomplete="organization"
                                            />
                                        </a-form-item>
                                    </a-col>
                                    <a-col :xs="24" :sm="12">
                                        <a-form-item
                                            label="Phone"
                                            :validate-status="form.errors.phone ? 'error' : ''"
                                            :help="form.errors.phone"
                                        >
                                            <a-input
                                                v-model:value="form.phone"
                                                placeholder="+1 234 567 890"
                                                autocomplete="tel"
                                            />
                                        </a-form-item>
                                    </a-col>
                                </a-row>

                                <a-form-item
                                    label="What do you need help with?"
                                    :validate-status="form.errors.message ? 'error' : ''"
                                    :help="form.errors.message"
                                >
                                    <a-textarea
                                        v-model:value="form.message"
                                        :rows="4"
                                        :maxlength="2000"
                                        show-count
                                        placeholder="Tell us briefly..."
                                    />
                                </a-form-item>

                                <a-button
                                    type="primary"
                                    html-type="submit"
                                    :loading="form.processing"
                                    block
                                    size="large"
                                >
                                    <template #icon><SendOutlined /></template>
                                    Get My Free Review
                                </a-button>
                            </a-form>
                        </template>
                    </a-card>
                </a-col>
            </a-row>
        </a-layout-content>

        <a-layout-footer class="landing-footer">
            © {{ new Date().getFullYear() }} Analytica
        </a-layout-footer>
    </a-layout>
</template>

<style scoped>
.landing {
    min-height: 100vh;
    background: var(--app-hero-bg);
}

.landing-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: transparent;
}

.brand {
    display: flex;
    align-items: center;
    gap: 10px;
}

.brand-logo {
    display: block;
    height: 52px;
    width: auto;
}

:root[data-theme='dark'] .brand-logo {
    padding: 4px 8px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.92);
}

.benefit-icon {
    color: var(--brand-green);
}

.landing-content {
    display: flex;
    align-items: center;
    padding: 32px 16px;
}

.landing-inner {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto !important;
}

.eyebrow {
    margin-bottom: 16px;
}

.hero-title {
    font-size: 40px !important;
    margin-top: 0 !important;
}

.hero-text {
    font-size: 18px;
}

.benefit {
    padding: 6px 0 !important;
}

.form-card {
    box-shadow: var(--app-shadow);
}

.landing-footer {
    text-align: center;
    background: transparent;
    color: var(--app-text-muted);
}

@media (max-width: 575px) {
    .hero-title {
        font-size: 30px !important;
    }
}
</style>
