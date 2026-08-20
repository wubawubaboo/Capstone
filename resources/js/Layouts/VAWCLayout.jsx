import React from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function VAWCLayout({ children }) {
    const { url } = usePage();
    const { auth } = usePage().props;

    const navItems = [
        { label: 'VAWC Blotters', href: route('vawc.blotters'), name: 'vawc.blotters' },
        { label: 'Mediation & BPO', href: route('vawc.mediation-calendar'), name: 'vawc.mediation-calendar' },
        { label: 'Analytics', href: route('vawc.analytics'), name: 'vawc.analytics' },
    ];

    return (
        <div className="flex min-h-screen bg-rose-50/40 font-sans text-slate-800">
            <aside className="w-64 bg-[#3B122D] text-white flex flex-col justify-between p-6 shrink-0 shadow-lg">
                <div>
                    <h1 className="text-lg font-bold tracking-wider mb-8 uppercase border-b border-rose-900/50 pb-4 text-rose-200">
                        VAWC Desk
                    </h1>
                    <nav className="space-y-2">
                        {navItems.map((item) => {
                            const cleanUrl = url || '';
                            const isActive = cleanUrl.includes(item.href.replace(window.location.origin, ''));
                            return (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className={`block px-4 py-2.5 rounded-md text-sm font-medium transition ${
                                        isActive
                                            ? 'bg-rose-900/60 text-white font-semibold shadow-inner'
                                            : 'text-rose-200/80 hover:bg-rose-950 hover:text-white'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
                <div className="space-y-3 pt-4 border-t border-rose-900/50">
                    <div className="px-4 text-xs text-rose-200/70">
                        <span className="font-bold text-white block truncate">
                            {auth?.user?.full_name || 'VAWC Officer'}
                        </span>
                        <span>Officer in Charge</span>
                    </div>
                    <Link
                        href={route('secretary.logout')}
                        method="post"
                        as="button"
                        className="w-full text-left px-4 py-2 text-xs text-rose-300 hover:text-rose-100 transition block"
                    >
                        Sign Out
                    </Link>
                </div>
            </aside>

            <main className="flex-1 p-8 overflow-y-auto">{children}</main>
        </div>
    );
}