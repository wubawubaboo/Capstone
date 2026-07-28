import React from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function DocumentRequests() {
    const requests = [
        {
            name: 'Juan Dela Cruz',
            phone: '0922-563-8218',
            type: 'Barangay Indigency',
            purpose: 'Scholarship',
        },
    ];

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200 min-h-[500px]">
                <h2 className="text-xl font-bold text-slate-900 mb-6">Document Request</h2>

                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                            <th className="py-3 px-2">Name</th>
                            <th className="py-3 px-2">Phone Number</th>
                            <th className="py-3 px-2">Document Type</th>
                            <th className="py-3 px-2">Purpose</th>
                            <th className="py-3 px-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {requests.map((item, index) => (
                            <tr key={index} className="hover:bg-slate-50">
                                <td className="py-4 px-2 font-medium text-slate-800">{item.name}</td>
                                <td className="py-4 px-2 text-slate-600">{item.phone}</td>
                                <td className="py-4 px-2 text-slate-600">{item.type}</td>
                                <td className="py-4 px-2 text-slate-600">{item.purpose}</td>
                                <td className="py-4 px-2 text-center">
                                    <div className="flex justify-center gap-2">
                                        <button className="bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-emerald-700">
                                            APPROVE
                                        </button>
                                        <button className="bg-red-700 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-red-800">
                                            REJECT
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </SecretaryLayout>
    );
}