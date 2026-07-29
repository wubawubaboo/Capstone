import React from 'react';
import { useForm, Link } from '@inertiajs/react';

export default function StaffLogin() {
    const { data, setData, post, processing, errors } = useForm({
        phone_number: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('staff.login'));
    };

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-slate-900 p-6 text-white">
            <div className="w-full max-w-md bg-slate-800 p-8 rounded-lg shadow-xl border border-slate-700">
                <h1 className="text-2xl font-bold mb-6 text-center">Staff Secure Portal</h1>

                <form onSubmit={submit} className="space-y-4">
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

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-red-700 hover:bg-red-800 text-white font-bold py-2.5 rounded transition text-sm"
                    >
                        Secure Login
                    </button>
                </form>

                <div className="mt-6 text-center space-y-2 text-xs">
                    <p className="text-slate-400">
                        Need staff registration?{' '}
                        <Link href={route('staff.register')} className="text-blue-400 font-bold hover:underline">
                            Register here
                        </Link>
                    </p>
                    <p>
                        <Link href={route('login')} className="text-slate-300 hover:underline">
                            &larr; Back to Resident Login
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}