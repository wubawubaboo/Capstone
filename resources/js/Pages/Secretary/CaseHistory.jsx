import React from 'react';
import { Link } from '@inertiajs/react';

export default function CaseHistory({ blotter }) {
    if (!blotter) return <div className="p-6">Loading...</div>;

    const caseId = blotter.case_number || `BLT-${blotter.id}`;

    return (
        <div className="min-h-screen bg-slate-100 p-6 font-sans">
            {/* Header */}
            <div className="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm mb-6 border border-slate-200">
                <div className="flex items-center gap-3">
                    <Link href={route('secretary.blotters')} className="text-slate-600 hover:text-slate-900 text-lg">
                        ←
                    </Link>
                    <h1 className="text-xl font-bold text-slate-800">Case History & Disposition</h1>
                </div>
                <div className="flex items-center gap-3">
                    <button className="border border-slate-300 text-slate-700 px-4 py-1.5 rounded text-xs font-semibold hover:bg-slate-50">
                        Generate PDF Report
                    </button>
                    <span className="bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1.5 rounded uppercase">
                        STATUS: {blotter.status || 'PENDING'}
                    </span>
                </div>
            </div>

            {/* Main Content Grid */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {/* Left Panel - Case Summary */}
                <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                    <div className="space-y-6">
                        <h2 className="text-2xl font-extrabold text-[#0a2342]">#{caseId}</h2>
                        <div>
                            <p className="text-xs font-semibold text-slate-400 uppercase">Complainant</p>
                            <p className="font-bold text-slate-800 text-base">{blotter.report?.user?.full_name || 'Anonymous'}</p>
                        </div>
                        <div>
                            <p className="text-xs font-semibold text-slate-400 uppercase">Respondent (Accused)</p>
                            <p className="font-bold text-slate-800 text-base">{blotter.receiver?.full_name || 'Not assigned yet'}</p>
                        </div>
                        <div>
                            <p className="text-xs font-semibold text-slate-400 uppercase">Incident</p>
                            <p className="font-bold text-slate-800 text-base">{blotter.report?.incident_type}</p>
                        </div>
                    </div>

                    <button className="w-full bg-red-700 text-white py-2.5 rounded text-xs font-bold tracking-wider uppercase hover:bg-red-800 mt-8">
                        Escalate Case to Court
                    </button>
                </div>

                {/* Right Panel - Activity Timeline */}
                <div className="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                    <div>
                        <h3 className="text-lg font-bold text-slate-800 mb-6">Case Activity History</h3>
                        
                        <div className="relative border-l-2 border-slate-200 ml-3 space-y-6 pl-6">
                            {/* Initial Filing */}
                            <div className="relative">
                                <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-slate-800 border-2 border-white"></span>
                                <p className="text-xs font-bold text-slate-400 uppercase">
                                    {new Date(blotter.created_at).toLocaleDateString()}
                                </p>
                                <p className="font-bold text-slate-800 text-sm">Initial Report Filed</p>
                                <p className="text-xs text-slate-500">{blotter.report?.description}</p>
                            </div>

                            {/* Dynamic Schedule (if any) */}
                            {blotter.scheduled_date && (
                                <div className="relative">
                                    <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-blue-600 border-2 border-white"></span>
                                    <p className="text-xs font-bold text-slate-400 uppercase">
                                        {new Date(blotter.scheduled_date).toLocaleDateString()}
                                    </p>
                                    <Link href={route('secretary.mediation-meeting-details', { id: blotter.id })} className="font-bold text-slate-800 text-sm underline hover:text-blue-600">
                                        Mediation Meeting
                                    </Link>
                                    <p className="text-xs text-slate-500">Scheduled Meeting Session</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Bottom Action Bar */}
                    <div className="flex justify-end gap-3 mt-8 pt-4 border-t border-slate-100">
                        <button className="border border-slate-300 text-slate-700 px-4 py-2 rounded text-xs font-bold hover:bg-slate-50">
                            📝 UPDATE LOG
                        </button>
                        <button className="bg-[#0a2342] text-white px-4 py-2 rounded text-xs font-bold hover:bg-slate-800">
                            + SET NEW SCHEDULE
                        </button>
                        <button className="bg-emerald-600 text-white px-4 py-2 rounded text-xs font-bold hover:bg-emerald-700">
                            ✓ MARK AS RESOLVED
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}