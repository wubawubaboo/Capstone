import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function EmergencyReport() {
    const { data, setData, post, processing, errors, reset } = useForm({
        incident_type: '',
        description: '',
        latitude: null, 
        longitude: null,
        attachment: null,
    });

    const [locationStatus, setLocationStatus] = useState('');

    const getLocation = () => {
        if (!navigator.geolocation) {
            setLocationStatus('Geolocation is not supported by your browser');
        } else {
            setLocationStatus('Locating...');
            navigator.geolocation.getCurrentPosition((position) => {
                setLocationStatus('Location captured!');
                setData({
                    ...data,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                });
            }, () => {
                setLocationStatus('Unable to retrieve your location');
            });
        }
    };

    const submit = (e) => {
    e.preventDefault();
    post(route('resident.reports.store'));
    };

    return (
        <div className="max-w-2xl mx-auto p-4 sm:p-6 bg-white rounded-lg shadow-sm border-t-4 border-red-600 mt-6">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">Report an Emergency / Incident</h2>
            
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700">Incident Type</label>
                    <select 
                        value={data.incident_type} 
                        onChange={e => setData('incident_type', e.target.value)}
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                    >
                        <option value="">Select Type</option>
                        <option value="Fire">Fire</option>
                        <option value="Medical">Medical Emergency</option>
                        <option value="Crime">Crime in Progress</option>
                        <option value="Accident">Traffic Accident</option>
                        <option value="Other">Other</option>
                    </select>
                    {errors.incident_type && <div className="text-red-500 text-sm mt-1 font-medium">{errors.incident_type}</div>}
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700">Description</label>
                    <textarea 
                        value={data.description} 
                        onChange={e => setData('description', e.target.value)}
                        rows="4"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                        placeholder="Provide details about the incident..."
                    />
                    {errors.description && <div className="text-red-500 text-sm mt-1 font-medium">{errors.description}</div>}
                </div>

                <div className="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                    <button 
                        type="button" 
                        onClick={getLocation}
                        className="px-4 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition text-sm font-medium"
                    >
                        Pin Current Location
                    </button>
                    <span className="text-sm text-gray-600 font-medium">{locationStatus}</span>
                </div>
                {/* 3. Added error displays for location if they fail backend validation */}
                {errors.latitude && <div className="text-red-500 text-sm mt-1 font-medium">Location error: {errors.latitude}</div>}

                <div>
                    <label className="block text-sm font-medium text-gray-700">Photo Evidence (Optional)</label>
                    <input 
                        type="file" 
                        onChange={e => setData('attachment', e.target.files[0])}
                        className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100"
                    />
                    {/* 4. Added error display for attachments (e.g. if file is over 2MB or not an image) */}
                    {errors.attachment && <div className="text-red-500 text-sm mt-1 font-medium">{errors.attachment}</div>}
                </div>

                <button 
                    type="submit" 
                    disabled={processing}
                    className="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                >
                    {processing ? 'Submitting Alert...' : 'Submit Emergency Alert'}
                </button>
            </form>
        </div>
    );
}

EmergencyReport.layout = page => <ResidentLayout>{page}</ResidentLayout>;