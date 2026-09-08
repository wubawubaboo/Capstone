import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function Home() {
    const { auth } = usePage().props;
    const [isTriggering, setIsTriggering] = useState(false);

    const triggerSOS = () => {
        if (confirm("Trigger URGENT SOS? This will immediately alert the Barangay Police.")) {
            setIsTriggering(true);
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    router.post(route('resident.sos.trigger'), {
                        emergency_type: 'Immediate SOS',
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        type: 'SOS_CRITICAL' 
                    }, {
                        preserveScroll: true,
                        onFinish: () => setIsTriggering(false)
                    });
                },
                () => {
                    // Fallback if the user denies location access
                    router.post(route('resident.sos.trigger'), { 
                        emergency_type: 'Immediate SOS',
                        latitude: 0.000000, 
                        longitude: 0.000000,
                        type: 'SOS_CRITICAL' 
                    }, {
                        preserveScroll: true,
                        onFinish: () => setIsTriggering(false)
                    });
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
    };

    return (
        <div className="min-h-screen bg-slate-50 p-4 md:p-8">
            <div className="max-w-6xl mx-auto w-full flex flex-col lg:flex-row gap-6 lg:gap-10 items-stretch">
                
                {/* Left Panel: SOS Section */}
                <div className="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-8 md:p-12 lg:p-16 flex flex-col items-center justify-center text-center">
                    
                    <h2 className="text-red-600 font-black mb-8 tracking-widest text-xs md:text-sm uppercase bg-red-50 px-4 py-2 rounded-full border border-red-100">
                        Emergency Dispatch
                    </h2>
                    
                    <button 
                        onClick={triggerSOS}
                        disabled={isTriggering}
                        className={`w-56 h-56 md:w-72 md:h-72 lg:w-80 lg:h-80 bg-red-600 text-white rounded-full flex flex-col items-center justify-center shadow-[0_0_30px_rgba(220,38,38,0.3)] border-[12px] md:border-[16px] border-red-50 transition-all active:scale-95 ${isTriggering ? 'animate-pulse bg-red-700' : 'hover:bg-red-700 hover:scale-105'}`}
                    >
                        <span className="text-6xl md:text-7xl font-black tracking-tighter drop-shadow-md">
                            {isTriggering ? '...' : 'SOS'}
                        </span>
                    </button>
                    
                    <p className="text-sm md:text-base text-slate-500 font-medium mt-10 max-w-sm mx-auto">
                        For Fire, Crimes in Progress, and Severe Medical Emergencies. <br className="hidden md:block"/>
                        <strong className="text-slate-700">Barangay Police will be dispatched immediately.</strong>
                    </p>
                </div>
                
                {/* Right Panel: Quick Menu Section */}
                <div className="flex-1 flex flex-col justify-center">
                    
                    <div className="mb-6 hidden lg:block">
                        <h1 className="text-2xl font-black text-slate-900">Welcome, {auth?.user?.full_name?.split(' ')[0] || 'Resident'}</h1>
                        <p className="text-slate-500 font-medium mt-1">What do you need assistance with today?</p>
                    </div>

                    <h3 className="font-black text-xs md:text-sm text-slate-500 mb-4 uppercase tracking-wider text-center lg:text-left lg:hidden">
                        Quick Menu
                    </h3>
                    
                    <div className="grid grid-cols-2 gap-4 md:gap-6">
                        {/* Standard Non-Urgent Incident Report */}
                        <Link 
                            href={route('resident.reports.create')} 
                            className="bg-white p-5 md:p-8 rounded-xl shadow-sm border border-slate-200 flex flex-col items-center text-center justify-center min-h-[130px] md:min-h-[180px] hover:border-red-500 hover:shadow-md transition-all active:scale-95 group"
                        >
                            <div className="text-3xl md:text-4xl mb-3 md:mb-4 group-hover:scale-110 transition-transform">📝</div>
                            <span className="text-xs md:text-sm font-bold text-[#0a2342] leading-tight">File Incident Report</span>
                        </Link>

                        {/* Service Requests */}
                        <Link 
                            href={route('resident.services.create')} 
                            className="bg-white p-5 md:p-8 rounded-xl shadow-sm border border-slate-200 flex flex-col items-center text-center justify-center min-h-[130px] md:min-h-[180px] hover:border-red-500 hover:shadow-md transition-all active:scale-95 group"
                        >
                            <div className="text-3xl md:text-4xl mb-3 md:mb-4 group-hover:scale-110 transition-transform">🚑</div>
                            <span className="text-xs md:text-sm font-bold text-[#0a2342] leading-tight">Service Requests</span>
                        </Link>

                        {/* Document Requests */}
                        <Link 
                            href={route('resident.documents.create')} 
                            className="bg-white p-5 md:p-8 rounded-xl shadow-sm border border-slate-200 flex flex-col items-center text-center justify-center min-h-[130px] md:min-h-[180px] hover:border-blue-500 hover:shadow-md transition-all active:scale-95 group"
                        >
                            <div className="text-3xl md:text-4xl mb-3 md:mb-4 group-hover:scale-110 transition-transform">📄</div>
                            <span className="text-xs md:text-sm font-bold text-[#0a2342] leading-tight">Request Documents</span>
                        </Link>

                        {/* Hotlines */}
                        <Link 
                            href={route('hotlines')} 
                            className="bg-white p-5 md:p-8 rounded-xl shadow-sm border border-slate-200 flex flex-col items-center text-center justify-center min-h-[130px] md:min-h-[180px] hover:border-blue-500 hover:shadow-md transition-all active:scale-95 group"
                        >
                            <div className="text-3xl md:text-4xl mb-3 md:mb-4 group-hover:scale-110 transition-transform">📞</div>
                            <span className="text-xs md:text-sm font-bold text-[#0a2342] leading-tight">Barangay Hotlines</span>
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    );
}

Home.layout = page => <ResidentLayout>{page}</ResidentLayout>;