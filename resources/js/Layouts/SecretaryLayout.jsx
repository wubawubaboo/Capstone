import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function SecretaryLayout({ children }) {
    const { url } = usePage();
    const { auth } = usePage().props;

    const navItems = [
        { label: 'Blotter Records', href: route('secretary.blotters'), name: 'secretary.blotters' },
        { label: 'Mediation Calendar', href: route('secretary.mediation-calendar'), name: 'secretary.mediation-calendar' },
        { label: 'Account Requests', href: route('secretary.account-requests'), name: 'secretary.account-requests' },
        { label: 'Document Request', href: route('secretary.document-requests'), name: 'secretary.document-requests' },
        { label: 'Service Dispatch', href: route('secretary.service-requests'), name: 'secretary.service-requests' },
        { label: 'Asset Management', href: route('secretary.assets'), name: 'secretary.assets' },
        { label: 'Analytics', href: route('secretary.analytics'), name: 'secretary.analytics' },
    ];

    return (
        <div className="flex min-h-screen bg-slate-100 font-sans text-gray-800">
            {/* Sidebar */}
            <aside className="w-64 bg-[#0a2342] text-white flex flex-col justify-between p-6 shrink-0">
                <div>
                    <h1 className="text-xl font-bold tracking-wider mb-8 uppercase border-b border-slate-700 pb-4">
                        Secretary
                    </h1>
                    <nav className="space-y-3">
                        {navItems.map((item) => {
                            // Protect against undefined url with optional chaining / fallback
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
            <main className="flex-1 p-8 overflow-y-auto">{children}</main>
        </div>
    );
}