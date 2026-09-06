import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Bar, Doughnut } from 'react-chartjs-2';
import 'chart.js/auto';

export default function Analytics({ incidentTrends, transactionVolumes }) {
    const incidentData = {
        labels: incidentTrends.map(item => item.type),
        datasets: [{
            label: 'Total Incidents',
            data: incidentTrends.map(item => item.total),
            backgroundColor: '#ef4444',
        }]
    };

    const transactionData = {
        labels: transactionVolumes.map(item => item.status),
        datasets: [{
            label: 'Document Requests',
            data: transactionVolumes.map(item => item.total),
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
        }]
    };

    return (
        <AdminLayout>
            <Head title="City-Level Analytics" />
            <div className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <h2 className="text-2xl font-bold text-gray-800 mb-6">Macro-Level Analytics Dashboard</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white p-6 rounded-lg shadow">
                        <h3 className="text-lg font-semibold mb-4">Incident Trends</h3>
                        <Bar data={incidentData} />
                    </div>
                    <div className="bg-white p-6 rounded-lg shadow">
                        <h3 className="text-lg font-semibold mb-4">Transaction Volumes</h3>
                        <Doughnut data={transactionData} />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}