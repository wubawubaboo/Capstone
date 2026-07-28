import React from 'react';
import { Link } from '@inertiajs/react';

export default function CaseHistory({ caseId = 'BLT-062' }) {
    return (
        <div className="min-h-screen bg-slate-100 p-6 font-sans">
            {/* Header */}
            <div className="flex justify-between items-center bg-white p-4 rounded-lg shadow-sm mb-6 border border-slate-200">
                <div className="flex items-center gap-3">
                    <Link href="/secretary/blotter" className="text-slate-600 hover:text-slate-900 text-lg">
                        ←
                    </Link>
                    <h1 className="text-xl font-bold text-slate-800">Case History & Disposition</h1>
                </div>
                <div className="flex items-center gap-3">
                    <button className="border border-slate-300 text-slate-700 px-4 py-1.5 rounded text-xs font-semibold hover:bg-slate-50">
                        Generate PDF Report
                    </button>
                    <span className="bg-amber-100 text-amber-800 text-xs font-bold px-3 py-1.5 rounded">
                        STATUS: ONGOING MEDIATION
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
                            <p className="font-bold text-slate-800 text-base">Juan Dela Cruz</p>
                        </div>
                        <div>
                            <p className="text-xs font-semibold text-slate-400 uppercase">Respondent (Accused)</p>
                            <p className="font-bold text-slate-800 text-base">Pedro Chavez</p>
                        </div>
                        <div>
                            <p className="text-xs font-semibold text-slate-400 uppercase">Incident</p>
                            <p className="font-bold text-slate-800 text-base">Noise Complaint</p>
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
                            {/* Item 1 */}
                            <div className="relative">
                                <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-slate-800 border-2 border-white"></span>
                                <p className="text-xs font-bold text-slate-400 uppercase">March 24, 2026</p>
                                <p className="font-bold text-slate-800 text-sm">Initial Report Filed</p>
                                <p className="text-xs text-slate-500">Resident filed the complaint</p>
                            </div>

                            {/* Item 2 */}
                            <div className="relative">
                                <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-slate-800 border-2 border-white"></span>
                                <p className="text-xs font-bold text-slate-400 uppercase">March 28, 2026</p>
                                <Link href={`/secretary/blotter/${caseId}/meeting/1`} className="font-bold text-slate-800 text-sm underline hover:text-blue-600">
                                    1st Mediation Meeting
                                </Link>
                                <p className="text-xs text-slate-500">
                                    Presided by: Kagawad Maria Clara. Both parties present. Agreement reached but respondent failed to follow terms.
                                </p>
                            </div>

                            {/* Item 3 */}
                            <div className="relative">
                                <span className="absolute -left-[31px] top-1.5 w-3 h-3 rounded-full bg-blue-600 border-2 border-white"></span>
                                <p className="text-xs font-bold text-slate-400 uppercase">April 01, 2026</p>
                                <p className="font-bold text-slate-800 text-sm">2nd Mediation Scheduled</p>
                                <p className="text-xs text-slate-500">Set for 9:00 AM at Barangay Hall</p>
                            </div>
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