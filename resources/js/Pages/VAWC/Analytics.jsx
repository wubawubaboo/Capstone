import React from 'react';
import VAWCLayout from '@/Layouts/VAWCLayout';

export default function Analytics() {
    return (
        <VAWCLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 className="text-2xl font-bold text-[#0a2540]">Administrative Analytics</h2>
                    <div className="flex items-center gap-3">
                        <select className="border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-700 bg-white font-medium focus:outline-none">
                            <option>Monthly Report (April 2026)</option>
                        </select>
                        <button className="bg-[#0a2540] text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-slate-800">
                            Generate PDF Report
                        </button>
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm text-center">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total VAWC Cases</p>
                        <p className="text-4xl font-extrabold text-[#0a2540] mt-2">2</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm text-center">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Settled via Mediation</p>
                        <p className="text-4xl font-extrabold text-[#0a2540] mt-2">0</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm text-center">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Escalated to Court</p>
                        <p className="text-4xl font-extrabold text-[#0a2540] mt-2">2</p>
                    </div>
                </div>

                {/* Charts Area */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Incident Distribution */}
                    <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                        <h3 className="text-base font-bold text-slate-800 mb-6">Incident Distribution</h3>
                        <div className="space-y-6">
                            <div>
                                <div className="flex justify-between text-xs font-bold text-slate-600 mb-2">
                                    <span>Assault</span>
                                    <span>1 Case</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-[#0a2540] h-2 rounded-full" style={{ width: '50%' }}></div>
                                </div>
                            </div>
                            <div>
                                <div className="flex justify-between text-xs font-bold text-slate-600 mb-2">
                                    <span>Abuse</span>
                                    <span>1 Case</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-[#0a2540] h-2 rounded-full" style={{ width: '50%' }}></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Reports by Area Pie Chart */}
                    <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex flex-col justify-center items-center relative">
                        <h3 className="text-base font-bold text-slate-800 mb-4 absolute top-6 left-6">Reports by Area</h3>
                        
                        <div className="flex w-full mt-8">
                            <div className="flex-1 flex justify-center items-center">
                                {/* CSS Conic Gradient Pie Chart Representation */}
                                <div
                                    className="w-32 h-32 rounded-full"
                                    style={{
                                        background: 'conic-gradient(#f5a623 0% 50%, #5882f6 50% 100%)',
                                    }}
                                ></div>
                            </div>
                            
                            <div className="flex flex-col justify-center gap-3 text-xs font-semibold text-slate-600 pr-8">
                                <div className="flex items-center gap-2">
                                    <span className="w-3 h-3 bg-[#5882f6] rounded-sm"></span>
                                    <span>Zone 4</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <span className="w-3 h-3 bg-[#f5a623] rounded-sm"></span>
                                    <span>Zone 3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </VAWCLayout>
    );
}