// Mirrors App\Enums\LeadPlatform.
export const platformOptions = [
    { value: 'website', label: 'Website' },
    { value: 'linkedin', label: 'LinkedIn' },
    { value: 'upwork', label: 'Upwork' },
    { value: 'fiverr', label: 'Fiverr' },
    { value: 'referral', label: 'Referral' },
    { value: 'email', label: 'Email' },
    { value: 'other', label: 'Other' },
];

export const platformLabels = Object.fromEntries(
    platformOptions.map((opt) => [opt.value, opt.label]),
);

export const platformColors = {
    website: 'blue',
    linkedin: 'geekblue',
    upwork: 'green',
    fiverr: 'lime',
    referral: 'purple',
    email: 'cyan',
    other: 'default',
};
