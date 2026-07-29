import React, { useMemo, useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Link } from '@inertiajs/react';

export default function MediationCalendar({ schedules = [] }) {
    const [currentDate, setCurrentDate] = useState(new Date());

    // Generate dynamic events based on the month and schedules passed from backend
    const events = useMemo(() => {
        const eventsMap = {};
        schedules.forEach(schedule => {
            if (schedule.scheduled_date) {
                const dateObj = new Date(schedule.scheduled_date);
                // Check if the schedule is in the currently viewed month
                if (dateObj.getMonth() === currentDate.getMonth() && dateObj.getFullYear() === currentDate.getFullYear()) {
                    const day = dateObj.getDate();
                    const time = dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    eventsMap[day] = {
                        id: schedule.id,
                        title: `${time}\n${schedule.report?.user?.full_name || 'Meeting'}`,
                        color: 'bg-emerald-100 text-emerald-800'
                    };
                }
            }
        });
        return eventsMap;
    }, [schedules, currentDate]);

    // Simple calendar logic to get days in the current month
    const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
    const firstDayIndex = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1).getDay();

    const days = [];
    for (let i = 0; i < firstDayIndex; i++) days.push(null); // padding for empty slots
    for (let i = 1; i <= daysInMonth; i++) days.push(i);

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200 max-w-5xl mx-auto">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-bold text-slate-800">Mediation Calendar</h2>
                    <div className="flex items-center gap-4 text-slate-800 font-bold">
                        <button 
                            onClick={() => setCurrentDate(new Date(currentDate.setMonth(currentDate.getMonth() - 1)))} 
                            className="hover:text-slate-500"
                        >&lt;</button>
                        <span>
                            {currentDate.toLocaleString('default', { month: 'long', year: 'numeric' })}
                        </span>
                        <button 
                            onClick={() => setCurrentDate(new Date(currentDate.setMonth(currentDate.getMonth() + 1)))} 
                            className="hover:text-slate-500"
                        >&gt;</button>
                    </div>
                </div>

                {/* Calendar Grid */}
                <div className="grid grid-cols-7 border-t border-l border-slate-200 text-xs">
                    {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(dayName => (
                        <div key={dayName} className="font-bold text-center py-2 border-r border-b border-slate-200 bg-slate-50 text-slate-600">
                            {dayName}
                        </div>
                    ))}
                    {days.map((day, idx) => {
                        const event = day ? events[day] : null;

                        return (
                            <div
                                key={idx}
                                className={`h-24 border-r border-b border-slate-200 p-2 flex flex-col justify-between ${
                                    day ? 'bg-white' : 'bg-slate-50'
                                }`}
                            >
                                {day && <span className="font-semibold text-slate-600">{day}</span>}
                                {event && (
                                    <Link href={route('secretary.mediation-meeting-details', { id: event.id })}>
                                        <div className={`p-1.5 rounded text-[10px] font-medium leading-tight whitespace-pre-line hover:opacity-80 cursor-pointer ${event.color}`}>
                                            {event.title}
                                        </div>
                                    </Link>
                                )}
                            </div>
                        );
                    })}
                </div>
            </div>
        </SecretaryLayout>
    );
}