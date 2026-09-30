import dayjs from 'dayjs';

// Quick picks for the "date received" range pickers on the dashboard and leads list.
// Built on each call so "today" is correct even if the page stays open past midnight.
export function datePresets() {
    const today = dayjs();
    const lastMonth = today.subtract(1, 'month');

    return [
        { label: 'Today', value: [today, today] },
        { label: 'Last 7 days', value: [today.subtract(6, 'day'), today] },
        { label: 'Last 30 days', value: [today.subtract(29, 'day'), today] },
        { label: 'This month', value: [today.startOf('month'), today] },
        { label: 'Last month', value: [lastMonth.startOf('month'), lastMonth.endOf('month')] },
        { label: 'This year', value: [today.startOf('year'), today] },
    ];
}

// A readable name for a from/to pair: the matching preset's label, the dates themselves, or "All time".
export function describeRange(from, to) {
    if (!from && !to) {
        return 'All time';
    }

    const preset = datePresets().find(
        ({ value: [start, end] }) =>
            start.format('YYYY-MM-DD') === from && end.format('YYYY-MM-DD') === to,
    );

    if (preset) {
        return preset.label;
    }

    const format = (date) => dayjs(date).format('D MMM YYYY');

    if (from && to) {
        return from === to ? format(from) : `${format(from)} – ${format(to)}`;
    }

    return from ? `Since ${format(from)}` : `Up to ${format(to)}`;
}
