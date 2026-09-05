import React, { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';

export default function SecretaryLayout({ children }) {
    const { url } = usePage();
    const { auth } = usePage().props;
    const [sosAlert, setSosAlert] = useState(null);

    const navItems = [
        { label: 'Blotter Records', href: route('secretary.blotters'), name: 'secretary.blotters' },
        { label: 'Mediation Calendar', href: route('secretary.mediation-calendar'), name: 'secretary.mediation-calendar' },
        { label: 'Account Requests', href: route('secretary.account-requests'), name: 'secretary.account-requests' },
        { label: 'Document Request', href: route('secretary.document-requests'), name: 'secretary.document-requests' },
        { label: 'Service Requests', href: route('secretary.service-requests'), name: 'secretary.service-requests' },
        { label: 'Asset Management', href: route('secretary.assets'), name: 'secretary.assets' },
        { label: 'Analytics', href: route('secretary.analytics'), name: 'secretary.analytics' },
    ];

    useEffect(() => {
        window.Echo.channel('emergency-alerts')
            .listen('SosTriggered', (e) => {
                setSosAlert(e.report);
            });

        return () => {
            window.Echo.leaveChannel('emergency-alerts');
        };
    }, []);

    const acknowledgeAlert = () => {
        setSosAlert(null);
        router.reload({ only: ['reports'] });
    };

    return (
        <div className="flex min-h-screen bg-slate-100 font-sans text-gray-800 relative">
            {/* Sidebar */}
            <aside className="w-64 bg-[#0a2342] text-white flex flex-col justify-between p-6 shrink-0 z-10">
                <div>
                    <h1 className="text-xl font-bold tracking-wider mb-8 uppercase border-b border-slate-700 pb-4">
                        Secretary
                    </h1>
                    <nav className="space-y-3">
                        {navItems.map((item) => {
                            const cleanUrl = url || '';
                            const isActive = cleanUrl.includes(item.href.replace(window.location.origin, ''));
                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={`block px-4 py-2.5 rounded-md text-sm font-medium transition ${
                                        isActive
                                            ? 'bg-slate-700/60 text-white font-semibold'
                                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
                <div className="space-y-3 pt-4 border-t border-slate-700">
                    <div className="px-4 text-xs text-slate-300">
                        <span className="font-bold text-white block truncate">
                            {auth?.user?.full_name || 'Secretary'}
                        </span>
                    </div>
                    <Link
                        href={route('secretary.logout')}
                        method="post"
                        as="button"
                        className="w-full text-left px-4 py-2 text-xs text-red-400 hover:text-red-300 transition block"
                    >
                        Sign Out
                    </Link>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="flex-1 p-8 overflow-y-auto z-0">{children}</main>

            {/* Real-Time SOS Modal Overlay */}
            {sosAlert && (
                <div className="fixed inset-0 flex items-center justify-center bg-red-900 bg-opacity-80 z-50 p-4">
                    <div className="bg-white rounded-xl shadow-2xl p-8 max-w-lg w-full text-center border-4 border-red-600 animate-pulse">
                        <div className="text-6xl mb-4">🚨</div>
                        <h2 className="text-3xl font-black text-red-600 mb-2">CRITICAL SOS TRIGGERED</h2>
                        <p className="text-lg text-slate-800 font-bold mb-4">
                            Location: {sosAlert.location_details}
                        </p>
                        <p className="text-sm text-slate-600 mb-8">
                            The Barangay Police have been notified via SMS[cite: 2]. Please coordinate the response immediately.
                        </p>
                        <button 
                            onClick={acknowledgeAlert}
                            className="bg-red-600 text-white font-bold py-3 px-8 rounded-lg text-lg hover:bg-red-700 w-full transition-colors"
                        >
                            ACKNOWLEDGE & VIEW DASHBOARD
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}