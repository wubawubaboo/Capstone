import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function AdminLayout({ children }) {
    const { url } = usePage();
    const { auth } = usePage().props;

    const navItems = [
        { label: 'Analytics', href: route('admin.analytics'), name: 'admin.analytics' },
        { label: 'Accounts', href: route('admin.accounts'), name: 'admin.accounts' },
        { label: 'Audit Logs', href: route('admin.logs'), name: 'admin.logs' },
        { label: 'Settings', href: route('admin.settings'), name: 'admin.settings' },
    ];

    return (
        <div className="flex min-h-screen bg-slate-100 font-sans text-gray-800 relative">
            {/* Sidebar */}
            <aside className="w-64 bg-[#0a2342] text-white flex flex-col justify-between p-6 shrink-0 z-10">
                <div>
                    <h1 className="text-xl font-bold tracking-wider mb-8 uppercase border-b border-slate-700 pb-4">
                        City Admin
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
                            {auth?.user?.name || auth?.user?.full_name || 'Administrator'}
                        </span>
                    </div>
                    {/* Note: Ensure your logout route is accessible globally or adjust the route name if scoped */}
                    <Link
                        href={route('admin.logout')}
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
        </div>
    );
}