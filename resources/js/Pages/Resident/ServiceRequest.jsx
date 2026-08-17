import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function ServiceRequest() {

    const { data, setData, post, processing, errors, reset } = useForm({
        service_type: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('resident.services.store'));
    };

    return (
        <ResidentLayout>
            <Head title="Request Barangay Service" />
            <div className="max-w-2xl mx-auto py-8">
                <div className="bg-white p-8 rounded-lg shadow-sm border border-slate-200">
                    <h2 className="text-2xl font-bold text-slate-800 mb-2">Request Barangay Service</h2>
                    <p className="text-sm text-slate-500 mb-6">Request immediate assistance or deployment of barangay assets to your location.</p>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <label className="block text-sm font-semibold text-slate-700 mb-2">
                                Type of Service / Assistance Needed
                            </label>
                            <select
                                value={data.service_type}
                                onChange={e => setData('service_type', e.target.value)}
                                className="w-full border border-slate-300 rounded-md p-3 text-slate-700 focus:ring-2 focus:ring-[#0a2342] focus:border-[#0a2342]"
                                required
                            >
                                <option value="" disabled>Select a service type...</option>
                                <option value="Medical Assistance (Ambulance)">Medical Assistance (Ambulance)</option>
                                <option value="Police/Tanod Patrol">Police / Tanod Patrol</option>
                                <option value="Fire Emergency">Fire Emergency</option>
                                <option value="Rescue / Calamity Response">Rescue / Calamity Response</option>
                                <option value="Other">Other</option>
                            </select>
                            {errors.barangay_id && <p className="text-red-500 text-xs mt-1">System Error: {errors.barangay_id}</p>}
                        </div>

                        <div className="flex justify-end pt-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="bg-[#0a2342] text-white px-6 py-2.5 rounded-md font-bold hover:bg-slate-800 disabled:opacity-50 transition"
                            >
                                {processing ? 'Submitting...' : 'Submit Request'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </ResidentLayout>
    );
}