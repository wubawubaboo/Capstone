import React, { useMemo, useState } from 'react';
import VAWCLayout from '@/Layouts/VAWCLayout';
import { Head, Link } from '@inertiajs/react';

export default function MediationCalendar({ schedules = [] }) {
    const [currentDate, setCurrentDate] = useState(new Date());
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const prevMonth = () => setCurrentDate(new Date(year, month - 1, 1));
    const nextMonth = () => setCurrentDate(new Date(year, month + 1, 1));
    const goToToday = () => setCurrentDate(new Date());

    const getParties = (blotter) => {
        if (!blotter) return { complainant: 'Confidential', respondent: 'N/A' };
        const complainant = blotter.report?.user?.full_name || blotter.complainant_name || 'Confidential';
        const respondent = blotter.receiver?.full_name || blotter.receiver_name || 'Respondent';
        return { complainant, respondent };
    };

    const eventsByDay = useMemo(() => {
        const map = {};
        schedules.forEach((schedule) => {
            if (!schedule.scheduled_date) return;
            const dateObj = new Date(schedule.scheduled_date);
            if (dateObj.getFullYear() === year && dateObj.getMonth() === month) {
                const day = dateObj.getDate();
                if (!map[day]) map[day] = [];
                map[day].push(schedule);
            }
        });
        return map;
    }, [schedules, year, month]);

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const firstDayIndex = new Date(year, month, 1).getDay();

    const days = [];
    for (let i = 0; i < firstDayIndex; i++) days.push(null);
    for (let i = 1; i <= daysInMonth; i++) days.push(i);

    const todayDate = new Date();
    const isCurrentMonthToday = todayDate.getFullYear() === year && todayDate.getMonth() === month;

    return (
        <VAWCLayout>
            <Head title="VAWC Mediation Calendar" />

            <div className="space-y-6 max-w-7xl mx-auto">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-rose-100 shadow-xs">
                    <div>
                        <h2 className="text-2xl font-bold text-[#3B122D]">VAWC Mediation Calendar</h2>
                        <p className="text-xs text-rose-600 mt-0.5">
                            Confidential conciliation and mediation sessions under R.A. 9262.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            onClick={goToToday}
                            className="px-3 py-1.5 text-xs font-bold text-rose-900 bg-rose-50 hover:bg-rose-100 rounded-md transition"
                        >
                            Today
                        </button>
                        <div className="flex items-center bg-rose-50/50 rounded-md p-1 border border-rose-100">
                            <button onClick={prevMonth} className="px-3 py-1 text-rose-900 font-bold hover:bg-white rounded transition">
                                &larr;
                            </button>
                            <span className="px-4 text-sm font-bold text-[#3B122D] min-w-[140px] text-center">
                                {currentDate.toLocaleString('default', { month: 'long', year: 'numeric' })}
                            </span>
                            <button onClick={nextMonth} className="px-3 py-1 text-rose-900 font-bold hover:bg-white rounded transition">
                                &rarr;
                            </button>
                        </div>
                    </div>
                </div>

                <div className="bg-white rounded-xl shadow-xs border border-rose-100 overflow-hidden">
                    <div className="grid grid-cols-7 border-b border-rose-100 bg-rose-50/40 text-center font-bold text-xs text-rose-900">
                        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d) => (
                            <div key={d} className="py-3 border-r border-rose-100 last:border-r-0">
                                {d}
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-7 text-xs">
                        {days.map((day, idx) => {
                            const dayEvents = day ? eventsByDay[day] || [] : [];
                            const isToday = isCurrentMonthToday && day === todayDate.getDate();

                            return (
                                <div
                                    key={idx}
                                    className={`min-h-[110px] border-r border-b border-rose-50 p-2 flex flex-col justify-between ${
                                        day ? 'bg-white' : 'bg-rose-50/20'
                                    } ${(idx + 1) % 7 === 0 ? 'border-r-0' : ''}`}
                                >
                                    <div className="flex justify-between items-center">
                                        {day && (
                                            <span
                                                className={`w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold ${
                                                    isToday ? 'bg-[#3B122D] text-white shadow-xs' : 'text-slate-700'
                                                }`}
                                            >
                                                {day}
                                            </span>
                                        )}
                                    </div>

                                    <div className="space-y-1 my-1 overflow-y-auto max-h-[70px]">
                                        {dayEvents.map((item) => {
                                            const time = new Date(item.scheduled_date).toLocaleTimeString([], {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            });
                                            const parties = getParties(item.blotter);

                                            return (
                                                <Link
                                                    key={item.id}
                                                    href={route('vawc.case-history', { id: item.blotter_record_id || item.blotter?.id })}
                                                    className="block p-1.5 rounded bg-rose-50 hover:bg-rose-100 border border-rose-200 transition text-left"
                                                >
                                                    <p className="text-[10px] font-bold text-rose-950 truncate">
                                                        {time} - {parties.complainant}
                                                    </p>
                                                    <p className="text-[9px] text-rose-700 font-medium truncate">
                                                        {item.blotter?.case_number} (Session #{item.meeting_number})
                                                    </p>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </VAWCLayout>
    );
}