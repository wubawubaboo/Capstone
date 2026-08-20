import React from 'react';
import { Head, Link } from '@inertiajs/react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function BlotterManagement({ blotters }) {
    const records = blotters?.data || [];

    return (
        <VAWCLayout>
            <Head title="VAWC Incident Records" />

            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-slate-900">Confidential VAWC Records</h2>
                    <p className="text-sm text-slate-500">Cases protected under R.A. 9262.</p>
                </div>
                <Link
                    href={route('vawc.blotters.create')}
                    className="bg-[#3B122D] text-white px-4 py-2 rounded-md font-semibold text-sm hover:bg-[#280c1e] transition shadow-sm"
                >
                    + New VAWC Incident
                </Link>
            </div>

            <div className="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                <table className="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold">
                            <th className="py-3 px-4">Case #</th>
                            <th className="py-3 px-4">Complainant / Victim</th>
                            <th className="py-3 px-4">Respondent</th>
                            <th className="py-3 px-4">Violation Type</th>
                            <th className="py-3 px-4">BPO Status</th>
                            <th className="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {records.length > 0 ? (
                            records.map((row) => (
                                <tr key={row.id} className="hover:bg-rose-50/20 transition">
                                    <td className="py-3 px-4 font-semibold text-slate-800">VAWC-{row.id}</td>
                                    <td className="py-3 px-4 text-slate-700 font-medium">
                                        {row.report?.user?.full_name || 'Confidential'}
                                    </td>
                                    <td className="py-3 px-4 text-slate-700">
                                        {row.receiver?.full_name || row.receiver_name || 'N/A'}
                                    </td>
                                    <td className="py-3 px-4 text-slate-600">
                                        {row.vawc_detail?.violation_type || 'General R.A. 9262'}
                                    </td>
                                    <td className="py-3 px-4">
                                        <span className={`px-2.5 py-1 rounded-full text-xs font-bold ${
                                            row.vawc_detail?.has_bpo 
                                                ? 'bg-purple-100 text-purple-800' 
                                                : 'bg-slate-100 text-slate-700'
                                        }`}>
                                            {row.vawc_detail?.has_bpo ? 'BPO Issued' : 'No BPO'}
                                        </span>
                                    </td>
                                    <td className="py-3 px-4 text-right">
                                        <Link
                                            href={route('vawc.case-history', { id: row.id })}
                                            className="text-[#3B122D] hover:underline font-bold text-xs"
                                        >
                                            View Details →
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan="6" className="py-8 text-center text-slate-500 italic">
                                    No VAWC records found.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </VAWCLayout>
    );
}