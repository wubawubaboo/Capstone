import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function ServiceRequest() {
    const { auth } = usePage().props;
    const user = auth?.user || {};

    const { data, setData, post, processing, errors } = useForm({
        service_type: '',
        description: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('resident.services.store'));
    };

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col p-4 md:p-8">
            <Head title="Request Barangay Asset" />
            
            <div className="max-w-6xl mx-auto w-full flex flex-col lg:flex-row gap-6 lg:gap-12 items-start">
                
                {/* Left Panel: Navigation, Title & Guidelines */}
                <div className="w-full lg:w-5/12 flex flex-col space-y-4 md:space-y-6">
                    
                    <div className="flex items-center justify-between lg:justify-start">
                        <Link
                            href={route('resident.home')}
                            className="text-sm font-bold text-slate-600 hover:text-slate-900 transition flex items-center gap-2 bg-white px-4 py-2 rounded-lg border border-slate-200 shadow-sm w-fit"
                        >
                            &larr; Back to Dashboard
                        </Link>
                    </div>

                    <div>
                        <h2 className="text-2xl md:text-3xl lg:text-4xl font-black text-slate-900 flex items-center gap-3">
                            <span className="text-blue-600">📦</span> Request Service
                        </h2>
                        <p className="text-sm md:text-base text-slate-600 mt-3 leading-relaxed">
                            Request to borrow barangay equipment, vehicles for non-emergency transport, or logistical support for your events.
                        </p>
                    </div>

                    {/* Desktop Guidelines */}
                    <div className="hidden lg:block bg-amber-50 border border-amber-200 p-6 rounded-xl mt-4">
                        <h3 className="font-bold text-amber-800 mb-3 uppercase tracking-wider text-sm flex items-center gap-2">
                            <span>⚠️</span> Important Reminders
                        </h3>
                        <ul className="text-sm text-amber-800 space-y-3 list-disc pl-4 marker:text-amber-500 font-medium">
                            <li><strong className="text-red-600">DO NOT use this form for emergencies.</strong> Use the SOS button or Incident Report for urgent matters.</li>
                            <li>Asset approval is subject to scheduling and current availability.</li>
                            <li>Please submit requests at least 1 to 2 days prior to the date you need the equipment.</li>
                            <li>You are responsible for returning borrowed items (like chairs and tents) in good condition.</li>
                        </ul>
                    </div>
                </div>

                {/* Right Panel: Form Card Container */}
                <div className="w-full lg:w-7/12">
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-[#0a2342] p-5 md:p-8">
                        <form id="serviceForm" onSubmit={handleSubmit} className="space-y-5 md:space-y-6">
                            
                            {/* Read-Only User Information */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6 bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <div>
                                    <label className="text-xs font-bold text-slate-500 block mb-1 uppercase tracking-wider">Requester Name</label>
                                    <input 
                                        type="text" 
                                        value={user.full_name || 'Loading...'} 
                                        readOnly 
                                        className="w-full border-0 bg-transparent p-0 text-sm font-bold text-slate-800 focus:ring-0 cursor-not-allowed" 
                                    />
                                </div>
                            </div>
                            
                            {/* Interactive Form Fields */}
                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">
                                    Asset / Equipment Needed <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.service_type}
                                    onChange={e => setData('service_type', e.target.value)}
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-base p-3 focus:ring-[#0a2342] focus:border-[#0a2342] bg-white"
                                    required
                                >
                                    <option value="" disabled>Select an asset to borrow...</option>
                                    <option value="Barangay Vehicle (Non-Emergency)">Barangay Vehicle (Non-Emergency Transport)</option>
                                    <option value="Chairs and Tables">Monobloc Chairs and Tables</option>
                                    <option value="Barangay Tent">Barangay Tent</option>
                                    <option value="Sound System / PA">Sound System / PA System</option>
                                    <option value="Other Logistical Support">Other Logistical Support</option>
                                </select>
                                {errors.service_type && <p className="text-red-500 text-xs mt-1 font-medium">{errors.service_type}</p>}
                                {errors.barangay_id && <p className="text-red-500 text-xs mt-1 font-medium">System Error: {errors.barangay_id}</p>}
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">
                                    Schedule & Delivery Details <span className="text-red-500">*</span>
                                </label>
                                <textarea
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    rows="4"
                                    placeholder="Please specify the date needed, quantity (e.g., 50 chairs, 2 tents), and the exact delivery address or pickup time..."
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-base p-3 focus:ring-[#0a2342] focus:border-[#0a2342]"
                                    required
                                />
                                {errors.description && <p className="text-red-500 text-xs mt-1 font-medium">{errors.description}</p>}
                            </div>

                            {/* Mobile Guidelines Warning (Visible only on small screens) */}
                            <div className="lg:hidden bg-amber-50 border border-amber-200 p-4 rounded-lg mt-4">
                                <p className="text-xs text-amber-800 font-bold">
                                    ⚠️ DO NOT use this form for emergencies. Subject to scheduling and asset availability.
                                </p>
                            </div>

                            {/* Submission Action */}
                            <div className="mt-8 pt-4 md:pt-0 border-t border-slate-100 md:border-none">
                                <button
                                    form="serviceForm"
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-[#0a2342] text-white py-4 rounded-lg text-sm md:text-base font-bold shadow-md hover:bg-slate-800 transition active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'SUBMITTING REQUEST...' : 'SUBMIT ASSET REQUEST'}
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    );
}