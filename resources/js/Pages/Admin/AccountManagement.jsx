import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AccountManagement({ staffAccounts }) {
    const { data, setData, post, reset, processing, errors } = useForm({
        name: '',
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
                <div className="bg-white p-6 rounded-lg shadow">
                    <h2 className="text-xl font-bold mb-4">Create Administrative Account</h2>
                    <form onSubmit={submit} className="space-y-4 max-w-xl">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                                value={data.name} onChange={e => setData('name', e.target.value)} required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                                value={data.email} onChange={e => setData('email', e.target.value)} required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Role</label>
                            <select className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                                value={data.role} onChange={e => setData('role', e.target.value)}>
                                <option value="secretary">Barangay Secretary</option>
                                <option value="vawc_officer">VAWC Officer</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" className="mt-1 block w-full rounded-md border-gray-300 shadow-sm" 
                                value={data.password} onChange={e => setData('password', e.target.value)} required />
                        </div>
                        <button type="submit" disabled={processing} className="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                            Create Account
                        </button>
                    </form>
                </div>

                {/* Staff Directory Table */}
                <div className="bg-white p-6 rounded-lg shadow overflow-x-auto">
                    <h2 className="text-xl font-bold mb-4">Active Staff Accounts</h2>
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {staffAccounts.map((account) => (
                                <tr key={account.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{account.name}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{account.email}</td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{account.role.replace('_', ' ')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}