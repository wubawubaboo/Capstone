import React from 'react';
import { Link } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function BlotterManagement() {
    const records = [
        { id: '#BLT-062', complainant: 'Juan Dela Cruz', type: 'Noise', status: 'Scheduled', badgeColor: 'bg-amber-100 text-amber-800' },
        { id: '#BLT-021', complainant: 'Paolo Reyes', type: 'Theft', status: 'Unscheduled', badgeColor: 'bg-slate-200 text-slate-700' },
        { id: '#BLT-074', complainant: 'Cardo Dalisay', type: 'Assault', status: 'Escalated', badgeColor: 'bg-red-100 text-red-700' },
    ];

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
                        <button className="bg-[#0a2342] text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-slate-800 transition whitespace-nowrap">
                            + New Blotter
                        </button>
                    </div>
                </div>

                {/* Table */}
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-600 font-semibold bg-slate-50">
                                <th className="py-3 px-4">Case ID</th>
                                <th className="py-3 px-4">Complainant</th>
                                <th className="py-3 px-4">Incident Type</th>
                                <th className="py-3 px-4">Status</th>
                                <th className="py-3 px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {records.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50 transition">
                                    <td className="py-3 px-4 font-semibold text-slate-800">{row.id}</td>
                                    <td className="py-3 px-4 text-slate-700">{row.complainant}</td>
                                    <td className="py-3 px-4 text-slate-700">{row.type}</td>
                                    <td className="py-3 px-4">
                                        <span className={`px-3 py-1 rounded-full text-xs font-medium ${row.badgeColor}`}>
                                            {row.status}
                                        </span>
                                    </td>
                                    <td className="py-3 px-4">
                                        <Link
                                            href={`/secretary/blotter/${row.id.replace('#', '')}/history`}
                                            className="bg-[#0a2342] text-white px-5 py-1.5 rounded text-xs font-medium hover:bg-slate-800 inline-block"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </SecretaryLayout>
    );
}