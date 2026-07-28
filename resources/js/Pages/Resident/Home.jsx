import React from 'react';
import { Link } from '@inertiajs/react';

export default function Home() {
  return (
    <div className="h-screen bg-gray-50 flex flex-col relative justify-between">
      <div className="flex-grow flex flex-col items-center pt-10 px-4">
        <h2 className="text-red-600 font-bold mb-4 tracking-widest text-xs">EMERGENCY ONLY</h2>
        
        {/* SOS Button */}
        <button className="w-48 h-48 bg-red-600 text-white rounded-full flex flex-col items-center justify-center shadow-[0_0_20px_rgba(220,38,38,0.5)] border-8 border-red-100 mb-6 active:scale-95 transition-transform">
          <span className="text-5xl font-black">SOS</span>
          <span className="text-xs font-bold mt-2">HOLD 3S</span>
        </button>
        <p className="text-xs text-gray-500 italic mb-8">For Fire, Crimes, Severe Accidents/ Injury</p>
        
        {/* Quick Menu */}
        <div className="w-full">
          <h3 className="font-bold text-xs text-gray-700 mb-3">Quick Menu</h3>
          <div className="grid grid-cols-2 gap-3">
            <Link href="#" className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[90px] hover:border-red-500 transition">
              <div className="text-blue-900 text-2xl mb-1">🚨</div>
              <span className="text-[10px] font-bold text-blue-900 leading-tight">Request Emergency Assistance</span>
            </Link>

            <Link href="#" className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[90px] hover:border-red-500 transition">
              <div className="text-blue-900 text-2xl mb-1">📝</div>
              <span className="text-[10px] font-bold text-blue-900 leading-tight">File Incident Report</span>
            </Link>

            <Link href="/resident/document-request" className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[90px] hover:border-red-500 transition">
              <div className="text-blue-900 text-2xl mb-1">📄</div>
              <span className="text-[10px] font-bold text-blue-900 leading-tight">Request Barangay Documents</span>
            </Link>

            <Link href="/hotlines" className="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center justify-center min-h-[90px] hover:border-red-500 transition">
              <div className="text-blue-900 text-2xl mb-1">📞</div>
              <span className="text-[10px] font-bold text-blue-900 leading-tight">Barangay Hotlines</span>
            </Link>
          </div>
        </div>
      </div>

      {/* Bottom Navigation */}
      <div className="h-16 bg-white border-t border-gray-200 flex justify-around items-center">
         <Link href="/resident/home" className="text-red-600 flex flex-col items-center">
            <span className="text-xl">🏠</span>
            <span className="text-[10px] font-bold">Home</span>
         </Link>
         <Link href="/resident/profile" className="text-gray-400 hover:text-blue-900 flex flex-col items-center">
            <span className="text-xl">👤</span>
            <span className="text-[10px] font-bold">Profile</span>
         </Link>
      </div>
    </div>
  );
}