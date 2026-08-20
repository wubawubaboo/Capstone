import React, { useMemo, useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Head, Link } from '@inertiajs/react';

export default function MediationCalendar({ schedules = [] }) {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDateEvents, setSelectedDateEvents] = useState(null);

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    const prevMonth = () => setCurrentDate(new Date(year, month - 1, 1));
    const nextMonth = () => setCurrentDate(new Date(year, month + 1, 1));
    const goToToday = () => setCurrentDate(new Date());

    // Helper: format party names
    const getParties = (blotter) => {
        if (!blotter) return { complainant: 'Confidential', respondent: 'N/A' };
        const complainant = blotter.report?.user?.full_name || blotter.complainant_name || 'Complainant';
        const respondent = blotter.receiver?.full_name || blotter.receiver_name || 'Respondent';
        return { complainant, respondent };
    };

    // Group schedules by day of current month
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
        <SecretaryLayout>
            <Head title="Mediation Calendar" />

            <div className="space-y-6 max-w-7xl mx-auto">
                {/* Header Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-800">Mediation & Conciliation Calendar</h2>
                        <p className="text-xs text-slate-500 mt-0.5">
                            Scheduled hearings for active barangay blotter cases.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <button
                            onClick={goToToday}
                            className="px-3 py-1.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-md transition"
                        >
                            Today
                        </button>
                        <div className="flex items-center bg-slate-100 rounded-md p-1 border border-slate-200">
                            <button
                                onClick={prevMonth}
                                className="px-3 py-1 text-slate-700 font-bold hover:bg-white hover:shadow-xs rounded transition"
                            >
                                &larr;
                            </button>
                            <span className="px-4 text-sm font-bold text-slate-800 min-w-[140px] text-center">
                                {currentDate.toLocaleString('default', { month: 'long', year: 'numeric' })}
                            </span>
                            <button
                                onClick={nextMonth}
                                className="px-3 py-1 text-slate-700 font-bold hover:bg-white hover:shadow-xs rounded transition"
                            >
                                &rarr;
                            </button>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    {/* Main Calendar Grid */}
                    <div className="lg:col-span-3 bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
                        {/* Days of Week Header */}
                        <div className="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center font-bold text-xs text-slate-600">
                            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d) => (
                                <div key={d} className="py-3 border-r border-slate-200 last:border-r-0">
                                    {d}
                                </div>
                            ))}
                        </div>

                        {/* Calendar Days */}
                        <div className="grid grid-cols-7 text-xs">
                            {days.map((day, idx) => {
                                const dayEvents = day ? eventsByDay[day] || [] : [];
                                const isToday = isCurrentMonthToday && day === todayDate.getDate();

                                return (
                                    <div
                                        key={idx}
                                        onClick={() => day && dayEvents.length > 0 && setSelectedDateEvents({ day, events: dayEvents })}
                                        className={`min-h-[110px] border-r border-b border-slate-100 p-2 flex flex-col justify-between transition ${
                                            day ? 'bg-white hover:bg-slate-50/70' : 'bg-slate-50/50'
                                        } ${(idx + 1) % 7 === 0 ? 'border-r-0' : ''}`}
                                    >
                                        <div className="flex justify-between items-center">
                                            {day && (
                                                <span
                                                    className={`w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold ${
                                                        isToday
                                                            ? 'bg-blue-600 text-white shadow-xs'
                                                            : 'text-slate-700'
                                                    }`}
                                                >
                                                    {day}
                                                </span>
                                            )}
                                            {dayEvents.length > 0 && (
                                                <span className="text-[10px] font-extrabold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded-full">
                                                    {dayEvents.length} {dayEvents.length > 1 ? 'Hearings' : 'Hearing'}
                                                </span>
                                            )}
                                        </div>

                                        {/* Event Pills */}
                                        <div className="space-y-1 my-1 overflow-y-auto max-h-[70px]">
                                            {dayEvents.slice(0, 2).map((item) => {
                                                const time = new Date(item.scheduled_date).toLocaleTimeString([], {
                                                    hour: '2-digit',
                                                    minute: '2-digit',
                                                });
                                                const parties = getParties(item.blotter);

                                                return (
                                                    <Link
                                                        key={item.id}
                                                        href={route('secretary.case-history', { id: item.blotter_record_id || item.blotter?.id })}
                                                        className="block p-1 rounded bg-blue-50 hover:bg-blue-100 border border-blue-200/80 transition text-left cursor-pointer"
                                                    >
                                                        <p className="text-[10px] font-bold text-blue-900 truncate">
                                                            {time} - {parties.complainant}
                                                        </p>
                                                        <p className="text-[9px] text-blue-700 font-medium truncate">
                                                            {item.blotter?.case_number || 'Blotter Case'} (Session #{item.meeting_number})
                                                        </p>
                                                    </Link>
                                                );
                                            })}

                                            {dayEvents.length > 2 && (
                                                <button
                                                    onClick={() => setSelectedDateEvents({ day, events: dayEvents })}
                                                    className="text-[10px] text-slate-500 font-bold hover:underline block text-left"
                                                >
                                                    +{dayEvents.length - 2} more...
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Upcoming Agenda Sidebar */}
                    <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-5 flex flex-col h-full">
                        <h3 className="text-base font-bold text-slate-800 mb-3 pb-2 border-b border-slate-100">
                            Upcoming Hearings
                        </h3>

                        <div className="space-y-3 overflow-y-auto max-h-[500px] pr-1">
                            {schedules.length > 0 ? (
                                schedules.map((item) => {
                                    const d = new Date(item.scheduled_date);
                                    const dateStr = d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                                    const timeStr = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                    const parties = getParties(item.blotter);

                                    return (
                                        <div
                                            key={item.id}
                                            className="p-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-white hover:border-blue-300 transition shadow-2xs"
                                        >
                                            <div className="flex justify-between items-start mb-1">
                                                <span className="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                                                    Session #{item.meeting_number}
                                                </span>
                                                <span className="text-xs font-semibold text-slate-500">{timeStr}</span>
                                            </div>
                                            <p className="text-xs font-bold text-slate-800">{dateStr}</p>
                                            <p className="text-xs text-slate-700 mt-1 font-medium">
                                                <span className="font-semibold text-slate-500">Vs: </span>
                                                {parties.complainant} & {parties.respondent}
                                            </p>
                                            <div className="mt-2 pt-2 border-t border-slate-200/60 flex justify-between items-center text-[11px]">
                                                <span className="text-slate-500">{item.blotter?.case_number}</span>
                                                <Link
                                                    href={route('secretary.case-history', { id: item.blotter_record_id || item.blotter?.id })}
                                                    className="font-bold text-blue-600 hover:underline"
                                                >
                                                    View Case →
                                                </Link>
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <p className="text-xs text-slate-400 italic text-center py-8">
                                    No mediation hearings scheduled.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Modal for Days with Multiple Events */}
            {selectedDateEvents && (
                <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-xl shadow-lg border border-slate-200 max-w-md w-full p-6 space-y-4">
                        <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                            <h4 className="text-base font-bold text-slate-800">
                                Hearings on {currentDate.toLocaleString('default', { month: 'long' })} {selectedDateEvents.day}, {year}
                            </h4>
                            <button
                                onClick={() => setSelectedDateEvents(null)}
                                className="text-slate-400 hover:text-slate-700 text-lg font-bold"
                            >
                                ✕
                            </button>
                        </div>
                        <div className="space-y-2 max-h-[300px] overflow-y-auto">
                            {selectedDateEvents.events.map((ev) => {
                                const time = new Date(ev.scheduled_date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                const parties = getParties(ev.blotter);
                                return (
                                    <div key={ev.id} className="p-3 bg-slate-50 rounded-lg border border-slate-200">
                                        <div className="flex justify-between text-xs font-bold text-blue-900">
                                            <span>{time} (Session #{ev.meeting_number})</span>
                                            <span>{ev.blotter?.case_number}</span>
                                        </div>
                                        <p className="text-xs text-slate-700 mt-1 font-medium">
                                            {parties.complainant} vs. {parties.respondent}
                                        </p>
                                        <Link
                                            href={route('secretary.case-history', { id: ev.blotter_record_id || ev.blotter?.id })}
                                            className="text-xs text-blue-600 font-bold hover:underline block mt-2"
                                        >
                                            Open Case Details →
                                        </Link>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            )}
        </SecretaryLayout>
    );
}