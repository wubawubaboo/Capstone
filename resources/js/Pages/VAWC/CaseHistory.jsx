import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function CaseHistory({ blotter }) {
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const mediations = blotter?.mediations || [];

    const { data, setData, post, processing, reset, errors } = useForm({
        scheduled_date: '',
    });

    const handleSchedule = (e) => {
        e.preventDefault();
        post(route('vawc.cases.schedule-mediation', blotter.id), {
            onSuccess: () => {
                reset();
                setShowScheduleModal(false);
            },
        });
    };

    return (
        <VAWCLayout>
            <Head title={`VAWC Case Details - ${blotter.case_number}`} />

            <div className="space-y-6 max-w-5xl mx-auto">
                <div className="flex justify-between items-center bg-white p-5 rounded-xl border border-rose-100 shadow-xs">
                    <div>
                        <Link href={route('vawc.blotters')} className="text-xs font-bold text-rose-800 hover:underline">
                            &larr; Back to Confidential Records
                        </Link>
                        <h2 className="text-xl font-bold text-[#3B122D] mt-1">Case #{blotter.case_number}</h2>
                    </div>
                    {mediations.length < 3 && (
                        <button
                            onClick={() => setShowScheduleModal(true)}
                            className="bg-[#3B122D] text-white text-xs font-bold px-4 py-2 rounded-md hover:bg-[#280c1e] transition"
                        >
                            + Schedule VAWC Mediation ({mediations.length + 1}/3)
                        </button>
                    )}
                </div>

                {/* Mediation History */}
                <div className="bg-white p-6 rounded-xl border border-rose-100 shadow-xs space-y-4">
                    <h3 className="text-base font-bold text-[#3B122D]">Conciliation & Hearing Schedule</h3>
                    {mediations.length > 0 ? (
                        <div className="space-y-3">
                            {mediations.map((m) => (
                                <div key={m.id} className="flex justify-between items-center p-3 bg-rose-50/40 border border-rose-100 rounded-md text-xs">
                                    <div>
                                        <span className="font-bold text-rose-950">Session #{m.meeting_number}: </span>
                                        <span>{new Date(m.scheduled_date).toLocaleString()}</span>
                                    </div>
                                    <span className="bg-rose-100 text-rose-900 font-bold px-2 py-0.5 rounded">
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
                    <div className="bg-white rounded-xl shadow-lg border border-rose-100 max-w-sm w-full p-6 space-y-4">
                        <h4 className="text-base font-bold text-[#3B122D]">Schedule VAWC Session #{mediations.length + 1}</h4>
                        <form onSubmit={handleSchedule} className="space-y-3">
                            <div>
                                <label className="block text-xs font-bold text-slate-700 mb-1">Date & Time</label>
                                <input
                                    type="datetime-local"
                                    value={data.scheduled_date}
                                    onChange={(e) => setData('scheduled_date', e.target.value)}
                                    className="w-full border border-rose-200 rounded-md p-2 text-xs focus:ring-rose-800"
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
                                    className="bg-[#3B122D] text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-[#280c1e] disabled:opacity-50"
                                >
                                    Confirm Hearing
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </VAWCLayout>
    );
}