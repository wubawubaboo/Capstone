import React from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Link, router } from '@inertiajs/react';

export default function Reports({ reports }) {
    const data = reports?.data || [];

    const handleStatusChange = (reportId, newStatus) => {
        router.put(route('secretary.reports.update-status', reportId), {
            status: newStatus
        }, { preserveScroll: true });
    };

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <h2 className="text-xl font-bold text-slate-900 mb-6">Incident & Emergency Queue</h2>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                                <th className="py-3 px-2">Date</th>
                                <th className="py-3 px-2">Reporter</th>
                                <th className="py-3 px-2">Type / Location</th>
                                <th className="py-3 px-2">Status</th>
                                <th className="py-3 px-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {data.length > 0 ? data.map((report) => {
                                const isCritical = report.type === 'SOS_CRITICAL' && report.status !== 'completed';

                                return (
                                    <tr key={report.id} className={isCritical ? 'bg-red-50' : 'hover:bg-slate-50'}>
                                        <td className="py-4 px-2 text-slate-600">
                                            {new Date(report.created_at).toLocaleDateString()}
                                        </td>
                                        <td className="py-4 px-2 font-medium text-slate-800">
                                            {report.user?.full_name || 'N/A'}
                                        </td>
                                        <td className="py-4 px-2">
                                            <div className="font-bold text-slate-800">
                                                {isCritical ? <span className="text-red-600 animate-pulse">🚨 URGENT SOS</span> : (report.incident_type || report.type)}
                                            </div>
                                            <div className="text-xs text-slate-500">{report.location_details || report.description}</div>
                                        </td>
                                        <td className="py-4 px-2">
                                            <select 
                                                value={report.status.toLowerCase()}
                                                onChange={(e) => handleStatusChange(report.id, e.target.value)}
                                                className={`text-xs font-bold rounded border border-slate-300 p-1 cursor-pointer ${
                                                    report.status.toLowerCase() === 'pending' ? 'text-amber-700 bg-amber-100' :
                                                    report.status.toLowerCase() === 'in_progress' ? 'text-blue-700 bg-blue-100' :
                                                    'text-emerald-700 bg-emerald-100'
                                                }`}
                                            >
                                                <option value="pending">PENDING</option>
                                                <option value="in_progress">IN PROGRESS</option>
                                                <option value="completed">COMPLETED</option>
                                            </select>
                                        </td>
                                        <td className="py-4 px-2 text-center flex justify-center gap-2">
                                            {/* Routes to your existing CreateBlotter page, passing the report ID as a query param */}
                                            <Link 
                                                href={route('secretary.create-blotter', { report_id: report.id })}
                                                className="bg-[#0a2342] text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-slate-800 transition"
                                            >
                                                LOG TO BLOTTER
                                            </Link>
                                        </td>
                                    </tr>
                                );
                            }) : (
                                <tr>
                                    <td colSpan="5" className="py-6 text-center text-slate-500">No active reports.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </SecretaryLayout>
    );
}