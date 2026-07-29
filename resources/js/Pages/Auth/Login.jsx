import React from 'react';
import { useForm, Link } from '@inertiajs/react';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({
        phone_number: '',
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'));
    };

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-6">
            <div className="w-full max-w-md bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                <h1 className="text-2xl font-bold text-blue-900 mb-6 text-center">Resident Login</h1>

                {/* SUCCESS MESSAGE FOR REGISTRATION */}
                {errors.success && (
                    <div className="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded text-sm font-semibold text-center">
                        {errors.success}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Phone Number</label>
                        <input
                            type="text"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm focus:ring-blue-900 focus:border-blue-900"
                            required
                        />
                        {errors.phone_number && <div className="text-red-600 text-xs mt-1">{errors.phone_number}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Password</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm focus:ring-blue-900 focus:border-blue-900"
                            required
                        />
                        {errors.password && <div className="text-red-600 text-xs mt-1">{errors.password}</div>}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-blue-900 text-white font-bold py-2.5 rounded hover:bg-blue-950 transition text-sm disabled:opacity-50"
                    >
                        {processing ? 'Logging in...' : 'Login'}
                    </button>
                </form>

                <div className="mt-6 text-center space-y-2 text-xs">
                    <p className="text-gray-600">
                        Don't have an account?{' '}
                        <Link href={route('register')} className="text-blue-900 font-bold hover:underline">
                            Register here
                        </Link>
                    </p>
                    <p>
                        <Link href={route('staff.login')} className="text-red-700 font-bold hover:underline">
                            Staff Portal Login
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}