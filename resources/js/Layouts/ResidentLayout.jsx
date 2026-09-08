import React from 'react';
import { Link } from '@inertiajs/react';

export default function ResidentLayout({ children }) {
    // Define navigation items dynamically
    const navItems = [
        { 
            name: 'Home', 
            route: 'resident.home', 
            icon: '🏠' 
        },
        { 
            name: 'Tracking', 
            route: 'resident.tracking', 
            icon: '📋' 
        },
        { 
            name: 'Profile', 
            route: 'resident.profile', 
            icon: '👤' 
        }
    ];

    return (
        <div className="min-h-screen bg-slate-50 pb-24 relative font-sans"> 
            
            <main>
                {children}
            </main>

            <nav className="fixed bottom-0 left-0 w-full bg-white border-t border-slate-200 z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.03)]">
                {/* Changed max-w-md to max-w-4xl to allow spreading on PC displays */}
                <div className="max-w-4xl mx-auto w-full flex items-center p-2 md:p-3">
                    {navItems.map((item, index) => {
                        const isActive = route().current(item.route);

                        return (
                            <Link 
                                key={index}
                                href={route(item.route)}
                                className={`flex-1 flex flex-col items-center justify-center py-2 text-xs md:text-sm font-bold transition-all duration-200 ${
                                    isActive 
                                        ? 'text-[#0a2342] scale-110' 
                                        : 'text-slate-400 hover:text-slate-700'
                                }`}
                            >
                                <span className={`text-xl md:text-2xl mb-1 ${isActive ? 'drop-shadow-sm' : 'grayscale opacity-70'}`}>
                                    {item.icon}
                                </span>
                                <span>{item.name}</span>
                            </Link>
                        );
                    })}
                </div>
            </nav>
            
        </div>
    );
}