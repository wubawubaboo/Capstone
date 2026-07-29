import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function SecretaryLayout({ children }) {
    const { url } = usePage();

    const navItems = [
        { label: 'Blotter Records', href: '/secretary/blotter' },
        { label: 'Mediation Calendar', href: '/secretary/mediation-calendar' },
        { label: 'Account Requests', href: '/secretary/account-requests' },
        { label: 'Document Request', href: '/secretary/document-requests' },
        { label: 'Analytics', href: '/secretary/analytics' },
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
                            const isActive = url.startsWith(item.href);
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
                <div>
                    <Link
                        href="/secretary/account-settings"
                        className="block text-xs text-slate-400 hover:text-white pt-4 border-t border-slate-700"
                    >
                        Account Settings
                    </Link>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="flex-1 p-8 overflow-y-auto">{children}</main>
        </div>
    );
}