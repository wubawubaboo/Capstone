import React from 'react';
import { Head, useForm, usePage, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

const emptyForm = {
    full_name: '',
    phone_number: '',
    barangay_id: '',
    role: 'secretary',
    password: '',
    address: '',
    date_of_birth: '',
    start_of_residency: '',
};

export default function AccountManagement({ staffAccounts, barangays }) {
    const { auth } = usePage().props;
    const [editingId, setEditingId] = React.useState(null);

    const { data, setData, post, put, reset, processing, errors } = useForm(emptyForm);

    const isEditing = editingId !== null;

    const startEdit = (account) => {
        setEditingId(account.id);
        setData({
            full_name: account.full_name,
            phone_number: account.phone_number,
            barangay_id: account.barangay_id ?? '',
            role: account.role,
            password: '',
            address: account.address ?? '',
            date_of_birth: account.date_of_birth ? account.date_of_birth.substring(0, 10) : '',
            start_of_residency: account.start_of_residency ?? '',
        });
    };

    const cancelEdit = () => {
        setEditingId(null);
        reset();
    };

    const submit = (e) => {
        e.preventDefault();

        if (isEditing) {
            put(route('admin.accounts.update', editingId), {
                onSuccess: () => {
                    setEditingId(null);
                    reset();
                },
            });
        } else {
            post(route('admin.accounts.store'), { onSuccess: () => reset() });
        }
    };

    const destroy = (account) => {
        if (!confirm(`Delete the account for ${account.full_name}? This cannot be undone.`)) return;

        router.delete(route('admin.accounts.destroy', account.id));
    };

    return (
        <AdminLayout>
            <Head title="Account Management" />
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-8">

                {/* Account Creation / Edit Form */}
                <div className="bg-white p-6 rounded-lg shadow border border-slate-200">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-bold text-slate-900">
                            {isEditing ? 'Edit Administrative Account' : 'Create Administrative Account'}
                        </h2>
                        {isEditing && (
                            <button
                                type="button"
                                onClick={cancelEdit}
                                className="text-xs font-bold text-slate-500 hover:text-slate-800"
                            >
                                Cancel edit
                            </button>
                        )}
                    </div>
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
                            <label className="block text-sm font-medium text-slate-700 mb-1">Phone Number</label>
                            <input
                                type="text"
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors"
                                value={data.phone_number}
                                onChange={e => setData('phone_number', e.target.value)}
                                placeholder="09xxxxxxxxx"
                                required
                            />
                            {errors.phone_number && <p className="text-red-500 text-xs mt-1">{errors.phone_number}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Address</label>
                            <input
                                type="text"
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors"
                                value={data.address}
                                onChange={e => setData('address', e.target.value)}
                                placeholder="Street, Phase, or Block No."
                                required
                            />
                            {errors.address && <p className="text-red-500 text-xs mt-1">{errors.address}</p>}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Date of Birth</label>
                                <input
                                    type="date"
                                    className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors"
                                    value={data.date_of_birth}
                                    onChange={e => setData('date_of_birth', e.target.value)}
                                    required
                                />
                                {errors.date_of_birth && <p className="text-red-500 text-xs mt-1">{errors.date_of_birth}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Start of Residency (Year)</label>
                                <input
                                    type="number"
                                    min="1900"
                                    max={new Date().getFullYear()}
                                    className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors"
                                    value={data.start_of_residency}
                                    onChange={e => setData('start_of_residency', e.target.value)}
                                    placeholder="e.g. 2015"
                                    required
                                />
                                {errors.start_of_residency && <p className="text-red-500 text-xs mt-1">{errors.start_of_residency}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">Role</label>
                            <select
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors bg-white"
                                value={data.role}
                                onChange={e => setData('role', e.target.value)}
                                required
                            >
                                <option value="secretary">Barangay Secretary</option>
                                <option value="vawc_officer">VAWC Officer</option>
                                <option value="admin">Administrator</option>
                            </select>
                            {errors.role && <p className="text-red-500 text-xs mt-1">{errors.role}</p>}
                        </div>

                        {data.role !== 'admin' && (
                            <div>
                                <label className="block text-sm font-medium text-slate-700 mb-1">Barangay</label>
                                <select
                                    className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors bg-white"
                                    value={data.barangay_id}
                                    onChange={e => setData('barangay_id', e.target.value)}
                                    required
                                >
                                    <option value="" disabled>Select a Barangay</option>
                                    {barangays && barangays.map((barangay) => (
                                        <option key={barangay.id} value={barangay.id}>
                                            {barangay.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.barangay_id && <p className="text-red-500 text-xs mt-1">{errors.barangay_id}</p>}
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-slate-700 mb-1">
                                Password {isEditing && <span className="font-normal text-slate-400">(leave blank to keep current password)</span>}
                            </label>
                            <input
                                type="password"
                                className="block w-full rounded-md border border-slate-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm transition-colors"
                                value={data.password}
                                onChange={e => setData('password', e.target.value)}
                                required={!isEditing}
                            />
                            {errors.password && <p className="text-red-500 text-xs mt-1">{errors.password}</p>}
                        </div>

                        <div className="pt-2 flex items-center gap-3">
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-blue-600 text-white px-6 py-2 rounded shadow text-sm font-bold hover:bg-blue-700 transition-colors disabled:opacity-50"
                            >
                                {isEditing ? 'Save Changes' : 'Create Account'}
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
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Phone Number</th>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Address</th>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Barangay</th>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Role</th>
                                <th className="px-6 py-3 font-bold text-slate-500 uppercase text-xs tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100 bg-white">
                            {staffAccounts && staffAccounts.length > 0 ? (
                                staffAccounts.map((account) => (
                                    <tr key={account.id} className="hover:bg-slate-50 transition-colors">
                                        <td className="px-6 py-4 whitespace-nowrap font-medium text-slate-900">
                                            {account.full_name}
                                            {auth.user?.id === account.id && (
                                                <span className="ml-2 text-xs font-normal text-blue-500">(you)</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                            {account.phone_number}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                            {account.address || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600">
                                            {account.barangay?.name || 'N/A'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-slate-600 capitalize">
                                            {account.role.replace('_', ' ')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            <div className="flex items-center gap-3">
                                                <button
                                                    type="button"
                                                    onClick={() => startEdit(account)}
                                                    className="text-xs font-bold text-blue-600 hover:text-blue-800"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => destroy(account)}
                                                    disabled={auth.user?.id === account.id}
                                                    className="text-xs font-bold text-red-600 hover:text-red-800 disabled:opacity-30 disabled:cursor-not-allowed"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="6" className="px-6 py-8 text-center text-slate-500">
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
