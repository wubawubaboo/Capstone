import React from 'react';
import { Link, router } from '@inertiajs/react';

export default function Registration() {
  function handleSubmit(e) {
    e.preventDefault();
    router.post('/register', {});
  }

  return (
    <div className="min-h-screen bg-white flex flex-col">
      <div className="p-4 flex items-center text-blue-900">
        <Link href="/login" className="mr-4 text-xl font-bold">←</Link>
        <h1 className="text-lg font-bold">Gumawa ng Account</h1>
      </div>
      <form onSubmit={handleSubmit} className="p-6 space-y-4">
        <p className="text-xs text-gray-500">Ibigay ang inyong tunay na impormasyon para sa pagpapatunay ng iyong account</p>
        
        <div><label className="text-xs font-bold block mb-1">Full Name<span className="text-red-500">*</span></label><input type="text" placeholder="Juan Dela Cruz" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
        <div><label className="text-xs font-bold block mb-1">Phone Number<span className="text-red-500">*</span></label><input type="text" placeholder="09XX-XXX-XXXX" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
        <div><label className="text-xs font-bold block mb-1">Address<span className="text-red-500">*</span></label><input type="text" placeholder="Liwasan Street" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
        
        <div>
          <label className="text-xs font-bold block mb-1">Upload National ID<span className="text-red-500">*</span></label>
          <div className="border-2 border-dashed border-gray-300 rounded-md p-6 text-center text-gray-400 cursor-pointer hover:border-blue-900 transition">
            <span className="text-2xl block mb-2">📸</span>
            <span className="text-xs">Click to upload or take photo</span>
          </div>
        </div>
        
        <div><label className="text-xs font-bold block mb-1">Password<span className="text-red-500">*</span></label><input type="password" placeholder="••••••••••••" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
        
        <button type="submit" className="w-full bg-blue-900 text-white font-bold py-3 rounded-md mt-6 shadow-md hover:bg-blue-950 transition">REGISTER NOW</button>
      </form>
    </div>
  );
}