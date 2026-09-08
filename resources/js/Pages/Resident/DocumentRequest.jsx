import React from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function DocumentRequest() {
    const { auth } = usePage().props;
    const user = auth?.user || {};

    const { data, setData, post, processing, errors } = useForm({
        document_type_id: 1,
        purpose: 'Scholarship',
        barangay_id: user.barangay_id || 1,
    });

    function handleSubmit(e) {
        e.preventDefault();
        post(route('resident.documents.store'));
    }

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col p-4 md:p-8">
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
                            <span className="text-blue-600">📄</span> Request Document
                        </h2>
                        <p className="text-sm md:text-base text-slate-600 mt-3 leading-relaxed">
                            Skip the line at the Barangay Hall. Request clearances, certificates, and official documents directly from your phone or computer.
                        </p>
                    </div>

                    {/* Desktop Guidelines (Hidden on mobile to save space) */}
                    <div className="hidden lg:block bg-blue-50 border border-blue-100 p-6 rounded-xl mt-4">
                        <h3 className="font-bold text-blue-800 mb-3 uppercase tracking-wider text-sm">Reminders</h3>
                        <ul className="text-sm text-blue-700 space-y-3 list-disc pl-4 marker:text-blue-400 font-medium">
                            <li>Standard processing time is usually <strong>1 to 2 business days</strong>.</li>
                            <li>Please bring a <strong>valid ID</strong> when claiming your requested document at the Barangay Hall.</li>
                            <li>You will receive a notification on your dashboard once your document is ready.</li>
                        </ul>
                    </div>
                </div>

                {/* Right Panel: Form Card Container */}
                <div className="w-full lg:w-7/12">
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-[#0a2342] p-5 md:p-8">
                        <form id="docForm" onSubmit={handleSubmit} className="space-y-5 md:space-y-6">
                            
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
                                    Document Type <span className="text-red-500">*</span>
                                </label>
                                <select 
                                    value={data.document_type_id}
                                    onChange={e => setData('document_type_id', e.target.value)}
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-base p-3 focus:ring-[#0a2342] focus:border-[#0a2342] bg-white"
                                >
                                    <option value={1}>Barangay Indigency</option>
                                    <option value={2}>Barangay Clearance</option>
                                </select>
                                {errors.document_type_id && <div className="text-red-500 text-xs mt-1">{errors.document_type_id}</div>}
                            </div>
                            
                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">
                                    Purpose <span className="text-red-500">*</span>
                                </label>
                                <select 
                                    value={data.purpose}
                                    onChange={e => setData('purpose', e.target.value)}
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-base p-3 focus:ring-[#0a2342] focus:border-[#0a2342] bg-white"
                                >
                                    <option value="Scholarship">Scholarship / Education</option>
                                    <option value="Employment">Employment / Job Application</option>
                                    <option value="Bank Requirement">Bank Requirement</option>
                                    <option value="Other">Other</option>
                                </select>
                                {errors.purpose && <div className="text-red-500 text-xs mt-1">{errors.purpose}</div>}
                            </div>

                            {/* Mobile pushes button to bottom, Desktop keeps it inline */}
                            <div className="mt-8 pt-4 md:pt-0 border-t border-slate-100 md:border-none">
                                <button 
                                    form="docForm" 
                                    type="submit" 
                                    disabled={processing} 
                                    className="w-full bg-[#0a2342] text-white py-4 rounded-lg text-sm md:text-base font-bold shadow-md hover:bg-slate-800 transition active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'SUBMITTING REQUEST...' : 'REQUEST DOCUMENT'}
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

            </div>
        </div>
    );
}

DocumentRequest.layout = page => <ResidentLayout>{page}</ResidentLayout>;