import React from 'react';
import { useForm, Head, Link } from '@inertiajs/react';

export default function Registration({barangays}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        phone_number: '',
        password: '',
        password_confirmation: '',
        barangay_id: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-100">
            <Head title="Register" />
            
            <div className="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
                <h2 className="text-2xl font-bold text-center mb-6">Create an Account</h2>
                
                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Name</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md"
                            required
                        />
                        {errors.name && <div className="text-red-500 text-sm mt-1">{errors.name}</div>}
                    </div>

                    {/* Phone Number Field */}
                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                        <input
                            type="tel"
                            value={data.phone_number}
                            onChange={(e) => setData('phone_number', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md"
                            required
                        />
                        {errors.phone_number && <div className="text-red-500 text-sm mt-1">{errors.phone_number}</div>}
                    </div>

                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Barangay</label>
                        <select
                            value={data.barangay_id}
                            onChange={(e) => setData('barangay_id', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md bg-white"
                            required
                        >
                            <option value="" disabled>Select your barangay</option>
                            
                            {/* Dynamically render the barangays */}
                            {barangays && barangays.map((barangay) => (
                                <option key={barangay.id} value={barangay.id}>
                                    {barangay.name}
                                </option>
                            ))}
                        </select>
                        {errors.barangay_id && <div className="text-red-500 text-sm mt-1">{errors.barangay_id}</div>}
                    </div>
                    
                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Roles</label>
                        <select
                            value={data.barangay_id}
                            onChange={(e) => setData('barangay_id', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md bg-white"
                            required
                        >
                            <option value="" disabled>Select your barangay</option>
                            
                            {/* Dynamically render the barangays */}
                            {barangays && barangays.map((barangay) => (
                                <option key={barangay.id} value={barangay.id}>
                                    {barangay.name}
                                </option>
                            ))}
                        </select>
                        {errors.barangay_id && <div className="text-red-500 text-sm mt-1">{errors.barangay_id}</div>}
                    </div>
                    
                    <div className="mb-4">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input
                            type="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md"
                            required
                        />
                        {errors.password && <div className="text-red-500 text-sm mt-1">{errors.password}</div>}
                    </div>

                    <div className="mb-6">
                        <label className="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                        <input
                            type="password"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="w-full px-3 py-2 border rounded-md"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded focus:outline-none"
                    >
                        {processing ? 'Registering...' : 'Register'}
                    </button>
                    
                    <div className="mt-4 text-center">
                        <Link href="/login" className="text-sm text-blue-500 hover:underline">
                            Already have an account? Login here.
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    );
}