import React from 'react';
import VAWCLayout from '@/Layouts/VAWCLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function CreateBlotter({ residents = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        is_registered_complainant: true,
        complainant_id: '',
        complainant_name: '',
        is_registered_respondent: false,
        receiver_id: '',
        receiver_name: '',
        incident_type: '',
        description: '',
        confidential_notes: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('vawc.blotters.store'));
    };

    return (
        <VAWCLayout>
            <Head title="File VAWC Incident" />

            <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-sm border border-rose-100 p-8">
                {/* Header */}
                <div className="flex items-center gap-4 mb-6 pb-4 border-b border-rose-100">
                    <Link
                        href={route('vawc.blotters')}
                        className="text-slate-400 hover:text-rose-900 text-xl font-bold transition"
                    >
                        &larr;
                    </Link>
                    <div>
                        <h2 className="text-2xl font-extrabold text-[#3B122D]">File New VAWC Incident</h2>
                        <p className="text-xs text-rose-600 font-medium">
                            Confidential Entry under Republic Act No. 9262
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Section 1: Complainant / Victim */}
                    <div className="bg-rose-50/40 p-5 rounded-lg border border-rose-100 space-y-4">
                        <div className="flex justify-between items-center">
                            <label className="block text-sm font-bold text-slate-800 uppercase tracking-wide">
                                1. Complainant / Victim Information
                            </label>
                            <label className="flex items-center cursor-pointer text-xs font-semibold text-slate-600">
                                <span className="mr-2">Unregistered / Walk-In?</span>
                                <input
                                    type="checkbox"
                                    className="sr-only peer"
                                    checked={!data.is_registered_complainant}
                                    onChange={(e) => {
                                        setData((prev) => ({
                                            ...prev,
                                            is_registered_complainant: !e.target.checked,
                                            complainant_id: '',
                                            complainant_name: '',
                                        }));
                                    }}
                                />
                                <div className="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-700 relative"></div>
                            </label>
                        </div>

                        {data.is_registered_complainant ? (
                            <div>
                                <select
                                    value={data.complainant_id}
                                    onChange={(e) => setData('complainant_id', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm focus:ring-rose-800 focus:border-rose-800"
                                >
                                    <option value="">-- Select Registered Resident --</option>
                                    {residents.map((res) => (
                                        <option key={res.id} value={res.id}>
                                            {res.full_name} ({res.email})
                                        </option>
                                    ))}
                                </select>
                                {errors.complainant_id && (
                                    <p className="text-red-600 text-xs mt-1 font-medium">{errors.complainant_id}</p>
                                )}
                            </div>
                        ) : (
                            <div>
                                <input
                                    type="text"
                                    placeholder="Enter victim/complainant full name..."
                                    value={data.complainant_name}
                                    onChange={(e) => setData('complainant_name', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm focus:ring-rose-800 focus:border-rose-800"
                                />
                                {errors.complainant_name && (
                                    <p className="text-red-600 text-xs mt-1 font-medium">{errors.complainant_name}</p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Section 2: Respondent / Perpetrator */}
                    <div className="bg-slate-50 p-5 rounded-lg border border-slate-200 space-y-4">
                        <div className="flex justify-between items-center">
                            <label className="block text-sm font-bold text-slate-800 uppercase tracking-wide">
                                2. Respondent / Accused Information
                            </label>
                            <label className="flex items-center cursor-pointer text-xs font-semibold text-slate-600">
                                <span className="mr-2">Unregistered / Outsider?</span>
                                <input
                                    type="checkbox"
                                    className="sr-only peer"
                                    checked={!data.is_registered_respondent}
                                    onChange={(e) => {
                                        setData((prev) => ({
                                            ...prev,
                                            is_registered_respondent: !e.target.checked,
                                            receiver_id: '',
                                            receiver_name: '',
                                        }));
                                    }}
                                />
                                <div className="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-700 relative"></div>
                            </label>
                        </div>

                        {data.is_registered_respondent ? (
                            <div>
                                <select
                                    value={data.receiver_id}
                                    onChange={(e) => setData('receiver_id', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm focus:ring-rose-800 focus:border-rose-800"
                                >
                                    <option value="">-- Select Registered Resident --</option>
                                    {residents.map((res) => (
                                        <option key={res.id} value={res.id}>
                                            {res.full_name}
                                        </option>
                                    ))}
                                </select>
                                {errors.receiver_id && (
                                    <p className="text-red-600 text-xs mt-1 font-medium">{errors.receiver_id}</p>
                                )}
                            </div>
                        ) : (
                            <div>
                                <input
                                    type="text"
                                    placeholder="Enter full name of the accused / respondent..."
                                    value={data.receiver_name}
                                    onChange={(e) => setData('receiver_name', e.target.value)}
                                    className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm focus:ring-rose-800 focus:border-rose-800"
                                />
                                {errors.receiver_name && (
                                    <p className="text-red-600 text-xs mt-1 font-medium">{errors.receiver_name}</p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Section 3: Incident Details */}
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-bold text-slate-700 mb-1">
                                Specific Violation / Nature of Incident *
                            </label>
                            <select
                                value={data.incident_type}
                                onChange={(e) => setData('incident_type', e.target.value)}
                                className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm focus:ring-rose-800 focus:border-rose-800"
                            >
                                <option value="">-- Select VAWC Incident Type --</option>
                                <option value="Physical Abuse">Physical Violence / Abuse</option>
                                <option value="Psychological Abuse">Psychological / Emotional Abuse</option>
                                <option value="Economic Abuse">Economic Abuse / Denial of Support</option>
                                <option value="Sexual Harassment">Sexual Abuse / Harassment</option>
                                <option value="Threats and Intimidation">Threats & Intimidation</option>
                                <option value="Other VAWC Offense">Other R.A. 9262 Offense</option>
                            </select>
                            {errors.incident_type && (
                                <p className="text-red-600 text-xs mt-1 font-medium">{errors.incident_type}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-slate-700 mb-1">
                                Incident Narrative / Description *
                            </label>
                            <textarea
                                rows="4"
                                placeholder="Detailed narration of the incident..."
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm focus:ring-rose-800 focus:border-rose-800"
                            ></textarea>
                            {errors.description && (
                                <p className="text-red-600 text-xs mt-1 font-medium">{errors.description}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-bold text-slate-700 mb-1">
                                Confidential Notes / Intake Assessment
                            </label>
                            <textarea
                                rows="3"
                                placeholder="Internal case officer assessment, initial safety measures, remarks..."
                                value={data.confidential_notes}
                                onChange={(e) => setData('confidential_notes', e.target.value)}
                                className="w-full border-slate-300 rounded-md shadow-xs p-2.5 border text-sm bg-rose-50/20 focus:ring-rose-800 focus:border-rose-800"
                            ></textarea>
                            {errors.confidential_notes && (
                                <p className="text-red-600 text-xs mt-1 font-medium">{errors.confidential_notes}</p>
                            )}
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex justify-end items-center gap-3 pt-6 border-t border-slate-200">
                        <Link
                            href={route('vawc.blotters')}
                            className="px-5 py-2.5 rounded text-sm font-bold text-slate-600 hover:bg-slate-100 transition"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="bg-[#3B122D] text-white px-6 py-2.5 rounded text-sm font-bold hover:bg-[#280c1e] transition shadow-xs disabled:opacity-50"
                        >
                            {processing ? 'Filing Confidential Case...' : 'File VAWC Record'}
                        </button>
                    </div>
                </form>
            </div>
        </VAWCLayout>
    );
}