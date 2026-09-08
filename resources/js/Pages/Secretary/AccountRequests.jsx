import React, { useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Link, useForm, router } from '@inertiajs/react';

const RejectAccountModal = ({ user, onClose }) => {
    const { data, setData, post, processing, errors } = useForm({
        reason: 'Blurry ID',
        custom_message: '',
    });

    const submitRejection = (e) => {
        e.preventDefault();
        post(route('secretary.account-requests.reject', user.id), {
            onSuccess: () => onClose(),
            preserveScroll: true,
        });
    };

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-slate-900 bg-opacity-50 z-50 p-4">
            <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-md border border-slate-200">
                <h3 className="text-lg font-bold mb-4 text-slate-800">Decline Verification for {user.full_name}</h3>
                <form onSubmit={submitRejection}>
                    <div className="mb-4">
                        <label className="block text-sm font-bold text-slate-700 mb-2">Select Reason</label>
                        <select value={data.reason} onChange={e => setData('reason', e.target.value)} className="w-full border-slate-300 rounded shadow-sm text-sm p-2">
                            <option value="Blurry ID">Blurry or Unreadable ID</option>
                            <option value="Mismatched Information">Mismatched Information</option>
                            <option value="Invalid ID">Invalid or Unsupported ID</option>
                            <option value="Expired ID">Expired ID</option>
                        </select>
                        {errors.reason && <p className="text-red-500 text-xs mt-1">{errors.reason}</p>}
                    </div>
                    <div className="mb-6">
                        <label className="block text-sm font-bold text-slate-700 mb-2">Additional Notes (Optional)</label>
                        <textarea value={data.custom_message} onChange={e => setData('custom_message', e.target.value)} placeholder="Add any specific details here..." className="w-full border-slate-300 rounded shadow-sm text-sm p-2" rows="3" maxLength="150" />
                        {errors.custom_message && <p className="text-red-500 text-xs mt-1">{errors.custom_message}</p>}
                    </div>
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="px-4 py-2 bg-slate-200 text-slate-700 rounded text-sm font-bold">CANCEL</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-red-700 text-white rounded text-sm font-bold">{processing ? 'PROCESSING...' : 'DECLINE & SEND SMS'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
};

const EditAccountModal = ({ user, type, onClose }) => {
    const { data, setData, put, processing, errors } = useForm({
        full_name: user.full_name,
        phone_number: user.phone_number,
    });

    const submitUpdate = (e) => {
        e.preventDefault();
        const updateRoute = type === 'resident' 
            ? route('secretary.resident.update', user.id) 
            : route('secretary.police.update', user.id);

        put(updateRoute, {
            onSuccess: () => onClose(),
            preserveScroll: true,
        });
    };

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-slate-900 bg-opacity-50 z-50 p-4">
            <div className="bg-white p-6 rounded-lg shadow-xl w-full max-w-md border border-slate-200">
                <h3 className="text-lg font-bold mb-4 text-slate-800">Edit {type === 'resident' ? 'Resident' : 'Police'} Account</h3>
                <form onSubmit={submitUpdate}>
                    <div className="mb-4">
                        <label className="block text-sm font-bold text-slate-700 mb-1">Full Name</label>
                        <input type="text" value={data.full_name} onChange={e => setData('full_name', e.target.value)} className="w-full border-slate-300 rounded text-sm p-2" required />
                        {errors.full_name && <p className="text-red-500 text-xs mt-1">{errors.full_name}</p>}
                    </div>
                    <div className="mb-6">
                        <label className="block text-sm font-bold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" value={data.phone_number} onChange={e => setData('phone_number', e.target.value)} className="w-full border-slate-300 rounded text-sm p-2" maxLength="11" required />
                        {errors.phone_number && <p className="text-red-500 text-xs mt-1">{errors.phone_number}</p>}
                    </div>
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="px-4 py-2 bg-slate-200 text-slate-700 rounded text-sm font-bold">CANCEL</button>
                        <button type="submit" disabled={processing} className="px-4 py-2 bg-emerald-600 text-white rounded text-sm font-bold">{processing ? 'SAVING...' : 'SAVE CHANGES'}</button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default function AccountRequests({ pendingResidents = [], verifiedResidents = [], policeAccounts = [] }) {
    const [userToReject, setUserToReject] = useState(null);
    const [userToEdit, setUserToEdit] = useState(null);
    const [editType, setEditType] = useState(null); 
    const [activeTab, setActiveTab] = useState('pending'); // 'pending', 'verified', or 'police'

    // Form setup for creating Barangay Police accounts
    const { data: policeData, setData: setPoliceData, post: postPolice, processing: policeProcessing, errors: policeErrors, reset: resetPolice } = useForm({
        name: '',
        phone_number: '',
        password: '',
        password_confirmation: '',
    });

    const submitPolice = (e) => {
        e.preventDefault();
        postPolice(route('secretary.police.store'), {
            onSuccess: () => resetPolice(),
            preserveScroll: true,
        });
    };

    const deleteAccount = (userId, type) => {
        if (confirm(`Are you sure you want to permanently delete this ${type} account? This cannot be undone.`)) {
            const deleteRoute = type === 'resident' 
                ? route('secretary.resident.destroy', userId) 
                : route('secretary.police.destroy', userId);
            router.delete(deleteRoute, { preserveScroll: true });
        }
    };

    const openEditModal = (user, type) => {
        setUserToEdit(user);
        setEditType(type);
    };

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200 mb-6">
                
                {/* 3-Tab Navigation */}
                <div className="flex gap-4 border-b border-slate-200 mb-6 overflow-x-auto">
                    <button 
                        onClick={() => setActiveTab('pending')}
                        className={`pb-3 text-sm font-bold uppercase whitespace-nowrap transition-colors ${activeTab === 'pending' ? 'border-b-2 border-slate-800 text-slate-900' : 'text-slate-400 hover:text-slate-600'}`}
                    >
                        Pending Requests ({pendingResidents.length})
                    </button>
                    <button 
                        onClick={() => setActiveTab('verified')}
                        className={`pb-3 text-sm font-bold uppercase whitespace-nowrap transition-colors ${activeTab === 'verified' ? 'border-b-2 border-slate-800 text-slate-900' : 'text-slate-400 hover:text-slate-600'}`}
                    >
                        Verified Residents
                    </button>
                    <button 
                        onClick={() => setActiveTab('police')}
                        className={`pb-3 text-sm font-bold uppercase whitespace-nowrap transition-colors ${activeTab === 'police' ? 'border-b-2 border-slate-800 text-slate-900' : 'text-slate-400 hover:text-slate-600'}`}
                    >
                        Manage Police
                    </button>
                </div>

                {/* PENDING REQUESTS TAB CONTENT */}
                {activeTab === 'pending' && (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                                    <th className="py-3 px-2">Name</th>
                                    <th className="py-3 px-2">Phone Number</th>
                                    <th className="py-3 px-2">Submitted ID</th>
                                    <th className="py-3 px-2">Selfie w/ ID</th>
                                    <th className="py-3 px-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {pendingResidents.length > 0 ? pendingResidents.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50">
                                        <td className="py-4 px-2 font-medium text-slate-800">{item.full_name}</td>
                                        <td className="py-4 px-2 text-slate-600">{item.phone_number}</td>
                                        <td className="py-4 px-2 font-bold">
                                            {item.id_photo_path ? (
                                                <a href={route('secretary.account-requests.id-photo', item.id)} target="_blank" rel="noreferrer" className="text-blue-600 hover:text-blue-800 underline flex items-center gap-1">
                                                    🔍 View ID
                                                </a>
                                            ) : (
                                                <span className="text-red-500">No ID Provided</span>
                                            )}
                                        </td>
                                        <td className="py-4 px-2 font-bold">
                                            {item.selfie_id_path ? (
                                                <a href={route('secretary.account-requests.selfie-photo', item.id)} target="_blank" rel="noreferrer" className="text-emerald-600 hover:text-emerald-800 underline flex items-center gap-1">
                                                    📸 View Selfie
                                                </a>
                                            ) : (
                                                <span className="text-red-500">No Selfie</span>
                                            )}
                                        </td>
                                        <td className="py-4 px-2 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Link 
                                                    href={route('secretary.account-requests.approve', item.id)} 
                                                    method="post" as="button" preserveScroll
                                                    className="bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-emerald-700 transition"
                                                >
                                                    APPROVE
                                                </Link>
                                                <button 
                                                    type="button" onClick={() => setUserToReject(item)}
                                                    className="bg-amber-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-amber-700 transition"
                                                >
                                                    REJECT
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="5" className="py-6 text-center text-slate-500">No pending account requests.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* VERIFIED RESIDENTS TAB CONTENT */}
                {activeTab === 'verified' && (
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 text-slate-500 font-bold uppercase text-xs">
                                    <th className="py-3 px-2">Name</th>
                                    <th className="py-3 px-2">Phone Number</th>
                                    <th className="py-3 px-2">Uploaded Files</th>
                                    <th className="py-3 px-2 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {verifiedResidents.length > 0 ? verifiedResidents.map((item) => (
                                    <tr key={item.id} className="hover:bg-slate-50">
                                        <td className="py-4 px-2 font-medium text-slate-800">{item.full_name}</td>
                                        <td className="py-4 px-2 text-slate-600">{item.phone_number}</td>
                                        <td className="py-4 px-2 text-xs font-bold space-y-1">
                                            {item.id_photo_path ? <a href={route('secretary.account-requests.id-photo', item.id)} target="_blank" className="text-blue-600 hover:underline block">🔍 View ID</a> : <span className="text-slate-400 block">No ID</span>}
                                            {item.selfie_id_path ? <a href={route('secretary.account-requests.selfie-photo', item.id)} target="_blank" className="text-emerald-600 hover:underline block">📸 View Selfie</a> : <span className="text-slate-400 block">No Selfie</span>}
                                        </td>
                                        <td className="py-4 px-2 text-right">
                                            <div className="flex justify-end gap-2">
                                                <button onClick={() => openEditModal(item, 'resident')} className="bg-blue-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-blue-700">EDIT</button>
                                                <button onClick={() => deleteAccount(item.id, 'resident')} className="bg-red-700 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-red-800">DELETE</button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="4" className="py-6 text-center text-slate-500">No verified residents found.</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* POLICE TAB CONTENT */}
                {activeTab === 'police' && (
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Police Creation Form */}
                        <div className="lg:col-span-1 bg-slate-50 p-5 rounded border border-slate-200 h-fit">
                            <h3 className="text-sm font-bold uppercase text-slate-800 mb-4">Register Police</h3>
                            <form onSubmit={submitPolice} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Full Name</label>
                                    <input type="text" value={policeData.name} onChange={e => setPoliceData('name', e.target.value)} className="w-full border-slate-300 rounded text-sm p-2" required />
                                    {policeErrors.name && <p className="text-red-500 text-xs mt-1">{policeErrors.name}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                                    <input type="text" value={policeData.phone_number} onChange={e => setPoliceData('phone_number', e.target.value)} className="w-full border-slate-300 rounded text-sm p-2" maxLength="11" required />
                                    {policeErrors.phone_number && <p className="text-red-500 text-xs mt-1">{policeErrors.phone_number}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Password</label>
                                    <input type="password" value={policeData.password} onChange={e => setPoliceData('password', e.target.value)} className="w-full border-slate-300 rounded text-sm p-2" required />
                                    {policeErrors.password && <p className="text-red-500 text-xs mt-1">{policeErrors.password}</p>}
                                </div>
                                <div>
                                    <label className="block text-xs font-bold text-slate-700 mb-1">Confirm Password</label>
                                    <input type="password" value={policeData.password_confirmation} onChange={e => setPoliceData('password_confirmation', e.target.value)} className="w-full border-slate-300 rounded text-sm p-2" required />
                                </div>
                                <button type="submit" disabled={policeProcessing} className="w-full bg-slate-800 text-white py-2 rounded text-sm font-bold hover:bg-slate-900 transition mt-2">
                                    {policeProcessing ? 'CREATING...' : 'CREATE ACCOUNT'}
                                </button>
                            </form>
                        </div>

                        {/* Active Police List */}
                        <div className="lg:col-span-2">
                            <h3 className="text-sm font-bold uppercase text-slate-800 mb-4">Manage Police Accounts</h3>
                            <div className="overflow-x-auto border border-slate-200 rounded">
                                <table className="w-full text-left text-sm">
                                    <thead>
                                        <tr className="border-b border-slate-200 bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                                            <th className="py-3 px-4">Name</th>
                                            <th className="py-3 px-4">Phone Number</th>
                                            <th className="py-3 px-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {policeAccounts.map(police => (
                                            <tr key={police.id} className="hover:bg-slate-50">
                                                <td className="py-3 px-4 font-medium text-slate-800">{police.full_name}</td>
                                                <td className="py-3 px-4 text-slate-600">{police.phone_number}</td>
                                                <td className="py-3 px-4 text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <button onClick={() => openEditModal(police, 'police')} className="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700">EDIT</button>
                                                        <button onClick={() => deleteAccount(police.id, 'police')} className="bg-red-700 text-white px-3 py-1 rounded text-xs font-bold hover:bg-red-800">DELETE</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {policeAccounts.length === 0 && (
                                            <tr><td colSpan="3" className="py-6 text-center text-slate-500">No police accounts found.</td></tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}
            </div>

            {userToReject && <RejectAccountModal user={userToReject} onClose={() => setUserToReject(null)} />}
            {userToEdit && <EditAccountModal user={userToEdit} type={editType} onClose={() => setUserToEdit(null)} />}
        </SecretaryLayout>
    );
}