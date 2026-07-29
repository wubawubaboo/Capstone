import React from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function AccountRequests({ requests }) {
    const accounts = requests?.data || [];

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <h2 className="text-xl font-bold text-slate-900 mb-6">Pending Account Verification</h2>

                <table className="w-full text-left text-sm">
                    <thead>
                        <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                            <th className="py-3 px-2">Name</th>
                            <th className="py-3 px-2">Phone Number</th>
                            <th className="py-3 px-2">Barangay</th>
                            <th className="py-3 px-2">Submitted ID</th>
                            <th className="py-3 px-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {accounts.length > 0 ? accounts.map((item) => (
                            <tr key={item.id} className="hover:bg-slate-50">
                                <td className="py-4 px-2 font-medium text-slate-800">{item.full_name}</td>
                                <td className="py-4 px-2 text-slate-600">{item.phone_number}</td>
                                <td className="py-4 px-2 text-slate-600">{item.barangay?.name || 'N/A'}</td>
                                <td className="py-4 px-2 text-blue-600 underline cursor-pointer">View ID</td>
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
                        )) : (
                            <tr>
                                <td colSpan="5" className="py-6 text-center text-slate-500">No pending account requests.</td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SecretaryLayout>
    );
}