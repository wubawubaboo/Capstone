import React from 'react';
import { Link, router } from '@inertiajs/react';

export default function DocumentRequest() {
  function handleSubmit(e) {
    e.preventDefault();
    post('/resident/document-request', {});
  }

  return (
    <div className="min-h-screen bg-white flex flex-col justify-between">
      <div>
        <div className="p-4 flex items-center text-blue-900 border-b border-gray-100">
          <Link href="/resident/home" className="mr-4 text-xl font-bold">←</Link>
          <h1 className="text-lg font-bold">Request Document</h1>
        </div>
        <form id="docForm" onSubmit={handleSubmit} className="p-6 space-y-4">
          <div><label className="text-xs font-bold block mb-1">Full Name<span className="text-red-500">*</span></label><input type="text" defaultValue="Juan Dela Cruz" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
          <div><label className="text-xs font-bold block mb-1">Phone Number<span className="text-red-500">*</span></label><input type="text" placeholder="09XX-XXX-XXXX" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
          
          <div>
            <label className="text-xs font-bold block mb-1">Document Type<span className="text-red-500">*</span></label>
            <select className="w-full border border-gray-300 rounded-md p-2 text-sm bg-white focus:outline-none">
              <option>Barangay Indigency</option>
              <option>Barangay Clearance</option>
            </select>
          </div>
          
          <div>
            <label className="text-xs font-bold block mb-1">Purpose<span className="text-red-500">*</span></label>
            <select className="w-full border border-gray-300 rounded-md p-2 text-sm bg-white focus:outline-none">
              <option>Scholarship</option>
              <option>Employment</option>
            </select>
          </div>
        </form>
      </div>

      <div className="p-6">
        <button form="docForm" type="submit" className="w-full bg-blue-900 text-white font-bold py-3 rounded-md shadow-md hover:bg-blue-950 transition">
          REQUEST DOCUMENT
        </button>
      </div>
    </div>
  );
}