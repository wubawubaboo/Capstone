import React from 'react';
import { Link } from '@inertiajs/react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function CaseHistory({ blotter }) {
    const complainant = blotter?.report?.user?.full_name || blotter?.complainant_name || 'Confidential';
    const respondent = blotter?.receiver?.full_name || blotter?.receiver_name || 'N/A';
    const incident = blotter?.incident_type || 'VAWC Case';
    const mediations = blotter?.mediations || [];

    return (
        <VAWCLayout>
            <div className="min-h-screen font-sans space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <div className="flex items-center gap-3">
                        <Link href={route('vawc.blotters')} className="text-slate-600 hover:text-slate-900 font-bold">
                            ← Back to Records
                        </Link>
                        <h1 className="text-xl font-bold text-slate-800">Case History & Disposition</h1>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1.5 rounded uppercase">
                            Status: {blotter?.status || 'Pending'}
                        </span>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {/* Left Panel */}
                    <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                        <div className="space-y-6">
                            <h2 className="text-2xl font-extrabold text-[#0a2540]">{blotter?.case_number || `VAWC-${blotter?.id}`}</h2>
                            <div>
                                <p className="text-xs font-semibold text-slate-400 uppercase">Complainant</p>
                                <p className="font-bold text-slate-800 text-base">{complainant}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-slate-400 uppercase">Respondent (Accused)</p>
                                <p className="font-bold text-slate-800 text-base">{respondent}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-slate-400 uppercase">Incident</p>
                                <p className="font-bold text-slate-800 text-base">{incident}</p>
                            </div>
                            {blotter?.incident_description && (
                                <div>
                                    <p className="text-xs font-semibold text-slate-400 uppercase">Description</p>
                                    <p className="text-sm text-slate-600 mt-1 bg-slate-50 p-3 rounded">{blotter.incident_description}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Panel */}
                    <div className="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                        <div>
                            <h3 className="text-lg font-bold text-slate-800 mb-6">Case Activity History</h3>
                            
                            <div className="relative border-l-2 border-slate-200 ml-3 space-y-6 pl-6">
                                {/* Initial Entry */}
                                <div className="relative">
                                    <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-[#0a2540] border-2 border-white"></span>
                                    <p className="text-xs font-bold text-slate-400 uppercase">
                                        {blotter?.created_at ? new Date(blotter.created_at).toLocaleDateString() : 'N/A'}
                                    </p>
                                    <p className="font-bold text-slate-800 text-sm">Initial Report Registered</p>
                                    <p className="text-xs text-slate-500">Official blotter entry created</p>
                                </div>

                                {/* Mediation Schedules */}
                                {mediations.map((m, idx) => (
                                    <div key={m.id || idx} className="relative">
                                        <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-blue-500 border-2 border-white"></span>
                                        <p className="text-xs font-bold text-slate-400 uppercase">
                                            {m.scheduled_date ? new Date(m.scheduled_date).toLocaleDateString() : ''}
                                        </p>
                                        <p className="font-bold text-slate-800 text-sm">Mediation Session #{m.meeting_number || idx + 1}</p>
                                        <p className="text-xs text-slate-500">Status: {m.status}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </VAWCLayout>
    );
}