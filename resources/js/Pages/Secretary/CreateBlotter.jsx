import React, { useState, useEffect } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function CreateBlotter({ pendingReports, residents }) {
    
    const queryParams = new URLSearchParams(window.location.search);
    const initialReportId = queryParams.get('report_id') || '';

    const [entryType, setEntryType] = useState(initialReportId ? 'existing' : 'walk-in');

    const { data, setData, post, processing, errors } = useForm({
        report_id: initialReportId,
        complainant_id: '',
        incident_type: '',
        description: '',
        is_registered_respondent: true,
        receiver_id: '',
        receiver_name: '',
    });

    const activeReport = pendingReports?.find(r => r.id === parseInt(data.report_id));

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('secretary.blotters.store'));
    };

    return (
        <SecretaryLayout>
            <Head title="Create Blotter Record" />
            
            <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-sm border border-slate-200 p-8">
                <div className="flex justify-between items-center mb-6 pb-4 border-b border-slate-100">
                    <div className="flex items-center gap-3">
                        <Link href={route('secretary.blotters')} className="text-slate-400 hover:text-[#0a2342] text-xl font-bold transition-colors">
                            &larr;
                        </Link>
                        <div>
                            <h2 className="text-2xl font-bold text-[#0a2342]">File New Blotter Record</h2>
                            <p className="text-sm text-slate-500">Transition an incident into the secure digital database[cite: 3].</p>
                        </div>
                    </div>
                    {initialReportId && (
                        <Link href={route('secretary.reports.index')} className="text-sm font-bold text-slate-500 hover:text-slate-800">
                            Back to Incident Queue
                        </Link>
                    )}
                </div>

                <div className="flex gap-4 mb-6">
                    <button 
                        type="button" 
                        onClick={() => { 
                            setEntryType('existing'); 
                            setData(prev => ({ ...prev, complainant_id: '', incident_type: '', description: '' })); 
                        }}
                        className={`px-4 py-2 rounded text-sm font-bold transition-colors ${entryType === 'existing' ? 'bg-[#0a2342] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}
                    >
                        From Existing Report
                    </button>
                    <button 
                        type="button" 
                        onClick={() => { 
                            setEntryType('walk-in'); 
                            setData('report_id', ''); 
                        }}
                        className={`px-4 py-2 rounded text-sm font-bold transition-colors ${entryType === 'walk-in' ? 'bg-[#0a2342] text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`}
                    >
                        Walk-In Complaint
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-8">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div className="space-y-6">
                            <h3 className="font-bold text-slate-800 border-b pb-2">1. Complainant Details</h3>
                            
                            {entryType === 'existing' ? (
                                <div className="space-y-4">
                                    <div>
                                        <label className="block text-sm font-bold text-slate-700 mb-2">Select Pending Report</label>
                                        <select 
                                            value={data.report_id} 
                                            onChange={e => setData('report_id', e.target.value)}
                                            className="w-full border-slate-300 rounded shadow-sm focus:ring-[#0a2342] focus:border-[#0a2342] text-sm p-2.5"
                                        >
                                            <option value="">-- Select an Incident Report --</option>
                                            {pendingReports?.map(report => (
                                                <option key={report.id} value={report.id}>
                                                    Report #{report.id} - {report.incident_type} ({report.user?.full_name})
                                                </option>
                                            ))}
                                        </select>
                                        {errors.report_id && <div className="text-red-500 text-xs mt-1">{errors.report_id}</div>}
                                    </div>

                                    {activeReport && (
                                        <div className="p-4 bg-blue-50 border border-blue-100 rounded text-sm text-blue-900 space-y-2">
                                            <p><strong className="text-[#0a2342]">Reporter:</strong> {activeReport.user?.full_name}</p>
                                            <p><strong className="text-[#0a2342]">Incident:</strong> {activeReport.incident_type}</p>
                                            <p><strong className="text-[#0a2342]">Description:</strong> {activeReport.description}</p>
                                            <p className="mt-2 text-xs italic text-blue-600">This data will automatically link to the blotter record.</p>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-4 bg-slate-50 p-4 rounded border border-slate-100">
                                    <div>
                                        <label className="block text-sm font-bold text-slate-700 mb-2">Complainant (Resident)</label>
                                        <select 
                                            value={data.complainant_id} 
                                            onChange={e => setData('complainant_id', e.target.value)}
                                            className="w-full border-slate-300 rounded shadow-sm p-2.5 text-sm focus:ring-[#0a2342]"
                                        >
                                            <option value="">-- Select Complainant --</option>
                                            {residents?.map(res => <option key={res.id} value={res.id}>{res.full_name}</option>)}
                                        </select>
                                        {errors.complainant_id && <div className="text-red-500 text-xs mt-1">{errors.complainant_id}</div>}
                                    </div>
                                    
                                    <div>
                                        <label className="block text-sm font-bold text-slate-700 mb-2">Nature of Incident</label>
                                        <input 
                                            type="text" 
                                            placeholder="e.g. Noise Complaint, Property Dispute"
                                            value={data.incident_type} 
                                            onChange={e => setData('incident_type', e.target.value)}
                                            className="w-full border-slate-300 rounded shadow-sm p-2.5 text-sm focus:ring-[#0a2342]"
                                        />
                                        {errors.incident_type && <div className="text-red-500 text-xs mt-1">{errors.incident_type}</div>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-bold text-slate-700 mb-2">Incident Description</label>
                                        <textarea 
                                            rows="4" 
                                            value={data.description} 
                                            onChange={e => setData('description', e.target.value)}
                                            className="w-full border-slate-300 rounded shadow-sm p-2.5 text-sm focus:ring-[#0a2342]"
                                        ></textarea>
                                        {errors.description && <div className="text-red-500 text-xs mt-1">{errors.description}</div>}
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="space-y-6">
                            <h3 className="font-bold text-slate-800 border-b pb-2">2. Respondent Details (Accused)</h3>
                            
                            <div>
                                <label className="flex items-center cursor-pointer text-sm font-bold text-slate-600 mb-4 bg-slate-50 p-3 rounded border border-slate-100">
                                    <span className="mr-3">Is the respondent an Unregistered Outsider?</span>
                                    <input 
                                        type="checkbox" 
                                        className="sr-only peer"
                                        checked={!data.is_registered_respondent}
                                        onChange={(e) => {
                                            setData(prev => ({
                                                ...prev, 
                                                is_registered_respondent: !e.target.checked,
                                                receiver_id: '',
                                                receiver_name: ''
                                            }));
                                        }}
                                    />
                                    <div className="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500 relative"></div>
                                </label>
                            </div>

                            {data.is_registered_respondent ? (
                                <div>
                                    <label className="block text-sm font-bold text-slate-700 mb-2">Select Registered Resident</label>
                                    <select 
                                        value={data.receiver_id} 
                                        onChange={e => setData('receiver_id', e.target.value)}
                                        className="w-full border-slate-300 rounded shadow-sm p-2.5 text-sm focus:ring-[#0a2342]"
                                    >
                                        <option value="">-- Select Registered Respondent --</option>
                                        {residents?.map(res => (
                                            <option key={res.id} value={res.id}>{res.full_name}</option>
                                        ))}
                                    </select>
                                    {errors.receiver_id && <div className="text-red-500 text-xs mt-1">{errors.receiver_id}</div>}
                                </div>
                            ) : (
                                <div>
                                    <label className="block text-sm font-bold text-slate-700 mb-2">Respondent Full Name</label>
                                    <input 
                                        type="text" 
                                        placeholder="Enter full name of the accused..."
                                        value={data.receiver_name} 
                                        onChange={e => setData('receiver_name', e.target.value)}
                                        className="w-full border-slate-300 rounded shadow-sm p-2.5 text-sm focus:ring-[#0a2342]"
                                    />
                                    {errors.receiver_name && <div className="text-red-500 text-xs mt-1">{errors.receiver_name}</div>}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end pt-6 border-t border-slate-100">
                        <button 
                            type="submit" 
                            disabled={processing || (entryType === 'existing' && !data.report_id)}
                            className="bg-[#0a2342] text-white px-8 py-3 rounded text-sm font-bold hover:bg-slate-800 disabled:opacity-50 transition-colors shadow-md"
                        >
                            {processing ? 'Filing Record...' : 'Submit & File Blotter'}
                        </button>
                    </div>
                </form>
            </div>
        </SecretaryLayout>
    );
}