import React, { useState } from 'react';
import { ArrowDownRight, ArrowUpRight, ChartColumn, CircleCheck, Info, Minus, OctagonAlert, Table2, TriangleAlert } from 'lucide-react';
import { SERIES, percent } from './theme';

export function Card({ title, subtitle, actions, children, className = '' }) {
    return (
        <section className={`bg-white rounded-xl border border-slate-200 shadow-sm p-5 break-inside-avoid ${className}`}>
            {(title || actions) && (
                <div className="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h2 className="text-base font-bold text-slate-800">{title}</h2>
                        {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
                    </div>
                    {actions}
                </div>
            )}
            {children}
        </section>
    );
}

/**
 * A chart with a table twin: every value stays readable without hovering,
 * and without telling colours apart.
 */
export function ChartCard({ title, subtitle, table, empty, emptyText = 'No incidents in this period.', children, className = '' }) {
    const [showTable, setShowTable] = useState(false);

    const toggle = table && !empty && (
        <button
            type="button"
            onClick={() => setShowTable(!showTable)}
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 border border-slate-200 rounded-md px-2 py-1 print:hidden"
        >
            {showTable ? <ChartColumn size={14} aria-hidden /> : <Table2 size={14} aria-hidden />}
            {showTable ? 'Chart' : 'Table'}
        </button>
    );

    return (
        <Card title={title} subtitle={subtitle} actions={toggle} className={className}>
            {empty ? <EmptyState text={emptyText} /> : showTable ? <DataTable {...table} /> : children}
        </Card>
    );
}

export function EmptyState({ text }) {
    return <p className="text-sm text-slate-500 italic text-center py-10">{text}</p>;
}

export function DataTable({ columns, rows }) {
    return (
        <div className="overflow-x-auto max-h-80">
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b border-slate-200 text-left text-xs uppercase tracking-wider text-slate-500">
                        {columns.map((column, i) => (
                            <th key={column} className={`py-2 pr-4 font-semibold ${i > 0 ? 'text-right' : ''}`}>{column}</th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row, r) => (
                        <tr key={r} className="border-b border-slate-100 last:border-0">
                            {row.map((cell, i) => (
                                <td key={i} className={`py-2 pr-4 ${i > 0 ? 'text-right tabular-nums text-slate-700' : 'text-slate-800 font-medium'}`}>{cell}</td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

/** "+35%" style change against the previous period, or null when there is nothing to compare. */
export function changeText(current, previous) {
    if (previous === 0) return current === 0 ? '0%' : 'new';
    const change = Math.round(((current - previous) / previous) * 100);
    return `${change > 0 ? '+' : ''}${change}%`;
}

/**
 * A headline number with its change against the previous period. For
 * incident counts a rise is bad news, so it reads red.
 */
export function StatTile({ label, value, previous, comparison, hint, upIsGood = false }) {
    let delta = null;
    if (previous !== undefined && previous !== null) {
        const diff = value - previous;
        const good = diff === 0 ? null : (diff > 0) === upIsGood;
        const Icon = diff > 0 ? ArrowUpRight : diff < 0 ? ArrowDownRight : Minus;
        const color = good === null ? 'text-slate-500' : good ? 'text-emerald-700' : 'text-red-700';
        delta = (
            <p className={`flex items-center gap-1 text-xs font-semibold ${color}`}>
                <Icon size={14} aria-hidden />
                {previous === 0 ? (value === 0 ? 'No change' : `None in the ${comparison}`) : `${changeText(value, previous)} vs ${comparison} (${previous.toLocaleString()})`}
            </p>
        );
    }

    return (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col gap-1 break-inside-avoid">
            <p className="text-xs font-semibold text-slate-500">{label}</p>
            <p className="text-3xl font-bold text-slate-900">{value.toLocaleString()}</p>
            {delta}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
        </div>
    );
}

/** Magnitudes that are read as numbers first, drawn as thin single-hue bars. */
export function BarList({ items, emptyText = 'Nothing recorded in this period.' }) {
    const total = items.reduce((sum, item) => sum + item.count, 0);
    const max = Math.max(...items.map((item) => item.count), 0);

    if (total === 0) return <EmptyState text={emptyText} />;

    return (
        <ul className="space-y-3">
            {items.map((item) => (
                <li key={item.label}>
                    <div className="flex justify-between text-sm mb-1">
                        <span className="text-slate-700">{item.label}</span>
                        <span className="tabular-nums text-slate-900 font-semibold">
                            {item.count.toLocaleString()} <span className="text-slate-500 font-normal">({percent(item.count, total)}%)</span>
                        </span>
                    </div>
                    <div className="h-2 rounded-full bg-slate-100">
                        <div className="h-2 rounded-full" style={{ width: `${max ? (item.count / max) * 100 : 0}%`, backgroundColor: SERIES[0] }} />
                    </div>
                </li>
            ))}
        </ul>
    );
}

const TONES = {
    critical: { Icon: OctagonAlert, color: '#d03b3b', label: 'Urgent' },
    warning: { Icon: TriangleAlert, color: '#d97706', label: 'Watch' },
    good: { Icon: CircleCheck, color: '#0ca30c', label: 'On track' },
    info: { Icon: Info, color: '#2a78d6', label: 'Note' },
};

export function InsightList({ insights }) {
    if (!insights.length) return <EmptyState text="Not enough data yet to draw findings." />;

    return (
        <ul className="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-3">
            {insights.map((insight, i) => {
                const { Icon, color, label } = TONES[insight.tone] ?? TONES.info;
                return (
                    <li key={i} className="flex gap-3 items-start">
                        <Icon size={18} className="shrink-0 mt-0.5" style={{ color }} aria-hidden />
                        <p className="text-sm text-slate-700">
                            <span className="text-[10px] font-bold uppercase tracking-wider text-slate-500 mr-2">{label}</span>
                            {insight.text}
                        </p>
                    </li>
                );
            })}
        </ul>
    );
}
