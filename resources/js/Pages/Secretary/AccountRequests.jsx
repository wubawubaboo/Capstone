import React, { useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Link, useForm } from '@inertiajs/react';

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
                <h3 className="text-lg font-bold mb-4 text-slate-800">
                    Decline Verification for {user.full_name}
                </h3>
                
                <form onSubmit={submitRejection}>
                    <div className="mb-4">
                        <label className="block text-sm font-bold text-slate-700 mb-2">
                            Select Reason
                        </label>
                        <select 
                            value={data.reason} 
                            onChange={e => setData('reason', e.target.value)}
                            className="w-full border-slate-300 rounded shadow-sm text-sm p-2 focus:border-red-500 focus:ring-red-500"
                        >
                            <option value="Blurry ID">Blurry or Unreadable ID</option>
                            <option value="Mismatched Information">Mismatched Information</option>
                            <option value="Invalid ID">Invalid or Unsupported ID</option>
                            <option value="Expired ID">Expired ID</option>
                        </select>
                        {errors.reason && <p className="text-red-500 text-xs mt-1">{errors.reason}</p>}
                    </div>

                    <div className="mb-6">
                        <label className="block text-sm font-bold text-slate-700 mb-2">
                            Additional Notes (Optional)
                        </label>
                        <textarea 
                            value={data.custom_message}
                            onChange={e => setData('custom_message', e.target.value)}
                            placeholder="Add any specific details here..."
                            className="w-full border-slate-300 rounded shadow-sm text-sm p-2 focus:border-red-500 focus:ring-red-500"
                            rows="3"
                            maxLength="150"
                        />
                        {errors.custom_message && <p className="text-red-500 text-xs mt-1">{errors.custom_message}</p>}
                    </div>

                    <div className="flex justify-end gap-2">
                        <button 
                            type="button" 
                            onClick={onClose} 
                            className="px-4 py-2 bg-slate-200 text-slate-700 rounded text-sm font-bold hover:bg-slate-300 transition"
                        >
                            CANCEL
                        </button>
                        <button 
                            type="submit" 
                            disabled={processing} 
                            className="px-4 py-2 bg-red-700 text-white rounded text-sm font-bold hover:bg-red-800 transition disabled:opacity-50"
                        >
                            {processing ? 'PROCESSING...' : 'DECLINE & SEND SMS'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};


export default function AccountRequests({ requests }) {
    const accounts = requests?.data || [];
    
    const [userToReject, setUserToReject] = useState(null);

    return (
        <SecretaryLayout>
            <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-200">
                <h2 className="text-xl font-bold text-slate-900 mb-6">Pending Account Verification</h2>

                <div className="overflow-x-auto">
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
                                    <td className="py-4 px-2 font-bold">
                                        {item.id_photo_path ? (
                                            <a 
                                                href={route('secretary.account-requests.id-photo', item.id)} 
                                                target="_blank" 
                                                rel="noreferrer" 
                                                className="text-blue-600 hover:text-blue-800 underline flex items-center gap-1"
                                            >
                                                🔍 View ID
                                            </a>
                                        ) : (
                                            <span className="text-red-500">No ID Provided</span>
                                        )}
                                    </td>
                                    <td className="py-4 px-2 text-center">
                                        <div className="flex justify-center gap-2">
                                            <Link 
                                                href={route('secretary.account-requests.approve', item.id)} 
                                                method="post" 
                                                as="button" 
                                                preserveScroll
                                                className="bg-emerald-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-emerald-700 transition"
                                            >
                                                APPROVE
                                            </Link>
                                            
                                            <button 
                                                type="button"
                                                onClick={() => setUserToReject(item)}
                                                className="bg-red-700 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-red-800 transition"
                                            >
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
            </div>

            {userToReject && (
                <RejectAccountModal 
                    user={userToReject} 
                    onClose={() => setUserToReject(null)} 
                />
            )}
        </SecretaryLayout>
    );
}