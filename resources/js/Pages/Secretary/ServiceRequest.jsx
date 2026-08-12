import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function ServiceRequests({ serviceRequests, availableAssets }) {
    const requests = serviceRequests?.data || [];
    const [selectedAsset, setSelectedAsset] = useState({});

    const handleAssignAsset = (requestId) => {
        const assetId = selectedAsset[requestId];
        if (!assetId) {
            alert("Please select an asset to dispatch first.");
            return;
        }

        router.post(route('secretary.service-requests.assign', requestId), {
            asset_id: assetId
        });
    };

    const handleCompleteService = (requestId) => {
        if (confirm("Are you sure you want to mark this service as completed? The asset will be returned to base.")) {
            router.post(route('secretary.service-requests.complete', requestId));
        }
    };

    const getStatusColor = (status) => {
        switch (status) {
            case 'Pending': return 'bg-yellow-100 text-yellow-800';
            case 'In Progress': return 'bg-blue-100 text-blue-800';
            case 'Completed': return 'bg-emerald-100 text-emerald-800';
            default: return 'bg-slate-200 text-slate-700';
        }
    };

    return (
        <SecretaryLayout>
            <Head title="Service Requests" />
            
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-slate-900">Service & Dispatch Requests</h2>
                    <p className="text-sm text-slate-500">Manage community requests and dispatch barangay assets.</p>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr className="border-b border-slate-200 text-slate-600 font-semibold bg-slate-50">
                                <th className="py-3 px-4">Request ID</th>
                                <th className="py-3 px-4">Requester</th>
                                <th className="py-3 px-4">Service Type</th>
                                <th className="py-3 px-4">Status</th>
                                <th className="py-3 px-4">Dispatched Asset</th>
                                <th className="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {requests.length > 0 ? requests.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50 transition">
                                    <td className="py-3 px-4 font-semibold text-slate-800">REQ-{row.id}</td>
                                    <td className="py-3 px-4 text-slate-700">{row.requester?.full_name || 'Unknown'}</td>
                                    <td className="py-3 px-4 text-slate-700 font-medium">{row.service_type}</td>
                                    <td className="py-3 px-4">
                                        <span className={`px-3 py-1 rounded-full text-xs font-bold ${getStatusColor(row.status)}`}>
                                            {row.status}
                                        </span>
                                    </td>
                                    <td className="py-3 px-4">
                                        {row.status === 'Pending' ? (
                                            <select
                                                className="text-xs border border-slate-300 rounded px-2 py-1 w-full max-w-[150px]"
                                                onChange={(e) => setSelectedAsset({ ...selectedAsset, [row.id]: e.target.value })}
                                                defaultValue=""
                                            >
                                                <option value="" disabled>Select Asset...</option>
                                                {availableAssets.map(asset => (
                                                    <option key={asset.id} value={asset.id}>
                                                        {asset.asset_name}
                                                    </option>
                                                ))}
                                            </select>
                                        ) : (
                                            <span className="text-slate-600 font-semibold">{row.asset?.asset_name || 'N/A'}</span>
                                        )}
                                    </td>
                                    <td className="py-3 px-4 text-right">
                                        {row.status === 'Pending' && (
                                            <button
                                                onClick={() => handleAssignAsset(row.id)}
                                                className="bg-[#0a2342] text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-slate-800"
                                            >
                                                Dispatch
                                            </button>
                                        )}
                                        {row.status === 'In Progress' && (
                                            <button
                                                onClick={() => handleCompleteService(row.id)}
                                                className="bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-emerald-700"
                                            >
                                                Complete
                                            </button>
                                        )}
                                        {row.status === 'Completed' && (
                                            <span className="text-emerald-600 font-bold text-xs">✓ Done</span>
                                        )}
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="6" className="py-6 text-center text-slate-500">No service requests found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </SecretaryLayout>
    );
}