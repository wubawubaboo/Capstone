import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AccountManagement({ staffAccounts }) {
    const { data, setData, post, reset, processing, errors } = useForm({
        full_name: '',
        email: '',
        role: 'secretary',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.accounts.store'), { onSuccess: () => reset() });
    };

    return (
        <AdminLayout>
            <Head title="Account Management" />
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-8">
                
                {/* Account Creation Form */}
                <div className="bg-white p-6 rounded-lg shadow border border-slate-200">
                    <h2 className="text-xl font-bold mb-4 text-slate-900">Create Administrative Account</h2>
                    <form onSubmit={submit} className="space-y-4 max-w-xl">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Name</label>
                            <input 
                                type="text" 
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors" 
                                value={data.full_name} 
                                onChange={e => setData('full_name', e.target.value)} 
                                required 
                            />
                            {errors.full_name && <p className="text-red-500 text-xs mt-1">{errors.full_name}</p>}
                        </div>
                        
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input 
                                type="email" 
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors" 
                                value={data.email} 
                                onChange={e => setData('email', e.target.value)} 
                                required 
                            />
                            {errors.email && <p className="text-red-500 text-xs mt-1">{errors.email}</p>}
                        </div>
                        
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Role</label>
                            <select 
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors bg-white" 
                                value={data.role} 
                                onChange={e => setData('role', e.target.value)}
                            >
                                <option value="secretary">Barangay Secretary</option>
                                <option value="vawc_officer">VAWC Officer</option>
                            </select>
                            {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                        </div>
                        
                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Password</label>
                            <input 
                                type="password" 
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors" 
                                value={data.password} 
                                onChange={e => setData('password', e.target.value)} 
                                required 
                            />
                            {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                        </div>
                        
                        <div className="pt-2">
                            <button 
                                type="submit" 
                                disabled={processing} 
                                className="bg-blue-600 text-white px-6 py-2 rounded shadow text-sm font-bold hover:bg-blue-700 transition-colors disabled:opacity-50"
                            >
                                Create Account
                            </button>
                        </div>
                    </form>
                </div>

                {/* Staff Directory Table */}
                <div className="bg-white p-6 rounded-lg shadow border border-slate-200 overflow-x-auto">
                    <h2 className="text-xl font-bold mb-4 text-slate-900">Active Staff Accounts</h2>
                    <table className="min-w-full text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Name</th>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Email</th>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Role</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white">
                            {staffAccounts && staffAccounts.length > 0 ? (
                                staffAccounts.map((account) => (
                                    <tr key={account.id} className="hover:bg-slate-50 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap font-medium text-slate-900">
                                            {account.full_name}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                            {account.email}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600 capitalize">
                                            {account.role.replace('_', ' ')}
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="3" className="px-6 py-8 text-center text-slate-500">
                                        No active staff accounts found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}