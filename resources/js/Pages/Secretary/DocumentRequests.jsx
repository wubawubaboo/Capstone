import React from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { router } from '@inertiajs/react';

export default function DocumentRequests({ requests }) {
    const docs = requests?.data || [];

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
                <h2 className="text-xl font-bold text-slate-900 mb-6">Document Requests</h2>

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
                                <td colSpan="6" className="py-6 text-center text-slate-500">No document requests at this time.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SecretaryLayout>
    );
}