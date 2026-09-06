import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Settings({ sms_gateway }) {
    return (
        <AdminLayout>
            <Head title="System Settings" />
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <div className="bg-white p-6 rounded-lg shadow max-w-2xl">
                    <h2 className="text-xl font-bold text-gray-800 mb-6">Integration Status</h2>
                    <div className="flex items-center justify-between p-4 border rounded-md mb-4">
                        <div>
                            <h3 className="font-semibold text-gray-700">PhilSMS Gateway</h3>
                            <p className="text-sm text-gray-500">Automated emergency and notification routing.</p>
                        </div>
                        <span className={`px-3 py-1 rounded-full text-sm font-bold ${sms_gateway === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                            {sms_gateway === 'active' ? 'Online' : 'Offline'}
                        </span>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}