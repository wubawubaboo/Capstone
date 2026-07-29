import React from 'react';
import { Link } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function BlotterManagement({ blotters }) {
    const records = blotters?.data || [];

    const getStatusColor = (status) => {
        switch (status?.toLowerCase()) {
            case 'scheduled': return 'bg-amber-100 text-amber-800';
            case 'escalated': return 'bg-red-100 text-red-700';
            case 'resolved': return 'bg-emerald-100 text-emerald-800';
            default: return 'bg-slate-200 text-slate-700';
        }
    };

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                    <div>
                        <h2 className="text-2xl font-bold text-slate-900">Blotter Management</h2>
                        <p className="text-sm text-slate-500">Manage and review all reported community incidents.</p>
                    </div>
                    <div className="flex items-center gap-3 w-full sm:w-auto">
                        <button className="p-2 border border-slate-300 rounded-md hover:bg-slate-50 text-slate-600">
                            🔍
                        </button>
                        <input
                            type="text"
                            placeholder="Search by name or case ID..."
                            className="text-sm border border-slate-300 rounded-md px-3 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-slate-400"
                        />
                        {/* Link to the Create Blotter page we just built */}
                        <Link 
                            href='secretary/blotters.create'
                            className="bg-[#0a2342] text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition whitespace-nowrap"
                        >
                            + New Blotter
                        </Link>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-600 font-semibold bg-slate-50">
                                <th className="py-3 px-4">Case ID</th>
                                <th className="py-3 px-4">Complainant</th>
                                <th className="py-3 px-4">Respondent (Accused)</th>
                                <th className="py-3 px-4">Incident Type</th>
                                <th className="py-3 px-4">Status</th>
                                <th className="py-3 px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {records.length > 0 ? records.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50 transition">
                                    <td className="py-3 px-4 font-semibold text-slate-800">
                                        {row.case_number}
                                    </td>
                                    <td className="py-3 px-4 text-slate-700">
                                        {row.report?.user?.full_name || 'Anonymous'}
                                    </td>
                                    <td className="py-3 px-4 font-medium text-amber-700">
                                        {/* Checks for registered user first, falls back to manually typed name */}
                                        {row.receiver?.full_name || row.receiver_name || 'Unknown'}
                                    </td>
                                    <td className="py-3 px-4 text-slate-700">
                                        {row.report?.incident_type || 'N/A'}
                                    </td>
                                    <td className="py-3 px-4">
                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(row.status)}`}>
                                            {row.status || 'Pending'}
                                        </span>
                                    </td>
                                    <td className="py-3 px-4">
                                        <Link
                                            href={route('secretary.case-history', { blotter: row.id })}
                                            className="bg-[#0a2342] text-white px-5 py-1.5 rounded text-xs font-medium hover:bg-slate-800 inline-block"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="6" className="py-6 text-center text-slate-500">No blotter records found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </SecretaryLayout>
    );
}