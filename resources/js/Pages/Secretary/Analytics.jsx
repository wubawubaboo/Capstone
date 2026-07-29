import React from 'react';
import { Head } from '@inertiajs/react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';
import { 
    BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer,
    PieChart, Pie, Cell, Legend 
} from 'recharts';
import { FileText, AlertOctagon, Wrench, Clock } from 'lucide-react';

const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

export default function Analytics({ auth, stats, documentTrends, blotterStatusData }) {
    return (
        <SecretaryLayout user={auth.user}>
            <Head title="Secretary Analytics" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Header */}
                    <div className="mb-6">
                        <h2 className="text-2xl font-bold text-gray-800">Analytics Overview</h2>
                        <p className="text-sm text-gray-500">Track and monitor barangay requests and blotter records.</p>
                    </div>

                    {/* Quick Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <StatCard 
                            title="Total Document Requests" 
                            value={stats.document_requests} 
                            icon={<FileText className="w-6 h-6 text-blue-600" />} 
                        />
                        <StatCard 
                            title="Pending Documents" 
                            value={stats.pending_documents} 
                            icon={<Clock className="w-6 h-6 text-yellow-500" />} 
                        />
                        <StatCard 
                            title="Total Blotter Records" 
                            value={stats.blotter_records} 
                            icon={<AlertOctagon className="w-6 h-6 text-red-500" />} 
                        />
                        <StatCard 
                            title="Service Requests" 
                            value={stats.service_requests} 
                            icon={<Wrench className="w-6 h-6 text-green-500" />} 
                        />
                    </div>

                    {/* Charts Section */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                        
                        {/* Bar Chart: Document Trends */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-700 mb-6">Document Requests (Last 6 Months)</h3>
                            <div className="h-[300px] w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={documentTrends}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#E5E7EB" />
                                        <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fill: '#6B7280' }} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fill: '#6B7280' }} />
                                        <Tooltip cursor={{ fill: '#F3F4F6' }} contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                                        <Bar dataKey="total" fill="#3B82F6" radius={[4, 4, 0, 0]} barSize={40} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>

                        {/* Pie Chart: Blotter Status Distribution */}
                        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                            <h3 className="text-lg font-semibold text-gray-700 mb-6">Blotter Records by Status</h3>
                            <div className="h-[300px] w-full flex justify-center items-center">
                                {blotterStatusData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={blotterStatusData}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={70}
                                                outerRadius={100}
                                                paddingAngle={5}
                                                dataKey="value"
                                            >
                                                {blotterStatusData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip contentStyle={{ borderRadius: '8px', border: 'none', boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)' }} />
                                            <Legend verticalAlign="bottom" height={36} iconType="circle" />
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <p className="text-gray-500 italic">No blotter records found.</p>
                                )}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </SecretaryLayout>
    );
}

// Reusable Subcomponent for Stats Card
function StatCard({ title, value, icon }) {
    return (
        <div className="bg-white rounded-lg p-6 shadow-sm border border-gray-200 flex items-center justify-between">
            <div>
                <p className="text-sm font-medium text-gray-500 mb-1">{title}</p>
                <p className="text-2xl font-bold text-gray-900">{value}</p>
            </div>
            <div className="p-3 bg-gray-50 rounded-full">
                {icon}
            </div>
        </div>
    );
}