import React from 'react';
import { Link } from '@inertiajs/react';

export default function ResidentLayout({ children }) {
    return (
        // The pb-16 ensures the main content doesn't get hidden behind the sticky nav
        <div className="min-h-screen bg-gray-50 pb-16 relative"> 
            
            {/* Page Content injected here */}
            <main className="p-4">
                {children}
            </main>

            {/* Sticky Bottom Navigation */}
            <nav className="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-50 flex justify-around items-center p-3 shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">
                
                {/* Adjust route names based on your web.php routes */}
                <Link 
                    href="/resident/home"
                    className="flex flex-col items-center text-sm font-medium text-gray-600 hover:text-blue-600 focus:text-blue-600"
                >
                    {/* Add an Icon here if desired */}
                    <span className="text-xl">🏠</span>
                    <span>Home</span>
                </Link>
                
                <Link 
                    href="/resident/profile" 
                    className="flex flex-col items-center text-sm font-medium text-gray-600 hover:text-blue-600 focus:text-blue-600"
                >
                    {/* Add an Icon here if desired */}
                    <span className="text-xl">👤</span>
                    <span>Profile</span>
                </Link>

            </nav>
        </div>
    );
}