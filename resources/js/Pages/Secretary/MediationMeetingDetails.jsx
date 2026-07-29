import React from 'react';
import { Link } from '@inertiajs/react';

export default function MediationMeetingDetails({ blotter }) {
    if (!blotter) return <div className="p-6">Loading...</div>;

    const caseId = blotter.case_number || `BLT-${blotter.id}`;
    
    const meetingDate = blotter.scheduled_date 
        ? new Date(blotter.scheduled_date).toLocaleString('default', { 
            month: 'long', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' 
        })
        : 'Schedule Pending';

    return (
        <div className="min-h-screen bg-slate-100 p-6 font-sans">
            <div className="flex items-center gap-3 bg-white p-4 rounded-lg shadow-sm mb-6 border border-slate-200">
                <Link href={route('secretary.case-history', { blotter: blotter.id })} className="text-slate-600 hover:text-slate-900 text-lg">
                    ←
                </Link>
                <h1 className="text-xl font-bold text-[#0a2342]">Mediation Meeting Details</h1>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p className="text-xs font-bold text-slate-400 uppercase">Case ID</p>
                    <p className="text-lg font-extrabold text-[#0a2342]">{caseId}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p className="text-xs font-bold text-slate-400 uppercase">Date & Time of Meeting</p>
                    <p className="text-sm font-bold text-slate-800">{meetingDate}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p className="text-xs font-bold text-slate-400 uppercase">Nature of Complaint</p>
                    <p className="text-sm font-bold text-slate-800">{blotter.report?.incident_type || 'N/A'}</p>
                </div>
            </div>

            <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mb-6">
                <h3 className="text-xs font-bold text-slate-400 uppercase mb-3">Statement of Complaint / Description</h3>
                <blockquote className="italic text-sm text-slate-600 leading-relaxed bg-slate-50 p-4 rounded border-l-2 border-slate-400">
                    "{blotter.report?.description || 'No detailed description provided by the complainant.'}"
                </blockquote>
            </div>

            <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mb-6">
                <h3 className="text-xs font-bold text-slate-400 uppercase mb-4">Meeting Attestation</h3>
                <div className="grid grid-cols-2 gap-6">
                    <div className="border border-slate-200 h-24 rounded flex flex-col items-center justify-center text-xs text-slate-400 bg-slate-50">
                        <span className="font-bold text-slate-600 uppercase mb-2">{blotter.report?.user?.full_name || 'Complainant'}</span>
                        Complainant Signature
                    </div>
                    <div className="border border-slate-200 h-24 rounded flex flex-col items-center justify-center text-xs text-slate-400 bg-slate-50">
                        {/* Updated Logic Here */}
                        <span className="font-bold text-amber-700 uppercase mb-2">
                            {blotter.receiver?.full_name || blotter.receiver_name || 'Respondent'}
                        </span>
                        Respondent Signature
                    </div>
                </div>
            </div>

            <div className="flex justify-end gap-3">
                <button className="border border-slate-300 text-slate-700 px-5 py-2 rounded text-xs font-bold hover:bg-slate-50">
                    Download PDF Report
                </button>
                <button className="bg-emerald-600 text-white px-5 py-2 rounded text-xs font-bold hover:bg-emerald-700">
                    Verify & Save
                </button>
            </div>
        </div>
    );
}