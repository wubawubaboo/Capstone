import React from 'react';
import { useForm, Head, Link } from '@inertiajs/react';

export default function StaffLogin() {
    const { data, setData, post, processing, errors } = useForm({
        phone_number: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/portal/secure-login');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-900 text-slate-100">
            <form onSubmit={submit} className="w-full max-w-md p-8 bg-slate-800 rounded-lg shadow-xl border border-slate-700">
                <h2 className="text-2xl font-bold mb-2 text-center">Staff Portal</h2>
                <p className="text-sm text-slate-400 mb-6 text-center">Authorized personnel only</p>
                
                    {/* Phone Number Field */}
                    <div className="mb-4">
                        <label className="block text-white-700 text-sm font-bold mb-2">Phone Number</label>
                        <input
                            type="tel"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md"
                            required
                        />
                        {errors.phone_number && <div className="text-red-500 text-sm mt-1">{errors.phone_number}</div>}
                    </div>

                    <div className="mb-6">
                        <label className="block text-white-700 text-sm font-bold mb-2">Password</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md"
                            required
                        />
                        {errors.password && <div className="text-red-500 text-sm mt-1">{errors.password}</div>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none"
                    >
                        {processing ? 'Logging in...' : 'Log in'}
                    </button>
                </form>
            </div>
    );
}