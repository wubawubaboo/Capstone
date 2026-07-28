import React from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function AccountRequests() {
    const requests = [
        {
            name: 'Josephina Victoria',
            phone: '0922-563-8218',
            address: "Pugad's Tumana",
            file: 'National ID.jpg',
        },
        {
            name: 'Marcus Keith Catacutan',
            phone: '0943-672-1094',
            address: 'Bon Ehemplo Architects',
            file: 'ID.jpg',
        },
    ];

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <h2 className="text-xl font-bold text-slate-900 mb-6">Pending Verification</h2>

                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                            <th className="py-3 px-2">Name</th>
                            <th className="py-3 px-2">Phone Number</th>
                            <th className="py-3 px-2">Address</th>
                            <th className="py-3 px-2">Submitted ID</th>
                            <th className="py-3 px-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {requests.map((item, index) => (
                            <tr key={index} className="hover:bg-slate-50">
                                <td className="py-4 px-2 font-medium text-slate-800">{item.name}</td>
                                <td className="py-4 px-2 text-slate-600">{item.phone}</td>
                                <td className="py-4 px-2 text-slate-600">{item.address}</td>
                                <td className="py-4 px-2 text-blue-600 underline cursor-pointer">{item.file}</td>
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