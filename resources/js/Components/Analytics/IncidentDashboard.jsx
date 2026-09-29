import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Bar } from 'react-chartjs-2';
import 'chart.js/auto';
import { Printer } from 'lucide-react';
import { BarList, Card, ChartCard, InsightList, StatTile, changeText } from './parts';
import TimeOfWeekGrid, { timeOfWeekTable } from './TimeOfWeekGrid';
import HotspotMap from './HotspotMap';
import { OTHER, PREVIOUS, SERIES, SURFACE, baseOptions, categoryAxis, percent, plural, valueAxis } from './theme';

function TrendChart({ trend }) {
    const last = trend.series.length - 1;
    const data = {
        labels: trend.labels,
        datasets: trend.series.map((series, i) => ({
            label: series.name,
            data: series.data,
            backgroundColor: series.folded ? OTHER : SERIES[i],
            borderColor: SURFACE,
            borderWidth: { top: 2 },
            borderSkipped: 'start',
            borderRadius: i === last ? { topLeft: 4, topRight: 4 } : 0,
            maxBarThickness: 24,
        })),
    };

    const options = {
        ...baseOptions,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            ...baseOptions.plugins,
            tooltip: {
                ...baseOptions.plugins.tooltip,
                filter: (item) => item.raw > 0,
                footer: (items) => `Total: ${items.reduce((sum, item) => sum + item.raw, 0)}`,
            },
        },
        scales: { x: { ...categoryAxis, stacked: true }, y: { ...valueAxis, stacked: true } },
    };

    return (
        <div className="h-80">
            <Bar data={data} options={options} />
        </div>
    );
}

/** This period against the previous one, per item, for the leading items. */
function ComparisonChart({ items, onSelect }) {
    const shown = items.slice(0, 8);
    const data = {
        labels: shown.map((item) => item.name),
        datasets: [
            { label: 'This period', data: shown.map((item) => item.current), backgroundColor: SERIES[0] },
            { label: 'Previous period', data: shown.map((item) => item.previous), backgroundColor: PREVIOUS },
        ].map((dataset) => ({ ...dataset, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 12, categoryPercentage: 0.7, barPercentage: 0.9 })),
    };

    const options = {
        ...baseOptions,
        indexAxis: 'y',
        interaction: { mode: 'index', intersect: false, axis: 'y' },
        scales: { x: valueAxis, y: { ...categoryAxis, ticks: { ...categoryAxis.ticks, autoSkip: false, color: '#334155' } } },
        onClick: onSelect ? (_, elements) => elements[0] && onSelect(shown[elements[0].index]) : undefined,
        onHover: onSelect ? (event, elements) => { event.native.target.style.cursor = elements.length ? 'pointer' : 'default'; } : undefined,
    };

    return (
        <div style={{ height: Math.max(180, shown.length * 44 + 60) }}>
            <Bar data={data} options={options} />
        </div>
    );
}

const comparisonTable = (items, firstColumn) => ({
    columns: [firstColumn, 'This period', 'Previous', 'Change'],
    rows: items.map((item) => [item.name, item.current, item.previous, changeText(item.current, item.previous)]),
});

function RangePicker({ options, value, onChange }) {
    return (
        <div className="inline-flex rounded-lg border border-slate-300 bg-white p-0.5" role="group" aria-label="Time period">
            {options.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => onChange(option.value)}
                    aria-pressed={option.value === value}
                    className={`px-3 py-1.5 text-xs font-semibold rounded-md transition ${
                        option.value === value ? 'bg-[#0a2540] text-white' : 'text-slate-600 hover:bg-slate-100'
                    }`}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}

/**
 * The incident analytics and monitoring dashboard: headline numbers, the
 * findings they add up to, then the trend, types, timing, places and
 * outcomes behind them. Each page passes the dashboard its controller built.
 */
export default function IncidentDashboard({ dashboard, title, heading, scope, routeName, params = {}, filters, boundary, onSelectBarangay, children }) {
    const { period, totals, sos, backlog, trend, categories, timeOfWeek, reportStatuses, caseOutcomes, map, barangays, insights } = dashboard;
    const [loading, setLoading] = useState(false);

    const visit = (changes) => {
        const query = Object.fromEntries(Object.entries({ range: period.range, ...params, ...changes }).filter(([, v]) => v !== null && v !== '' && v !== undefined));
        router.get(route(routeName), query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    const hasIncidents = totals.current > 0;
    const closedCases = caseOutcomes.settled + caseOutcomes.escalated;

    return (
        <>
            <Head title={title} />

            <div className="space-y-6">
                <header className="flex flex-col xl:flex-row xl:items-end justify-between gap-4">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-wider text-slate-500">Incident analytics &amp; monitoring</p>
                        <h1 className="text-2xl font-bold text-[#0a2540]">{heading}</h1>
                        <p className="text-sm text-slate-500 mt-1">
                            {scope ? `${scope} · ` : ''}{period.from} – {period.to}, compared with the {period.comparison}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2 print:hidden">
                        {filters?.(visit)}
                        <RangePicker options={period.options} value={period.range} onChange={(range) => visit({ range })} />
                        <button
                            type="button"
                            onClick={() => window.print()}
                            className="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-100"
                        >
                            <Printer size={14} aria-hidden /> Print
                        </button>
                    </div>
                </header>

                <div className={`space-y-6 transition-opacity ${loading ? 'opacity-50' : ''}`} aria-busy={loading}>
                    {/* Headline numbers */}
                    <div className={`grid grid-cols-1 sm:grid-cols-2 gap-4 ${sos ? 'xl:grid-cols-4' : 'xl:grid-cols-3'}`}>
                        <StatTile
                            label="Incidents recorded"
                            value={totals.current}
                            previous={totals.previous}
                            comparison={period.comparison}
                            hint={`${plural(totals.reports, 'resident report')} · ${plural(totals.walk_ins, 'walk-in blotter')}`}
                        />
                        {sos && (
                            <StatTile
                                label="SOS emergency alerts"
                                value={sos.current}
                                previous={sos.previous}
                                comparison={period.comparison}
                                hint={sos.medianAckMinutes !== null ? `Median ${sos.medianAckMinutes} min to acknowledge` : sos.current ? 'None acknowledged yet' : 'No alerts this period'}
                            />
                        )}
                        <StatTile
                            label="Reports awaiting action"
                            value={backlog.open}
                            hint={backlog.open ? `Pending or in progress · oldest waiting ${plural(backlog.oldestDays, 'day')}` : 'No pending reports, from any date'}
                        />
                        <StatTile
                            label="Blotter cases recorded"
                            value={caseOutcomes.filed}
                            hint={caseOutcomes.settlementRate !== null ? `${caseOutcomes.settlementRate}% of closed cases settled at the barangay` : 'None closed yet this period'}
                        />
                    </div>

                    <Card title="Key findings" subtitle="What this period's data points to, most pressing first">
                        <InsightList insights={insights} />
                    </Card>

                    <ChartCard
                        title="Incident trend"
                        subtitle={`Incidents per ${period.bucket}, by type`}
                        empty={!hasIncidents}
                        table={{
                            columns: [period.bucket === 'month' ? 'Month' : period.bucket === 'week' ? 'Week of' : 'Day', ...trend.series.map((s) => s.name), 'Total'],
                            rows: trend.labels.map((label, i) => {
                                const counts = trend.series.map((s) => s.data[i]);
                                return [label, ...counts, counts.reduce((a, b) => a + b, 0)];
                            }),
                        }}
                    >
                        <TrendChart trend={trend} />
                    </ChartCard>

                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <ChartCard
                            title="Incident types"
                            subtitle={`This period against the ${period.comparison}`}
                            empty={categories.length === 0}
                            table={comparisonTable(categories, 'Type')}
                        >
                            <ComparisonChart items={categories} />
                            {categories.length > 8 && (
                                <p className="text-xs text-slate-500 mt-2">Showing the 8 most frequent of {categories.length} types. Switch to the table for all.</p>
                            )}
                        </ChartCard>

                        <ChartCard
                            title="When incidents happen"
                            subtitle="By day of the week and time of day, when reported or recorded"
                            empty={!hasIncidents}
                            table={timeOfWeekTable(timeOfWeek)}
                        >
                            <TimeOfWeekGrid {...timeOfWeek} />
                        </ChartCard>
                    </div>

                    {map && (
                        <Card
                            title="Incident hotspots"
                            subtitle={`${plural(map.located, 'resident report')} with a location this period${map.located < totals.reports ? ` (of ${totals.reports}; walk-in blotters have none)` : ''}. SOS alerts count double.`}
                        >
                            <HotspotMap points={map.points} hotspot={map.hotspot} boundary={boundary} />
                            {map.hotspot && (
                                <p className="text-xs text-slate-500 mt-2">
                                    Circled: the densest area, with {plural(map.hotspot.count, 'report')} ({percent(map.hotspot.count, map.located)}% of located reports).
                                </p>
                            )}
                        </Card>
                    )}

                    {barangays && (
                        <ChartCard
                            title="Incidents by barangay"
                            subtitle="Select a barangay to see its dashboard"
                            empty={barangays.every((b) => b.current === 0 && b.previous === 0)}
                            table={comparisonTable(barangays, 'Barangay')}
                        >
                            <ComparisonChart items={barangays} onSelect={onSelectBarangay && ((item) => onSelectBarangay(item, visit))} />
                            {barangays.length > 8 && (
                                <p className="text-xs text-slate-500 mt-2">Showing the 8 barangays with the most incidents. Switch to the table for all {barangays.length}.</p>
                            )}
                        </ChartCard>
                    )}

                    <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <Card title="Report handling" subtitle="Where this period's resident reports stand now">
                            <BarList items={reportStatuses} emptyText="No resident reports in this period." />
                        </Card>

                        <Card title="Case outcomes" subtitle="Blotter cases recorded this period, by current status">
                            <BarList items={caseOutcomes.statuses} emptyText="No blotter cases recorded in this period." />
                            {(caseOutcomes.filed > 0 || caseOutcomes.mediation.some((m) => m.count > 0)) && (
                                <dl className="grid grid-cols-3 gap-4 mt-5 pt-4 border-t border-slate-100 text-sm">
                                    <div>
                                        <dt className="text-xs text-slate-500">Settled locally</dt>
                                        <dd className="font-semibold text-slate-900">
                                            {caseOutcomes.settlementRate !== null ? `${caseOutcomes.settlementRate}%` : '—'}
                                            <span className="block text-xs font-normal text-slate-500">of {plural(closedCases, 'closed case')}</span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-slate-500">Median time to close</dt>
                                        <dd className="font-semibold text-slate-900">
                                            {caseOutcomes.medianDaysToClose !== null ? `${caseOutcomes.medianDaysToClose} days` : '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-slate-500">Mediation hearings</dt>
                                        <dd className="font-semibold text-slate-900">
                                            {caseOutcomes.mediation.reduce((sum, m) => sum + m.count, 0)}
                                            <span className="block text-xs font-normal text-slate-500">
                                                {caseOutcomes.mediation.map((m) => `${m.count} ${m.label.toLowerCase()}`).join(' · ')}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            )}
                        </Card>
                    </div>

                    {children}
                </div>
            </div>
        </>
    );
}
