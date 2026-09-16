import React, { useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { router } from '@inertiajs/react';
import GenerateReportModal from '@/Components/GenerateReportModal';

export default function DocumentRequests({ requests, filters }) {
    const docs = requests?.data || [];

    const [statusFilter, setStatusFilter] = useState(filters?.status || '');
    const [showReportModal, setShowReportModal] = useState(false);

    const handleFilterChange = (e) => {
        const selectedStatus = e.target.value;
        setStatusFilter(selectedStatus);

        router.get(
            route(route().current()), 
            { status: selectedStatus },
            { preserveState: true, replace: true }
        );
    };

    const handleStatusUpdate = (id, newStatus) => {
        if(confirm(`Are you sure you want to mark this request as ${newStatus}?`)) {
            router.post(route('secretary.document-requests.update-status', { documentRequest: id }), {
                status: newStatus
            });
        }
    };

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200 min-h-[500px]">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-bold text-slate-900">Document Requests</h2>

                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setShowReportModal(true)}
                            className="bg-emerald-700 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-emerald-800 transition whitespace-nowrap inline-block"
                        >
                            ⬇ Generate Report
                        </button>

                        {/* Styled Filter Dropdown */}
                        <div className="relative">
                            <select
                                value={statusFilter}
                                onChange={handleFilterChange}
                                className="appearance-none bg-white border border-slate-200 text-slate-700 text-sm rounded-md pl-4 pr-10 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer hover:bg-slate-50 transition-colors duration-200"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="ready_for_pickup">Ready for Pickup</option>
                                <option value="Claimed">Claimed</option>
                            </select>

                            {/* Custom Chevron Icon */}
                            <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                                <svg className="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                            <th className="py-3 px-2">Name</th>
                            <th className="py-3 px-2">Phone Number</th>
                            <th className="py-3 px-2">Document Type</th>
                            <th className="py-3 px-2">Purpose</th>
                            <th className="py-3 px-2">Status</th>
                            <th className="py-3 px-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {docs.length > 0 ? docs.map((item) => (
                            <tr key={item.id} className="hover:bg-slate-50">
                                <td className="py-4 px-2 font-medium text-slate-800">{item.requester?.full_name}</td>
                                <td className="py-4 px-2 text-slate-600">{item.requester?.phone_number}</td>
                                <td className="py-4 px-2 text-slate-600">{item.document_type?.name || 'Document'}</td>
                                <td className="py-4 px-2 text-slate-600">{item.purpose}</td>
                                <td className="py-4 px-2 text-slate-600">
                                    <span className="bg-slate-100 text-slate-700 px-2 py-1 rounded text-xs font-bold">
                                        {item.status}
                                    </span>
                                </td>
                                <td className="py-4 px-2 text-center">
                                    <div className="flex justify-center gap-2">
                                        <button 
                                            onClick={() => handleStatusUpdate(item.id, 'Ready')}
                                            className="bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-emerald-700"
                                        >
                                            READY
                                        </button>
                                        <button 
                                            onClick={() => handleStatusUpdate(item.id, 'Claimed')}
                                            className="bg-blue-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-blue-700"
                                        >
                                            CLAIMED
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        )) : (
                            <tr>
                                <td colSpan="6" className="py-6 text-center text-slate-500">No document requests match this status.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <GenerateReportModal
                show={showReportModal}
                onClose={() => setShowReportModal(false)}
                exportUrl={route('secretary.document-requests.export')}
                extraParams={statusFilter ? { status: statusFilter } : {}}
            />
        </SecretaryLayout>
    );
}