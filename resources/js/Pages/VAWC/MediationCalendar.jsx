import React from 'react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function MediationCalendar() {
    const events = {
        7: { title: '11:00 AM\nMaria Aquino', color: 'bg-amber-100 text-amber-800' },
    };

    const days = [
        30, 31, 1, 2, 3, 4, 5,
        6, 7, 8, 9, 10, 11, 12,
        13, 14, 15, 16, 17, 18, 19,
        20, 21, 22, 23, 24, 25, 26,
        27, 28, 29, 30, 31, 1, 2
    ];

    return (
        <VAWCLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-bold text-slate-900">Mediation Calendar</h2>
                    <div className="flex items-center gap-4 text-slate-800 font-bold">
                        <button className="hover:text-slate-500">&lt;</button>
                        <span>April 2026</span>
                        <button className="hover:text-slate-500">&gt;</button>
                    </div>
                </div>

                <div className="grid grid-cols-7 border-t border-l border-slate-200 text-xs">
                    {days.map((day, idx) => {
                        const isCurrentMonth = !(idx < 2 || idx > 32);
                        const event = isCurrentMonth ? events[day] : null;

                        return (
                            <div
                                key={idx}
                                className={`h-28 border-r border-b border-slate-200 p-2 flex flex-col ${
                                    isCurrentMonth ? 'bg-white' : 'bg-slate-50 text-slate-400'
                                }`}
                            >
                                <span className={`font-semibold mb-1 ${!isCurrentMonth ? 'text-slate-400' : 'text-slate-700'}`}>
                                    {day}
                                </span>
                                {event && (
                                    <div className={`p-1.5 mt-1 rounded text-[10px] font-medium leading-tight whitespace-pre-line ${event.color}`}>
                                        {event.title}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </VAWCLayout>
    );
}