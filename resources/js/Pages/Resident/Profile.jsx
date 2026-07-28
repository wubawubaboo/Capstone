import React from 'react';
import { Link } from '@inertiajs/react';

export default function Profile() {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-between">
      <div>
        <div className="bg-blue-900 h-32 relative">
           <button className="absolute top-4 right-4 text-white">⚙️</button>
           <div className="absolute -bottom-6 left-6 flex items-end">
              <div className="w-16 h-16 bg-white rounded-full border-2 border-white overflow-hidden shadow-sm flex items-center justify-center text-2xl">👤</div>
              <div className="ml-3 mb-1">
                 <p className="text-white text-xs opacity-80">Hello,</p>
                 <h2 className="text-white font-bold text-lg leading-tight">Juan Dela Cruz</h2>
              </div>
           </div>
        </div>
        
        <div className="mt-12 px-4">
          <div className="flex bg-gray-200 rounded-full p-1 mb-6">
            <button className="flex-1 bg-blue-900 text-white text-xs font-bold py-2 rounded-full">Active</button>
            <button className="flex-1 text-gray-500 text-xs font-bold py-2 rounded-full">History</button>
          </div>
          
          <div className="space-y-3">
            <StatusCard type="EMERGENCY REQUEST" title="Medical Assistance" date="Reported: April 12, 2026 • 2:32 PM" status="RESPONDING" statusColor="bg-red-100 text-red-600" id="REME-001" />
            <StatusCard type="INCIDENT REPORT" title="Noise Complaint" date="April 1, 2026 • 9:00 AM • Barangay Hall" status="SCHEDULED" statusColor="bg-blue-100 text-blue-600" id="RBLT-062" note="Mediation Hearing" />
          </div>
        </div>
      </div>

      {/* Bottom Navigation */}
      <div className="h-16 bg-white border-t border-gray-200 flex justify-around items-center">
         <Link href="/resident/home" className="text-gray-400 hover:text-red-600 flex flex-col items-center">
            <span className="text-xl">🏠</span>
            <span className="text-[10px] font-bold">Home</span>
         </Link>
         <Link href="/resident/profile" className="text-blue-900 flex flex-col items-center">
            <span className="text-xl">👤</span>
            <span className="text-[10px] font-bold">Profile</span>
         </Link>
      </div>
    </div>
  );
}

function StatusCard({ type, title, date, status, statusColor, id, note }) {
  return (
    <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
      <div className="flex justify-between items-center mb-1">
        <span className="text-[10px] font-bold text-red-600">🚨 {type}</span>
        <span className={`text-[9px] font-bold px-2 py-0.5 rounded-full ${statusColor}`}>{status}</span>
      </div>
      <h3 className="font-bold text-sm text-gray-800">{title}</h3>
      <p className="text-[10px] text-gray-400 mb-2">Case ID: #{id}</p>
      {note && <div className="border-l-2 border-blue-900 pl-2 mb-2"><p className="text-xs font-bold text-gray-700">{note}</p></div>}
      <p className="text-[10px] text-gray-500">{date}</p>
    </div>
  );
}