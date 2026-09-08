import React, { useState } from 'react';
import { useForm, Link } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function EmergencyReport() {
    const { data, setData, post, processing, errors } = useForm({
        incident_type: '',
        description: '',
        latitude: null,
        longitude: null,
        attachment: null,
    });

    const [locationStatus, setLocationStatus] = useState('');
    const [isLocating, setIsLocating] = useState(false);

    const getLocation = () => {
        if (!navigator.geolocation) {
            setLocationStatus('Geolocation is not supported by your browser.');
            return;
        }

        setIsLocating(true);
        setLocationStatus('Locating device...');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                setIsLocating(false);
                setLocationStatus('Location captured successfully!');
                setData((prev) => ({
                    ...prev,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                }));
            },
            () => {
                setIsLocating(false);
                setLocationStatus('Unable to retrieve your location. Check browser permissions.');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('resident.reports.store'));
    };

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
                            <span className="text-red-600">🚨</span> Report Incident
                        </h2>
                        <p className="text-sm md:text-base text-slate-600 mt-3 leading-relaxed">
                            File an official incident report with the barangay. Ensure you provide accurate details and a pinned location so responders can act swiftly.
                        </p>
                    </div>

                    {/* Desktop Guidelines (Hidden on mobile to save space during emergencies) */}
                    <div className="hidden lg:block bg-red-50 border border-red-100 p-6 rounded-xl mt-4">
                        <h3 className="font-bold text-red-800 mb-3 uppercase tracking-wider text-sm">Important Guidelines</h3>
                        <ul className="text-sm text-red-700 space-y-3 list-disc pl-4 marker:text-red-400 font-medium">
                            <li>Ensure you are in a safe, secure location before filing this report.</li>
                            <li>Pinning your exact GPS location drastically reduces response times.</li>
                            <li>Attach clear photographic evidence only if it is safe to do so.</li>
                            <li>Filing false emergency reports is punishable by law.</li>
                        </ul>
                    </div>
                </div>

                {/* Right Panel: Form Card Container */}
                <div className="w-full lg:w-7/12">
                    <div className="bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-red-600 p-5 md:p-8">
                        <form onSubmit={submit} className="space-y-5 md:space-y-6">
                            
                            {/* Incident Type Selector */}
                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">
                                    Incident Type <span className="text-red-500">*</span>
                                </label>
                                <select
                                    value={data.incident_type}
                                    onChange={(e) => setData('incident_type', e.target.value)}
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-base p-3 focus:ring-red-500 focus:border-red-500 bg-white"
                                    required
                                >
                                    <option value="" disabled>Select incident classification...</option>
                                    <option value="Fire">Fire Incident</option>
                                    <option value="Medical">Medical Emergency</option>
                                    <option value="Crime">Crime in Progress / Disturbance</option>
                                    <option value="Accident">Traffic Accident</option>
                                    <option value="Other">Other Emergency</option>
                                </select>
                                {errors.incident_type && (
                                    <p className="text-red-500 text-xs mt-1 font-medium">{errors.incident_type}</p>
                                )}
                            </div>

                            {/* Description Field */}
                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">
                                    Incident Details <span className="text-red-500">*</span>
                                </label>
                                <textarea
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows="4"
                                    className="w-full border-slate-300 rounded-lg shadow-sm text-base p-3 focus:ring-red-500 focus:border-red-500"
                                    placeholder="Describe what occurred, any landmarks, persons involved, or specific assistance required..."
                                    required
                                />
                                {errors.description && (
                                    <p className="text-red-500 text-xs mt-1 font-medium">{errors.description}</p>
                                )}
                            </div>

                            {/* Interactive Geolocation Pinning Module */}
                            <div className="bg-slate-50 p-4 md:p-5 rounded-xl border border-slate-200 space-y-3">
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600">
                                        GPS Verification (Optional)
                                    </label>
                                    <p className="text-xs text-slate-500 mt-1 leading-relaxed">
                                        Pinning your exact coordinates enables responders to locate the scene faster.
                                    </p>
                                </div>

                                <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                                    <button
                                        type="button"
                                        onClick={getLocation}
                                        disabled={isLocating}
                                        className="w-full sm:w-auto bg-slate-800 text-white px-5 py-2.5 rounded-lg text-xs font-bold hover:bg-slate-900 transition flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50"
                                    >
                                        <span>📍</span>
                                        <span>{isLocating ? 'Capturing Location...' : data.latitude ? 'Re-pin Location' : 'Pin My Location'}</span>
                                    </button>
                                    
                                    {locationStatus && (
                                        <span className={`text-xs font-semibold ${data.latitude ? 'text-emerald-600' : 'text-slate-600'}`}>
                                            {locationStatus}
                                        </span>
                                    )}
                                </div>

                                {data.latitude && data.longitude && (
                                    <div className="text-xs font-mono text-slate-700 bg-white p-3 rounded-lg border border-slate-200 shadow-sm inline-block">
                                        Coordinates: {data.latitude.toFixed(6)}, {data.longitude.toFixed(6)}
                                    </div>
                                )}

                                {errors.latitude && (
                                    <p className="text-red-500 text-xs font-medium">{errors.latitude}</p>
                                )}
                            </div>

                            {/* File Upload / Camera Input */}
                            <div>
                                <label className="block text-sm font-bold text-slate-700 mb-2">
                                    Photo Evidence (Optional)
                                </label>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg,image/jpg"
                                    onChange={(e) => setData('attachment', e.target.files[0])}
                                    className="w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 border border-slate-300 rounded-lg p-2 bg-slate-50 cursor-pointer"
                                />
                                {errors.attachment && (
                                    <p className="text-red-500 text-xs mt-1 font-medium">{errors.attachment}</p>
                                )}
                            </div>

                            {/* Submission Action */}
                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-lg text-sm md:text-base font-black tracking-wide shadow-md transition active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'DISPATCHING ALERT...' : 'SUBMIT EMERGENCY REPORT'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    );
}

EmergencyReport.layout = (page) => <ResidentLayout>{page}</ResidentLayout>;