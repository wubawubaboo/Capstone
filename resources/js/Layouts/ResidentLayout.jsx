import React from 'react';
import { Link } from '@inertiajs/react';

export default function ResidentLayout({ children }) {
    return (
        <div className="min-h-screen bg-gray-50 pb-16 relative"> 
            <main className="p-4">
                {children}
            </main>

            <nav className="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-50 flex justify-around items-center p-3 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                <Link 
                    href={route('resident.home')}
                    className="flex flex-col items-center text-sm font-medium text-gray-600 hover:text-blue-600 focus:text-blue-600"
                >
                    <span className="text-xl">🏠</span>
                    <span>Home</span>
                </Link>
                
                <Link 
                    href={route('resident.profile')} 
                    className="flex flex-col items-center text-sm font-medium text-gray-600 hover:text-blue-600 focus:text-blue-600"
                >
                    <span className="text-xl">👤</span>
                    <span>Profile</span>
                </Link>
            </nav>
        </div>
    );
}