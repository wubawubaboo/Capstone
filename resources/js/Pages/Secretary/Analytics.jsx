import React from 'react';
import SecretaryLayout from '@/Layouts/SecretaryLayout';

export default function Analytics() {
    return (
        <SecretaryLayout>
            <div className="space-y-6">
                {/* Header Row */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <h2 className="text-2xl font-bold text-[#0a2342]">Administrative Analytics</h2>
                    <div className="flex items-center gap-3">
                        <select className="border border-slate-300 rounded px-3 py-1.5 text-xs text-slate-700 bg-white font-medium focus:outline-none">
                            <option>Monthly Report (April 2026)</option>
                        </select>
                        <button className="bg-[#0a2342] text-white px-4 py-1.5 rounded text-xs font-bold hover:bg-slate-800">
                            Generate PDF Report
                        </button>
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Blotter</p>
                        <p className="text-3xl font-extrabold text-[#0a2342] mt-2">19</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Settled via Mediation</p>
                        <p className="text-3xl font-extrabold text-[#0a2342] mt-2">13</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Escalated to Court</p>
                        <p className="text-3xl font-extrabold text-[#0a2342] mt-2">4</p>
                    </div>
                    <div className="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
                        <p className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">VAWC Cases Filed</p>
                        <p className="text-3xl font-extrabold text-red-700 mt-2">2</p>
                    </div>
                </div>

                {/* Charts Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Incident Distribution */}
                    <div className="lg:col-span-2 bg-white p-6 rounded-lg border border-slate-200 shadow-sm">
                        <h3 className="text-base font-bold text-slate-800 mb-6">Incident Distribution</h3>
                        <div className="space-y-5">
                            <div>
                                <div className="flex justify-between text-xs font-bold text-slate-600 mb-1">
                                    <span>Public Disturbance</span>
                                    <span>8 Cases</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-[#0a2342] h-2 rounded-full" style={{ width: '80%' }}></div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-xs font-bold text-slate-600 mb-1">
                                    <span>Noise Complaint</span>
                                    <span>6 Cases</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-[#0a2342] h-2 rounded-full" style={{ width: '60%' }}></div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-xs font-bold text-slate-600 mb-1">
                                    <span>Pagnanakaw</span>
                                    <span>3 Cases</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-[#0a2342] h-2 rounded-full" style={{ width: '30%' }}></div>
                                </div>
                            </div>

                            <div>
                                <div className="flex justify-between text-xs font-bold text-slate-600 mb-1">
                                    <span>VAWC (Violence Against Women & Children)</span>
                                    <span>2 Cases</span>
                                </div>
                                <div className="w-full bg-slate-100 rounded-full h-2">
                                    <div className="bg-red-600 h-2 rounded-full" style={{ width: '20%' }}></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Reports by Area (Pie Chart View) */}
                    <div className="bg-white p-6 rounded-lg border border-slate-200 shadow-sm flex flex-col justify-between">
                        <h3 className="text-base font-bold text-slate-800 mb-4 text-center">Reports by Area</h3>
                        <div className="flex items-center justify-center my-4">
                            {/* CSS Conic Gradient Pie Chart Representation */}
                            <div
                                className="w-40 h-40 rounded-full"
                                style={{
                                    background: 'conic-gradient(#5882f6 0% 45%, #7ed321 45% 65%, #f5a623 65% 85%, #ed3b83 85% 100%)',
                                }}
                            ></div>
                        </div>

                        {/* Legend */}
                        <div className="grid grid-cols-2 gap-2 text-xs font-semibold text-slate-600 mt-4">
                            <div className="flex items-center gap-2">
                                <span className="w-3 h-3 bg-[#5882f6] rounded-sm"></span>
                                <span>Zone 4</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="w-3 h-3 bg-[#f5a623] rounded-sm"></span>
                                <span>Zone 3</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="w-3 h-3 bg-[#7ed321] rounded-sm"></span>
                                <span>Zone 1</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="w-3 h-3 bg-[#ed3b83] rounded-sm"></span>
                                <span>Zone 2</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SecretaryLayout>
    );
}