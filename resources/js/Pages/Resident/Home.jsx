import React from 'react';
import { Link, router } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function Home() {
    // Function to handle the high-priority SOS trigger
    const triggerSOS = () => {
        if (confirm("Trigger URGENT SOS? This will immediately alert the Barangay Police.")) {
            // Get location if available, then post to a dedicated SOS route
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    router.post(route('resident.sos.trigger'), {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        type: 'SOS_CRITICAL' 
                    });
                },
                () => {
                    // Fallback if location is denied
                    router.post(route('resident.sos.trigger'), { type: 'SOS_CRITICAL' });
                }
            );
        }
    };

    return (
        <div className="flex flex-col items-center pt-6 px-2 pb-4">
            <h2 className="text-red-600 font-bold mb-4 tracking-widest text-xs">EMERGENCY ONLY</h2>
            
            {/* High-Priority SOS Button */}
            <button 
                onClick={triggerSOS}
                className="w-48 h-48 bg-red-600 text-white rounded-full flex flex-col items-center justify-center shadow-[0_0_20px_rgba(220,38,38,0.5)] border-8 border-red-100 mb-6 active:scale-95 transition-transform"
            >
                <span className="text-5xl font-black">SOS</span>
            </button>
            <p className="text-xs text-gray-500 italic mb-8 text-center">For Fire, Crimes, Severe Accidents/Injury</p>
            
            <div className="w-full">
                <h3 className="font-bold text-xs text-gray-700 mb-3 uppercase tracking-wider">Quick Menu</h3>
                
                <div className="grid grid-cols-2 gap-3">
                    {/* Standard Non-Urgent Incident Report */}
                    <Link href={route('resident.reports.create')} className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[100px] hover:border-red-500 hover:shadow-md transition-all">
                        <div className="text-2xl mb-2">📝</div>
                        <span className="text-[10px] font-bold text-blue-900 leading-tight">File an Incident Report</span>
                    </Link>

                    {/* Service Requests */}
                    <Link href={route('resident.services.create')} className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[100px] hover:border-red-500 hover:shadow-md transition-all">
                        <div className="text-2xl mb-2">🚑</div>
                        <span className="text-[10px] font-bold text-blue-900 leading-tight">Service Requests</span>
                    </Link>

                    {/* Document Requests */}
                    <Link href={route('resident.documents.create')} className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[100px] hover:border-blue-500 hover:shadow-md transition-all">
                        <div className="text-2xl mb-2">📄</div>
                        <span className="text-[10px] font-bold text-blue-900 leading-tight">Request Barangay Documents</span>
                    </Link>

                    {/* Hotlines */}
                    <Link href={route('hotlines')} className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[100px] hover:border-blue-500 hover:shadow-md transition-all">
                        <div className="text-2xl mb-2">📞</div>
                        <span className="text-[10px] font-bold text-blue-900 leading-tight">Barangay Hotlines</span>
                    </Link>
                </div>
            </div>
        </div>
    );
}

Home.layout = page => <ResidentLayout>{page}</ResidentLayout>;