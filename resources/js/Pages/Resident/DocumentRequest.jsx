import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

export default function DocumentRequest() {
  const { data, setData, post, processing, errors } = useForm({
    document_type_id: 1,
    purpose: 'Scholarship',
    barangay_id: 1,
  });

  function handleSubmit(e) {
    e.preventDefault();
    post(route('resident.documents.store'));
  }

  return (
    <div className="min-h-screen bg-white flex flex-col justify-between">
      <div>
        <div className="p-4 flex items-center text-blue-900 border-b border-gray-100">
          <Link href={route('resident.home')} className="mr-4 text-xl font-bold">←</Link>
          <h1 className="text-lg font-bold">Request Document</h1>
        </div>
        <form id="docForm" onSubmit={handleSubmit} className="p-6 space-y-4">
          <div><label className="text-xs font-bold block mb-1">Full Name<span className="text-red-500">*</span></label><input type="text" defaultValue="Juan Dela Cruz" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
          <div><label className="text-xs font-bold block mb-1">Phone Number<span className="text-red-500">*</span></label><input type="text" placeholder="09XX-XXX-XXXX" className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none" /></div>
          
          <div>
            <label className="text-xs font-bold block mb-1">Document Type<span className="text-red-500">*</span></label>
            <select 
              value={data.document_type_id}
              onChange={e => setData('document_type_id', e.target.value)}
              className="w-full border border-gray-300 rounded-md p-2 text-sm bg-white focus:outline-none"
            >
              <option value={1}>Barangay Indigency</option>
              <option value={2}>Barangay Clearance</option>
            </select>
            {errors.document_type_id && <div className="text-red-500 text-xs mt-1">{errors.document_type_id}</div>}
          </div>
          
          <div>
            <label className="text-xs font-bold block mb-1">Purpose<span className="text-red-500">*</span></label>
            <select 
              value={data.purpose}
              onChange={e => setData('purpose', e.target.value)}
              className="w-full border border-gray-300 rounded-md p-2 text-sm bg-white focus:outline-none"
            >
              <option value="Scholarship">Scholarship</option>
              <option value="Employment">Employment</option>
            </select>
            {errors.purpose && <div className="text-red-500 text-xs mt-1">{errors.purpose}</div>}
          </div>
        </form>
      </div>

      <div className="p-6">
        <button form="docForm" type="submit" disabled={processing} className="w-full bg-blue-900 text-white font-bold py-3 rounded-md shadow-md hover:bg-blue-950 transition disabled:opacity-50">
          {processing ? 'SUBMITTING...' : 'REQUEST DOCUMENT'}
        </button>
      </div>
    </div>
  );
}

DocumentRequest.layout = page => <ResidentLayout>{page}</ResidentLayout>;