import React from 'react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function Analytics({
    totalVawcCases = 0,
    settledCases = 0,
    escalatedCases = 0,
    incidentDistribution = [],
    caseStatusData = []
}) {
    return (
        <VAWCLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 className="text-2xl font-bold text-[#0a2540]">Administrative Analytics</h2>
                    <div className="flex items-center gap-3">
                        <button className="bg-[#0a2540] text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-slate-800">
                            Generate PDF Report
                        </button>
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm text-center">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total VAWC Cases</p>
                        <p className="text-4xl font-extrabold text-[#0a2540] mt-2">{totalVawcCases}</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm text-center">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Settled via Mediation</p>
                        <p className="text-4xl font-extrabold text-emerald-600 mt-2">{settledCases}</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm text-center">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Escalated to Court</p>
                        <p className="text-4xl font-extrabold text-red-600 mt-2">{escalatedCases}</p>
                    </div>
                </div>

                {/* Breakdown Area */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Incident Distribution */}
                    <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                        <h3 className="text-base font-bold text-slate-800 mb-6">Incident Distribution</h3>
                        <div className="space-y-4">
                            {incidentDistribution.length > 0 ? (
                                incidentDistribution.map((item, index) => {
                                    const percentage = totalVawcCases > 0 
                                        ? Math.round((item.value / totalVawcCases) * 100) 
                                        : 0;
                                    return (
                                        <div key={index}>
                                            <div className="flex justify-between text-xs font-bold text-slate-600 mb-1">
                                                <span>{item.name || 'Unspecified'}</span>
                                                <span>{item.value} Case{item.value > 1 ? 's' : ''} ({percentage}%)</span>
                                            </div>
                                            <div className="w-full bg-slate-100 rounded-full h-2">
                                                <div 
                                                    className="bg-[#0a2540] h-2 rounded-full transition-all duration-300" 
                                                    style={{ width: `${percentage}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    );
                                })
                            ) : (
                                <p className="text-sm text-slate-500 italic text-center py-6">No incident records yet.</p>
                            )}
                        </div>
                    </div>

                    {/* Case Status Distribution */}
                    <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                        <h3 className="text-base font-bold text-slate-800 mb-6">Case Status Overview</h3>
                        <div className="space-y-4">
                            {caseStatusData.length > 0 ? (
                                caseStatusData.map((item, index) => (
                                    <div key={index} className="flex justify-between items-center p-3 bg-slate-50 rounded-md">
                                        <span className="text-sm font-semibold text-slate-700">{item.name}</span>
                                        <span className="text-sm font-bold text-slate-900 bg-white px-3 py-1 rounded shadow-xs border border-slate-200">
                                            {item.value}
                                        </span>
                                    </div>
                                ))
                            ) : (
                                <p className="text-sm text-slate-500 italic text-center py-6">No status data available.</p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </VAWCLayout>
    );
}