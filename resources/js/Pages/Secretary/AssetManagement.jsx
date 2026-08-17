import React from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function AssetManagement({ assets }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        asset_name: '',
        asset_type: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('secretary.assets.store'), {
            onSuccess: () => reset('asset_name', 'asset_type'),
        });
    };

    const handleToggleAvailability = (assetId) => {
        router.patch(route('secretary.assets.toggle', assetId), {}, { preserveScroll: true });
    };

    const handleArchive = (assetId) => {
        if (confirm('Are you sure you want to archive this asset?')) {
            router.delete(route('secretary.assets.archive', assetId), { preserveScroll: true });
        }
    };

    return (
        <SecretaryLayout>
            <Head title="Asset Management" />
            
            <div className="mb-6">
                <h2 className="text-2xl font-bold text-slate-900">Barangay Assets</h2>
                <p className="text-sm text-slate-500">Manage and add dispatchable barangay equipment and vehicles.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                {/* Add Asset Form */}
                <div className="bg-white p-6 rounded-lg shadow-sm border border-slate-200 h-fit">
                    <h3 className="text-lg font-semibold text-slate-800 mb-4">Add New Asset</h3>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Asset Name</label>
                            <input
                                type="text"
                                value={data.asset_name}
                                onChange={e => setData('asset_name', e.target.value)}
                                className="w-full border border-slate-300 rounded-md p-2.5"
                                placeholder="e.g., Ambulance 1"
                                required
                            />
                            {errors.asset_name && <p className="text-red-500 text-xs mt-1">{errors.asset_name}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Asset Type</label>
                            <input
                                type="text"
                                value={data.asset_type}
                                onChange={e => setData('asset_type', e.target.value)}
                                className="w-full border border-slate-300 rounded-md p-2.5"
                                placeholder="e.g., Vehicle, Equipment"
                                required
                            />
                            {errors.asset_type && <p className="text-red-500 text-xs mt-1">{errors.asset_type}</p>}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full bg-[#0a2342] text-white px-4 py-2 rounded-md font-bold hover:bg-slate-800"
                        >
                            {processing ? 'Adding...' : '+ Add Asset'}
                        </button>
                    </form>
                </div>

                {/* Asset Inventory Table */}
                <div className="md:col-span-2 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
                    <table className="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr className="border-b border-slate-200 bg-slate-50">
                                <th className="py-3 px-4">Asset ID</th>
                                <th className="py-3 px-4">Name</th>
                                <th className="py-3 px-4">Type</th>
                                <th className="py-3 px-4">Status</th>
                                <th className="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {assets.data && assets.data.length > 0 ? assets.data.map((asset) => (
                                <tr key={asset.id} className="hover:bg-slate-50 transition">
                                    <td className="py-3 px-4 font-semibold">AST-{asset.id}</td>
                                    <td className="py-3 px-4">{asset.asset_name}</td>
                                    <td className="py-3 px-4">{asset.asset_type}</td>
                                    <td className="py-3 px-4">
                                        <span className={`px-3 py-1 rounded-full text-xs font-bold ${asset.is_available ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}`}>
                                            {asset.is_available ? 'Available' : 'Unavailable'}
                                        </span>
                                    </td>
                                    <td className="py-3 px-4 flex justify-end gap-2">
                                        <button onClick={() => handleToggleAvailability(asset.id)} className="bg-slate-100 px-3 py-1.5 rounded text-xs font-bold hover:bg-slate-200">
                                            {asset.is_available ? 'Mark Unavailable' : 'Mark Available'}
                                        </button>
                                        <button onClick={() => handleArchive(asset.id)} className="bg-red-50 text-red-600 px-3 py-1.5 rounded text-xs font-bold hover:bg-red-100">
                                            Archive
                                        </button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="5" className="py-6 text-center text-slate-500">No active assets found.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </SecretaryLayout>
    );
}