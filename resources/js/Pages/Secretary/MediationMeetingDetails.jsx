import React from 'react';
import { Link } from '@inertiajs/react';

export default function MediationMeetingDetails({ caseId = 'BLT-062' }) {
    return (
        <div className="min-h-screen bg-slate-100 p-6 font-sans">
            {/* Top Bar */}
            <div className="flex items-center gap-3 bg-white p-4 rounded-lg shadow-sm mb-6 border border-slate-200">
                <Link href={`/secretary/blotter/${caseId}/history`} className="text-slate-600 hover:text-slate-900 text-lg">
                    ←
                </Link>
                <h1 className="text-xl font-bold text-[#0a2342]">1st Mediation Meeting Details</h1>
            </div>

            {/* Info Cards Row */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p className="text-xs font-bold text-slate-400 uppercase">Case ID</p>
                    <p className="text-lg font-extrabold text-[#0a2342]">#{caseId}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p className="text-xs font-bold text-slate-400 uppercase">Date & Time of Meeting</p>
                    <p className="text-sm font-bold text-slate-800">March 28, 2026 | 10:30 AM</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
                    <p className="text-xs font-bold text-slate-400 uppercase">Nature of Complaint</p>
                    <p className="text-sm font-bold text-slate-800">Noise Complaint</p>
                </div>
            </div>

            {/* Statement Box */}
            <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mb-6">
                <h3 className="text-xs font-bold text-slate-400 uppercase mb-3">Statement of Complaint / Description</h3>
                <blockquote className="italic text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded border-l-2 border-slate-400">
                    "Noong mga bandang gabi (around 10:00 PM), nagkaroon ng reklamo ang complainant tungkol sa malakas na ingay na nagmumula sa bahay ng idinidemanda. Ayon sa complainant, madalas daw itong nangyayari lalo na tuwing gabi, kung saan may malakas na tugtog at minsan ay may inuman pa na nagdudulot ng istorbo sa kanilang pamilya, lalo na sa mga bata at matanda na nagpapahinga na. Sinubukan na raw kausapin ng complainant ang respondent dati, pero nauulit pa rin ang sitwasyon. Dahil dito, napilitan na silang lumapit ang concern sa barangay para maayos sa pamamagitan ng mediation. Sa panig naman ng respondent, aminado siya na may mga pagkakataon na nagiging maingay ang kanilang gatherings, pero hindi raw nila intensyon na makaperwisyo sa kapitbahay. Nagkasundo ang magkabilang panig na pag-uusapan ang posibleng solusyon para maiwasan ang ganitong issue sa susunod."
                </blockquote>
            </div>

            {/* Attestation & Signature Box */}
            <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 mb-6">
                <h3 className="text-xs font-bold text-slate-400 uppercase mb-4">Meeting Attestation</h3>
                <div className="grid grid-cols-2 gap-6">
                    <div className="border border-slate-200 h-24 rounded flex items-center justify-center text-xs text-slate-400 bg-slate-50">
                        Complainant Signature
                    </div>
                    <div className="border border-slate-200 h-24 rounded flex items-center justify-center text-xs text-slate-400 bg-slate-50">
                        Respondent Signature
                    </div>
                </div>
            </div>

            {/* Actions */}
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