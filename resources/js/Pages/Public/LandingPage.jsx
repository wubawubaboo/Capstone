import React from 'react';
import { Link } from '@inertiajs/react';

export default function LandingPage() {
  return (
    <div className="flex flex-col items-center justify-center h-screen bg-white p-6">
      <div className="flex-grow flex items-center justify-center">
        {/* Placeholder for Logo */}
        <div className="w-32 h-32 bg-gray-200 rounded-full mb-8"></div>
      </div>
      
      <div className="w-full max-w-sm space-y-4 mb-12">
        <Link 
          href="/hotlines"
          className="block text-center w-full bg-red-700 text-white font-bold py-3 rounded-full shadow-md hover:bg-red-800 transition"
        >
          Emergency Hotlines
        </Link>
        
        <Link 
          href="/login"
          className="block text-center w-full bg-blue-900 text-white font-bold py-3 rounded-full shadow-md hover:bg-blue-950 transition"
        >
          Login
        </Link>
      </div>
    </div>
  );
}