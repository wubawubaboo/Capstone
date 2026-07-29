import React from 'react';
import { useForm, Link } from '@inertiajs/react';

export default function Registration({ barangays }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone_number: '',
        password: '',
        password_confirmation: '',
        barangay_id: '',
        id_photo: null,
    });

    const submit = (e) => {
        e.preventDefault();
        // Inertia automatically converts this to multipart/form-data because id_photo is a file
        post(route('register'));
    };

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-6 pt-12 pb-12">
            <div className="w-full max-w-md bg-white p-8 rounded-lg shadow-sm border border-gray-200">
                <h1 className="text-2xl font-bold text-blue-900 mb-6 text-center">Resident Registration</h1>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Full Name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm"
                            required
                        />
                        {errors.name && <div className="text-red-600 text-xs mt-1">{errors.name}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Phone Number</label>
                        <input
                            type="text"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm"
                            required
                        />
                        {errors.phone_number && <div className="text-red-600 text-xs mt-1">{errors.phone_number}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Barangay</label>
                        <select
                            value={data.barangay_id}
                            onChange={(e) => setData('barangay_id', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm bg-white"
                            required
                        >
                            <option value="">-- Select Barangay --</option>
                            {barangays?.map((b) => (
                                <option key={b.id} value={b.id}>{b.name}</option>
                            ))}
                        </select>
                        {errors.barangay_id && <div className="text-red-600 text-xs mt-1">{errors.barangay_id}</div>}
                    </div>

                    {/* NEW ID PHOTO UPLOAD FIELD */}
                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Valid ID Photo (Required for Verification)</label>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('id_photo', e.target.files[0])}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm bg-white cursor-pointer file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-900 hover:file:bg-blue-100"
                            required
                        />
                        {errors.id_photo && <div className="text-red-600 text-xs mt-1">{errors.id_photo}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Password</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm"
                            required
                        />
                        {errors.password && <div className="text-red-600 text-xs mt-1">{errors.password}</div>}
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-gray-700 uppercase mb-1">Confirm Password</label>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="w-full border border-gray-300 rounded p-2.5 text-sm"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-blue-900 text-white font-bold py-2.5 rounded hover:bg-blue-950 transition text-sm disabled:opacity-50"
                    >
                        {processing ? 'Submitting...' : 'Register Account'}
                    </button>
                </form>

                <div className="mt-6 text-center text-xs">
                    <p className="text-gray-600">
                        Already have an account?{' '}
                        <Link href={route('login')} className="text-blue-900 font-bold hover:underline">
                            Login here
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}