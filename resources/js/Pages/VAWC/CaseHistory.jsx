import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function CaseHistory({ blotter }) {
    const [showScheduleModal, setShowScheduleModal] = useState(false);
    const [selectedMediation, setSelectedMediation] = useState(null);
    const mediations = blotter?.mediations || [];

    const { data, setData, post, processing, reset, errors } = useForm({
        scheduled_date: '',
    });
    const notesForm = useForm({
        notes: '',
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

    const handleResolve = () => {
        if (confirm('Are you sure you want to mark this case as resolved?')) {
            router.post(route('vawc.cases.resolve', blotter.id));
        }
    };

    const handleEscalate = () => {
        if (confirm('Are you sure you want to escalate this case to court?')) {
            router.post(route('vawc.cases.escalate', blotter.id));
        }
    };

    const openMediationDetails = (mediation) => {
        setSelectedMediation(mediation);
        notesForm.setData('notes', mediation.notes || '');
        notesForm.clearErrors();
    };

    const closeMediationDetails = () => {
        if (!notesForm.processing) {
            setSelectedMediation(null);
            notesForm.reset();
        }
    };

    const handleNotesSave = (e) => {
        e.preventDefault();
        notesForm.put(route('vawc.mediation-notes.update', selectedMediation.id), {
            preserveScroll: true,
            onSuccess: () => {
                setSelectedMediation((mediation) => (
                    mediation ? { ...mediation, notes: notesForm.data.notes } : mediation
                ));
            },
        });
    };

    const isCaseClosed = blotter.status === 'Resolved' || blotter.status === 'Escalated to Court';

    return (
        <VAWCLayout>
            <Head title={`VAWC Case Details - ${blotter.case_number}`} />

            <div className="space-y-6 max-w-5xl mx-auto">
                <div className="flex justify-between items-center bg-white p-5 rounded-xl border border-rose-100 shadow-xs">
                    <div>
                        <Link href={route('vawc.blotters')} className="text-xs font-bold text-rose-800 hover:underline">
                            &larr; Back to Confidential Records
                        </Link>
                        <h2 className="text-xl font-bold text-[#3B122D] mt-1">
                            Case #{blotter.case_number}
                            <span className="ml-3 text-sm font-normal px-2 py-1 bg-rose-50 rounded-md text-rose-800">
                                {blotter.status}
                            </span>
                        </h2>
                    </div>

                    <div className="flex items-center gap-2">
                        <a
                            href={route('vawc.case-history.report', blotter.id)}
                            className="bg-slate-700 text-white text-xs font-bold px-4 py-2 rounded-md hover:bg-slate-800 transition"
                        >
                            Generate Report (PDF)
                        </a>

                        {!isCaseClosed && (
                            <>
                                <button
                                    onClick={handleResolve}
                                    className="bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-md hover:bg-green-700 transition"
                                >
                                    Resolve Case
                                </button>

                                {mediations.length >= 3 ? (
                                    <button
                                        onClick={handleEscalate}
                                        className="bg-red-600 text-white text-xs font-bold px-4 py-2 rounded-md hover:bg-red-700 transition"
                                    >
                                        Escalate to Court
                                    </button>
                                ) : (
                                    <button
                                        onClick={() => setShowScheduleModal(true)}
                                        className="bg-[#3B122D] text-white text-xs font-bold px-4 py-2 rounded-md hover:bg-[#280c1e] transition"
                                    >
                                        + Schedule VAWC Mediation ({mediations.length + 1}/3)
                                    </button>
                                )}
                            </>
                        )}
                    </div>
                </div>

                {/* Mediation History */}
                <div className="bg-white p-6 rounded-xl border border-rose-100 shadow-xs space-y-4">
                    <h3 className="text-base font-bold text-[#3B122D]">Conciliation & Hearing Schedule</h3>
                    {mediations.length > 0 ? (
                        <div className="space-y-3">
                            {mediations.map((m) => (
                                <button
                                    key={m.id}
                                    type="button"
                                    onClick={() => openMediationDetails(m)}
                                    className="w-full flex justify-between items-center p-3 bg-rose-50/40 border border-rose-100 rounded-md text-xs text-left hover:bg-rose-100/60 hover:border-rose-200 transition"
                                >
                                    <div>
                                        <span className="font-bold text-rose-950">Session #{m.meeting_number}: </span>
                                        <span>{new Date(m.scheduled_date).toLocaleString()}</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className="bg-rose-100 text-rose-900 font-bold px-2 py-0.5 rounded">
                                            {m.status}
                                        </span>
                                        <span className="text-rose-800 font-bold">View details &rarr;</span>
                                    </div>
                                </button>
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

            {/* Mediation Details Modal */}
            {selectedMediation && (
                <div
                    className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4 z-50"
                    onMouseDown={(e) => {
                        if (e.target === e.currentTarget) closeMediationDetails();
                    }}
                >
                    <div className="bg-white rounded-xl shadow-lg border border-rose-100 max-w-lg w-full p-6 space-y-5">
                        <div className="flex justify-between items-start gap-4">
                            <div>
                                <p className="text-xs font-bold text-rose-800 uppercase">Confidential Mediation Meeting</p>
                                <h4 className="text-lg font-bold text-[#3B122D]">
                                    Session #{selectedMediation.meeting_number}
                                </h4>
                            </div>
                            <button
                                type="button"
                                onClick={closeMediationDetails}
                                className="text-slate-400 hover:text-slate-700 text-xl leading-none"
                                aria-label="Close mediation details"
                            >
                                &times;
                            </button>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div className="bg-rose-50/40 rounded-md p-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Date &amp; Time</p>
                                <p className="font-semibold text-slate-800">
                                    {new Date(selectedMediation.scheduled_date).toLocaleString()}
                                </p>
                            </div>
                            <div className="bg-rose-50/40 rounded-md p-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Status</p>
                                <p className="font-semibold text-slate-800">{selectedMediation.status}</p>
                            </div>
                            <div className="bg-rose-50/40 rounded-md p-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Complainant</p>
                                <p className="font-semibold text-slate-800">
                                    {blotter.complainant_name || blotter.report?.user?.full_name || 'Confidential'}
                                </p>
                            </div>
                            <div className="bg-rose-50/40 rounded-md p-3">
                                <p className="text-xs font-bold text-slate-400 uppercase">Respondent</p>
                                <p className="font-semibold text-slate-800">
                                    {blotter.receiver?.full_name || blotter.receiver_name || 'Unknown'}
                                </p>
                            </div>
                        </div>

                        <div>
                            <p className="text-xs font-bold text-slate-400 uppercase mb-1">Nature of Complaint</p>
                            <p className="text-sm text-slate-700">
                                {blotter.incident_type || blotter.report?.incident_type || 'N/A'}
                            </p>
                        </div>

                        <form onSubmit={handleNotesSave} className="space-y-3">
                            <div>
                                <label htmlFor="mediation-notes" className="block text-xs font-bold text-slate-700 mb-1">
                                    Meeting Notes
                                </label>
                                <textarea
                                    id="mediation-notes"
                                    value={notesForm.data.notes}
                                    onChange={(e) => notesForm.setData('notes', e.target.value)}
                                    rows="5"
                                    maxLength="10000"
                                    placeholder="Record the discussion, agreements, or follow-up actions..."
                                    className="w-full border border-rose-200 rounded-md p-3 text-sm resize-y focus:border-rose-800 focus:ring-rose-800"
                                />
                                {notesForm.errors.notes && (
                                    <p className="text-red-600 text-xs mt-1">{notesForm.errors.notes}</p>
                                )}
                            </div>
                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeMediationDetails}
                                    className="px-3 py-1.5 rounded text-xs font-bold text-slate-600 hover:bg-slate-100"
                                >
                                    Close
                                </button>
                                <button
                                    type="submit"
                                    disabled={notesForm.processing}
                                    className="bg-[#3B122D] text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-[#280c1e] disabled:opacity-50"
                                >
                                    {notesForm.processing ? 'Saving...' : 'Save Notes'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </VAWCLayout>
    );
}
