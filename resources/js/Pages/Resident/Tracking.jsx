import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import ResidentLayout from '@/Layouts/ResidentLayout';

// Helper component to color-code statuses consistently
const StatusBadge = ({ status }) => {
    const s = (status || 'pending').toLowerCase();
    let colors = 'bg-amber-100 text-amber-700'; // Default: Pending
    
    if (s === 'completed' || s === 'resolved' || s === 'approved' || s === 'ready') {
        colors = 'bg-emerald-100 text-emerald-700';
    } else if (s === 'in_progress' || s === 'ongoing' || s === 'processing') {
        colors = 'bg-blue-100 text-blue-700';
    } else if (s === 'rejected' || s === 'cancelled') {
        colors = 'bg-red-100 text-red-700';
    }
    
    return (
        <span className={`px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider ${colors}`}>
            {status}
        </span>
    );
};

export default function Tracking({ caseUpdates = [], reports = [], serviceRequests = [], documentRequests = [] }) {
    const [activeTab, setActiveTab] = useState('reports');

    return (
        <div className="min-h-screen bg-slate-50 p-4 md:p-8">
            <Head title="Activity Tracking" />
            
            <div className="max-w-6xl mx-auto w-full">
                
                <div className="mb-6">
                    <h2 className="text-2xl md:text-3xl font-black text-slate-900">Activity & Tracking</h2>
                    <p className="text-sm text-slate-600 mt-1">Monitor the real-time status of your requests and reports.</p>
                </div>

                {/* Mobile-Scrollable Tabs Navigation */}
                <div className="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div className="flex border-b border-slate-200 overflow-x-auto snap-x hide-scrollbar">
                        <button 
                            onClick={() => setActiveTab('reports')} 
                            className={`px-4 md:px-6 py-4 text-xs md:text-sm font-bold uppercase whitespace-nowrap snap-start transition-colors focus:outline-none ${activeTab === 'reports' ? 'border-b-2 border-[#0a2342] text-[#0a2342] bg-slate-50' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Incident Reports ({reports.length})
                        </button>
                        <button 
                            onClick={() => setActiveTab('services')} 
                            className={`px-4 md:px-6 py-4 text-xs md:text-sm font-bold uppercase whitespace-nowrap snap-start transition-colors focus:outline-none ${activeTab === 'services' ? 'border-b-2 border-[#0a2342] text-[#0a2342] bg-slate-50' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Service Requests ({serviceRequests.length})
                        </button>
                        <button 
                            onClick={() => setActiveTab('documents')} 
                            className={`px-4 md:px-6 py-4 text-xs md:text-sm font-bold uppercase whitespace-nowrap snap-start transition-colors focus:outline-none ${activeTab === 'documents' ? 'border-b-2 border-[#0a2342] text-[#0a2342] bg-slate-50' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Document Requests ({documentRequests.length})
                        </button>
                        <button 
                            onClick={() => setActiveTab('mediation')} 
                            className={`px-4 md:px-6 py-4 text-xs md:text-sm font-bold uppercase whitespace-nowrap snap-start transition-colors focus:outline-none ${activeTab === 'mediation' ? 'border-b-2 border-[#0a2342] text-[#0a2342] bg-slate-50' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'}`}
                        >
                            Mediation Updates ({caseUpdates.length})
                        </button>
                    </div>

                    {/* Tab Contents */}
                    <div className="p-4 md:p-6">
                        
                        {/* 1. Incident Reports Tab */}
                        {activeTab === 'reports' && (
                            <div>
                                {/* Mobile View: Stacked Cards */}
                                <div className="block md:hidden space-y-4">
                                    {reports.length > 0 ? reports.map(req => (
                                        <div key={req.id} className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                            <div className="flex justify-between items-start mb-2">
                                                <span className="font-bold text-slate-800 text-sm">{req.incident_type || req.type}</span>
                                                <StatusBadge status={req.status} />
                                            </div>
                                            <p className="text-xs text-slate-500 mb-2">{new Date(req.created_at).toLocaleDateString()}</p>
                                            <p className="text-sm text-slate-600 line-clamp-2">{req.description || req.location_details || 'N/A'}</p>
                                        </div>
                                    )) : <div className="text-center text-slate-500 py-8 text-sm bg-slate-50 rounded-lg border border-dashed border-slate-300">No incident reports filed.</div>}
                                </div>
                                
                                {/* Desktop View: Table */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="w-full text-left text-sm whitespace-nowrap">
                                        <thead>
                                            <tr className="border-b border-slate-200 text-slate-500 uppercase text-xs">
                                                <th className="py-3 px-2">Date Submitted</th>
                                                <th className="py-3 px-2">Type</th>
                                                <th className="py-3 px-2">Description</th>
                                                <th className="py-3 px-2 text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {reports.length > 0 ? reports.map(req => (
                                                <tr key={req.id} className="hover:bg-slate-50">
                                                    <td className="py-4 px-2 text-slate-600">{new Date(req.created_at).toLocaleDateString()}</td>
                                                    <td className="py-4 px-2 font-bold text-slate-800">{req.incident_type || req.type}</td>
                                                    <td className="py-4 px-2 text-slate-500 truncate max-w-[300px]">{req.description || req.location_details || 'N/A'}</td>
                                                    <td className="py-4 px-2 text-right"><StatusBadge status={req.status} /></td>
                                                </tr>
                                            )) : <tr><td colSpan="4" className="py-8 text-center text-slate-500 bg-slate-50 border border-dashed border-slate-300 rounded">No incident reports filed.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* 2. Service Requests Tab */}
                        {activeTab === 'services' && (
                            <div>
                                {/* Mobile View */}
                                <div className="block md:hidden space-y-4">
                                    {serviceRequests.length > 0 ? serviceRequests.map(req => (
                                        <div key={req.id} className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                            <div className="flex justify-between items-start mb-2">
                                                <span className="font-bold text-slate-800 text-sm">{req.service_type || req.type || 'Barangay Service'}</span>
                                                <StatusBadge status={req.status} />
                                            </div>
                                            <p className="text-xs text-slate-500 mb-2">{new Date(req.created_at).toLocaleDateString()}</p>
                                            <p className="text-sm text-slate-600 line-clamp-2">{req.description || req.details || 'N/A'}</p>
                                        </div>
                                    )) : <div className="text-center text-slate-500 py-8 text-sm bg-slate-50 rounded-lg border border-dashed border-slate-300">No service requests found.</div>}
                                </div>

                                {/* Desktop View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="w-full text-left text-sm whitespace-nowrap">
                                        <thead>
                                            <tr className="border-b border-slate-200 text-slate-500 uppercase text-xs">
                                                <th className="py-3 px-2">Date Submitted</th>
                                                <th className="py-3 px-2">Service Type</th>
                                                <th className="py-3 px-2">Details</th>
                                                <th className="py-3 px-2 text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {serviceRequests.length > 0 ? serviceRequests.map(req => (
                                                <tr key={req.id} className="hover:bg-slate-50">
                                                    <td className="py-4 px-2 text-slate-600">{new Date(req.created_at).toLocaleDateString()}</td>
                                                    <td className="py-4 px-2 font-bold text-slate-800">{req.service_type || req.type || 'Barangay Service'}</td>
                                                    <td className="py-4 px-2 text-slate-500 truncate max-w-[300px]">{req.description || req.details || 'N/A'}</td>
                                                    <td className="py-4 px-2 text-right"><StatusBadge status={req.status} /></td>
                                                </tr>
                                            )) : <tr><td colSpan="4" className="py-8 text-center text-slate-500 bg-slate-50 border border-dashed border-slate-300 rounded">No service requests found.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* 3. Document Requests Tab */}
                        {activeTab === 'documents' && (
                            <div>
                                {/* Mobile View */}
                                <div className="block md:hidden space-y-4">
                                    {documentRequests.length > 0 ? documentRequests.map(req => (
                                        <div key={req.id} className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                            <div className="flex justify-between items-start mb-2">
                                                <span className="font-bold text-slate-800 text-sm">{req.document_type?.name || req.document_type || 'Barangay Certificate'}</span>
                                                <StatusBadge status={req.status} />
                                            </div>
                                            <p className="text-xs text-slate-500 mb-2">Requested: {new Date(req.created_at).toLocaleDateString()}</p>
                                            <p className="text-sm text-slate-600 line-clamp-2"><span className="font-bold text-slate-500">Purpose:</span> {req.purpose || 'N/A'}</p>
                                        </div>
                                    )) : <div className="text-center text-slate-500 py-8 text-sm bg-slate-50 rounded-lg border border-dashed border-slate-300">No document requests found.</div>}
                                </div>

                                {/* Desktop View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="w-full text-left text-sm whitespace-nowrap">
                                        <thead>
                                            <tr className="border-b border-slate-200 text-slate-500 uppercase text-xs">
                                                <th className="py-3 px-2">Date Requested</th>
                                                <th className="py-3 px-2">Document Type</th>
                                                <th className="py-3 px-2">Purpose</th>
                                                <th className="py-3 px-2 text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {documentRequests.length > 0 ? documentRequests.map(req => (
                                                <tr key={req.id} className="hover:bg-slate-50">
                                                    <td className="py-4 px-2 text-slate-600">{new Date(req.created_at).toLocaleDateString()}</td>
                                                    <td className="py-4 px-2 font-bold text-slate-800">{req.document_type?.name || req.document_type || 'Barangay Certificate'}</td>
                                                    <td className="py-4 px-2 text-slate-500 truncate max-w-[300px]">{req.purpose || 'N/A'}</td>
                                                    <td className="py-4 px-2 text-right"><StatusBadge status={req.status} /></td>
                                                </tr>
                                            )) : <tr><td colSpan="4" className="py-8 text-center text-slate-500 bg-slate-50 border border-dashed border-slate-300 rounded">No document requests found.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                        {/* 4. Mediation Case Updates Tab */}
                        {activeTab === 'mediation' && (
                            <div>
                                {/* Mobile View */}
                                <div className="block md:hidden space-y-4">
                                    {caseUpdates.length > 0 ? caseUpdates.map(update => (
                                        <div key={update.id} className="bg-slate-50 p-4 rounded-lg border border-slate-200">
                                            <div className="flex justify-between items-start mb-2">
                                                <span className="font-black text-slate-800 text-sm uppercase">{update.case_number}</span>
                                                <StatusBadge status={update.status} />
                                            </div>
                                            <p className="text-sm font-bold text-slate-700 mb-1">{update.incident_type}</p>
                                            <div className="mt-3 pt-3 border-t border-slate-200 flex justify-between items-center">
                                                <span className="text-xs font-bold text-slate-500">Meeting {update.meeting_number} of 3</span>
                                                <span className="text-xs font-bold text-blue-600">{new Date(update.scheduled_date).toLocaleString([], { dateStyle: 'short', timeStyle: 'short' })}</span>
                                            </div>
                                        </div>
                                    )) : <div className="text-center text-slate-500 py-8 text-sm bg-slate-50 rounded-lg border border-dashed border-slate-300">No active mediation cases.</div>}
                                </div>

                                {/* Desktop View */}
                                <div className="hidden md:block overflow-x-auto">
                                    <table className="w-full text-left text-sm whitespace-nowrap">
                                        <thead>
                                            <tr className="border-b border-slate-200 text-slate-500 uppercase text-xs">
                                                <th className="py-3 px-2">Case Number</th>
                                                <th className="py-3 px-2">Incident Type</th>
                                                <th className="py-3 px-2">Meeting Number</th>
                                                <th className="py-3 px-2">Scheduled Date</th>
                                                <th className="py-3 px-2 text-right">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {caseUpdates.length > 0 ? caseUpdates.map(update => (
                                                <tr key={update.id} className="hover:bg-slate-50">
                                                    <td className="py-4 px-2 font-bold text-slate-800">{update.case_number}</td>
                                                    <td className="py-4 px-2 text-slate-600">{update.incident_type}</td>
                                                    <td className="py-4 px-2 font-medium text-slate-700">Meeting {update.meeting_number} of 3</td>
                                                    <td className="py-4 px-2 text-blue-600 font-bold">{new Date(update.scheduled_date).toLocaleString()}</td>
                                                    <td className="py-4 px-2 text-right"><StatusBadge status={update.status} /></td>
                                                </tr>
                                            )) : <tr><td colSpan="5" className="py-8 text-center text-slate-500 bg-slate-50 border border-dashed border-slate-300 rounded">No active mediation cases.</td></tr>}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}

                    </div>
                </div>
            </div>
        </div>
    );
}

Tracking.layout = page => <ResidentLayout>{page}</ResidentLayout>;