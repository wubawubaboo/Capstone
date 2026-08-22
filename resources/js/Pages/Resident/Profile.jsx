import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function Profile({ caseUpdates = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const phoneNumber = user?.phone_number || user?.contact_number || 'No contact number registered';

    const getStatusBadge = (status) => {
        switch (status?.toLowerCase()) {
            case 'scheduled':
                return 'bg-blue-50 text-blue-700 border-blue-200';
            case 'completed':
            case 'settled':
                return 'bg-emerald-50 text-emerald-700 border-emerald-200';
            case 'escalated':
                return 'bg-rose-50 text-rose-700 border-rose-200';
            default:
                return 'bg-amber-50 text-amber-700 border-amber-200';
        }
    };

    return (
        <ResidentLayout>
            <Head title="My Account & Case Notices" />

            <div className="max-w-4xl mx-auto py-8 space-y-6">
                {/* Account Greeting Banner */}
                <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-6 flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-900">
                            Hello, {user?.full_name || 'Resident'}
                        </h2>
                        <p className="text-sm font-medium text-slate-600 mt-1">
                            📱 {phoneNumber}
                        </p>
                    </div>
                    <span className="bg-emerald-50 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full border border-emerald-200">
                        Verified
                    </span>
                </div>

                {/* Case & Mediation Notices */}
                <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-6 space-y-4">
                    <div className="flex justify-between items-center border-b border-slate-100 pb-3">
                        <div>
                            <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wide">Case & Hearing Notices</h3>
                            <p className="text-xs text-slate-500">Official updates on your registered blotter cases and mediation proceedings.</p>
                        </div>
                        <span className="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-full">
                            {caseUpdates.length} {caseUpdates.length === 1 ? 'Notice' : 'Notices'}
                        </span>
                    </div>

                    {caseUpdates.length > 0 ? (
                        <div className="space-y-3">
                            {caseUpdates.map((notice) => (
                                <div
                                    key={notice.id}
                                    className="p-4 rounded-lg bg-slate-50 border border-slate-200 flex flex-col sm:flex-row justify-between sm:items-center gap-3"
                                >
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs font-bold text-slate-900">
                                                Case #{notice.case_number}
                                            </span>
                                            <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full border ${getStatusBadge(notice.status)}`}>
                                                {notice.status}
                                            </span>
                                        </div>
                                        <p className="text-xs text-slate-600">
                                            Hearing #{notice.meeting_number}: Scheduled for{' '}
                                            <span className="font-semibold text-slate-800">
                                                {new Date(notice.scheduled_date).toLocaleString([], {
                                                    dateStyle: 'medium',
                                                    timeStyle: 'short',
                                                })}
                                            </span>
                                        </p>
                                    </div>
                                    <span className="text-[11px] text-slate-500 font-medium shrink-0">
                                        {notice.incident_type}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-6 bg-slate-50/50 rounded-lg border border-dashed border-slate-200">
                            <p className="text-xs text-slate-500 italic">No scheduled hearings or active case notices.</p>
                        </div>
                    )}
                </div>

                {/* Account Details */}
                <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-6 space-y-4">
                    <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wide border-b border-slate-100 pb-3">
                        Account Details
                    </h3>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label className="text-slate-400 font-semibold block mb-1">Full Name</label>
                            <p className="bg-slate-50 p-2.5 rounded-lg border border-slate-200 font-semibold text-slate-800">
                                {user?.full_name || 'N/A'}
                            </p>
                        </div>

                        <div>
                            <label className="text-slate-400 font-semibold block mb-1">Contact Number</label>
                            <p className="bg-slate-50 p-2.5 rounded-lg border border-slate-200 font-semibold text-slate-800">
                                {phoneNumber}
                            </p>
                        </div>

                        <div>
                            <label className="text-slate-400 font-semibold block mb-1">Account Role</label>
                            <p className="bg-slate-50 p-2.5 rounded-lg border border-slate-200 font-semibold text-slate-800 uppercase">
                                {user?.role || 'Resident'}
                            </p>
                        </div>

                        <div>
                            <label className="text-slate-400 font-semibold block mb-1">Assigned Barangay</label>
                            <p className="bg-slate-50 p-2.5 rounded-lg border border-slate-200 font-semibold text-slate-800">
                                {user?.barangay?.name || `Barangay #${user?.barangay_id || 'Assigned'}`}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Account Actions */}
                <div className="bg-white rounded-xl shadow-xs border border-slate-200 p-6 flex justify-between items-center">
                    <div>
                        <h4 className="text-xs font-bold text-slate-800">Account Session</h4>
                        <p className="text-xs text-slate-500">Terminate your current authenticated session.</p>
                    </div>

                    <Link
                        href={route('resident.logout')}
                        method="post"
                        as="button"
                        className="bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs font-bold px-4 py-2 rounded-lg border border-rose-200 transition"
                    >
                        Sign Out
                    </Link>
                </div>
            </div>
        </ResidentLayout>
    );
}