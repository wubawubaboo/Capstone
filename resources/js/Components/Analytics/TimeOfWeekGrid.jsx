import React from 'react';
import { EMPTY_CELL, RAMP } from './theme';

const blockRange = (blocks, index) => `${blocks[index]}–${blocks[(index + 1) % blocks.length]}`;

/** Incidents by weekday and time of day, darker where there were more. */
export default function TimeOfWeekGrid({ days, blocks, grid }) {
    const max = Math.max(...grid.flat());
    const step = (count) => (count === 0 ? null : Math.min(RAMP.length - 1, Math.ceil((count / max) * RAMP.length) - 1));

    return (
        <div>
            <div className="grid gap-0.5 text-[11px] text-slate-500" style={{ gridTemplateColumns: `3rem repeat(${blocks.length}, minmax(0, 1fr))` }}>
                <span />
                {blocks.map((block) => (
                    <span key={block} className="text-center pb-1">{block}</span>
                ))}
                {days.map((day, d) => (
                    <React.Fragment key={day}>
                        <span className="self-center font-medium text-slate-600">{day.slice(0, 3)}</span>
                        {grid[d].map((count, b) => {
                            const level = step(count);
                            const text = `${day}, ${blockRange(blocks, b)}: ${count} incident${count === 1 ? '' : 's'}`;
                            return (
                                <div
                                    key={b}
                                    title={text}
                                    aria-label={text}
                                    role="img"
                                    className="h-7 rounded-sm"
                                    style={{ backgroundColor: level === null ? EMPTY_CELL : RAMP[level] }}
                                />
                            );
                        })}
                    </React.Fragment>
                ))}
            </div>
            <div className="flex items-center justify-end gap-1 mt-3 text-[11px] text-slate-500">
                <span className="mr-1">0</span>
                <span className="h-3 w-5 rounded-sm" style={{ backgroundColor: EMPTY_CELL }} />
                {RAMP.map((color) => (
                    <span key={color} className="h-3 w-5 rounded-sm" style={{ backgroundColor: color }} />
                ))}
                <span className="ml-1">{max} incident{max === 1 ? '' : 's'}</span>
            </div>
        </div>
    );
}

export function timeOfWeekTable({ days, blocks, grid }) {
    return {
        columns: ['Day', ...blocks.map((_, b) => blockRange(blocks, b))],
        rows: days.map((day, d) => [day, ...grid[d]]),
    };
}
