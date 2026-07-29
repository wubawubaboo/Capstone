import React, { useState } from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function CreateBlotter({ pendingReports, residents }) {
    const [entryType, setEntryType] = useState('existing');

    const { data, setData, post, processing, errors } = useForm({
        report_id: '',
        complainant_id: '',
        incident_type: '',
        description: '',
        is_registered_respondent: true,
        receiver_id: '',
        receiver_name: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('secretary.blotters.store'));
    };

    return (
        <SecretaryLayout>
            <Head title="Create Blotter Record" />
            
            <div className="max-w-3xl mx-auto bg-white rounded-lg shadow-sm border border-slate-200 p-8">
                <div className="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
                    <Link href={route('secretary.blotters')} className="text-slate-400 hover:text-slate-800 text-xl font-bold">
                        &larr;
                    </Link>
                    <div>
                        <h2 className="text-2xl font-bold text-slate-900">File New Blotter Record</h2>
                        <p className="text-sm text-slate-500">Record a new community incident or formalize an existing report.</p>
                    </div>
                </div>

                <div className="flex gap-4 mb-6">
                    <button 
                        type="button" 
                        onClick={() => { 
                            setEntryType('existing'); 
                            setData(prev => ({
                                ...prev,
                                complainant_id: '',
                                incident_type: '',
                                description: ''
                            })); 
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

                <form onSubmit={handleSubmit} className="space-y-6">
                    {entryType === 'existing' && (
                        <div>
                            <label className="block text-sm font-bold text-slate-700 mb-2">Select Pending Report</label>
                            <select 
                                value={data.report_id} 
                                onChange={e => setData('report_id', e.target.value)}
                                className="w-full border-slate-300 rounded-md shadow-sm focus:ring-[#0a2342] focus:border-[#0a2342] text-sm p-2.5 border"
                            >
                                <option value="">-- Select an Incident Report --</option>
                                {pendingReports?.map(report => (
                                    <option key={report.id} value={report.id}>
                                        {report.incident_type} - Reported by {report.user?.full_name} ({new Date(report.created_at).toLocaleDateString()})
                                    </option>
                                ))}
                            </select>
                            {errors.report_id && <div className="text-red-500 text-xs mt-1">{errors.report_id}</div>}
                        </div>
                    )}

                    {entryType === 'walk-in' && (
                        <div className="space-y-6 bg-slate-50 p-4 rounded border border-slate-100">
                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">Complainant (Resident)</label>
                                <select 
                                    value={data.complainant_id} 
                                    onChange={e => setData('complainant_id', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-sm p-2.5 border text-sm"
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
                                    placeholder="e.g. Noise Complaint, Theft, Property Dispute"
                                    value={data.incident_type} 
                                    onChange={e => setData('incident_type', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-sm p-2.5 border text-sm"
                                />
                                {errors.incident_type && <div className="text-red-500 text-xs mt-1">{errors.incident_type}</div>}
                            </div>

                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">Incident Description</label>
                                <textarea 
                                    rows="4" 
                                    value={data.description} 
                                    onChange={e => setData('description', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-sm p-2.5 border text-sm"
                                ></textarea>
                                {errors.description && <div className="text-red-500 text-xs mt-1">{errors.description}</div>}
                            </div>
                        </div>
                    )}

                    <hr className="border-slate-200" />

                    <div>
                        <div className="flex justify-between items-end mb-2">
                            <label className="block text-sm font-bold text-slate-700">Accused / Respondent</label>
                            <label className="flex items-center cursor-pointer text-xs font-bold text-slate-500">
                                <span className="mr-2">Unregistered / Outsider?</span>
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
                                <div className="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500 relative"></div>
                            </label>
                        </div>

                        {data.is_registered_respondent ? (
                            <>
                                <select 
                                    value={data.receiver_id} 
                                    onChange={e => setData('receiver_id', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-sm p-2.5 border text-sm focus:ring-[#0a2342] focus:border-[#0a2342]"
                                >
                                    <option value="">-- Select Registered Respondent --</option>
                                    {residents?.map(res => (
                                        <option key={res.id} value={res.id}>{res.full_name}</option>
                                    ))}
                                </select>
                                {errors.receiver_id && <div className="text-red-500 text-xs mt-1">{errors.receiver_id}</div>}
                            </>
                        ) : (
                            <>
                                <input 
                                    type="text" 
                                    placeholder="Enter full name of the accused..."
                                    value={data.receiver_name} 
                                    onChange={e => setData('receiver_name', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-sm p-2.5 border text-sm focus:ring-[#0a2342] focus:border-[#0a2342]"
                                />
                                {errors.receiver_name && <div className="text-red-500 text-xs mt-1">{errors.receiver_name}</div>}
                            </>
                        )}
                    </div>

                    <div className="flex justify-end pt-4 border-t border-slate-100">
                        <button 
                            type="submit" 
                            disabled={processing}
                            className="bg-emerald-600 text-white px-6 py-2.5 rounded text-sm font-bold hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {processing ? 'Filing Record...' : 'Submit & File Blotter'}
                        </button>
                    </div>
                </form>
            </div>
        </SecretaryLayout>
    );
}