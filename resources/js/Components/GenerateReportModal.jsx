import React, { useState } from 'react';

const pad = (num) => String(num).padStart(2, '0');

const toDateInputValue = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const getIsoWeekRange = (weekValue) => {
    const [yearStr, weekStr] = weekValue.split('-W');
    const year = parseInt(yearStr, 10);
    const week = parseInt(weekStr, 10);

    const simple = new Date(year, 0, 1 + (week - 1) * 7);
    const dayOfWeek = simple.getDay();
    const isoWeekStart = new Date(simple);

    if (dayOfWeek <= 4) {
        isoWeekStart.setDate(simple.getDate() - dayOfWeek + 1);
    } else {
        isoWeekStart.setDate(simple.getDate() + 8 - dayOfWeek);
    }

    const start = new Date(isoWeekStart);
    const end = new Date(isoWeekStart);
    end.setDate(end.getDate() + 6);

    return { start, end };
};

const getCurrentWeekValue = () => {
    const now = new Date();
    const target = new Date(now.valueOf());
    const dayNumber = (now.getDay() + 6) % 7;
    target.setDate(target.getDate() - dayNumber + 3);
    const firstThursday = new Date(target.getFullYear(), 0, 4);
    const weekNumber = 1 + Math.round(((target - firstThursday) / 86400000 - 3 + ((firstThursday.getDay() + 6) % 7)) / 7);
    return `${target.getFullYear()}-W${pad(weekNumber)}`;
};

export default function GenerateReportModal({ show, onClose, exportUrl, extraParams = {} }) {
    const now = new Date();
    const [rangeType, setRangeType] = useState('week');
    const [weekValue, setWeekValue] = useState(getCurrentWeekValue());
    const [monthValue, setMonthValue] = useState(`${now.getFullYear()}-${pad(now.getMonth() + 1)}`);
    const [yearValue, setYearValue] = useState(String(now.getFullYear()));
    const [error, setError] = useState('');

    if (!show) return null;

    const handleGenerate = () => {
        let start;
        let end;

        if (rangeType === 'week') {
            if (!weekValue) {
                setError('Please select a week.');
                return;
            }
            const range = getIsoWeekRange(weekValue);
            start = range.start;
            end = range.end;
        } else if (rangeType === 'month') {
            if (!monthValue) {
                setError('Please select a month.');
                return;
            }
            const [year, month] = monthValue.split('-').map(Number);
            start = new Date(year, month - 1, 1);
            end = new Date(year, month, 0);
        } else {
            const year = parseInt(yearValue, 10);
            if (!year || year < 1900 || year > 2100) {
                setError('Please enter a valid year.');
                return;
            }
            start = new Date(year, 0, 1);
            end = new Date(year, 11, 31);
        }

        setError('');

        const params = new URLSearchParams({
            ...extraParams,
            start_date: toDateInputValue(start),
            end_date: toDateInputValue(end),
        });

        window.location.href = `${exportUrl}?${params.toString()}`;
        onClose();
    };

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-slate-900 bg-opacity-60 z-50 p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-md border border-slate-200">
                <div className="p-4 border-b flex justify-between items-center bg-[#0a2342] text-white rounded-t-xl">
                    <h3 className="text-lg font-bold">Generate Report</h3>
                    <button onClick={onClose} className="text-white hover:text-gray-200 font-bold text-xl">&times;</button>
                </div>

                <div className="p-6 space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Range Type</label>
                        <div className="flex gap-2">
                            {['week', 'month', 'year'].map((type) => (
                                <button
                                    key={type}
                                    type="button"
                                    onClick={() => { setRangeType(type); setError(''); }}
                                    className={`flex-1 py-2 rounded-md text-sm font-medium border transition ${
                                        rangeType === type
                                            ? 'bg-[#0a2342] text-white border-[#0a2342]'
                                            : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                    }`}
                                >
                                    {type.charAt(0).toUpperCase() + type.slice(1)}
                                </button>
                            ))}
                        </div>
                    </div>

                    {rangeType === 'week' && (
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Select Week</label>
                            <input
                                type="week"
                                value={weekValue}
                                onChange={(e) => setWeekValue(e.target.value)}
                                className="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    )}

                    {rangeType === 'month' && (
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Select Month</label>
                            <input
                                type="month"
                                value={monthValue}
                                onChange={(e) => setMonthValue(e.target.value)}
                                className="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    )}

                    {rangeType === 'year' && (
                        <div>
                            <label className="block text-xs font-bold text-slate-500 uppercase mb-2">Select Year</label>
                            <input
                                type="number"
                                value={yearValue}
                                onChange={(e) => setYearValue(e.target.value)}
                                min="1900"
                                max="2100"
                                className="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    )}

                    {error && <p className="text-sm text-red-600 font-medium">{error}</p>}
                </div>

                <div className="p-4 border-t bg-slate-50 flex justify-end gap-3 rounded-b-xl">
                    <button
                        type="button"
                        onClick={onClose}
                        className="bg-slate-200 text-slate-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-300 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={handleGenerate}
                        className="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition"
                    >
                        ⬇ Download
                    </button>
                </div>
            </div>
        </div>
    );
}
