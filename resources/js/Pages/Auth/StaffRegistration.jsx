import React from 'react';
import { useForm, Link } from '@inertiajs/react';

export default function StaffRegistration({ barangays }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone_number: '',
        password: '',
        password_confirmation: '',
        barangay_id: '',
        role: 'secretary',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('staff.register'));
    };

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-slate-900 p-6 text-white">
            <div className="w-full max-w-md bg-slate-800 p-8 rounded-lg shadow-xl border border-slate-700">
                <h1 className="text-2xl font-bold mb-6 text-center">Staff Portal Registration</h1>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-slate-300 uppercase mb-1">Full Name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-sm text-white"
                            required
                        />
                        {errors.name && <div className="text-red-400 text-xs mt-1">{errors.name}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-300 uppercase mb-1">Phone Number</label>
                        <input
                            type="text"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-sm text-white"
                            required
                        />
                        {errors.phone_number && <div className="text-red-400 text-xs mt-1">{errors.phone_number}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-300 uppercase mb-1">Barangay</label>
                        <select
                            value={data.barangay_id}
                            onChange={(e) => setData('barangay_id', e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-sm text-white"
                            required
                        >
                            <option value="">-- Select Barangay --</option>
                            {barangays.map((b) => (
                                <option key={b.id} value={b.id}>{b.name}</option>
                            ))}
                        </select>
                        {errors.barangay_id && <div className="text-red-400 text-xs mt-1">{errors.barangay_id}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-300 uppercase mb-1">Staff Role</label>
                        <select
                            value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-sm text-white"
                            required
                        >
                            <option value="secretary">Secretary</option>
                            <option value="vawc">VAWC Officer</option>
                            <option value="admin">Admin</option>
                        </select>
                        {errors.role && <div className="text-red-400 text-xs mt-1">{errors.role}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-300 uppercase mb-1">Password</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-sm text-white"
                            required
                        />
                        {errors.password && <div className="text-red-400 text-xs mt-1">{errors.password}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-slate-300 uppercase mb-1">Confirm Password</label>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-sm text-white"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-2.5 rounded transition text-sm"
                    >
                        Register Staff Account
                    </button>
                </form>

                <div className="mt-6 text-center text-xs">
                    <p className="text-slate-400">
                        Already have a staff account?{' '}
                        <Link href={route('staff.login')} className="text-blue-400 font-bold hover:underline">
                            Login here
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}