import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function CaseHistory({ blotter }) {
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const mediations = blotter?.mediations || [];

    const { data, setData, post, processing, reset, errors } = useForm({
        scheduled_date: '',
    });

    const handleSchedule = (e) => {
        e.preventDefault();
        post(route('secretary.cases.schedule-mediation', blotter.id), {
            onSuccess: () => {
                reset();
                setShowScheduleModal(false);
            },
        });
    };

    return (
        <SecretaryLayout>
            <Head title={`Case Details - ${blotter.case_number}`} />

            <div className="space-y-6 max-w-5xl mx-auto">
                <div className="flex justify-between items-center bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                    <div>
                        <Link href={route('secretary.blotters')} className="text-xs font-bold text-blue-600 hover:underline">
                            &larr; Back to Blotter Records
                        </Link>
                        <h2 className="text-xl font-bold text-slate-800 mt-1">Case #{blotter.case_number}</h2>
                    </div>
                    {mediations.length < 3 && (
                        <button
                            onClick={() => setShowScheduleModal(true)}
                            className="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-md hover:bg-blue-700 transition"
                        >
                            + Schedule Next Mediation ({mediations.length + 1}/3)
                        </button>
                    )}
                </div>

                {/* Scheduled Hearings Section */}
                <div className="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <h3 className="text-base font-bold text-slate-800">Mediation Hearings</h3>
                    {mediations.length > 0 ? (
                        <div className="space-y-3">
                            {mediations.map((m) => (
                                <div key={m.id} className="flex justify-between items-center p-3 bg-slate-50 border border-slate-200 rounded-md text-xs">
                                    <div>
                                        <span className="font-bold text-blue-900">Session #{m.meeting_number}: </span>
                                        <span>{new Date(m.scheduled_date).toLocaleString()}</span>
                                    </div>
                                    <span className="bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded">
                                        {m.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-xs text-slate-400 italic">No mediation hearings scheduled yet.</p>
                    )}
                </div>
            </div>

            {/* Schedule Modal */}
            {showScheduleModal && (
                <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50">
                    <div className="bg-white rounded-xl shadow-lg border border-slate-200 max-w-sm w-full p-6 space-y-4">
                        <h4 className="text-base font-bold text-slate-800">Schedule Session #{mediations.length + 1}</h4>
                        <form onSubmit={handleSchedule} className="space-y-3">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Date & Time</label>
                                <input
                                    type="datetime-local"
                                    value={data.scheduled_date}
                                    onChange={(e) => setData('scheduled_date', e.target.value)}
                                    className="w-full border border-slate-300 rounded-md p-2 text-xs"
                                    required
                                />
                                {errors.scheduled_date && <p className="text-red-600 text-xs mt-1">{errors.scheduled_date}</p>}
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setShowScheduleModal(false)}
                                    className="px-3 py-1.5 rounded text-xs font-bold text-slate-600 hover:bg-slate-100"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-blue-600 text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-blue-700 disabled:opacity-50"
                                >
                                    Save Hearing
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </SecretaryLayout>
    );
}