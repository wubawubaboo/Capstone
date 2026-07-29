import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function SecretaryLayout({ children }) {
    // usePage() gives us the current URL to determine which tab is active,
    // and 'auth' to access the currently logged-in user's data.
    const { url, auth } = usePage().props; 
    const currentUrl = usePage().url;

    // Fixed URLs to match exactly what is in routes/web.php
    const navItems = [
        { label: 'Analytics', href: '/secretary/analytics' },
        { label: 'Blotter Records', href: '/secretary/blotter-management' },
        { label: 'Mediation Calendar', href: '/secretary/mediation-calendar' },
        { label: 'Account Requests', href: '/secretary/account-requests' },
        { label: 'Document Requests', href: '/secretary/document-requests' },
    ];

    return (
        <div className="flex min-h-screen bg-slate-100 font-sans text-gray-800">
            {/* Sidebar */}
            <aside className="w-64 bg-[#0a2342] text-white flex flex-col justify-between p-6 shrink-0 shadow-xl">
                <div>
                    <h1 className="text-xl font-bold tracking-wider mb-8 uppercase border-b border-slate-700 pb-4">
                        Secretary Portal
                    </h1>
                    
                    <nav className="space-y-2">
                        {navItems.map((item) => {
                            const isActive = currentUrl.startsWith(item.href);
                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={`block px-4 py-2.5 rounded-md text-sm font-medium transition duration-150 ease-in-out ${
                                        isActive
                                            ? 'bg-slate-700 text-white font-semibold shadow-inner'
                                            : 'text-slate-300 hover:bg-slate-800 hover:text-white'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </div>

                {/* User Profile & Bottom Actions */}
                <div className="pt-4 border-t border-slate-700 space-y-3">
                    {/* Displays the logged in user's name if available */}
                    <div className="px-4 py-2 text-sm text-slate-300">
                        Logged in as:<br/>
                        <span className="font-bold text-white truncate block">
                            {auth?.user?.full_name || 'Secretary Staff'}
                        </span>
                    </div>

                    <Link
                        href="/secretary/account-settings"
                        className="block px-4 py-2 text-xs text-slate-400 hover:text-white transition"
                    >
                        Account Settings
                    </Link>

                    {/* Functional Logout Button configured to POST to your AuthController */}
                    <Link
                        href="/secretary/logout"
                        method="post"
                        as="button"
                        className="w-full text-left px-4 py-2 text-xs text-red-400 hover:text-red-300 transition"
                    >
                        Sign Out
                    </Link>
                </div>
            </aside>

            {/* Main Content Area */}
            <main className="flex-1 p-8 overflow-y-auto">
                {children}
            </main>
        </div>
    );
}