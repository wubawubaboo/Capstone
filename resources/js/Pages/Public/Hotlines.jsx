import React from 'react';
import { Link } from '@inertiajs/react';

export default function Hotlines() {
  const hotlines = [
    { name: 'Barangay Hotline', number: '0905-165-2535' },
    { name: 'PNP - Gapan', number: '0967-387-6877' },
    { name: 'BFP - Gapan', number: '0905-291-3329' },
  ];

  return (
    <div className="h-screen bg-gray-50 flex flex-col">
      <div className="bg-red-700 text-white p-4 flex items-center">
        <Link href="/" className="mr-4 text-xl font-bold">←</Link>
        <h1 className="text-lg font-bold">Emergency Hotlines</h1>
      </div>
      <div className="p-4 space-y-3">
        {hotlines.map((hotline, idx) => (
          <div key={idx} className="bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center">
            <div>
              <h2 className="font-bold text-blue-900 text-sm">{hotline.name}</h2>
              <p className="text-gray-500 text-xs mt-1">{hotline.number}</p>
            </div>
            <a href={`tel:${hotline.number.replace(/-/g, '')}`} className="text-red-600 p-2">
              📞
            </a>
          </div>
        ))}
      </div>
    </div>
  );
}