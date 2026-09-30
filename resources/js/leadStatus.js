export const statusOptions = [
    { value: 'new', label: 'New' },
    { value: 'contacted', label: 'Contacted' },
    { value: 'follow_up', label: 'Follow-up' },
    { value: 'won', label: 'Won' },
    { value: 'lost', label: 'Lost' },
];

export const statusLabels = Object.fromEntries(
    statusOptions.map((opt) => [opt.value, opt.label]),
);

export const statusColors = {
    new: 'blue',
    contacted: 'gold',
    follow_up: 'orange',
    won: 'green',
    lost: 'red',
};
