import React, { useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Link, router } from '@inertiajs/react';

// NEW: Report Details Modal Component
const ReportDetailsModal = ({ report, onClose }) => {
    if (!report) return null;

    const isCritical = report.incident_type === 'SOS_CRITICAL';

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-slate-900 bg-opacity-60 z-50 p-4">
            <div className="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto border border-slate-200 flex flex-col">
                
                {/* Modal Header */}
                <div className={`p-4 border-b flex justify-between items-center ${isCritical ? 'bg-red-600 text-white' : 'bg-slate-800 text-white'}`}>
                    <h3 className="text-lg font-black uppercase tracking-wider">
                        {isCritical ? '🚨 URGENT SOS ALERT' : '📝 Incident Report Details'}
                    </h3>
                    <button onClick={onClose} className="text-white hover:text-gray-200 font-bold text-xl">&times;</button>
                </div>

                {/* Modal Body */}
                <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    {/* Left Column: Details */}
                    <div className="space-y-4">
                        <div>
                            <p className="text-xs font-bold text-slate-500 uppercase">Reporter</p>
                            <p className="text-base font-medium text-slate-900">{report.user?.full_name || 'Unknown User'}</p>
                            <p className="text-sm text-slate-600">{report.user?.phone_number || 'No Contact Provided'}</p>
                        </div>

                        <div>
                            <p className="text-xs font-bold text-slate-500 uppercase">Incident Type</p>
                            <p className="text-base font-medium text-slate-900">{report.incident_type}</p>
                        </div>

                        <div>
                            <p className="text-xs font-bold text-slate-500 uppercase">Date & Time</p>
                            <p className="text-base font-medium text-slate-900">{new Date(report.created_at).toLocaleString()}</p>
                        </div>

                        <div>
                            <p className="text-xs font-bold text-slate-500 uppercase">Description / Details</p>
                            <div className="bg-slate-50 p-3 rounded border border-slate-200 text-sm text-slate-800 whitespace-pre-wrap">
                                {report.description || 'No additional details provided.'}
                            </div>
                        </div>

                        {report.attachment_path && (
                            <div>
                                <p className="text-xs font-bold text-slate-500 uppercase mb-1">Attached Evidence</p>
                                <a 
                                    href={route('resident.reports.attachment', report.id)} 
                                    target="_blank" 
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 bg-slate-200 text-slate-700 px-4 py-2 rounded text-sm font-bold hover:bg-slate-300 transition"
                                >
                                    📎 View Attachment
                                </a>
                            </div>
                        )}
                    </div>

                    {/* Right Column: Map */}
                    <div className="flex flex-col">
                        <p className="text-xs font-bold text-slate-500 uppercase mb-2">GPS Location</p>
                        {report.latitude && report.longitude ? (
                            <div className="flex-1 min-h-[300px] border-2 border-slate-300 rounded overflow-hidden shadow-inner">
                                <iframe 
                                    width="100%" 
                                    height="100%" 
                                    frameBorder="0" 
                                    scrolling="no" 
                                    marginHeight="0" 
                                    marginWidth="0" 
                                    src={`https://maps.google.com/maps?q=${report.latitude},${report.longitude}&z=17&output=embed`}
                                    title="Incident Location Map"
                                ></iframe>
                            </div>
                        ) : (
                            <div className="flex-1 min-h-[300px] flex items-center justify-center bg-slate-100 rounded border-2 border-dashed border-slate-300 text-slate-500 text-sm font-medium">
                                No valid GPS coordinates available.
                            </div>
                        )}
                        <p className="text-xs text-slate-500 mt-2 text-center">
                            Coordinates: {report.latitude}, {report.longitude}
                        </p>
                    </div>
                </div>

                {/* Modal Footer */}
                <div className="p-4 border-t bg-slate-50 flex justify-end">
                    <button 
                        onClick={onClose} 
                        className="bg-slate-700 text-white px-6 py-2 rounded font-bold hover:bg-slate-800 transition"
                    >
                        CLOSE
                    </button>
                </div>
            </div>
        </div>
    );
};

export default function Reports({ reports }) {
    const data = reports?.data || [];
    const [selectedReport, setSelectedReport] = useState(null);

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
                                <th className="py-3 px-2">Type / Details</th>
                                <th className="py-3 px-2">Status</th>
                                <th className="py-3 px-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {data.length > 0 ? data.map((report) => {
                                const isCritical = report.incident_type === 'SOS_CRITICAL' && report.status !== 'completed';

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
                                                {isCritical ? <span className="text-red-600 animate-pulse">🚨 URGENT SOS</span> : report.incident_type}
                                            </div>
                                            <div className="text-xs text-slate-500 truncate max-w-[200px]">
                                                {report.description}
                                            </div>
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
                                        <td className="py-4 px-2 text-center">
                                            <div className="flex justify-center gap-2">
                                                {/* NEW: View Details Button */}
                                                <button 
                                                    onClick={() => setSelectedReport(report)}
                                                    className="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-blue-700 transition"
                                                >
                                                    VIEW
                                                </button>
                                                
                                                <Link 
                                                    href={route('secretary.blotters.create', { report_id: report.id })}
                                                    className="bg-[#0a2342] text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-slate-800 transition"
                                                >
                                                    LOG TO BLOTTER
                                                </Link>
                                            </div>
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

            {/* Mount the Modal outside the table */}
            <ReportDetailsModal 
                report={selectedReport} 
                onClose={() => setSelectedReport(null)} 
            />
        </SecretaryLayout>
    );
}